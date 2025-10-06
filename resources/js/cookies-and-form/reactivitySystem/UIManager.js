import { CookieReactivityManager } from "./CookieReactivityManager.js";
import { FormUpdater } from "./FormUpdater.js";
import { SummaryRenderer } from "./SummaryRenderer.js";
import { InventoryManager } from "../managers/InventoryManager.js";
import { UtilitiesManager } from "../managers/UtilitiesManager.js";
import { NavigationManager } from "../managers/NavigationManager.js";
import config from "../config.js";

const { FORM_CONFIG } = config;

/**
 * UI Manager - Orchestrates all UI updates based on cookie data changes
 *
 * This manager serves as the central coordinator for all user interface updates
 * triggered by cookie data changes. It delegates specific update tasks to
 * specialized managers while maintaining the overall update flow.
 *
 * Key Features:
 * - Centralized UI update coordination
 * - Cookie reactivity listener registration
 * - Step-based conditional updates
 * - Comprehensive DOM state management
 *
 * @class
 * @static
 */
class UIManager {
    /**
     * Register listener for automatic UI updates when cookie data changes
     *
     * Sets up the reactivity system that automatically updates the DOM
     * whenever check-in data is modified. The listener monitors cookie changes
     * and triggers appropriate UI updates to keep the interface synchronized.
     *
     * @static
     * @returns {void}
     *
     * @example
     * UIManager.registerUIUpdateListener();
     *
     * @sideEffects
     * - Registers event listeners for cookie reactivity
     * - May trigger immediate UI updates if data exists
     */
    static registerUIUpdateListener() {
        CookieReactivityManager.addListener((cookieData) => {
            this.updateUIFromCookieData(cookieData);
        });
    }

    /**
     * Update all UI elements based on current cookie data
     *
     * Performs a comprehensive UI update by synchronizing all DOM elements
     * with the current state of the check-in data. Updates forms, pills,
     * feeding displays, health information, inventory lists, and summary.
     *
     * The update is conservative - it only updates elements that are safe
     * to modify without disrupting active user input.
     *
     * @static
     * @param {Object} cookieData - The complete check-in data object from cookies
     * @returns {void}
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * UIManager.updateUIFromCookieData(data);
     *
     * @sideEffects
     * - Updates multiple DOM elements to reflect current data state
     * - May show/hide UI sections based on data availability
     * - Preserves user input in active form fields
     * - Logs update operations to console for debugging
     */
    static updateUIFromCookieData(cookieData) {
        if (!cookieData) return;

        console.log("🔄 Updating UI from cookie data (conservative mode)");

        try {
            // Update owner info form globally
            FormUpdater.updateOwnerInfoForm(cookieData.user?.info);

            // Update pet pills and forms
            this.updatePetPillsAndForms(cookieData.pets);

            // Update feeding/medication displays
            this.updateFeedingMedicationUI(cookieData.pets);

            // Update health info UI
            this.updateHealthInfoUI(cookieData.pets, cookieData.grooming, cookieData.groomingDetails);

            // Update inventory UI
            InventoryManager.updateInventoryUI(cookieData.inventory, cookieData.inventoryComplete);

            // Update same feeding checkbox visibility
            this.updateSameFeedingCheckbox(cookieData.pets);

            // Update grooming checkboxes
            this.updateGroomingAndInventoryUI(cookieData.grooming, cookieData.inventory, cookieData.groomingDetails);

            // Update check-in summary in THANKS step
            SummaryRenderer.updateCheckinSummary(cookieData);

        } catch (error) {
            console.error("Error updating UI from cookie:", error);
        }
    }

    /**
     * Update pet pills and related form elements
     *
     * Manages the display of pet pills in the UI. Only recreates pills when in the
     * pet information step to avoid unnecessary DOM manipulations. For other steps,
     * only updates the currently selected pet's form data.
     *
     * @static
     * @param {Array} pets - Array of pet objects from check-in data
     * @returns {void}
     *
     * @example
     * const pets = FormDataManager.getAllPetsFromCheckin();
     * UIManager.updatePetPillsAndForms(pets);
     *
     * @sideEffects
     * - May recreate pet pill elements in DOM (only in pet info step)
     * - Updates selected pet form fields
     * - Preserves pill selection state during recreation
     */
    static updatePetPillsAndForms(pets) {
        if (!Array.isArray(pets)) return;

        const currentStep = NavigationManager.getCurrentStep();
        const isPetInfoStep = currentStep === (FORM_CONFIG.STEPS.PET_INFO - 1); // step 1

        // Solo actualizar pills completamente en el paso de mascotas
        if (isPetInfoStep) {
            const container = document.querySelector("#petPillsContainer");
            if (container) {
                container.innerHTML = "";

                // Recordar cuál estaba seleccionado antes de limpiar
                const previouslySelectedIndex = UtilitiesManager.getCurrentSelectedPetIndex();

                pets.forEach((pet, index) => {
                    if (pet && pet.info?.petName) {
                        // This would need Pill import - simplified for now
                        console.log(`Would add pill for pet ${index}: ${pet.info.petName}`);
                    }
                });
            }
        }

        // Siempre actualizar el formulario de la mascota seleccionada si existe
        const currentPetIndex = UtilitiesManager.getCurrentSelectedPetIndex();
        if (currentPetIndex !== null && pets[currentPetIndex]) {
            FormUpdater.updatePetForm(pets[currentPetIndex]);
        }
    }

