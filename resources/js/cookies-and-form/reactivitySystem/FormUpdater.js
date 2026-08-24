import { CoreDataManager } from "../managers/CoreDataManager.js";

/**
 * Form Updater - Handles updating form fields with cookie data
 *
 * This manager is responsible for populating form fields with data from cookies,
 * ensuring that forms reflect the current state of the check-in data. It handles
 * different input types and preserves user input when appropriate.
 *
 * Key Features:
 * - Owner information form population
 * - Emergency contact field updates
 * - Pet form field updates
 * - Selective field updating to avoid overwriting user input
 *
 * @class
 * @static
 */
class FormUpdater {
    /**
     * Update owner information form fields
     *
     * Populates the owner info form (phone, name, email, address, city, zip)
     * with data from cookies. Only updates fields that are empty to avoid
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
     * FormUpdater.updateOwnerInfoForm({
     *     phone: "555-0123",
     *     name: "John Doe",
     *     email: "john@example.com"
     * });
     *
     * @sideEffects
     * - Updates DOM input fields with provided data
     * - Only modifies empty fields to preserve user input
     */
    static updateOwnerInfoForm(userInfo) {
        if (!userInfo) return;

        const fields = ['phone', 'name', 'email', 'address', 'city', 'zip'];
        fields.forEach(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            if (element && userInfo[field]) {
                element.value = userInfo[field];
            }
        });

