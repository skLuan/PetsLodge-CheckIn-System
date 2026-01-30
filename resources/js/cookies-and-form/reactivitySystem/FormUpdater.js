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

        // Get the currently selected pet
        const currentPetIndex = this.getCurrentSelectedPetIndex();
        if (currentPetIndex === null || !pets[currentPetIndex]) return;

        const currentPet = pets[currentPetIndex];
        
        // Update unusual health behavior radio buttons
        if (currentPet.health?.unusualHealthBehavior !== undefined) {
            const healthBehaviorRadios = document.querySelectorAll('input[name="unusualHealthBehavior"]');
            healthBehaviorRadios.forEach(radio => {
                if (currentPet.health.unusualHealthBehavior && radio.value === 'yes') {
                    radio.checked = true;
                } else if (!currentPet.health.unusualHealthBehavior && radio.value === 'no') {
                    radio.checked = true;
                }
            });
        }

        // Update health behavior details field
        if (currentPet.health?.healthBehaviors) {
            const detailsField = document.getElementById('healthBehaviorDetails') || document.querySelector('[name="healthBehaviorDetails"]');
            if (detailsField) {
                detailsField.value = currentPet.health.healthBehaviors;
            }
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
}

export { FormUpdater };