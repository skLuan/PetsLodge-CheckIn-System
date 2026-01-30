# API Reference - FormDataManager

**Version:** 2.0.0  
**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready

---

## Table of Contents

1. [Core Methods](#core-methods)
2. [Data Retrieval Methods](#data-retrieval-methods)
3. [Data Update Methods](#data-update-methods)
4. [Pet Management Methods](#pet-management-methods)
5. [Inventory Methods](#inventory-methods)
6. [Editing Mode Methods](#editing-mode-methods)
7. [UI Update Methods](#ui-update-methods)
8. [Utility Methods](#utility-methods)
9. [Deprecated Methods](#deprecated-methods)

---

## Core Methods

### initialize()

Initializes the form data management system.

**Signature:**
```javascript
static async initialize(): Promise<void>
```

**Description:**
Sets up the entire data management infrastructure including creating initial check-in cookie if needed, starting cookie reactivity monitoring, and registering UI update listeners.

**Parameters:** None

**Returns:** Promise that resolves when initialization is complete

**Throws:** None (errors logged to console)

**Example:**
```javascript
document.addEventListener("DOMContentLoaded", async () => {
    await FormDataManager.initialize();
});
```

**Side Effects:**
- Creates initial check-in cookie if none exists
- Starts cookie reactivity monitoring
- Registers global event listeners for UI updates
- Updates DOM elements to reflect current data state
- Logs initialization status to console

---

### getCheckinData()

Retrieves the complete check-in data from cookies.

**Signature:**
```javascript
static getCheckinData(): Object|null
```

**Description:**
Gets the current check-in session data stored in browser cookies. Returns the parsed JSON object or null if no data exists.

**Parameters:** None

**Returns:**
```javascript
{
  date: string,              // ISO 8601 timestamp
  id: string,                // Unique check-in identifier
  user: {
    info: {
      phone: string,
      name: string,
      email: string,
      address: string,
      city: string,
      zip: string
    },
    emergencyContact: {
      name: string,
      phone: string
    }
  },
  pets: Array,               // Array of pet objects
  grooming: Object,          // Grooming service selections
  groomingDetails: string,   // Additional grooming notes
  inventory: Array,          // Inventory items
  inventoryComplete: boolean,
  termsAccepted: boolean,
  editingMode: Object        // Editing mode state
}
```

**Throws:** None

**Example:**
```javascript
const data = FormDataManager.getCheckinData();
if (data) {
    console.log(`${data.pets.length} pets in check-in`);
}
```

---

### updateCheckinData(updates)

Updates check-in data in cookies with new information.

**Signature:**
```javascript
static updateCheckinData(updates: Object): boolean
```

**Description:**
Merges the provided updates into the existing check-in data and saves the result to cookies. Uses deep merge to preserve existing data while updating specified fields. Triggers reactivity for UI updates.

**Parameters:**
- `updates` (Object): Object containing data to update
  - `user` (Object, optional): User information updates
  - `pets` (Array, optional): Pet array updates
  - `grooming` (Object, optional): Grooming service updates
  - `inventory` (Array, optional): Inventory updates
  - `inventoryComplete` (boolean, optional): Inventory completion status
  - `termsAccepted` (boolean, optional): Terms acceptance status

**Returns:** `true` if update was successful, `false` otherwise

**Throws:** None (errors logged to console)

**Example:**
```javascript
// Update user information
FormDataManager.updateCheckinData({
    user: { info: { name: "John Doe" } }
});

// Mark inventory as complete
FormDataManager.updateCheckinData({
    inventoryComplete: true
});

// Update multiple sections
FormDataManager.updateCheckinData({
    grooming: { bath: true, nails: false },
    groomingDetails: "Be gentle with nails",
    termsAccepted: true
});
```

**Side Effects:**
- Updates browser cookie with merged data
- Triggers cookie reactivity listeners
- May cause automatic UI updates
- Logs update operations to console

---

### clearCheckinData()

Clears all check-in data from cookies.

**Signature:**
```javascript
static clearCheckinData(): void
```

**Description:**
Completely removes the check-in cookie, effectively ending the current check-in session. Used when starting fresh or after successful submission.

**Parameters:** None

**Returns:** void

**Throws:** None

**Example:**
```javascript
// Clear data after successful submission
FormDataManager.clearCheckinData();
```

**Side Effects:**
- Deletes browser cookie containing check-in data
- Logs clearance operation to console
- May trigger reactivity listeners with null data

---

## Data Retrieval Methods

### getAllPetsFromCheckin()

Retrieves all pets from the check-in data in a flattened format.

**Signature:**
```javascript
static getAllPetsFromCheckin(): Array
```

**Description:**
Gets the complete array of pets with flattened properties for easier access in legacy code. Each pet object includes both the structured data and flattened properties for backward compatibility.

**Parameters:** None

**Returns:**
```javascript
[
  {
    index: number,           // Zero-based index
    petName: string,
    petType: string,
    petColor: string,
    petBreed: string,
    petAge: string,
    petWeight: string,
    petGender: string,
    petSpayed: string,
    info: Object,            // Structured pet information
    health: Object,          // Health information
    feeding: Array,          // Feeding schedule
    medication: Array        // Medication schedule
  }
]
```

**Throws:** None

**Example:**
```javascript
const pets = FormDataManager.getAllPetsFromCheckin();
pets.forEach(pet => {
    console.log(`${pet.petName} is a ${pet.petType}`);
});
```

---

### getCurrentSelectedPetIndex()

Gets the index of the currently selected pet pill.

**Signature:**
```javascript
static getCurrentSelectedPetIndex(): number|null
```

**Description:**
Determines which pet is currently selected in the UI by checking for the "selected" class on pill elements.

**Parameters:** None

**Returns:** The index of the selected pet, or null if none selected

**Throws:** None

**Example:**
```javascript
const selectedIndex = FormDataManager.getCurrentSelectedPetIndex();
if (selectedIndex !== null) {
    console.log(`Pet ${selectedIndex} is selected`);
}
```

---

## Data Update Methods

### updateUserInfo(userData)

Updates user/owner information in the check-in data.

**Signature:**
```javascript
static updateUserInfo(userData: Object): boolean
```

**Description:**
Processes and validates owner information including contact details and emergency contact information. Merges the data into the existing check-in structure.

**Parameters:**
- `userData` (Object):
  - `phone` (string, required): Phone number
  - `name` (string, required): Full name
  - `email` (string, required): Email address
  - `address` (string, required): Street address
  - `city` (string, required): City name
  - `zip` (string, required): ZIP/postal code
  - `emergencyContactName` (string, optional): Emergency contact name
  - `emergencyContactPhone` (string, optional): Emergency contact phone

**Returns:** `true` if update was successful, `false` otherwise

**Throws:** None

**Example:**
```javascript
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
```

**Side Effects:**
- Updates user information in check-in cookie
- Triggers reactivity for UI updates

---

### handleFormStep(step, formData, selectedPetIndex)

Handles form submission for a specific step in the check-in process.

**Signature:**
```javascript
static handleFormStep(step: number, formData: Object, selectedPetIndex?: number): boolean
```

**Description:**
Processes form data based on the current step, validates input, and updates the appropriate data structures. Each step has different validation rules and data handling logic.

**Parameters:**
- `step` (number): Zero-based step index (0=owner, 1=pet, 2=feeding, etc.)
- `formData` (Object): Form data object from the submitted form
- `selectedPetIndex` (number, optional): Index of selected pet for pet-specific operations

**Returns:** `true` if step was handled successfully, `false` if validation failed

**Throws:** None

**Example:**
```javascript
// Handle owner information step
const success = FormDataManager.handleFormStep(0, {
    phone: "555-0123",
    name: "John Doe",
    email: "john@example.com"
});

// Handle pet information step
const success = FormDataManager.handleFormStep(1, {
    petName: "Max",
    petType: "dog"
}, 0);  // Update first pet
```

**Side Effects:**
- Updates check-in data in cookies
- May trigger UI updates via reactivity
- Shows validation error messages to user
- May redirect or show additional UI elements

---

## Pet Management Methods

### addPetToCheckin(petData)

Adds a new pet to the check-in data.

**Signature:**
```javascript
static addPetToCheckin(petData: Object): boolean
```

**Description:**
Creates a new pet entry with the provided information and adds it to the pets array in the check-in data. Generates a unique ID for the pet and initializes default structures for health, feeding, and medication data.

**Parameters:**
- `petData` (Object):
  - `petName` (string, required): Pet's name
  - `petType` (string, required): Pet's type/species
  - `petColor` (string, optional): Pet's color
  - `petBreed` (string, optional): Pet's breed
  - `petAge` (string, optional): Pet's birth date (YYYY-MM-DD)
  - `petWeight` (string, optional): Pet's weight in pounds
  - `petGender` (string, optional): Pet's gender (male/female)
  - `petSpayed` (string, optional): Spayed/neutered status (yes/no)

**Returns:** `true` if pet was added successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
FormDataManager.addPetToCheckin({
    petName: "Max",
    petType: "dog",
    petBreed: "Golden Retriever",
    petAge: "2020-03-15",
    petWeight: "65",
    petGender: "male",
    petSpayed: "yes"
});
```

**Side Effects:**
- Adds new pet object to check-in data
- Updates browser cookie
- Triggers reactivity for UI updates (may recreate pet pills)

---

### updatePetInCheckin(petIndex, petData)

Updates information for an existing pet.

**Signature:**
```javascript
static updatePetInCheckin(petIndex: number, petData: Object): boolean
```

**Description:**
Modifies the data for a specific pet identified by index. Merges the provided updates with existing pet data, preserving unchanged fields.

**Parameters:**
- `petIndex` (number): Zero-based index of the pet to update
- `petData` (Object): Updated pet information (same fields as addPetToCheckin)

**Returns:** `true` if pet was updated successfully, `false` if pet not found

**Throws:** None

**Example:**
```javascript
// Update first pet's weight
FormDataManager.updatePetInCheckin(0, {
    petWeight: "70"
});
```

**Side Effects:**
- Updates pet data in check-in cookie
- Triggers reactivity for UI updates
- May update pet form fields if pet is currently selected

---

### removePetFromCheckin(petIndex)

Removes a pet from the check-in data.

**Signature:**
```javascript
static removePetFromCheckin(petIndex: number): boolean
```

**Description:**
Deletes the pet at the specified index from the pets array. All subsequent pets are shifted down to fill the gap.

**Parameters:**
- `petIndex` (number): Zero-based index of the pet to remove

**Returns:** `true` if pet was removed successfully, `false` if pet not found

**Throws:** None

**Example:**
```javascript
// Remove the second pet
FormDataManager.removePetFromCheckin(1);
```

**Side Effects:**
- Removes pet from check-in data array
- Updates browser cookie
- Triggers reactivity (pet pills will be recreated without removed pet)
- May change indices of remaining pets

---

### addPetFeedingOrMedication(petIndex, type, data)

Adds feeding or medication schedule to a specific pet.

**Signature:**
```javascript
static addPetFeedingOrMedication(petIndex: number, type: string, data: Object): boolean
```

**Description:**
Appends a new feeding or medication entry to the specified pet's schedule. Used when submitting data from the feeding/medication popup forms.

**Parameters:**
- `petIndex` (number): Zero-based index of the target pet
- `type` (string): Type of schedule entry ("feeding" or "medication")
- `data` (Object):
  - `day_time` (string): Time slot (morning, noon, afternoon, night)
  - `feeding_med_details` (string): Description/details of the entry
  - `amount` (string, optional): Amount/quantity for feeding entries

**Returns:** `true` if entry was added successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// Add feeding schedule
FormDataManager.addPetFeedingOrMedication(0, "feeding", {
    day_time: "morning",
    feeding_med_details: "1 cup dry food",
    amount: "1"
});

// Add medication schedule
FormDataManager.addPetFeedingOrMedication(0, "medication", {
    day_time: "evening",
    feeding_med_details: "Heartworm prevention"
});
```

**Side Effects:**
- Adds schedule entry to pet's feeding or medication array
- Updates browser cookie
- Triggers reactivity for UI updates (feeding/medication displays)

---

### updatePetHealth(petIndex, healthData)

Updates health information for a specific pet.

**Signature:**
```javascript
static updatePetHealth(petIndex: number, healthData: Object): boolean
```

**Description:**
Modifies the health-related data for a pet, including unusual behaviors, warnings, and other health information.

**Parameters:**
- `petIndex` (number): Zero-based index of the target pet
- `healthData` (Object):
  - `unusualHealthBehavior` (boolean, optional): Whether pet has unusual behavior
  - `healthBehaviors` (string, optional): Description of unusual behaviors
  - `warnings` (string, optional): Health warnings or notes

**Returns:** `true` if health data was updated successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
FormDataManager.updatePetHealth(0, {
    unusualHealthBehavior: true,
    healthBehaviors: "Excessive barking at night",
    warnings: "Allergic to chicken"
});
```

**Side Effects:**
- Updates pet's health information in check-in data
- Triggers reactivity for UI updates

---

## Inventory Methods

### addInventoryItem(itemText)

Adds a new item to the inventory list.

**Signature:**
```javascript
static addInventoryItem(itemText: string): boolean
```

**Description:**
Appends a text description to the inventory items array. Used when users add items they are leaving at the facility.

**Parameters:**
- `itemText` (string): Description of the inventory item

**Returns:** `true` if item was added successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
FormDataManager.addInventoryItem("Dog collar and leash");
FormDataManager.addInventoryItem("Food bowl and water dish");
```

**Side Effects:**
- Adds item to inventory array in check-in data
- Updates browser cookie
- Triggers reactivity for UI updates (inventory list)

---

### removeInventoryItem(itemIndex)

Removes an item from the inventory list by index.

**Signature:**
```javascript
static removeInventoryItem(itemIndex: number): boolean
```

**Description:**
Deletes the inventory item at the specified position in the array. Used when users remove items from their inventory list.

**Parameters:**
- `itemIndex` (number): Zero-based index of the item to remove

**Returns:** `true` if item was removed successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// Remove the second inventory item
FormDataManager.removeInventoryItem(1);
```

**Side Effects:**
- Removes item from inventory array
- Updates browser cookie
- Triggers reactivity for UI updates

---

### updateInventoryItem(itemIndex, newText)

Updates the text of an existing inventory item.

**Signature:**
```javascript
static updateInventoryItem(itemIndex: number, newText: string): boolean
```

**Description:**
Modifies the description of an inventory item at the specified index. Used when users edit inventory item descriptions.

**Parameters:**
- `itemIndex` (number): Zero-based index of the item to update
- `newText` (string): New description for the inventory item

**Returns:** `true` if item was updated successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// Update first item with corrected description
FormDataManager.updateInventoryItem(0, "Blue dog collar and leash");
```

**Side Effects:**
- Updates item text in inventory array
- Updates browser cookie
- Triggers reactivity for UI updates

---

### setInventoryComplete(complete)

Sets the inventory completion status.

**Signature:**
```javascript
static setInventoryComplete(complete: boolean): boolean
```

**Description:**
Marks whether the user has completed their inventory declaration. When true, indicates they are not leaving any items (no inventory list needed). When false, requires inventory items to be listed.

**Parameters:**
- `complete` (boolean): Whether inventory is complete/confirmed

**Returns:** `true` if status was updated successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// User confirms they have no items to leave
FormDataManager.setInventoryComplete(true);
```

**Side Effects:**
- Updates inventory completion status in check-in data
- May affect form validation and progression
- Triggers reactivity for UI updates

---

## Editing Mode Methods

### setEditingMode(checkInId, originalData)

Enables editing mode for an existing check-in.

**Signature:**
```javascript
static setEditingMode(checkInId: string, originalData: Object): boolean
```

**Description:**
Sets the editing mode flag and stores a snapshot of the original data for comparison and change detection. Used when user is editing an existing check-in from the database.

**Parameters:**
- `checkInId` (string): ID of the check-in being edited
- `originalData` (Object): Original data snapshot from database

**Returns:** `true` if editing mode was enabled successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// Enable editing mode for check-in #123
FormDataManager.setEditingMode(123, originalCheckInData);
```

**Side Effects:**
- Sets editingMode.enabled flag to true
- Stores original data snapshot in cookie
- Updates browser cookie
- Triggers reactivity for UI updates

---

### getEditingMode()

Retrieves the current editing mode state.

**Signature:**
```javascript
static getEditingMode(): Object|null
```

**Description:**
Returns an object containing the editing mode flag, check-in ID being edited, and the original data snapshot. Useful for determining if the user is editing an existing check-in or creating a new one.

**Parameters:** None

**Returns:**
```javascript
{
  enabled: boolean,        // Whether editing mode is active
  checkInId: string,       // ID of check-in being edited
  originalData: Object     // Snapshot of original data
}
```

**Throws:** None

**Example:**
```javascript
const editingMode = FormDataManager.getEditingMode();
if (editingMode && editingMode.enabled) {
    console.log(`Editing check-in #${editingMode.checkInId}`);
}
```

---

### clearEditingMode()

Disables editing mode and clears original data snapshot.

**Signature:**
```javascript
static clearEditingMode(): boolean
```

**Description:**
Removes the editing mode flag and clears the stored original data. Used when user completes editing or cancels the edit operation.

**Parameters:** None

**Returns:** `true` if editing mode was disabled successfully, `false` otherwise

**Throws:** None

**Example:**
```javascript
// Disable editing mode after successful submission
FormDataManager.clearEditingMode();
```

**Side Effects:**
- Sets editingMode.enabled flag to false
- Clears original data snapshot
- Updates browser cookie
- Triggers reactivity for UI updates

---

### isEditingMode()

Checks if currently in editing mode.

**Signature:**
```javascript
static isEditingMode(): boolean
```

**Description:**
Determines whether the user is editing an existing check-in or creating a new one.

**Parameters:** None

**Returns:** `true` if in editing mode, `false` otherwise

**Throws:** None

**Example:**
```javascript
if (FormDataManager.isEditingMode()) {
    console.log("User is editing an existing check-in");
} else {
    console.log("User is creating a new check-in");
}
```

---

### getEditingCheckInId()

Gets the ID of the check-in currently being edited.

**Signature:**
```javascript
static getEditingCheckInId(): string|null
```

**Description:**
Returns the check-in ID if in editing mode, null otherwise.

**Parameters:** None

**Returns:** Check-in ID if editing, null otherwise

**Throws:** None

**Example:**
```javascript
const checkInId = FormDataManager.getEditingCheckInId();
if (checkInId) {
    console.log(`Editing check-in #${checkInId}`);
}
```

---

### getOriginalData()

Gets a deep copy of the original data snapshot.

**Signature:**
```javascript
static getOriginalData(): Object|null
```

**Description:**
Returns the original data from when editing mode was enabled. Useful for comparing current data with original to detect changes.

**Parameters:** None

**Returns:** Deep copy of original data or null if not editing

**Throws:** None

**Example:**
```javascript
const originalData = FormDataManager.getOriginalData();
if (originalData) {
    console.log("Original pet name:", originalData.pets[0].info.petName);
}
```

---

### hasDataChanged()

Detects if any data has changed since editing mode was enabled.

**Signature:**
```javascript
static hasDataChanged(): boolean
```

**Description:**
Performs a deep comparison between current check-in data and the original data snapshot to determine if any changes have been made.

**Parameters:** None

**Returns:** `true` if data has changed, `false` if unchanged

**Throws:** None

**Example:**
```javascript
if (FormDataManager.hasDataChanged()) {
    console.log("User has made changes");
    // Show save/discard options
}
```

---

### getChangeSummary()

Gets a summary of which sections have changed.

**Signature:**
```javascript
static getChangeSummary(): Object|null
```

**Description:**
Returns an object indicating which major sections (userInfo, pets, grooming, inventory, groomingDetails) have been modified since editing mode was enabled.

**Parameters:** None

**Returns:**
```javascript
{
  userInfo: boolean,       // Whether user info changed
  pets: boolean,           // Whether pet data changed
  grooming: boolean,       // Whether grooming changed
  inventory: boolean,      // Whether inventory changed
  groomingDetails: boolean // Whether grooming details changed
}
```

**Throws:** None

**Example:**
```javascript
const changes = FormDataManager.getChangeSummary();
if (changes) {
    if (changes.pets) console.log("Pet data was modified");
    if (changes.inventory) console.log("Inventory was modified");
}
```

---

### resetToOriginal()

Reverts all changes and restores original data.

**Signature:**
```javascript
static resetToOriginal(): boolean
```

**Description:**
Replaces current check-in data with the original data snapshot, effectively undoing all changes made during editing.

**Parameters:** None

**Returns:** `true` if reset was successful, `false` if not in editing mode

**Throws:** None

**Example:**
```javascript
// User clicks "Discard Changes" button
FormDataManager.resetToOriginal();
// Form is now populated with original data
```

**Side Effects:**
- Replaces current check-in data with original snapshot
- Updates browser cookie
- Triggers reactivity for UI updates (form fields reset)
- Logs reset operation to console

---

## UI Update Methods

### updateUIFromCookieData(cookieData)

Updates all UI elements based on current cookie data.

**Signature:**
```javascript
static updateUIFromCookieData(cookieData: Object): void
```

**Description:**
Performs a comprehensive UI update by synchronizing all DOM elements with the current state of the check-in data. It updates forms, pills, feeding displays, health information, inventory lists, and other UI components.

**Parameters:**
- `cookieData` (Object): The complete check-in data object from cookies

**Returns:** void

**Throws:** None

**Example:**
```javascript
const data = FormDataManager.getCheckinData();
FormDataManager.updateUIFromCookieData(data);
```

**Side Effects:**
- Updates multiple DOM elements to reflect current data state
- May show/hide UI sections based on data availability
- Preserves user input in active form fields
- Logs update operations to console for debugging

---

### registerUIUpdateListener()

Registers a listener for automatic UI updates when cookie data changes.

**Signature:**
```javascript
static registerUIUpdateListener(): void
```

**Description:**
Sets up the reactivity system that automatically updates the DOM whenever check-in data is modified. The listener monitors cookie changes and triggers appropriate UI updates to keep the interface synchronized with the data.

**Parameters:** None

**Returns:** void

**Throws:** None

**Example:**
```javascript
FormDataManager.registerUIUpdateListener();
```

**Side Effects:**
- Registers event listeners for cookie reactivity
- May trigger immediate UI updates if data exists

---

## Utility Methods

### debugCheckinData()

Displays detailed debugging information about current check-in data.

**Signature:**
```javascript
static debugCheckinData(): Object
```

**Description:**
Logs comprehensive information about the check-in state to the console, including data structure, pet count, cookie size, and system status. Useful for development and troubleshooting.

**Parameters:** None

**Returns:** The complete check-in data object for further inspection

**Throws:** None

**Example:**
```javascript
// View debug information in console
FormDataManager.debugCheckinData();
```

**Side Effects:**
- Logs detailed information to browser console
- Groups console output for better readability

---

### getCookieSize()

Gets the total size of all browser cookies in bytes.

**Signature:**
```javascript
static getCookieSize(): number
```

**Description:**
Useful for monitoring cookie storage usage and avoiding size limits.

**Parameters:** None

**Returns:** Total size of all cookies in bytes

**Throws:** None

**Example:**
```javascript
const size = FormDataManager.getCookieSize();
console.log(`Cookies use ${size} bytes`);
```

---

### canSetCookie(name, value)

Checks if a cookie can be set without exceeding size limits.

**Signature:**
```javascript
static canSetCookie(name: string, value: any): boolean
```

**Description:**
Validates whether the provided cookie name and value combination would fit within browser cookie size constraints (typically 4KB).

**Parameters:**
- `name` (string): Cookie name
- `value` (any): Cookie value (will be stringified if object)

**Returns:** `true` if cookie can be set, `false` if too large

**Throws:** None

**Example:**
```javascript
if (FormDataManager.canSetCookie("test", largeData)) {
    // Safe to set cookie
} else {
    console.warn("Cookie too large");
}
```

---

### deepMerge(target, source)

Performs deep merge of two objects.

**Signature:**
```javascript
static deepMerge(target: Object, source: Object): Object
```

**Description:**
Recursively merges source object properties into target object, preserving nested structures and avoiding reference issues.

**Parameters:**
- `target` (Object): Target object to merge into
- `source` (Object): Source object to merge from

**Returns:** New object with merged properties

**Throws:** None

**Example:**
```javascript
const result = FormDataManager.deepMerge(
    { user: { name: "John" } },
    { user: { age: 30 }, active: true }
);
// Result: { user: { name: "John", age: 30 }, active: true }
```

---

## Deprecated Methods

### getFormDataFromStep(step)

**Status:** ⚠️ DEPRECATED  
**Use Instead:** [`getCheckinData()`](#getcheckindata)

```javascript
static getFormDataFromStep(step: number): Object|null
```

---

### saveFormDataToStep(data, step)

**Status:** ⚠️ DEPRECATED  
**Use Instead:** [`handleFormStep()`](#handleformstepstep-formdata-selectedpetindex)

```javascript
static saveFormDataToStep(data: Object, step?: number): boolean
```

---

### getPetsFromCookies()

**Status:** ⚠️ DEPRECATED  
**Use Instead:** [`getAllPetsFromCheckin()`](#getallpetssfromcheckin)

```javascript
static getPetsFromCookies(): Array
```

---

### updatePetInCookies(petIndex, data)

**Status:** ⚠️ DEPRECATED  
**Use Instead:** [`updatePetInCheckin()`](#updatepetincheckinpetindex-petdata)

```javascript
static updatePetInCookies(petIndex: number, data: Object): boolean
```

---

## Related Documentation

- [`DATA_FLOW.md`](DATA_FLOW.md) - Architecture and data flow details
- [`DEVELOPER_GUIDE.md`](DEVELOPER_GUIDE.md) - How to use FormDataManager API
- [`DEPLOYMENT_GUIDE.md`](DEPLOYMENT_GUIDE.md) - Deployment procedures

---

**Last Updated:** 2026-01-30  
**Status:** ✅ Production Ready  
**Phase:** 5 (Documentation & Deployment)
