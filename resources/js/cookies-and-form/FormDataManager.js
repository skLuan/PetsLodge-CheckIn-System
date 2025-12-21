/**
 * @fileoverview FormDataManager - Unified interface for pet check-in form data management
 *
 * This module provides a clean, backward-compatible API for managing pet check-in form data
 * stored in browser cookies. It orchestrates multiple specialized managers to handle different
 * aspects of the check-in process while maintaining reactivity for automatic UI updates.
 *
 * Key Features:
 * - Modular architecture with separation of concerns
 * - Cookie-based data persistence with reactivity
 * - Automatic UI updates when data changes
 * - Backward compatibility with existing code
 * - Comprehensive form validation and step handling
 *
 * Architecture:
 * - CoreDataManager: Basic cookie storage/retrieval
 * - ValidationManager: Form step validation and data processing
 * - InventoryManager: Inventory item management
 * - PetManager: Pet CRUD operations and feeding/medication
 * - UtilitiesManager: Helper functions and debugging tools
 * - ReactivityManager: Cookie change detection and UI reactivity
 * - UIManager: DOM manipulation and UI state updates
 *
 * Data Flow:
 * User Input → FormDataManager → Specialized Manager → Cookie Update → Reactivity Trigger → UI Update
 *
 * @author Kilo Code
 * @version 2.0.0
 * @since 2025-10-05
 */

import {
    CoreDataManager,
    ValidationManager,
    InventoryManager,
    PetManager,
    UtilitiesManager
} from "./managers/index.js";

import {
    CookieReactivityManager,
    UIManager
} from "./reactivitySystem/index.js";

/**
 * Main FormDataManager class - Unified interface for pet check-in data management
 *
 * This class serves as the primary API for managing pet check-in form data. It delegates
 * operations to specialized manager modules while maintaining backward compatibility with
 * existing code that expects the monolithic FormDataManager interface.
 *
 * The class implements a facade pattern, providing a single entry point for all form data
 * operations while internally routing calls to appropriate specialized managers based on
 * the operation type (validation, pet management, inventory, etc.).
 *
 * Key responsibilities:
 * - Initialize the data management system and reactivity
 * - Route method calls to appropriate specialized managers
 * - Maintain backward compatibility with legacy code
 * - Provide unified error handling and logging
 *
 * @class
 * @static
 * @example
 * // Initialize the system
 * FormDataManager.initialize();
 *
 * // Add a pet to the check-in
 * FormDataManager.addPetToCheckin({ petName: 'Max', petType: 'dog' });
 *
 * // Handle form step submission
 * FormDataManager.handleFormStep(1, formData, selectedPetIndex);
 */
class FormDataManager {
    /**
     * Name of the cookie used to store check-in data
     * @static
     * @type {string}
     * @default "pl_checkin_data"
     * @readonly
     */
    static CHECKIN_COOKIE_NAME = CoreDataManager.CHECKIN_COOKIE_NAME;

    /**
     * Interval for auto-saving check-in data (currently disabled)
     * @static
     * @type {number}
     * @default 30000
     * @readonly
     * @deprecated Auto-save functionality is currently disabled
     */
    static AUTO_SAVE_INTERVAL = 30000; // 30 segundos

    /**
     * Timer reference for auto-save functionality
     * @static
     * @type {number|null}
     * @default null
     * @private
     * @deprecated Auto-save functionality is currently disabled
     */
    static autoSaveTimer = null;

    /**
     * Initializes the form data management system
     *
     * This method sets up the entire data management infrastructure including:
     * - Creating initial check-in cookie if it doesn't exist
     * - Starting cookie reactivity monitoring
     * - Registering UI update listeners for automatic DOM updates
     * - Triggering initial UI synchronization with existing data
     *
     * Should be called once when the application starts, typically in a DOMContentLoaded event handler.
     *
     * @static
     * @returns {void}
     *
     * @example
     * // Initialize when DOM is ready
     * document.addEventListener("DOMContentLoaded", () => {
     *     FormDataManager.initialize();
     * });
     *
     * @sideEffects
     * - Creates initial check-in cookie if none exists
     * - Starts cookie reactivity monitoring
     * - Registers global event listeners for UI updates
     * - Updates DOM elements to reflect current data state
     * - Logs initialization status to console
     */
    static async initialize() {
        // Crear cookie checkin si no existe
        if (!CoreDataManager.getCheckinData()) {
            await CoreDataManager.createInitialCheckin();
        }

        // Iniciar sistema de reactividad de cookies
        CookieReactivityManager.startListening();

        // Registrar listener para actualizar UI automáticamente
        UIManager.registerUIUpdateListener();

        // Trigger initial UI update with existing data
        UIManager.updateUIFromCookieData(CoreDataManager.getCheckinData());

        console.log(
            "FormDataManager initialized with checkin data:",
            CoreDataManager.getCheckinData()
        );
    }

    /**
     * Registers a listener for automatic UI updates when cookie data changes
     *
     * This method sets up the reactivity system that automatically updates the DOM
     * whenever check-in data is modified. The listener monitors cookie changes and
     * triggers appropriate UI updates to keep the interface synchronized with the data.
     *
     * @static
     * @returns {void}
     *
     * @example
     * FormDataManager.registerUIUpdateListener();
     *
     * @sideEffects
     * - Registers event listeners for cookie reactivity
     * - May trigger immediate UI updates if data exists
     *
     * @see CookieReactivityManager.addListener
     * @see UIManager.updateUIFromCookieData
     */
    static registerUIUpdateListener() {
        return UIManager.registerUIUpdateListener();
    }

