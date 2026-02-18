# Data Flow Architecture - PetsLodge Check-in System

**Version:** 2.0.0  
**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready (Phase 4 Complete)

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture Layers](#architecture-layers)
3. [Data Flow Diagrams](#data-flow-diagrams)
4. [Complete Data Transformation Pipeline](#complete-data-transformation-pipeline)
5. [Editing Mode Functionality](#editing-mode-functionality)
6. [Change Detection Mechanism](#change-detection-mechanism)
7. [Cookie Structure](#cookie-structure)
8. [Data Persistence](#data-persistence)
9. [Reactivity System](#reactivity-system)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The PetsLodge check-in system uses a **unified cookie-based data flow** that serves as the single source of truth for all form data. This architecture eliminates data inconsistency issues caused by overlapping storage layers (cookies, sessionStorage, and Laravel session).

### Key Principles

1. **Cookies as Single Source of Truth** - All form state stored in `pl_checkin_data` cookie
2. **One-Time Session Merge** - Session data merged once at initialization, not continuously
3. **Explicit Editing Mode** - Dedicated flag tracks whether user is editing or creating
4. **Lossless Data Transformation** - All fields preserved during data conversions
5. **Automatic Reactivity** - Cookie changes trigger automatic UI updates

---

## Architecture Layers

### Layer 1: Database (Backend)

**Location:** Laravel Database  
**Responsibility:** Persistent storage of check-in records

```
Database Tables:
├── check_ins (main check-in records)
├── pets (pet information)
├── emergency_contacts (emergency contact info)
├── extra_services (grooming, etc.)
└── medicines, foods, items (reference data)
```

**Data Flow:**
- Check-in data retrieved from database
- Transformed to cookie format via [`CheckInTransformer.php`](app/Services/CheckInTransformer.php)
- Passed to frontend via Laravel session

### Layer 2: Laravel Session (Backend)

**Location:** Server-side session storage  
**Responsibility:** Temporary storage for data transfer to frontend

**When Used:**
- User clicks "Edit" on existing check-in
- [`CheckInFormController.editCheckIn()`](app/Http/Controllers/CheckInFormController.php) retrieves data from database
- Data transformed and stored in session
- Editing mode flags set in session

**Session Keys:**
```php
session('pl_checkin_data')        // Transformed check-in data
session('editing_mode')           // Boolean flag
session('editing_check_in_id')    // ID of check-in being edited
```

### Layer 3: DOM Attributes (Frontend)

**Location:** HTML attributes in Blade template  
**Responsibility:** Bridge between backend session and frontend JavaScript

**Template:** [`resources/views/Process.blade.php`](resources/views/Process.blade.php)

**Attributes:**
```html
<div id="stepContainer"
     data-session-checkin="{{ json_encode($sessionData) }}"
     data-editing-mode="{{ $editingMode ? 'true' : 'false' }}"
     data-editing-check-in-id="{{ $editingCheckInId ?? '' }}">
</div>
```

**Data Encoding:**
- JSON data HTML-encoded to prevent XSS
- Entities: `&quot;`, `&#039;`, `&amp;`, `&lt;`, `&gt;`
- Decoded in JavaScript before parsing

### Layer 4: Browser Cookies (Frontend)

**Location:** Browser cookie storage  
**Responsibility:** Single source of truth for form data

**Cookie Name:** `pl_checkin_data`  
**Cookie Size:** Typically 2-4 KB (well under 4 KB limit)  
**Persistence:** Across page reloads and browser sessions

**Stored Data:**
```javascript
{
  date: "2026-01-30T01:09:00Z",
  id: "checkin_1642598400000_abc123",
  user: {
    info: {
      phone: "3044197396",
      name: "Luan",
      email: "erazo.luan@gmail.com",
      address: "123 Main Calle",
      city: "Valle del Cauca",
      zip: "0454"
    },
    emergencyContact: {
      name: "Varo",
      phone: "4561237890"
    }
  },
  pets: [
    {
      id: "pet_1642598400000_xyz789",
      info: {
        petName: "Max",
        petType: "dog",
        petColor: "brown",
        petBreed: "Golden Retriever",
        petAge: "2020-03-15",
        petWeight: "65",
        petGender: "male",
        petSpayed: "yes"
      },
      health: {
        unusualHealthBehavior: false,
        healthBehaviors: "",
        warnings: ""
      },
      feeding: [],
      medication: []
    }
  ],
  grooming: {
    bath: false,
    nails: false,
    haircut: false
  },
  groomingDetails: "",
  inventory: [],
  inventoryComplete: false,
  termsAccepted: false,
  editingMode: {
    enabled: true,
    checkInId: 1,
    originalData: { /* full snapshot */ }
  }
}
```

### Layer 5: DOM (Frontend)

**Location:** HTML form elements  
**Responsibility:** User interface for data input

**Form Steps:**
1. Owner Information (phone, name, email, address, city, zip)
2. Pet Information (name, type, breed, age, weight, gender, spayed)
3. Feeding & Medication (schedules for each pet)
4. Health Information (behaviors, warnings, grooming)
5. Inventory (items being left at facility)
6. Terms & Confirmation

---

## Data Flow Diagrams

### New Check-in Flow

```
User Opens Form
    ↓
form-processor.js DOMContentLoaded
    ├─ No session data available
    ├─ FormDataManager.initialize()
    │  └─ CoreDataManager.createInitialCheckin()
    │     └─ Create empty cookie with default structure
    ↓
User Fills Form
    ├─ Each field change triggers update
    ├─ FormDataManager.handleFormStep() or specific update method
    ├─ CoreDataManager.updateCheckinData()
    │  └─ Merge updates into cookie
    ├─ CookieReactivityManager detects change
    └─ UIManager updates related DOM elements
    ↓
User Submits Form
    ├─ SubmissionManager.submitCheckin()
    ├─ Retrieve data from cookie
    ├─ Send to backend via API
    ├─ Backend validates and saves to database
    └─ Clear cookie on success
```

### Editing Existing Check-in Flow

```
User Clicks "Edit" Button
    ↓
CheckInFormController.editCheckIn()
    ├─ Retrieve check-in from database
    ├─ CheckInTransformer.transformCheckInToCookieFormat()
    │  └─ Convert database format to cookie format
    ├─ Store in Laravel session
    ├─ Set editing_mode = true
    ├─ Set editing_check_in_id = {id}
    └─ Redirect to form page
    ↓
Process.blade.php Renders
    ├─ Extract session data
    ├─ HTML-encode for safety
    ├─ Set DOM attributes:
    │  ├─ data-session-checkin
    │  ├─ data-editing-mode="true"
    │  └─ data-editing-check-in-id="{id}"
    └─ Load JavaScript
    ↓
form-processor.js DOMContentLoaded
    ├─ Extract editing mode flags from DOM
    ├─ Extract and decode session data
    ├─ FormDataManager.initialize()
    │  └─ Create initial cookie (if needed)
    ├─ FormDataManager.mergeSessionDataIntoCookie(sessionData)
    │  └─ Merge pre-populated data into cookie
    ├─ FormDataManager.setEditingMode(checkInId, sessionData)
    │  └─ EditingModeManager.enableEditingMode()
    │     ├─ Set editingMode.enabled = true
    │     ├─ Set editingMode.checkInId = {id}
    │     └─ Store originalData snapshot
    └─ CookieReactivityManager triggers UI update
    ↓
Form Displays with Pre-populated Data
    ├─ UIManager.updateUIFromCookieData()
    ├─ All fields filled with existing data
    ├─ Editing mode flag set
    └─ Original data snapshot stored for change detection
    ↓
User Edits Form
    ├─ Each change updates cookie
    ├─ EditingModeManager.hasDataChanged() detects changes
    ├─ EditingModeManager.getChangeSummary() shows what changed
    └─ UI may show "unsaved changes" indicator
    ↓
User Submits Changes
    ├─ SubmissionManager.submitCheckin()
    ├─ Retrieve current data from cookie
    ├─ CheckInTransformer.transformCookieToCheckInFormat()
    │  └─ Convert cookie format back to database format
    ├─ Send to backend via API
    ├─ Backend validates and updates database
    └─ Clear editing mode and cookie on success
```

---

## Complete Data Transformation Pipeline

### Database → Cookie Format

**File:** [`app/Services/CheckInTransformer.php`](app/Services/CheckInTransformer.php)  
**Method:** `transformCheckInToCookieFormat()`

**Transformation Steps:**

```php
// 1. Extract user information
$userData = [
    'info' => [
        'phone' => $user->phone,
        'name' => $user->name,
        'email' => $user->email,
        'address' => $user->address,
        'city' => $user->city,
        'zip' => $user->zip,
    ],
    'emergencyContact' => [
        'name' => $user->emergencyContacts->first()?->name ?? '',
        'phone' => $user->emergencyContacts->first()?->phone ?? '',
    ]
];

// 2. Extract pet information
$petData = [
    'id' => $pet->id,
    'info' => [
        'petName' => $pet->name,
        'petType' => $pet->kind_of_pet_id,
        'petColor' => $pet->color,
        'petBreed' => $pet->breed,
        'petAge' => $pet->birth_date,
        'petWeight' => $pet->weight ?? '',
        'petGender' => $pet->gender_id,
        'petSpayed' => $pet->castrated_id,
    ],
    'health' => [
        'unusualHealthBehavior' => (bool)$checkIn->unusual_health_behavior,
        'healthBehaviors' => $checkIn->health_behaviors ?? '',
        'warnings' => $checkIn->warnings ?? '',
    ],
    'feeding' => [],
    'medication' => []
];

// 3. Extract grooming services
$groomingData = [
    'bath' => $checkIn->extraServices->contains('name', 'Bath'),
    'nails' => $checkIn->extraServices->contains('name', 'Nails'),
    'haircut' => $checkIn->extraServices->contains('name', 'Haircut'),
];

// 4. Combine into cookie format
return [
    'date' => now()->toIso8601String(),
    'id' => $checkIn->id,
    'user' => $userData,
    'pets' => [$petData],
    'grooming' => $groomingData,
    'groomingDetails' => '',
    'inventory' => [],
    'inventoryComplete' => false,
    'termsAccepted' => false,
    'editingMode' => [
        'enabled' => false,
        'checkInId' => null,
        'originalData' => null,
    ]
];
```

### Cookie → Database Format

**File:** [`app/Services/CheckInTransformer.php`](app/Services/CheckInTransformer.php)  
**Method:** `transformCookieToCheckInFormat()`

**Reverse Transformation:**

```php
// 1. Extract user data
$userData = [
    'phone' => $cookieData['user']['info']['phone'],
    'name' => $cookieData['user']['info']['name'],
    'email' => $cookieData['user']['info']['email'],
    'address' => $cookieData['user']['info']['address'],
    'city' => $cookieData['user']['info']['city'],
    'zip' => $cookieData['user']['info']['zip'],
];

// 2. Extract pet data
$petData = [
    'name' => $pet['info']['petName'],
    'kind_of_pet_id' => $pet['info']['petType'],
    'color' => $pet['info']['petColor'],
    'breed' => $pet['info']['petBreed'],
    'birth_date' => $pet['info']['petAge'],
    'weight' => $pet['info']['petWeight'],
    'gender_id' => $pet['info']['petGender'],
    'castrated_id' => $pet['info']['petSpayed'],
];

// 3. Extract check-in data
$checkInData = [
    'unusual_health_behavior' => $pet['health']['unusualHealthBehavior'],
    'health_behaviors' => $pet['health']['healthBehaviors'],
    'warnings' => $pet['health']['warnings'],
];

// 4. Extract grooming services
$groomingServices = [];
if ($cookieData['grooming']['bath']) $groomingServices[] = 'Bath';
if ($cookieData['grooming']['nails']) $groomingServices[] = 'Nails';
if ($cookieData['grooming']['haircut']) $groomingServices[] = 'Haircut';
```

---

## Editing Mode Functionality

### What is Editing Mode?

Editing mode is a flag that indicates the user is **updating an existing check-in** rather than creating a new one. It enables:

1. **Original Data Snapshot** - Stores the data as it was in the database
2. **Change Detection** - Compares current data with original to detect changes
3. **Reset Functionality** - Allows user to revert to original data
4. **Submission Handling** - Sends only changed data to backend

### Enabling Editing Mode

**File:** [`resources/js/cookies-and-form/managers/EditingModeManager.js`](resources/js/cookies-and-form/managers/EditingModeManager.js)

```javascript
// Enable editing mode with original data snapshot
EditingModeManager.enableEditingMode(checkInId, originalData);

// Or via FormDataManager
FormDataManager.setEditingMode(checkInId, originalData);
```

**What Happens:**
1. Editing mode flag set to `true`
2. Check-in ID stored for reference
3. Original data deep-copied and stored in cookie
4. Cookie reactivity triggered for UI updates

### Checking Editing Mode

```javascript
// Check if currently editing
if (FormDataManager.isEditingMode()) {
    console.log("User is editing an existing check-in");
}

// Get editing check-in ID
const checkInId = FormDataManager.getEditingCheckInId();

// Get editing mode object
const editingMode = FormDataManager.getEditingMode();
// Returns: { enabled: true, checkInId: 1, originalData: {...} }
```

### Disabling Editing Mode

```javascript
// Disable editing mode after submission
FormDataManager.clearEditingMode();

// Or via EditingModeManager
EditingModeManager.disableEditingMode();
```

---

## Change Detection Mechanism

### How Change Detection Works

The system compares current data with the original snapshot using deep JSON comparison:

```javascript
// Check if any data has changed
if (FormDataManager.hasDataChanged()) {
    console.log("User has made changes");
}

// Get summary of what changed
const changes = FormDataManager.getChangeSummary();
// Returns: {
//   userInfo: true,      // User info was modified
//   pets: false,         // Pet data unchanged
//   grooming: true,      // Grooming selections changed
//   inventory: false,    // Inventory unchanged
//   groomingDetails: false
// }
```

### Implementation Details

**File:** [`resources/js/cookies-and-form/managers/EditingModeManager.js`](resources/js/cookies-and-form/managers/EditingModeManager.js)

```javascript
static hasDataChanged() {
    if (!this.isEditingMode()) {
        return false;
    }

    const currentData = CoreDataManager.getCheckinData();
    const originalData = this.getOriginalData();

    // Deep comparison using JSON stringification
    const currentJson = JSON.stringify(currentData);
    const originalJson = JSON.stringify(originalData);

    return currentJson !== originalJson;
}

static getChangeSummary() {
    return {
        userInfo: JSON.stringify(currentData.user) !== JSON.stringify(originalData.user),
        pets: JSON.stringify(currentData.pets) !== JSON.stringify(originalData.pets),
        grooming: JSON.stringify(currentData.grooming) !== JSON.stringify(originalData.grooming),
        inventory: JSON.stringify(currentData.inventory) !== JSON.stringify(originalData.inventory),
        groomingDetails: currentData.groomingDetails !== originalData.groomingDetails,
    };
}
```

### Reset to Original

```javascript
// Revert all changes to original data
if (confirm("Discard all changes?")) {
    FormDataManager.resetToOriginal();
    // Form is now populated with original data
}
```

---

## Cookie Structure

### Complete Cookie Schema

```javascript
{
  // Metadata
  date: string,              // ISO 8601 timestamp of creation
  id: string,                // Unique check-in identifier

  // User Information
  user: {
    info: {
      phone: string,         // Phone number
      name: string,          // Full name
      email: string,         // Email address
      address: string,       // Street address
      city: string,          // City name
      zip: string            // ZIP/postal code
    },
    emergencyContact: {
      name: string,          // Emergency contact name
      phone: string          // Emergency contact phone
    }
  },

  // Pet Information
  pets: [
    {
      id: string,            // Unique pet identifier
      info: {
        petName: string,     // Pet's name
        petType: string,     // Pet type/species
        petColor: string,    // Pet's color
        petBreed: string,    // Pet's breed
        petAge: string,      // Birth date (YYYY-MM-DD)
        petWeight: string,   // Weight in pounds
        petGender: string,   // Gender (male/female)
        petSpayed: string    // Spayed/neutered (yes/no)
      },
      health: {
        unusualHealthBehavior: boolean,  // Has unusual behavior
        healthBehaviors: string,         // Description of behaviors
        warnings: string                 // Health warnings
      },
      feeding: [
        {
          day_time: string,              // Time slot (morning, noon, etc.)
          feeding_med_details: string,   // Details
          amount: string                 // Amount (optional)
        }
      ],
      medication: [
        {
          day_time: string,              // Time slot
          feeding_med_details: string    // Medication details
        }
      ]
    }
  ],

  // Grooming Services
  grooming: {
    bath: boolean,           // Bath service selected
    nails: boolean,          // Nail trim selected
    haircut: boolean         // Haircut selected
  },
  groomingDetails: string,   // Additional grooming notes

  // Inventory
  inventory: [string],       // Array of inventory item descriptions
  inventoryComplete: boolean,// Whether inventory is marked complete

  // Terms & Conditions
  termsAccepted: boolean,    // Whether terms accepted

  // Editing Mode
  editingMode: {
    enabled: boolean,        // Whether in editing mode
    checkInId: number|null,  // ID of check-in being edited
    originalData: object|null // Snapshot of original data
  }
}
```

---

## Data Persistence

### How Data Persists

1. **Page Reload** - Cookie persists across page reloads
2. **Browser Session** - Cookie persists for entire browser session
3. **Multiple Tabs** - Cookie shared across all tabs of same domain
4. **Form Navigation** - Data preserved when navigating between form steps

### Cookie Lifecycle

```
1. Creation
   └─ FormDataManager.initialize() creates initial cookie

2. Updates
   ├─ User input triggers updates
   ├─ FormDataManager.updateCheckinData() merges changes
   └─ CookieReactivityManager detects changes

3. Persistence
   ├─ Cookie stored in browser
   ├─ Survives page reloads
   └─ Survives browser restarts (session cookie)

4. Cleanup
   ├─ FormDataManager.clearCheckinData() removes cookie
   └─ Typically after successful submission
```

### Cookie Size Monitoring

```javascript
// Check current cookie size
const size = FormDataManager.getCookieSize();
console.log(`Cookies use ${size} bytes`);

// Verify cookie can be set
if (FormDataManager.canSetCookie("test", largeData)) {
    // Safe to set
} else {
    console.warn("Cookie too large");
}
```

---

## Reactivity System

### How Reactivity Works

The system automatically updates the UI whenever cookie data changes:

```
User Input
    ↓
FormDataManager.updateCheckinData()
    ↓
CoreDataManager.updateCheckinData()
    ├─ Merge updates into cookie
    └─ Set cookie in browser
    ↓
CookieReactivityManager.triggerCheck()
    ├─ Detect cookie change
    └─ Notify listeners
    ↓
UIManager.updateUIFromCookieData()
    ├─ Update form fields
    ├─ Update pet pills
    ├─ Update feeding/medication displays
    └─ Update inventory list
    ↓
DOM Updated
    ↓
User Sees Changes
```

### Registering Listeners

```javascript
// Register custom listener for cookie changes
CookieReactivityManager.addListener((newData, oldData) => {
    console.log("Cookie changed:", newData);
    // Custom update logic
});

// Or use built-in UI update listener
FormDataManager.registerUIUpdateListener();
```

---

## Troubleshooting

### Issue: Form Fields Not Pre-populated When Editing

**Symptoms:**
- User clicks "Edit" on existing check-in
- Form loads but fields are empty
- Editing mode flag not set

**Diagnosis:**
1. Check browser console for errors
2. Verify session data in DOM attributes:
   ```javascript
   const container = document.querySelector('[data-session-checkin]');
   console.log(container.getAttribute('data-session-checkin'));
   console.log(container.getAttribute('data-editing-mode'));
   ```
3. Check cookie content:
   ```javascript
   FormDataManager.debugCheckinData();
   ```

**Solutions:**
1. Verify [`CheckInFormController.editCheckIn()`](app/Http/Controllers/CheckInFormController.php) sets session data
2. Verify [`Process.blade.php`](resources/views/Process.blade.php) outputs DOM attributes
3. Check [`form-processor.js`](resources/js/cookies-and-form/form-processor.js) extracts and merges data
4. Clear browser cookies and reload

### Issue: Changes Not Detected

**Symptoms:**
- `FormDataManager.hasDataChanged()` returns false
- `FormDataManager.getChangeSummary()` returns null
- User made changes but system doesn't detect them

**Diagnosis:**
1. Check if in editing mode:
   ```javascript
   FormDataManager.isEditingMode();  // Should be true
   ```
2. Check original data snapshot:
   ```javascript
   FormDataManager.getOriginalData();  // Should not be null
   ```
3. Compare current vs original:
   ```javascript
   const current = FormDataManager.getCheckinData();
   const original = FormDataManager.getOriginalData();
   console.log("Current:", current);
   console.log("Original:", original);
   ```

**Solutions:**
1. Verify editing mode enabled correctly in [`form-processor.js`](resources/js/cookies-and-form/form-processor.js)
2. Verify original data snapshot stored in [`EditingModeManager.enableEditingMode()`](resources/js/cookies-and-form/managers/EditingModeManager.js)
3. Check for data transformation issues in [`CheckInTransformer.php`](app/Services/CheckInTransformer.php)

### Issue: Cookie Size Exceeds Limit

**Symptoms:**
- Cookie fails to set
- Data loss when adding pets or inventory
- Console errors about cookie size

**Diagnosis:**
```javascript
// Check cookie size
const size = FormDataManager.getCookieSize();
console.log(`Cookie size: ${size} bytes`);

// Check if specific data can be set
if (!FormDataManager.canSetCookie("pl_checkin_data", largeData)) {
    console.warn("Cookie too large");
}
```

**Solutions:**
1. Reduce number of pets (typically 1-2 pets per check-in)
2. Reduce inventory item descriptions
3. Clear unnecessary data before submission
4. Consider splitting data across multiple cookies (not recommended)

### Issue: Data Loss During Transformation

**Symptoms:**
- Data in form doesn't match data in database
- Fields missing after editing
- Validation errors on submission

**Diagnosis:**
1. Check transformation in [`CheckInTransformer.php`](app/Services/CheckInTransformer.php)
2. Verify all fields mapped correctly
3. Check for null/empty value handling
4. Compare database format with cookie format

**Solutions:**
1. Verify all required fields present in transformation
2. Add null coalescing operators (`??`) for optional fields
3. Test with sample data in different scenarios
4. Check [`CheckInDataValidator.php`](app/Services/CheckInDataValidator.php) for validation rules

### Issue: Editing Mode Flag Lost After Page Reload

**Symptoms:**
- Editing mode enabled initially
- After page reload, editing mode disabled
- Original data snapshot lost

**Diagnosis:**
1. Check if cookie persists:
   ```javascript
   FormDataManager.debugCheckinData();
   ```
2. Check if editing mode preserved:
   ```javascript
   FormDataManager.getEditingMode();
   ```

**Solutions:**
1. Verify cookie not cleared on page reload
2. Check [`form-processor.js`](resources/js/cookies-and-form/form-processor.js) doesn't clear editing mode
3. Verify [`FormDataManager.initialize()`](resources/js/cookies-and-form/FormDataManager.js) preserves editing mode
4. Check browser cookie settings (not blocking cookies)

---

## Related Documentation

- [`DEVELOPER_GUIDE.md`](DEVELOPER_GUIDE.md) - How to use FormDataManager API
- [`API_REFERENCE.md`](API_REFERENCE.md) - Complete method documentation
- [`DEPLOYMENT_GUIDE.md`](DEPLOYMENT_GUIDE.md) - Deployment procedures
- [`MIGRATION_GUIDE.md`](MIGRATION_GUIDE.md) - Upgrading to new system

---

**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready  
**Phase:** 5 (Documentation & Deployment)
