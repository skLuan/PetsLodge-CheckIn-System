# Developer Guide - PetsLodge Check-in System

**Version:** 2.0.0  
**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [FormDataManager API Overview](#formdatamanager-api-overview)
3. [Common Use Cases](#common-use-cases)
4. [Code Examples](#code-examples)
5. [Debugging Tips](#debugging-tips)
6. [Best Practices](#best-practices)
7. [Adding New Form Fields](#adding-new-form-fields)
8. [Troubleshooting Data Flow Issues](#troubleshooting-data-flow-issues)

---

## Quick Start

### Initialize the System

The system initializes automatically when the DOM loads:

```javascript
// Automatic initialization (no action needed)
document.addEventListener("DOMContentLoaded", async () => {
    await FormDataManager.initialize();
});
```

### Get Current Check-in Data

```javascript
// Get all check-in data
const data = FormDataManager.getCheckinData();
console.log(data);

// Get specific sections
const userInfo = data.user.info;
const pets = data.pets;
const grooming = data.grooming;
```

### Update Check-in Data

```javascript
// Update user information
FormDataManager.updateUserInfo({
    phone: "555-0123",
    name: "John Doe",
    email: "john@example.com",
    address: "123 Main St",
    city: "Anytown",
    zip: "12345"
});

// Update grooming selections
FormDataManager.updateCheckinData({
    grooming: {
        bath: true,
        nails: false,
        haircut: true
    }
});
```

---

## FormDataManager API Overview

### Core Methods

#### Data Retrieval

| Method | Returns | Purpose |
|--------|---------|---------|
| `getCheckinData()` | Object\|null | Get complete check-in data |
| `getAllPetsFromCheckin()` | Array | Get all pets with flattened properties |
| `getCurrentSelectedPetIndex()` | number\|null | Get index of selected pet |
| `getEditingMode()` | Object\|null | Get editing mode state |
| `getOriginalData()` | Object\|null | Get original data snapshot |

#### Data Updates

| Method | Parameters | Purpose |
|--------|-----------|---------|
| `updateCheckinData(updates)` | Object | Merge updates into cookie |
| `updateUserInfo(userData)` | Object | Update user/owner information |
| `updatePetInCheckin(index, data)` | number, Object | Update specific pet |
| `addPetToCheckin(petData)` | Object | Add new pet |
| `removePetFromCheckin(index)` | number | Remove pet by index |

#### Inventory Management

| Method | Parameters | Purpose |
|--------|-----------|---------|
| `addInventoryItem(text)` | string | Add inventory item |
| `removeInventoryItem(index)` | number | Remove inventory item |
| `updateInventoryItem(index, text)` | number, string | Update inventory item |
| `setInventoryComplete(complete)` | boolean | Mark inventory complete |

#### Editing Mode

| Method | Parameters | Purpose |
|--------|-----------|---------|
| `setEditingMode(id, data)` | string, Object | Enable editing mode |
| `clearEditingMode()` | - | Disable editing mode |
| `isEditingMode()` | - | Check if editing |
| `getEditingCheckInId()` | - | Get edited check-in ID |
| `hasDataChanged()` | - | Detect changes |
| `getChangeSummary()` | - | Get change summary |
| `resetToOriginal()` | - | Revert to original |

#### UI Updates

| Method | Parameters | Purpose |
|--------|-----------|---------|
| `updateUIFromCookieData(data)` | Object | Update all UI elements |
| `updateOwnerInfoForm(info)` | Object | Update owner form fields |
| `updatePetPillsAndForms(pets)` | Array | Update pet pills and forms |
| `updateFeedingMedicationUI(pets)` | Array | Update feeding/medication display |
| `updateHealthInfoUI(pets, grooming, details)` | Array, Object, string | Update health form |
| `updateInventoryUI(inventory, complete)` | Array, boolean | Update inventory display |

---

## Common Use Cases

### Use Case 1: Create New Check-in

```javascript
// 1. System initializes automatically with empty cookie
FormDataManager.initialize();

// 2. User fills owner information
FormDataManager.updateUserInfo({
    phone: "555-0123",
    name: "John Doe",
    email: "john@example.com",
    address: "123 Main St",
    city: "Anytown",
    zip: "12345",
    emergencyContactName: "Jane Doe",
    emergencyContactPhone: "555-0456"
});

// 3. User adds a pet
FormDataManager.addPetToCheckin({
    petName: "Max",
    petType: "dog",
    petColor: "brown",
    petBreed: "Golden Retriever",
    petAge: "2020-03-15",
    petWeight: "65",
    petGender: "male",
    petSpayed: "yes"
});

// 4. User adds feeding schedule
FormDataManager.addPetFeedingOrMedication(0, "feeding", {
    day_time: "morning",
    feeding_med_details: "1 cup dry food",
    amount: "1"
});

// 5. User selects grooming services
FormDataManager.updateCheckinData({
    grooming: {
        bath: true,
        nails: true,
        haircut: false
    }
});

// 6. User adds inventory items
FormDataManager.addInventoryItem("Dog collar and leash");
FormDataManager.addInventoryItem("Food bowl");

// 7. User accepts terms
FormDataManager.setTermsAccepted(true);

// 8. User submits form
const data = FormDataManager.getCheckinData();
// Send to backend API
```

### Use Case 2: Edit Existing Check-in

```javascript
// 1. Backend loads check-in and sets session data
// (Handled by CheckInFormController.editCheckIn())

// 2. Frontend initializes and merges session data
FormDataManager.initialize();
FormDataManager.mergeSessionDataIntoCookie(sessionData);

// 3. Enable editing mode with original data snapshot
FormDataManager.setEditingMode(checkInId, originalData);

// 4. Form displays with pre-populated data
// (Automatic via CookieReactivityManager)

// 5. User makes changes
FormDataManager.updateUserInfo({
    phone: "555-9999"  // Changed phone number
});

// 6. Check if data changed
if (FormDataManager.hasDataChanged()) {
    console.log("User made changes");
    
    // Get summary of changes
    const changes = FormDataManager.getChangeSummary();
    if (changes.userInfo) console.log("User info changed");
    if (changes.pets) console.log("Pet data changed");
}

// 7. User can revert changes
if (confirm("Discard changes?")) {
    FormDataManager.resetToOriginal();
}

// 8. Or submit changes
const currentData = FormDataManager.getCheckinData();
// Send to backend API for update
```

### Use Case 3: Manage Multiple Pets

```javascript
// Add first pet
FormDataManager.addPetToCheckin({
    petName: "Max",
    petType: "dog",
    petBreed: "Golden Retriever",
    petWeight: "65",
    petGender: "male",
    petSpayed: "yes"
});

// Add second pet
FormDataManager.addPetToCheckin({
    petName: "Whiskers",
    petType: "cat",
    petBreed: "Siamese",
    petWeight: "8",
    petGender: "female",
    petSpayed: "yes"
});

// Get all pets
const pets = FormDataManager.getAllPetsFromCheckin();
console.log(`${pets.length} pets in check-in`);

// Update specific pet
FormDataManager.updatePetInCheckin(0, {
    petWeight: "70"  // Max gained weight
});

// Add feeding for first pet
FormDataManager.addPetFeedingOrMedication(0, "feeding", {
    day_time: "morning",
    feeding_med_details: "1 cup dry food"
});

// Add medication for second pet
FormDataManager.addPetFeedingOrMedication(1, "medication", {
    day_time: "evening",
    feeding_med_details: "Allergy medication"
});

// Remove a pet
FormDataManager.removePetFromCheckin(1);  // Remove Whiskers
```

### Use Case 4: Handle Form Step Submission

```javascript
// Handle owner information step (step 0)
const ownerData = {
    phone: "555-0123",
    name: "John Doe",
    email: "john@example.com",
    address: "123 Main St",
    city: "Anytown",
    zip: "12345"
};

const success = FormDataManager.handleFormStep(0, ownerData);
if (success) {
    console.log("Owner info saved");
    // Navigate to next step
} else {
    console.log("Validation failed");
    // Show error messages
}

// Handle pet information step (step 1)
const petData = {
    petName: "Max",
    petType: "dog",
    petBreed: "Golden Retriever",
    petAge: "2020-03-15",
    petWeight: "65",
    petGender: "male",
    petSpayed: "yes"
};

const petSuccess = FormDataManager.handleFormStep(1, petData, 0);
if (petSuccess) {
    console.log("Pet info saved");
}
```

---

## Code Examples

### Example 1: Debug Check-in Data

```javascript
// Display comprehensive debug information
FormDataManager.debugCheckinData();

// Or access specific data
const data = FormDataManager.getCheckinData();
console.log("Check-in ID:", data.id);
console.log("Owner:", data.user.info.name);
console.log("Pets:", data.pets.length);
console.log("Grooming:", data.grooming);
console.log("Inventory:", data.inventory);
console.log("Editing mode:", FormDataManager.isEditingMode());
```

### Example 2: Monitor Cookie Changes

```javascript
// Register listener for cookie changes
import { CookieReactivityManager } from "./reactivitySystem/index.js";

CookieReactivityManager.addListener((newData, oldData) => {
    console.log("Cookie changed!");
    console.log("Old data:", oldData);
    console.log("New data:", newData);
    
    // Custom update logic
    if (newData.pets.length > oldData.pets.length) {
        console.log("Pet added");
    }
});
```

### Example 3: Validate Data Before Submission

```javascript
// Check if all required fields are filled
function validateCheckIn() {
    const data = FormDataManager.getCheckinData();
    
    // Check user info
    if (!data.user.info.phone || !data.user.info.name) {
        console.error("User info incomplete");
        return false;
    }
    
    // Check pets
    if (data.pets.length === 0) {
        console.error("No pets added");
        return false;
    }
    
    // Check terms
    if (!data.termsAccepted) {
        console.error("Terms not accepted");
        return false;
    }
    
    return true;
}

// Use in form submission
if (validateCheckIn()) {
    const data = FormDataManager.getCheckinData();
    // Submit to backend
} else {
    // Show validation errors
}
```

### Example 4: Track Changes During Editing

```javascript
// Enable editing mode
FormDataManager.setEditingMode(checkInId, originalData);

// Monitor changes
setInterval(() => {
    if (FormDataManager.hasDataChanged()) {
        const changes = FormDataManager.getChangeSummary();
        console.log("Current changes:", changes);
        
        // Show unsaved changes indicator
        document.querySelector(".unsaved-indicator").style.display = "block";
    } else {
        // Hide unsaved changes indicator
        document.querySelector(".unsaved-indicator").style.display = "none";
    }
}, 1000);
```

### Example 5: Export Check-in Data

```javascript
// Get check-in data and export as JSON
function exportCheckIn() {
    const data = FormDataManager.getCheckinData();
    const json = JSON.stringify(data, null, 2);
    
    // Create download link
    const blob = new Blob([json], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `checkin_${data.id}.json`;
    link.click();
}
```

---

## Debugging Tips

### Tip 1: Use Browser Console

```javascript
// In browser console, use global debug function
debugCheckin();

// Or access FormDataManager directly
FormDataManager.getCheckinData();
FormDataManager.isEditingMode();
FormDataManager.hasDataChanged();
```

### Tip 2: Monitor Cookie Changes

```javascript
// Watch cookie in DevTools
// Application → Cookies → pl_checkin_data

// Or monitor programmatically
setInterval(() => {
    const data = FormDataManager.getCheckinData();
    console.log("Current cookie:", data);
}, 5000);
```

### Tip 3: Check Data Transformations

```javascript
// Verify session data extraction
const container = document.querySelector('[data-session-checkin]');
const sessionData = container.getAttribute('data-session-checkin');
console.log("Session data:", sessionData);

// Verify editing mode flags
console.log("Editing mode:", container.getAttribute('data-editing-mode'));
console.log("Check-in ID:", container.getAttribute('data-editing-check-in-id'));
```

### Tip 4: Trace Data Flow

```javascript
// Add logging to trace data flow
const originalUpdate = FormDataManager.updateCheckinData;
FormDataManager.updateCheckinData = function(updates) {
    console.log("Updating check-in data:", updates);
    const result = originalUpdate.call(this, updates);
    console.log("Update result:", result);
    return result;
};
```

### Tip 5: Validate Data Structure

```javascript
// Check if data matches expected structure
function validateDataStructure(data) {
    const required = ['date', 'id', 'user', 'pets', 'grooming', 'inventory'];
    for (const field of required) {
        if (!(field in data)) {
            console.error(`Missing required field: ${field}`);
            return false;
        }
    }
    return true;
}

const data = FormDataManager.getCheckinData();
if (validateDataStructure(data)) {
    console.log("Data structure valid");
}
```

---

## Best Practices

### 1. Always Check for Null Data

```javascript
// ❌ Bad - May throw error if data is null
const petName = FormDataManager.getCheckinData().pets[0].info.petName;

// ✅ Good - Check for null and undefined
const data = FormDataManager.getCheckinData();
if (data && data.pets && data.pets.length > 0) {
    const petName = data.pets[0].info.petName;
}

// ✅ Better - Use optional chaining
const petName = FormDataManager.getCheckinData()?.pets?.[0]?.info?.petName;
```

### 2. Use Deep Copies for Original Data

```javascript
// ❌ Bad - Reference to original data
const original = FormDataManager.getOriginalData();
original.user.info.name = "Changed";  // Modifies original!

// ✅ Good - Deep copy
const original = JSON.parse(JSON.stringify(FormDataManager.getOriginalData()));
original.user.info.name = "Changed";  // Doesn't affect original
```

### 3. Batch Updates When Possible

```javascript
// ❌ Bad - Multiple updates trigger reactivity multiple times
FormDataManager.updateCheckinData({ grooming: { bath: true } });
FormDataManager.updateCheckinData({ grooming: { nails: true } });
FormDataManager.updateCheckinData({ groomingDetails: "Be gentle" });

// ✅ Good - Single update with all changes
FormDataManager.updateCheckinData({
    grooming: { bath: true, nails: true },
    groomingDetails: "Be gentle"
});
```

### 4. Validate Before Updating

```javascript
// ❌ Bad - No validation
FormDataManager.updateUserInfo(userInput);

// ✅ Good - Validate first
function updateUserInfoSafely(userInput) {
    if (!userInput.phone || !userInput.name) {
        console.error("Missing required fields");
        return false;
    }
    return FormDataManager.updateUserInfo(userInput);
}
```

### 5. Handle Errors Gracefully

```javascript
// ❌ Bad - No error handling
FormDataManager.addPetToCheckin(petData);

// ✅ Good - Handle errors
try {
    const success = FormDataManager.addPetToCheckin(petData);
    if (!success) {
        console.error("Failed to add pet");
        showErrorMessage("Could not add pet");
    }
} catch (error) {
    console.error("Error adding pet:", error);
    showErrorMessage("An error occurred");
}
```

---

## Adding New Form Fields

### Step 1: Update Cookie Structure

**File:** [`resources/js/cookies-and-form/config.js`](resources/js/cookies-and-form/config.js)

```javascript
// Add new field to DEFAULT_CHECKIN_STRUCTURE
const DEFAULT_CHECKIN_STRUCTURE = {
    // ... existing fields ...
    user: {
        info: {
            // ... existing fields ...
            newField: ""  // Add new field here
        }
    }
};
```

### Step 2: Update Data Transformation

**File:** [`app/Services/CheckInTransformer.php`](app/Services/CheckInTransformer.php)

```php
// Add field to transformCheckInToCookieFormat()
'user' => [
    'info' => [
        // ... existing fields ...
        'newField' => $user->new_field ?? '',  // Add mapping
    ]
]

// Add field to transformCookieToCheckInFormat()
'new_field' => $cookieData['user']['info']['newField'] ?? '',
```

### Step 3: Update Form Handler

**File:** [`resources/js/cookies-and-form/managers/ValidationManager.js`](resources/js/cookies-and-form/managers/ValidationManager.js)

```javascript
// Add validation for new field
static updateUserInfo(userData) {
    // ... existing validation ...
    
    // Add validation for new field
    if (userData.newField && userData.newField.length > 100) {
        console.error("New field too long");
        return false;
    }
    
    // ... rest of method ...
}
```

### Step 4: Update UI

**File:** [`resources/views/Process.blade.php`](resources/views/Process.blade.php)

```html
<!-- Add form input for new field -->
<input type="text" 
       id="newField" 
       name="newField" 
       placeholder="New Field"
       value="{{ $data['user']['info']['newField'] ?? '' }}">
```

### Step 5: Update UI Manager

**File:** [`resources/js/cookies-and-form/reactivitySystem/UIManager.js`](resources/js/cookies-and-form/reactivitySystem/UIManager.js)

```javascript
// Add UI update for new field
static updateOwnerInfoForm(userInfo) {
    // ... existing updates ...
    
    // Update new field
    const newFieldInput = document.querySelector('#newField');
    if (newFieldInput && userInfo.newField) {
        newFieldInput.value = userInfo.newField;
    }
}
```

---

## Troubleshooting Data Flow Issues

### Issue: Data Not Saving to Cookie

**Diagnosis:**
```javascript
// Check if update returns true
const success = FormDataManager.updateCheckinData({ /* data */ });
console.log("Update success:", success);

// Check cookie content
FormDataManager.debugCheckinData();
```

**Solutions:**
1. Verify data structure matches expected format
2. Check for JavaScript errors in console
3. Verify cookie not disabled in browser
4. Check cookie size not exceeded

### Issue: Form Fields Not Updating

**Diagnosis:**
```javascript
// Check if reactivity is working
import { CookieReactivityManager } from "./reactivitySystem/index.js";
CookieReactivityManager.addListener((newData) => {
    console.log("Cookie changed:", newData);
});

// Manually trigger update
FormDataManager.updateUIFromCookieData(FormDataManager.getCheckinData());
```

**Solutions:**
1. Verify CookieReactivityManager is listening
2. Check UIManager methods are implemented
3. Verify DOM elements have correct IDs/selectors
4. Check for JavaScript errors in console

### Issue: Editing Mode Not Working

**Diagnosis:**
```javascript
// Check editing mode state
console.log("Is editing:", FormDataManager.isEditingMode());
console.log("Editing mode:", FormDataManager.getEditingMode());
console.log("Original data:", FormDataManager.getOriginalData());
```

**Solutions:**
1. Verify session data passed from backend
2. Check form-processor.js extracts editing flags
3. Verify EditingModeManager.enableEditingMode() called
4. Check original data snapshot stored correctly

---

## Related Documentation

- [`DATA_FLOW.md`](DATA_FLOW.md) - Architecture and data flow details
- [`API_REFERENCE.md`](API_REFERENCE.md) - Complete method documentation
- [`DEPLOYMENT_GUIDE.md`](DEPLOYMENT_GUIDE.md) - Deployment procedures

---

**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready  
**Phase:** 5 (Documentation & Deployment)