    /**
     * Updates all UI elements based on current cookie data
     *
     * This method performs a comprehensive UI update by synchronizing all DOM elements
     * with the current state of the check-in data. It updates forms, pills, feeding displays,
     * health information, inventory lists, and other UI components.
     *
     * The update is conservative - it only updates elements that are safe to modify
     * without disrupting active user input (e.g., avoids clearing text fields being edited).
     *
     * @static
     * @param {Object} cookieData - The complete check-in data object from cookies
     * @param {Object} cookieData.user - User information including owner details
     * @param {Array} cookieData.pets - Array of pet objects with info, health, feeding data
     * @param {Object} cookieData.grooming - Grooming service selections
     * @param {Array} cookieData.inventory - List of inventory items
     * @param {boolean} cookieData.inventoryComplete - Whether inventory is marked complete
     * @param {boolean} cookieData.termsAccepted - Whether terms have been accepted
     * @returns {void}
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * FormDataManager.updateUIFromCookieData(data);
     *
     * @sideEffects
     * - Updates multiple DOM elements to reflect current data state
     * - May show/hide UI sections based on data availability
     * - Preserves user input in active form fields
     * - Logs update operations to console for debugging
     *
     * @see UIManager.updateUIFromCookieData
     */
    static updateUIFromCookieData(cookieData) {
        return UIManager.updateUIFromCookieData(cookieData);
    }

    /**
     * Updates the owner information form fields
     *
     * Populates the owner info form (phone, name, email, address, city, zip) with
     * the provided user data. Only updates fields that are empty to avoid
     * overwriting active user input.
     *
     * @static
     * @param {Object} userInfo - Owner information object
     * @param {string} userInfo.phone - Phone number
     * @param {string} userInfo.name - Full name
     * @param {string} userInfo.email - Email address
     * @param {string} userInfo.address - Street address
     * @param {string} userInfo.city - City name
     * @param {string} userInfo.zip - ZIP/postal code
     * @returns {void}
     *
     * @example
     * FormDataManager.updateOwnerInfoForm({
     *     phone: "555-0123",
     *     name: "John Doe",
     *     email: "john@example.com"
     * });
     *
     * @sideEffects
     * - Updates DOM input fields with provided data
     * - Only modifies empty fields to preserve user input
     *
     * @see UIManager.updateOwnerInfoForm
     */
    static updateOwnerInfoForm(userInfo) {
        return UIManager.updateOwnerInfoForm(userInfo);
    }

    /**
     * Updates pet pills and related form elements
     *
     * Manages the display of pet pills in the UI. Only recreates pills when in the
     * pet information step to avoid unnecessary DOM manipulations. For other steps,
     * only updates the currently selected pet's form data.
     *
     * @static
     * @param {Array} pets - Array of pet objects from check-in data
     * @param {Object} pets[].info - Basic pet information (name, type, breed, etc.)
     * @param {Object} pets[].health - Health information and behaviors
     * @param {Array} pets[].feeding - Feeding schedule data
     * @param {Array} pets[].medication - Medication schedule data
     * @returns {void}
     *
     * @example
     * const pets = FormDataManager.getAllPetsFromCheckin();
     * FormDataManager.updatePetPillsAndForms(pets);
     *
     * @sideEffects
     * - May recreate pet pill elements in DOM (only in pet info step)
     * - Updates selected pet form fields
     * - Preserves pill selection state during recreation
     *
     * @see UIManager.updatePetPillsAndForms
     * @see Pill
     */
    static updatePetPillsAndForms(pets) {
        return UIManager.updatePetPillsAndForms(pets);
    }

    /**
     * Updates the form fields for a specific pet
     *
     * Populates the pet information form with data for the specified pet.
     * Handles radio buttons, text inputs, and other form controls appropriately.
     *
     * @static
     * @param {Object} petData - Complete pet data object
     * @param {Object} petData.info - Pet information (name, type, breed, age, etc.)
     * @returns {void}
     *
     * @example
     * const pet = FormDataManager.getAllPetsFromCheckin()[0];
     * FormDataManager.updatePetForm(pet);
     *
     * @sideEffects
     * - Updates DOM form fields with pet data
     * - Handles different input types (text, radio, select)
     *
     * @see UIManager.updatePetForm
     */
    static updatePetForm(petData) {
        return UIManager.updatePetForm(petData);
    }

    /**
     * Updates the feeding and medication UI displays
     *
     * Refreshes the feeding/medication schedule displays, including time slot
     * visibility, editable input fields, and day section toggling based on
     * available data.
     *
     * @static
     * @param {Array} pets - Array of pet objects with feeding/medication data
     * @returns {void}
     *
     * @example
     * const pets = FormDataManager.getAllPetsFromCheckin();
     * FormDataManager.updateFeedingMedicationUI(pets);
     *
     * @sideEffects
     * - Updates multiple DOM elements for feeding/medication display
     * - Shows/hides day sections based on data availability
     * - Creates editable input elements for schedule items
     *
     * @see UIManager.updateFeedingMedicationUI
     */
    static updateFeedingMedicationUI(pets) {
        return UIManager.updateFeedingMedicationUI(pets);
    }