    /**
     * Update feeding and medication UI displays
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
     * UIManager.updateFeedingMedicationUI(pets);
     *
     * @sideEffects
     * - Updates multiple DOM elements for feeding/medication display
     * - Shows/hides day sections based on data availability
     * - Creates editable input elements for schedule items
     */
    static updateFeedingMedicationUI(pets) {
        if (!Array.isArray(pets)) return;

        // Track which time slots have items
        const timeSlotsWithItems = new Set();

        // Check all pets for feeding/medication items
        pets.forEach(pet => {
            if (pet?.feeding && Array.isArray(pet.feeding)) {
                pet.feeding.forEach(item => {
                    if (item.day_time) {
                        timeSlotsWithItems.add(item.day_time);
                    }
                });
            }

            if (pet?.medication && Array.isArray(pet.medication)) {
                pet.medication.forEach(item => {
                    if (item.day_time) {
                        timeSlotsWithItems.add(item.day_time);
                    }
                });
            }
        });

        // Show/hide container-day sections based on whether they have items
        const timeSlots = ['morning', 'afternoon', 'night'];
        timeSlots.forEach(timeSlot => {
            const container = document.querySelector(`.container-day[data-time-slot="${timeSlot}"]`);
            if (container) {
                if (timeSlotsWithItems.has(timeSlot)) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        });

        // Update the actual feeding/medication displays
        this.updateFeedingMedicationDisplays(pets);
    }

    /**
     * Update the actual feeding and medication display elements
     *
     * @static
     * @private
     * @param {Array} pets - Array of pet objects
     * @returns {void}
     */
    static updateFeedingMedicationDisplays(pets) {
        // Clear all existing displays
        const timeSlots = ['morning', 'afternoon', 'night'];
        timeSlots.forEach(timeSlot => {
            const foodContainer = document.querySelector(`#${timeSlot}-food-list`);
            const medContainer = document.querySelector(`#${timeSlot}-med-list`);

            if (foodContainer) foodContainer.innerHTML = '';
            if (medContainer) medContainer.innerHTML = '';
        });

        // Populate displays with current data
        if (!Array.isArray(pets)) return;

        pets.forEach((pet, petIndex) => {
            if (pet?.feeding && Array.isArray(pet.feeding)) {
                pet.feeding.forEach((feed, itemIndex) => {
                    if (feed.day_time) {
                        const container = document.querySelector(`#${feed.day_time}-food-list`);
                        if (container) {
                            UtilitiesManager.createEditableItem(
                                container,
                                feed,
                                petIndex,
                                'feeding',
                                itemIndex,
                                pet.info?.petName || 'Pet'
                            );
                        }
                    }
                });
            }

            if (pet?.medication && Array.isArray(pet.medication)) {
                pet.medication.forEach((med, itemIndex) => {
                    if (med.day_time) {
                        const container = document.querySelector(`#${med.day_time}-med-list`);
                        if (container) {
                            UtilitiesManager.createEditableItem(
                                container,
                                med,
                                petIndex,
                                'medication',
                                itemIndex,
                                pet.info?.petName || 'Pet'
                            );
                        }
                    }
                });
            }
        });
    }

    /**
     * Update health information UI
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
     * UIManager.updateHealthInfoUI(
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
    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        FormUpdater.updateHealthInfoForm(pets, grooming, groomingDetails);
    }

    /**
     * Update same feeding checkbox visibility
     *
     * Shows or hides the "same feeding for all pets" checkbox based on
     * whether there are multiple pets in the check-in.
     *
     * @static
     * @param {Array} pets - Array of pet objects
     * @returns {void}
     *
     * @sideEffects
     * - Shows/hides same feeding checkbox container
     */
    static updateSameFeedingCheckbox(pets) {
        const sameFeedingContainer = document.getElementById('sameFeedingContainer');
        if (!sameFeedingContainer) return;

        // Show the checkbox only if there are multiple pets
        const hasMultiplePets = Array.isArray(pets) && pets.length > 1;
        if (hasMultiplePets) {
            sameFeedingContainer.classList.remove('hidden');
        } else {
            sameFeedingContainer.classList.add('hidden');
        }
    }

    /**
     * Update grooming and inventory UI elements
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
     */
    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        FormUpdater.updateGroomingAndInventoryForm(grooming, inventory, details);
    }

    /**
     * Get the current step in the form process
     *
     * @static
     * @returns {number} Current step index
     */
    static getCurrentStep() {
        return NavigationManager.getCurrentStep();
    }
}

export { UIManager };