        // Update emergency contact fields
        this.updateEmergencyContactFields(userInfo);
    }

    /**
     * Update emergency contact form fields
     *
     * Populates emergency contact fields with data from the provided user info or cookie.
     * Retrieves emergency contact information and updates the corresponding form fields.
     *
     * @static
     * @param {Object} [userInfo] - Optional user information object containing emergency contact
     * @returns {void}
     *
     * @sideEffects
     * - Updates emergency contact name and phone fields
     * - Retrieves data from provided userInfo or current check-in cookie
     */
    static updateEmergencyContactFields(userInfo) {
        const emergencyFields = ['emergencyContactName', 'emergencyContactPhone'];
        emergencyFields.forEach(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            if (element) {
                // Get emergency contact data from provided userInfo or cookie
                let emergencyData = null;
                
                if (userInfo?.emergencyContact) {
                    emergencyData = userInfo.emergencyContact;
                } else {
                    const checkinData = CoreDataManager.getCheckinData();
                    emergencyData = checkinData?.user?.emergencyContact;
                }
                
                if (emergencyData) {
                    if (field === 'emergencyContactName' && emergencyData.name) {
                        element.value = emergencyData.name;
                    } else if (field === 'emergencyContactPhone' && emergencyData.phone) {
                        element.value = emergencyData.phone;
                    }
                }
            }
        });
    }

    /**
     * Update pet form fields for a specific pet
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
     * FormUpdater.updatePetForm(pet);
     *
     * @sideEffects
     * - Updates DOM form fields with pet data
     * - Handles different input types (text, radio, select)
     */
    static updatePetForm(petData) {
        if (!petData?.info) return;

        const petFields = ['petName', 'petColor', 'petType', 'petBreed', 'petAge', 'petWeight', 'petGender', 'petSpayed'];
        petFields.forEach(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            if (element && petData.info[field]) {
                if (element.type === 'radio') {
                    const radioButton = document.querySelector(`[name="${field}"][value="${petData.info[field]}"]`);
                    if (radioButton) radioButton.checked = true;
                } else {
                    element.value = petData.info[field];
                }
            }
        });
    }

    /**
     * Update health information form fields
     *
     * Populates health-related form elements including unusual behavior toggles,
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
     * FormUpdater.updateHealthInfoForm(
     *     data.pets,
     *     data.grooming,
     *     data.groomingDetails
     * );
     *
     * @sideEffects
     * - Updates health form fields and conditional sections
     * - Shows/hides grooming details based on selections
     * - Manages form field visibility and state
     */
    static updateHealthInfoForm(pets, grooming, groomingDetails) {
        if (!pets || !Array.isArray(pets) || pets.length === 0) return;

        // Bulk edit: use the first pet as the template for the shared health form.
        const currentPet = pets[0];

        // Update unusual health behavior radio buttons.
        // Reset both radios first so switching to a pet with no entry doesn't
        // leave the previous pet's selection visible.
        const healthBehaviorRadios = document.querySelectorAll('input[name="unusualHealthBehavior"]');
        if (currentPet.health?.unusualHealthBehavior !== undefined) {
            healthBehaviorRadios.forEach(radio => {
                if (currentPet.health.unusualHealthBehavior && radio.value === 'yes') {
                    radio.checked = true;
                } else if (!currentPet.health.unusualHealthBehavior && radio.value === 'no') {
                    radio.checked = true;
                } else {
                    radio.checked = false;
                }
            });
        } else {
            healthBehaviorRadios.forEach(radio => { radio.checked = false; });
        }

        // Update health behavior details field (reset when the pet has none)
        const detailsField = document.getElementById('healthBehaviorDetails') || document.querySelector('[name="healthBehaviorDetails"]');
        if (detailsField) {
            detailsField.value = currentPet.health?.healthBehaviors || '';
        }

        // Update warnings field (reset when the pet has none)
        const warningsField = document.getElementById('warnings') || document.querySelector('[name="warnings"]');
        if (warningsField) {
            warningsField.value = currentPet.health?.warnings || '';
        }

        // Update grooming checkboxes
        if (grooming && typeof grooming === 'object') {
            Object.entries(grooming).forEach(([key, value]) => {
                if (key !== 'appointmentDay' && key !== 'no') {
                    const checkbox = document.querySelector(`input[name="grooming[]"][value="${key}"]`);
                    if (checkbox) {
                        checkbox.checked = value === true;
                    }
                }
            });
        }

        // Update grooming details field
        if (groomingDetails) {
            const groomingDetailsField = document.getElementById('groomingDetails') || document.querySelector('[name="groomingDetails"]');
            if (groomingDetailsField) {
                groomingDetailsField.value = groomingDetails;
            }
        }

        console.log("Update health info form");
    }

    /**
     * Get the currently selected pet index
     * @static
     * @private
     * @returns {number|null} Index of selected pet or null
     */
    static getCurrentSelectedPetIndex() {
        const selectedPill = document.querySelector('.pill.selected');
        if (selectedPill && selectedPill.dataset.index !== undefined) {
            return parseInt(selectedPill.dataset.index, 10);
        }
        return null;
    }

    /**
     * Update grooming and inventory form fields
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
     * @deprecated Use InventoryManager.updateInventoryUI for inventory-specific updates
     * @see InventoryManager.updateInventoryUI
     */
    static updateGroomingAndInventoryForm(grooming, inventory, details) {
        // Update grooming checkboxes
        if (grooming && typeof grooming === 'object') {
            Object.entries(grooming).forEach(([key, value]) => {
                if (key !== 'appointmentDay' && key !== 'no') {
                    const checkbox = document.querySelector(`input[name="grooming[]"][value="${key}"]`);
                    if (checkbox) {
                        checkbox.checked = value === true;
                    }
                }
            });
        }

        // Update grooming details field
        if (details) {
            const groomingDetailsField = document.getElementById('groomingDetails') || document.querySelector('[name="groomingDetails"]');
            if (groomingDetailsField) {
                groomingDetailsField.value = details;
            }
        }

        console.log("Update grooming and inventory form");
    }

    /**
     * Populate feeding/medication popup fields from cookie data
     *
     * Pre-populates the feeding/medication popup form with data from the check-in cookie
     * when the popup is opened. This allows users to see and modify existing feeding/medication
     * entries during editing mode. Only populates if the selected pet has existing data.
     *
     * @static
     * @param {Array} pets - Array of pet objects with feeding/medication data
     * @returns {boolean} True if popup was populated, false otherwise
     *
     * @example
     * const pets = FormDataManager.getAllPetsFromCheckin();
     * const wasPopulated = FormUpdater.populateFeedingMedicationPopup(pets);
     *
     * @sideEffects
     * - Updates feeding/medication popup form fields with cookie data
     * - Pre-selects day_time checkboxes based on most recent item
     * - Pre-selects type radio based on most recent item
     * - Populates feeding_med_details field
     */
    static populateFeedingMedicationPopup(pets, petIndex = null) {
        if (!Array.isArray(pets)) {
            console.warn("[populateFeedingMedicationPopup] pets is not an array", pets);
            return false;
        }

        const popup = document.querySelector("#feedingMedicationPopup");
        if (!popup) {
            console.warn("[populateFeedingMedicationPopup] popup element not found");
            return false;
        }

        // Use the explicitly provided pet index, or fall back to the selected pet pill.
        const currentPetIndex = (petIndex !== null && petIndex !== undefined)
            ? petIndex
            : this.getCurrentSelectedPetIndex();
        console.log("[populateFeedingMedicationPopup] Current pet index:", currentPetIndex);
        if (currentPetIndex === null || !pets[currentPetIndex]) {
            console.warn("[populateFeedingMedicationPopup] Current pet not found at index", currentPetIndex);
            return false;
        }

        const currentPet = pets[currentPetIndex];
        console.log("[populateFeedingMedicationPopup] Current pet data:", currentPet);

        // Find the most recent feeding or medication item to pre-populate
        let mostRecentItem = null;
        let mostRecentType = null;
        let mostRecentIndex = -1;

        // Check for most recent feeding item
        if (currentPet.feeding && Array.isArray(currentPet.feeding) && currentPet.feeding.length > 0) {
            console.log(`[populateFeedingMedicationPopup] Found ${currentPet.feeding.length} feeding items`);
            mostRecentItem = currentPet.feeding[currentPet.feeding.length - 1];
            mostRecentType = 'food';
            mostRecentIndex = 0; // feeding priority
        }

        // Check for most recent medication item (compare timestamps if available, or just use last)
        if (currentPet.medication && Array.isArray(currentPet.medication) && currentPet.medication.length > 0) {
            console.log(`[populateFeedingMedicationPopup] Found ${currentPet.medication.length} medication items`);
            const lastMed = currentPet.medication[currentPet.medication.length - 1];
            // Prefer medication if we have no feeding, or if both exist (use the one from most recent add)
            if (!mostRecentItem) {
                mostRecentItem = lastMed;
                mostRecentType = 'medication';
                mostRecentIndex = 1; // medication priority
            }
        }

        // If no data exists, return false (don't populate)
        if (!mostRecentItem) {
            console.log("[populateFeedingMedicationPopup] No feeding or medication data found for current pet");
            return false;
        }

        console.log("[populateFeedingMedicationPopup] Most recent item:", mostRecentItem, "Type:", mostRecentType);

        // Pre-select day_time checkbox
        if (mostRecentItem.day_time) {
            const dayCheckbox = popup.querySelector(`input[name="day_time[]"][value="${mostRecentItem.day_time}"]`);
            if (dayCheckbox) {
                dayCheckbox.checked = true;
                console.log(`[populateFeedingMedicationPopup] Selected day_time: ${mostRecentItem.day_time}`);
                // Trigger change event to update visual feedback
                dayCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                console.warn(`[populateFeedingMedicationPopup] day_time checkbox not found for value: ${mostRecentItem.day_time}`);
            }
        }

        // Pre-select type radio
        if (mostRecentType) {
            const typeRadio = popup.querySelector(`input[name="type"][value="${mostRecentType}"]`);
            if (typeRadio) {
                typeRadio.checked = true;
                console.log(`[populateFeedingMedicationPopup] Selected type: ${mostRecentType}`);
                // Trigger change event to update visual feedback
                typeRadio.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                console.warn(`[populateFeedingMedicationPopup] type radio not found for value: ${mostRecentType}`);
            }
        }

        // Populate feeding_med_details field
        if (mostRecentItem.feeding_med_details) {
            const detailsField = popup.querySelector('[name="feeding_med_details"]');
            if (detailsField) {
                detailsField.value = mostRecentItem.feeding_med_details;
                console.log(`[populateFeedingMedicationPopup] Set details: ${mostRecentItem.feeding_med_details}`);
            } else {
                console.warn("[populateFeedingMedicationPopup] feeding_med_details field not found");
            }
        }

        console.log("✅ Populated feeding/medication popup from cookie - Type:", mostRecentType, "Details:", mostRecentItem.feeding_med_details);
        return true;
    }
}

export { FormUpdater };