    /**
     * Updates the health information UI
     *
     * Refreshes health-related form elements including unusual behavior toggles,
     * conditional detail fields, grooming checkboxes, and grooming notes.
     *
     * @static
     * @param {Array} pets - Array of pet objects
     * @param {Object} grooming - Grooming service selections
     * @param {string} groomingDetails - Additional grooming notes
     * @returns {void}
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * FormDataManager.updateHealthInfoUI(
     *     data.pets,
     *     data.grooming,
     *     data.groomingDetails
     * );
     *
     * @sideEffects
     * - Updates health form fields and conditional sections
     * - Shows/hides grooming details based on selections
     * - Manages form field visibility and state
     *
     * @see UIManager.updateHealthInfoUI
     */
    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        return UIManager.updateHealthInfoUI(pets, grooming, groomingDetails);
    }

    /**
     * Updates grooming and inventory UI elements
     *
     * Refreshes grooming service checkboxes and inventory-related displays.
     * Note: This method has been largely superseded by more specific inventory methods.
     *
     * @static
     * @param {Object} grooming - Grooming service selections
     * @param {Array} inventory - Inventory items array
     * @param {string} details - Grooming detail notes
     * @returns {void}
     *
     * @deprecated Use updateInventoryUI for inventory-specific updates
     * @see UIManager.updateGroomingAndInventoryUI
     */
    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        return UIManager.updateGroomingAndInventoryUI(grooming, inventory, details);
    }

    /**
     * Gets the index of the currently selected pet pill
     *
     * Determines which pet is currently selected in the UI by checking
     * for the "selected" class on pill elements.
     *
     * @static
     * @returns {number|null} The index of the selected pet, or null if none selected
     *
     * @example
     * const selectedIndex = FormDataManager.getCurrentSelectedPetIndex();
     * if (selectedIndex !== null) {
     *     console.log(`Pet ${selectedIndex} is selected`);
     * }
     *
     * @see UtilitiesManager.getCurrentSelectedPetIndex
     */
    static getCurrentSelectedPetIndex() {
        return UtilitiesManager.getCurrentSelectedPetIndex();
    }

    /**
     * Creates the initial check-in data structure in cookies
     *
     * Initializes a new check-in session with default structure including empty
     * arrays for pets, default user object, and metadata like creation timestamp.
     * Only creates data if none exists to avoid overwriting existing sessions.
     *
     * @static
     * @returns {boolean} True if initial data was created, false if data already existed
     *
     * @example
     * const created = FormDataManager.createInitialCheckin();
     * if (created) {
     *     console.log("New check-in session started");
     * }
     *
     * @sideEffects
     * - Sets browser cookie with initial check-in structure
     * - Logs creation status to console
     *
     * @see CoreDataManager.createInitialCheckin
     * @see DEFAULT_CHECKIN_STRUCTURE
     */
    static createInitialCheckin() {
        return CoreDataManager.createInitialCheckin();
    }

    /**
     * Retrieves the complete check-in data from cookies
     *
     * Gets the current check-in session data stored in browser cookies.
     * Returns the parsed JSON object or null if no data exists.
     *
     * @static
     * @returns {Object|null} Complete check-in data object or null if not found
     * @returns {string} return.date - ISO timestamp of check-in creation
     * @returns {string} return.id - Unique check-in identifier
     * @returns {Object} return.user - User/owner information
     * @returns {Array} return.pets - Array of pet objects
     * @returns {Object} return.grooming - Grooming service selections
     * @returns {Array} return.inventory - Inventory items list
     * @returns {boolean} return.inventoryComplete - Inventory completion status
     * @returns {boolean} return.termsAccepted - Terms acceptance status
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * if (data) {
     *     console.log(`${data.pets.length} pets in check-in`);
     * }
     *
     * @see CoreDataManager.getCheckinData
     */
    static getCheckinData() {
        return CoreDataManager.getCheckinData();
    }

    /**
     * Updates check-in data in cookies with new information
     *
     * Merges the provided updates into the existing check-in data and saves
     * the result to cookies. Uses deep merge to preserve existing data while
     * updating specified fields. Triggers reactivity for UI updates.
     *
     * @static
     * @param {Object} updates - Object containing data to update
     * @param {Object} [updates.user] - User information updates
     * @param {Array} [updates.pets] - Pet array updates
     * @param {Object} [updates.grooming] - Grooming service updates
     * @param {Array} [updates.inventory] - Inventory updates
     * @param {boolean} [updates.inventoryComplete] - Inventory completion status
     * @param {boolean} [updates.termsAccepted] - Terms acceptance status
     * @returns {boolean} True if update was successful, false otherwise
     *
     * @example
     * // Update user information
     * FormDataManager.updateCheckinData({
     *     user: { info: { name: "John Doe" } }
     * });
     *
     * // Mark inventory as complete
     * FormDataManager.updateCheckinData({
     *     inventoryComplete: true
     * });
     *
     * @sideEffects
     * - Updates browser cookie with merged data
     * - Triggers cookie reactivity listeners
     * - May cause automatic UI updates
     * - Logs update operations to console
     *
     * @see CoreDataManager.updateCheckinData
     * @see CookieReactivityManager.triggerCheck
     */
    static updateCheckinData(updates) {
        const result = CoreDataManager.updateCheckinData(updates);
        if (result) {
            // Trigger reactivity for UI updates
            CookieReactivityManager.triggerCheck();
        }
        return result;
    }

    /**
     * Clears all check-in data from cookies
     *
     * Completely removes the check-in cookie, effectively ending the current
     * check-in session. Used when starting fresh or after successful submission.
     *
     * @static
     * @returns {void}
     *
     * @example
     * // Clear data after successful submission
     * FormDataManager.clearCheckinData();
     *
     * @sideEffects
     * - Deletes browser cookie containing check-in data
     * - Logs clearance operation to console
     * - May trigger reactivity listeners with null data
     *
     * @see CoreDataManager.clearCheckinData
     */
    static clearCheckinData() {
        return CoreDataManager.clearCheckinData();
    }

    /**
     * Handles form submission for a specific step in the check-in process
     *
     * Processes form data based on the current step, validates input, and updates
     * the appropriate data structures. Each step has different validation rules
     * and data handling logic.
     *
     * @static
     * @param {number} step - Zero-based step index (0=owner, 1=pet, 2=feeding, etc.)
     * @param {Object} formData - Form data object from the submitted form
     * @param {number|null} [selectedPetIndex=null] - Index of selected pet for pet-specific operations
     * @returns {boolean} True if step was handled successfully, false if validation failed
     *
     * @example
     * // Handle owner information step
     * const success = FormDataManager.handleFormStep(0, {
     *     phone: "555-0123",
     *     name: "John Doe",
     *     email: "john@example.com"
     * });
     *
     * // Handle pet information step
     * const success = FormDataManager.handleFormStep(1, {
     *     petName: "Max",
     *     petType: "dog"
     * }, 0); // Update first pet
     *
     * @sideEffects
     * - Updates check-in data in cookies
     * - May trigger UI updates via reactivity
     * - Shows validation error messages to user
     * - May redirect or show additional UI elements
     *
     * @see ValidationManager.handleFormStep
     * @see FORM_CONFIG.STEPS
     */
    static handleFormStep(step, formData, selectedPetIndex = null) {
        return ValidationManager.handleFormStep(step, formData, selectedPetIndex);
    }

    /**
     * Updates user/owner information in the check-in data
     *
     * Processes and validates owner information including contact details
     * and emergency contact information. Merges the data into the existing
     * check-in structure.
     *
     * @static
     * @param {Object} userData - User information object
     * @param {string} userData.phone - Phone number (required)
     * @param {string} userData.name - Full name (required)
     * @param {string} userData.email - Email address (required)
     * @param {string} userData.address - Street address (required)
     * @param {string} userData.city - City name (required)
     * @param {string} userData.zip - ZIP/postal code (required)
     * @param {string} [userData.emergencyContactName] - Emergency contact name
     * @param {string} [userData.emergencyContactPhone] - Emergency contact phone
     * @returns {boolean} True if update was successful
     *
     * @example
     * FormDataManager.updateUserInfo({
     *     phone: "555-0123",
     *     name: "John Doe",
     *     email: "john@example.com",
     *     address: "123 Main St",
     *     city: "Anytown",
     *     zip: "12345",
     *     emergencyContactName: "Jane Doe",
     *     emergencyContactPhone: "555-0456"
     * });
     *
     * @sideEffects
     * - Updates user information in check-in cookie
     * - Triggers reactivity for UI updates
     *
     * @see ValidationManager.updateUserInfo
     */
    static updateUserInfo(userData) {
        return ValidationManager.updateUserInfo(userData);
    }

    /**
     * Shows the terms and conditions popup
     *
     * Displays the modal dialog containing terms and conditions that must
     * be accepted before completing the check-in process.
     *
     * @static
     * @returns {void}
     *
     * @example
     * // Show terms popup when needed
     * FormDataManager.showTermsPopup();
     *
     * @sideEffects
     * - Displays modal popup in DOM
     * - May prevent form progression until accepted
     *
     * @see ValidationManager.showTermsPopup
     */
    static showTermsPopup() {
        return ValidationManager.showTermsPopup();
    }

    /**
     * Hides the terms and conditions popup
     *
     * Closes the terms and conditions modal dialog, typically called
     * after user acceptance or cancellation.
     *
     * @static
     * @returns {void}
     *
     * @example
     * // Hide terms popup after acceptance
     * FormDataManager.hideTermsPopup();
     *
     * @sideEffects
     * - Hides/removes modal popup from DOM
     *
     * @see ValidationManager.hideTermsPopup
     */
    static hideTermsPopup() {
        return ValidationManager.hideTermsPopup();
    }

    /**
     * Sets the terms acceptance status
     *
     * Records whether the user has accepted the terms and conditions.
     * This is required before completing the check-in process.
     *
     * @static
     * @param {boolean} accepted - Whether terms have been accepted
     * @returns {boolean} True if status was updated successfully
     *
     * @example
     * // Mark terms as accepted
     * FormDataManager.setTermsAccepted(true);
     *
     * @sideEffects
     * - Updates terms acceptance status in check-in data
     * - May enable form progression or submission
     *
     * @see ValidationManager.setTermsAccepted
     */
    static setTermsAccepted(accepted) {
        return ValidationManager.setTermsAccepted(accepted);
    }

    /**
     * Adds a new pet to the check-in data
     *
     * Creates a new pet entry with the provided information and adds it to the
     * pets array in the check-in data. Generates a unique ID for the pet and
     * initializes default structures for health, feeding, and medication data.
     *
     * @static
     * @param {Object} petData - Pet information object
     * @param {string} petData.petName - Pet's name (required)
     * @param {string} petData.petType - Pet's type/species (dog, cat, etc.)
     * @param {string} petData.petColor - Pet's color
     * @param {string} petData.petBreed - Pet's breed
     * @param {string} petData.petAge - Pet's birth date (YYYY-MM-DD)
     * @param {string} petData.petWeight - Pet's weight in pounds
     * @param {string} petData.petGender - Pet's gender (male/female)
     * @param {string} petData.petSpayed - Spayed/neutered status (yes/no)
     * @returns {boolean} True if pet was added successfully
     *
     * @example
     * FormDataManager.addPetToCheckin({
     *     petName: "Max",
     *     petType: "dog",
     *     petBreed: "Golden Retriever",
     *     petAge: "2020-03-15",
     *     petWeight: "65",
     *     petGender: "male",
     *     petSpayed: "yes"
     * });
     *
     * @sideEffects
     * - Adds new pet object to check-in data
     * - Updates browser cookie
     * - Triggers reactivity for UI updates (may recreate pet pills)
     *
     * @see PetManager.addPetToCheckin
     * @see DEFAULT_PET_STRUCTURE
     */
    static addPetToCheckin(petData) {
        return PetManager.addPetToCheckin(petData);
    }

    /**
     * Updates information for an existing pet
     *
     * Modifies the data for a specific pet identified by index. Merges the
     * provided updates with existing pet data, preserving unchanged fields.
     *
     * @static
     * @param {number} petIndex - Zero-based index of the pet to update
     * @param {Object} petData - Updated pet information
     * @returns {boolean} True if pet was updated successfully, false if pet not found
     *
     * @example
     * // Update first pet's weight
     * FormDataManager.updatePetInCheckin(0, {
     *     petWeight: "70"
     * });
     *
     * @sideEffects
     * - Updates pet data in check-in cookie
     * - Triggers reactivity for UI updates
     * - May update pet form fields if pet is currently selected
     *
     * @see PetManager.updatePetInCheckin
     */
    static updatePetInCheckin(petIndex, petData) {
        return PetManager.updatePetInCheckin(petIndex, petData);
    }

    /**
     * Removes a pet from the check-in data
     *
     * Deletes the pet at the specified index from the pets array.
     * All subsequent pets are shifted down to fill the gap.
     *
     * @static
     * @param {number} petIndex - Zero-based index of the pet to remove
     * @returns {boolean} True if pet was removed successfully, false if pet not found
     *
     * @example
     * // Remove the second pet
     * FormDataManager.removePetFromCheckin(1);
     *
     * @sideEffects
     * - Removes pet from check-in data array
     * - Updates browser cookie
     * - Triggers reactivity (pet pills will be recreated without removed pet)
     * - May change indices of remaining pets
     *
     * @see PetManager.removePetFromCheckin
     */
    static removePetFromCheckin(petIndex) {
        return PetManager.removePetFromCheckin(petIndex);
    }

    /**
     * Adds feeding or medication schedule to a specific pet
     *
     * Appends a new feeding or medication entry to the specified pet's schedule.
     * Used when submitting data from the feeding/medication popup forms.
     *
     * @static
     * @param {number} petIndex - Zero-based index of the target pet
     * @param {string} type - Type of schedule entry ("feeding" or "medication")
     * @param {Object} data - Schedule entry data
     * @param {string} data.day_time - Time slot (morning, noon, afternoon, night)
     * @param {string} data.feeding_med_details - Description/details of the entry
     * @param {string} [data.amount] - Amount/quantity for feeding entries
     * @returns {boolean} True if entry was added successfully
     *
     * @example
     * // Add feeding schedule
     * FormDataManager.addPetFeedingOrMedication(0, "feeding", {
     *     day_time: "morning",
     *     feeding_med_details: "1 cup dry food",
     *     amount: "1"
     * });
     *
     * // Add medication schedule
     * FormDataManager.addPetFeedingOrMedication(0, "medication", {
     *     day_time: "evening",
     *     feeding_med_details: "Heartworm prevention"
     * });
     *
     * @sideEffects
     * - Adds schedule entry to pet's feeding or medication array
     * - Updates browser cookie
     * - Triggers reactivity for UI updates (feeding/medication displays)
     *
     * @see PetManager.addPetFeedingOrMedication
     */
    static addPetFeedingOrMedication(petIndex, type, data) {
        return PetManager.addPetFeedingOrMedication(petIndex, type, data);
    }

    /**
     * Updates health information for a specific pet
     *
     * Modifies the health-related data for a pet, including unusual behaviors,
     * warnings, and other health information.
     *
     * @static
     * @param {number} petIndex - Zero-based index of the target pet
     * @param {Object} healthData - Health information to update
     * @param {boolean} [healthData.unusualHealthBehavior] - Whether pet has unusual behavior
     * @param {string} [healthData.healthBehaviors] - Description of unusual behaviors
     * @param {string} [healthData.warnings] - Health warnings or notes
     * @returns {boolean} True if health data was updated successfully
     *
     * @example
     * FormDataManager.updatePetHealth(0, {
     *     unusualHealthBehavior: true,
     *     healthBehaviors: "Excessive barking at night",
     *     warnings: "Allergic to chicken"
     * });
     *
     * @sideEffects
     * - Updates pet's health information in check-in data
     * - Triggers reactivity for UI updates
     *
     * @see PetManager.updatePetHealth
     */
    static updatePetHealth(petIndex, healthData) {
        return PetManager.updatePetHealth(petIndex, healthData);
    }

    /**
     * Updates comprehensive health information for a specific pet
     *
     * Similar to updatePetHealth but designed for complete health data replacement
     * rather than merging. Used by the health information form step.
     *
     * @static
     * @param {number} petIndex - Zero-based index of the target pet
     * @param {Object} healthData - Complete health information object
     * @returns {boolean} True if health information was updated successfully
     *
     * @example
     * FormDataManager.updatePetHealthInfo(0, {
     *     unusualHealthBehavior: false,
     *     healthBehaviors: "",
     *     warnings: "Requires daily medication"
     * });
     *
     * @sideEffects
     * - Replaces pet's health information in check-in data
     * - Triggers reactivity for UI updates
     *
     * @see PetManager.updatePetHealthInfo
     */
    static updatePetHealthInfo(petIndex, healthData) {
        return PetManager.updatePetHealthInfo(petIndex, healthData);
    }

    /**
     * Retrieves all pets from the check-in data in a flattened format
     *
     * Gets the complete array of pets with flattened properties for easier
     * access in legacy code. Each pet object includes both the structured
     * data and flattened properties for backward compatibility.
     *
     * @static
     * @returns {Array} Array of pet objects with flattened properties
     * @returns {number} return[].index - Zero-based index of the pet
     * @returns {string} return[].petName - Pet's name
     * @returns {string} return[].petType - Pet's type/species
     * @returns {string} return[].petColor - Pet's color
     * @returns {string} return[].petBreed - Pet's breed
     * @returns {string} return[].petAge - Pet's birth date
     * @returns {string} return[].petWeight - Pet's weight
     * @returns {string} return[].petGender - Pet's gender
     * @returns {string} return[].petSpayed - Spayed/neutered status
     * @returns {Object} return[].info - Structured pet information
     * @returns {Object} return[].health - Health information
     * @returns {Array} return[].feeding - Feeding schedule
     * @returns {Array} return[].medication - Medication schedule
     *
     * @example
     * const pets = FormDataManager.getAllPetsFromCheckin();
     * pets.forEach(pet => {
     *     console.log(`${pet.petName} is a ${pet.petType}`);
     * });
     *
     * @see PetManager.getAllPetsFromCheckin
     */
    static getAllPetsFromCheckin() {
        return PetManager.getAllPetsFromCheckin();
    }

    /**
     * Adds a new item to the inventory list
     *
     * Appends a text description to the inventory items array. Used when
     * users add items they are leaving at the facility.
     *
     * @static
     * @param {string} itemText - Description of the inventory item
     * @returns {boolean} True if item was added successfully
     *
     * @example
     * FormDataManager.addInventoryItem("Dog collar and leash");
     * FormDataManager.addInventoryItem("Food bowl and water dish");
     *
     * @sideEffects
     * - Adds item to inventory array in check-in data
     * - Updates browser cookie
     * - Triggers reactivity for UI updates (inventory list)
     *
     * @see InventoryManager.addInventoryItem
     */
    static addInventoryItem(itemText) {
        return InventoryManager.addInventoryItem(itemText);
    }

    /**
     * Removes an item from the inventory list by index
     *
     * Deletes the inventory item at the specified position in the array.
     * Used when users remove items from their inventory list.
     *
     * @static
     * @param {number} itemIndex - Zero-based index of the item to remove
     * @returns {boolean} True if item was removed successfully
     *
     * @example
     * // Remove the second inventory item
     * FormDataManager.removeInventoryItem(1);
     *
     * @sideEffects
     * - Removes item from inventory array
     * - Updates browser cookie
     * - Triggers reactivity for UI updates
     *
     * @see InventoryManager.removeInventoryItem
     */
    static removeInventoryItem(itemIndex) {
        return InventoryManager.removeInventoryItem(itemIndex);
    }

    /**
     * Updates the text of an existing inventory item
     *
     * Modifies the description of an inventory item at the specified index.
     * Used when users edit inventory item descriptions.
     *
     * @static
     * @param {number} itemIndex - Zero-based index of the item to update
     * @param {string} newText - New description for the inventory item
     * @returns {boolean} True if item was updated successfully
     *
     * @example
     * // Update first item with corrected description
     * FormDataManager.updateInventoryItem(0, "Blue dog collar and leash");
     *
     * @sideEffects
     * - Updates item text in inventory array
     * - Updates browser cookie
     * - Triggers reactivity for UI updates
     *
     * @see InventoryManager.updateInventoryItem
     */
    static updateInventoryItem(itemIndex, newText) {
        return InventoryManager.updateInventoryItem(itemIndex, newText);
    }

    /**
     * Sets the inventory completion status
     *
     * Marks whether the user has completed their inventory declaration.
     * When true, indicates they are not leaving any items (no inventory list needed).
     * When false, requires inventory items to be listed.
     *
     * @static
     * @param {boolean} complete - Whether inventory is complete/confirmed
     * @returns {boolean} True if status was updated successfully
     *
     * @example
     * // User confirms they have no items to leave
     * FormDataManager.setInventoryComplete(true);
     *
     * @sideEffects
     * - Updates inventory completion status in check-in data
     * - May affect form validation and progression
     * - Triggers reactivity for UI updates
     *
     * @see InventoryManager.setInventoryComplete
     */
    static setInventoryComplete(complete) {
        return InventoryManager.setInventoryComplete(complete);
    }

    /**
     * Updates the inventory UI elements
     *
     * Refreshes the inventory display including item lists, completion checkboxes,
     * and visibility toggles based on the current inventory state.
     *
     * @static
     * @param {Array} inventory - Array of inventory item strings
     * @param {boolean} inventoryComplete - Whether inventory is marked complete
     * @returns {void}
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * FormDataManager.updateInventoryUI(data.inventory, data.inventoryComplete);
     *
     * @sideEffects
     * - Updates DOM elements for inventory display
     * - Shows/hides completion checkbox based on item presence
     * - Updates tab bar indicators if applicable
     *
     * @see InventoryManager.updateInventoryUI
     */
    static updateInventoryUI(inventory, inventoryComplete) {
        return InventoryManager.updateInventoryUI(inventory, inventoryComplete);
    }

    /**
     * Updates grooming and inventory data together
     *
     * Combines grooming service selections with inventory data updates.
     * Used during the health information step where both are collected.
     *
     * @static
     * @param {Object} groomingData - Grooming service selections
     * @param {Array} inventoryData - Inventory items array
     * @param {string} [groomingDetails=""] - Additional grooming notes
     * @returns {boolean} True if data was updated successfully
     *
     * @example
     * FormDataManager.updateGroomingAndInventory(
     *     { bath: true, nails: false },
     *     ["Collar", "Leash"],
     *     "Please be gentle with the nails"
     * );
     *
     * @sideEffects
     * - Updates grooming and inventory data in check-in cookie
     * - Triggers reactivity for UI updates
     *
     * @see ValidationManager.updateGroomingAndInventory
     */
    static updateGroomingAndInventory(groomingData, inventoryData, groomingDetails = "") {
        return ValidationManager.updateGroomingAndInventory(groomingData, inventoryData, groomingDetails);
    }

    /**
     * Displays detailed debugging information about current check-in data
     *
     * Logs comprehensive information about the check-in state to the console,
     * including data structure, pet count, cookie size, and system status.
     * Useful for development and troubleshooting.
     *
     * @static
     * @returns {Object} The complete check-in data object for further inspection
     *
     * @example
     * // View debug information in console
     * FormDataManager.debugCheckinData();
     *
     * @sideEffects
     * - Logs detailed information to browser console
     * - Groups console output for better readability
     *
     * @see UtilitiesManager.debugCheckinData
     */
    static debugCheckinData() {
        return UtilitiesManager.debugCheckinData();
    }

    /**
     * Legacy method for retrieving form data (deprecated)
     *
     * @deprecated Use {@link FormDataManager#getCheckinData} instead
     * @static
     * @param {number} step - Step number (unused in current implementation)
     * @returns {Object|null} Complete check-in data object
     *
     * @see FormDataManager.getCheckinData
     */
    static getFormDataFromStep(step) {
        console.warn("getFormDataFromStep is deprecated, use getCheckinData instead");
        return CoreDataManager.getCheckinData();
    }

    /**
     * Legacy method for saving form data (deprecated)
     *
     * @deprecated Use {@link FormDataManager#handleFormStep} instead
     * @static
     * @param {Object} data - Form data to save
     * @param {number|null} step - Step number for context
     * @returns {boolean} True if data was saved successfully
     *
     * @see FormDataManager.handleFormStep
     */
    static saveFormDataToStep(data, step = null) {
        console.warn("saveFormDataToStep is deprecated, use handleFormStep instead");
        return ValidationManager.saveFormDataToStep(data, step);
    }

    /**
     * Legacy method for retrieving pets (deprecated)
     *
     * @deprecated Use {@link FormDataManager#getAllPetsFromCheckin} instead
     * @static
     * @returns {Array} Array of pet objects with flattened properties
     *
     * @see FormDataManager.getAllPetsFromCheckin
     */
    static getPetsFromCookies() {
        console.warn("getPetsFromCookies is deprecated, use getAllPetsFromCheckin instead");
        return PetManager.getAllPetsFromCheckin();
    }

    /**
     * Legacy method for updating pets (deprecated)
     *
     * @deprecated Use {@link FormDataManager#updatePetInCheckin} instead
     * @static
     * @param {number} petIndex - Index of pet to update
     * @param {Object} data - Updated pet data
     * @returns {boolean} True if pet was updated successfully
     *
     * @see FormDataManager.updatePetInCheckin
     */
    static updatePetInCookies(petIndex, data) {
        console.warn("updatePetInCookies is deprecated, use updatePetInCheckin instead");
        return PetManager.updatePetInCheckin(petIndex, data);
    }

    /**
     * Generates a unique identifier for a new check-in session
     *
     * Creates a unique string combining timestamp and random characters
     * to ensure check-in sessions can be uniquely identified.
     *
     * @static
     * @returns {string} Unique check-in identifier
     *
     * @example
     * const checkinId = FormDataManager.generateCheckinId();
     * // Returns something like: "checkin_1642598400000_abc123def"
     *
     * @see CoreDataManager.generateCheckinId
     */
    static generateCheckinId() {
        return CoreDataManager.generateCheckinId();
    }

    /**
     * Generates a unique identifier for a new pet
     *
     * Creates a unique string for pet identification within the check-in system.
     *
     * @static
     * @returns {string} Unique pet identifier
     *
     * @example
     * const petId = FormDataManager.generatePetId();
     * // Returns something like: "pet_1642598400000_xyz789"
     *
     * @see PetManager.generatePetId
     */
    static generatePetId() {
        return PetManager.generatePetId();
    }

    /**
     * Performs deep merge of two objects
     *
     * Recursively merges source object properties into target object,
     * preserving nested structures and avoiding reference issues.
     *
     * @static
     * @param {Object} target - Target object to merge into
     * @param {Object} source - Source object to merge from
     * @returns {Object} New object with merged properties
     *
     * @example
     * const result = FormDataManager.deepMerge(
     *     { user: { name: "John" } },
     *     { user: { age: 30 }, active: true }
     * );
     * // Result: { user: { name: "John", age: 30 }, active: true }
     *
     * @see CoreDataManager.deepMerge
     */
    static deepMerge(target, source) {
        return CoreDataManager.deepMerge(target, source);
    }

    /**
     * Gets the total size of all browser cookies in bytes
     *
     * Useful for monitoring cookie storage usage and avoiding size limits.
     *
     * @static
     * @returns {number} Total size of all cookies in bytes
     *
     * @example
     * const size = FormDataManager.getCookieSize();
     * console.log(`Cookies use ${size} bytes`);
     *
     * @see UtilitiesManager.getCookieSize
     */
    static getCookieSize() {
        return UtilitiesManager.getCookieSize();
    }

    /**
     * Checks if a cookie can be set without exceeding size limits
     *
     * Validates whether the provided cookie name and value combination
     * would fit within browser cookie size constraints (typically 4KB).
     *
     * @static
     * @param {string} name - Cookie name
     * @param {*} value - Cookie value (will be stringified if object)
     * @returns {boolean} True if cookie can be set, false if too large
     *
     * @example
     * if (FormDataManager.canSetCookie("test", largeData)) {
     *     // Safe to set cookie
     * } else {
     *     console.warn("Cookie too large");
     * }
     *
     * @see UtilitiesManager.canSetCookie
     */
    static canSetCookie(name, value) {
        return UtilitiesManager.canSetCookie(name, value);
    }

    /**
     * Retrieves all cookies matching a pattern
     *
     * Finds and returns all cookies whose names contain the specified pattern.
     * Useful for finding related cookies or debugging cookie storage.
     *
     * @static
     * @param {string} pattern - Pattern to match in cookie names
     * @returns {Object} Object with cookie names as keys and values as values
     *
     * @example
     * // Get all form-related cookies
     * const formCookies = FormDataManager.getCookiesByPattern("form");
     *
     * @see UtilitiesManager.getCookiesByPattern
     */
    static getCookiesByPattern(pattern) {
        return UtilitiesManager.getCookiesByPattern(pattern);
    }

    /**
     * Clears all cookies matching a pattern
     *
     * Deletes all cookies whose names contain the specified pattern.
     * Returns the number of cookies that were deleted.
     *
     * @static
     * @param {string} pattern - Pattern to match in cookie names
     * @returns {number} Number of cookies that were deleted
     *
     * @example
     * // Clear all form step cookies
     * const deleted = FormDataManager.clearCookiesByPattern("formStep");
     * console.log(`Cleared ${deleted} form cookies`);
     *
     * @sideEffects
     * - Deletes matching cookies from browser storage
     *
     * @see UtilitiesManager.clearCookiesByPattern
     */
    static clearCookiesByPattern(pattern) {
        return UtilitiesManager.clearCookiesByPattern(pattern);
    }

    /**
     * Checks if a specific cookie exists
     *
     * Determines whether a cookie with the given name is currently set
     * in the browser's cookie storage.
     *
     * @static
     * @param {string} name - Name of the cookie to check
     * @returns {boolean} True if cookie exists, false otherwise
     *
     * @example
     * if (FormDataManager.hasCookie("pl_checkin_data")) {
     *     console.log("Check-in data exists");
     * }
     *
     * @see UtilitiesManager.hasCookie
     */
    static hasCookie(name) {
        return UtilitiesManager.hasCookie(name);
    }
}

/**
 * Module initialization and global exports
 *
 * Automatically initializes the FormDataManager when the DOM is ready,
 * ensuring the data management system is set up before any user interactions.
 * Also exposes debugging utilities globally for development purposes.
 */

// Initialize automatically when module loads
document.addEventListener("DOMContentLoaded", async () => {
    await FormDataManager.initialize();
});

/**
 * Global debug function for development and troubleshooting
 *
 * Exposes the debugCheckinData method globally on the window object,
 * allowing developers to inspect check-in data from browser console.
 *
 * @global
 * @function debugCheckin
 * @returns {Object} Complete check-in data for inspection
 *
 * @example
 * // In browser console:
 * debugCheckin();
 * // Displays detailed check-in information
 */
window.debugCheckin = () => FormDataManager.debugCheckinData();

/**
 * Module export
 *
 * Exports the FormDataManager class as the default and only export of this module.
 * This maintains backward compatibility with existing import statements.
 *
 * @module FormDataManager
 * @exports FormDataManager
 */
export { FormDataManager };
