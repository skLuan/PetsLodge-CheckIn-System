/**
 * FormHandler - Manages form data extraction, population, and general form operations
 *
 * This manager handles all form-related operations including extracting input values,
 * populating forms with data, and managing form state across the multi-step process.
 */

import { FormDataManager } from "../FormDataManager.js";
import Utils from "../../Utils.js";

class FormHandler {
    /**
     * Extracts input values from a form element
     * @param {HTMLElement|string} formElementOrSelector - Form element or CSS selector
     * @returns {Object} Object containing form data
     */
    static extractFormInputValues(formElementOrSelector) {
        console.log("Extracting form input values...");
        let formElement = formElementOrSelector;
        if (typeof formElementOrSelector === "string") {
            formElement = document.querySelector(formElementOrSelector);
        }

        if (!formElement) {
            console.warn("Form element not found:", formElementOrSelector);
            return {};
        }

        const inputs = formElement.querySelectorAll("input, select, textarea");
        const data = {};

        inputs.forEach((input) => {
            if (input.name) {
                // Handle array-named inputs (e.g., day_time[])
                if (input.name.endsWith('[]')) {
                    const baseName = input.name.slice(0, -2); // Remove []
                    if (!data[baseName]) {
                        data[baseName] = [];
                    }
                    if (input.type === "checkbox" && input.checked) {
                        data[baseName].push(input.value);
                    }
                } else if (input.type === "checkbox") {
                    data[input.name] = input.checked;
                } else if (input.type === "radio") {
                    if (input.checked) {
                        data[input.name] = input.value;
                    } else if (data[input.name] === undefined) {
                        // If no radio in the group is checked yet, set as empty string
                        data[input.name] = "";
                    }
                } else {
                    data[input.name] = input.value;
                }
            }
        });

        console.log("Extracted form input values:", data);
        // console.log("DEBUG: Checking for petName field specifically:", data.petName || "NOT FOUND");
        return data;
    }

    /**
     * Populates ALL step forms with data from cookies.
     * Since all steps exist in the DOM simultaneously (just hidden),
     * we populate them all on load so editing mode fills every step.
     */
    static populateFormWithCookies() {
        const forms = Array.from(document.querySelectorAll(".step")).map((step) => step.querySelector("form"));
        const checkinData = FormDataManager.getCheckinData();

        console.log("Populating ALL forms with cookies:", checkinData);

        if (!checkinData) return;

        // Step 0: Owner Info
        const ownerForm = forms[0];
        if (ownerForm) {
            const ownerData = checkinData.user?.info || {};
            this._populateForm(ownerForm, ownerData);

            // Emergency contact lives inside user.emergencyContact
            const emergency = checkinData.user?.emergencyContact || {};
            if (emergency.name) {
                const nameInput = ownerForm.querySelector('[name="emergencyContactName"]');
                if (nameInput) nameInput.value = emergency.name;
            }
            if (emergency.phone) {
                const phoneInput = ownerForm.querySelector('[name="emergencyContactPhone"]');
                if (phoneInput) phoneInput.value = emergency.phone;
            }
        }

        // Step 1: Pet Info (first pet or selected pill)
        const petForm = forms[1];
        if (petForm) {
            const selectedPetIndex = document.querySelector(".pill.selected")
                ? parseInt(document.querySelector(".pill.selected").dataset.index, 10)
                : 0;
            const petData = checkinData.pets?.[selectedPetIndex]?.info || {};
            this._populateForm(petForm, petData);
        }

        // Step 2: Feeding/Medication — list rendering handled by UIManager,
        // but we trigger it here so items show on load
        this._populateFeedingMedicationUI(checkinData.pets);

        // Step 3: Health Info
        const healthForm = forms[3];
        if (healthForm) {
            const selectedPetIndex = document.querySelector(".pill.selected")
                ? parseInt(document.querySelector(".pill.selected").dataset.index, 10)
                : 0;
            const pet = checkinData.pets?.[selectedPetIndex];
            if (pet?.health) {
                // Unusual health behavior radio
                const behaviorValue = pet.health.unusualHealthBehavior ? 'yes' : 'no';
                const radio = healthForm.querySelector(`[name="unusualHealthBehavior"][value="${behaviorValue}"]`);
                if (radio) {
                    radio.checked = true;
                    // Show/hide conditional details
                    const detailsContainer = healthForm.querySelector('.conditional-health-details');
                    if (detailsContainer) {
                        detailsContainer.style.display = behaviorValue === 'yes' ? '' : 'none';
                    }
                }

                // Health behavior details
                const detailsField = healthForm.querySelector('[name="healthBehaviorDetails"]');
                if (detailsField && pet.health.healthBehaviors) {
                    detailsField.value = pet.health.healthBehaviors;
                }

                // Warnings
                const warningsField = healthForm.querySelector('[name="warnings"]');
                if (warningsField && pet.health.warnings) {
                    warningsField.value = pet.health.warnings;
                }
            }
        }

        // Step 4: Inventory — list rendering + checkbox
        this._populateInventoryUI(checkinData.inventory, checkinData.inventoryComplete);
    }

    /**
     * Populate a form element with a flat key-value data object
     * @param {HTMLFormElement} form
     * @param {Object} data
     * @private
     */
    static _populateForm(form, data) {
        if (!form || !data) return;

        Object.keys(data).forEach((key) => {
            const inputs = form.querySelectorAll(`[name="${key}"]`);
            if (inputs.length === 0) return;

            const input = inputs[0];

            if (input.type === "radio") {
                const match = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                if (match) match.checked = true;
            } else if (input.type === "checkbox") {
                input.checked = !!data[key];
            } else if (input.tagName === "SELECT") {
                // Always set select value — the default empty option counts as "unfilled"
                if (data[key] != null && data[key] !== '') {
                    input.value = data[key];
                }
            } else {
                // For text/number/etc inputs, set value if field is empty
                // Use != null to preserve 0 and other falsy-but-valid values
                if (!input.value || input.value.trim() === '') {
                    input.value = data[key] != null ? data[key] : '';
                }
            }
        });
    }

    /**
     * Render feeding/medication list items from cookie data on page load
     * @param {Array} pets
     * @private
     */
    static _populateFeedingMedicationUI(pets) {
        if (!Array.isArray(pets)) return;

        const timeSlots = ['morning', 'afternoon', 'night'];

        // Track which slots have items to show their containers
        const slotsWithItems = new Set();

        pets.forEach((pet) => {
            (pet?.feeding || []).forEach(item => {
                if (item.day_time) slotsWithItems.add(item.day_time);
            });
            (pet?.medication || []).forEach(item => {
                if (item.day_time) slotsWithItems.add(item.day_time);
            });
        });

        // Show/hide day containers
        timeSlots.forEach(slot => {
            const container = document.querySelector(`.container-day[data-time-slot="${slot}"]`);
            if (container) {
                container.classList.toggle('hidden', !slotsWithItems.has(slot));
            }
        });

        // Clear and repopulate list containers
        timeSlots.forEach(slot => {
            const foodList = document.querySelector(`#${slot}-food-list`);
            const medList = document.querySelector(`#${slot}-med-list`);
            if (foodList) foodList.innerHTML = '';
            if (medList) medList.innerHTML = '';
        });

        pets.forEach((pet, petIndex) => {
            const petName = pet?.info?.petName || 'Pet';

            (pet?.feeding || []).forEach((feed, itemIndex) => {
                if (!feed.day_time) return;
                const container = document.querySelector(`#${feed.day_time}-food-list`);
                if (container) {
                    const el = document.createElement('div');
                    el.className = 'feeding-item text-sm py-1 px-2 bg-blue-50 rounded mb-1';
                    el.textContent = `${petName}: ${feed.feeding_med_details || ''}`;
                    container.appendChild(el);
                }
            });

            (pet?.medication || []).forEach((med, itemIndex) => {
                if (!med.day_time) return;
                const container = document.querySelector(`#${med.day_time}-med-list`);
                if (container) {
                    const el = document.createElement('div');
                    el.className = 'medication-item text-sm py-1 px-2 bg-red-50 rounded mb-1';
                    el.textContent = `${petName}: ${med.feeding_med_details || ''}`;
                    container.appendChild(el);
                }
            });
        });
    }

    /**
     * Populate inventory list and checkbox from cookie data on page load
     * @param {Array} inventory
     * @param {boolean} inventoryComplete
     * @private
     */
    static _populateInventoryUI(inventory, inventoryComplete) {
        // Populate the inventory complete checkbox
        const completeCheckbox = document.getElementById('inventoryComplete');
        if (completeCheckbox) {
            completeCheckbox.checked = !!inventoryComplete;
        }

        // Inventory items are rendered reactively by InventoryManager/UIManager,
        // but we trigger an initial render if items exist
        if (Array.isArray(inventory) && inventory.length > 0) {
            const inventoryList = document.querySelector('[data-inventory-list]');
            if (inventoryList) {
                inventoryList.innerHTML = '';
                inventory.forEach(item => {
                    const itemEl = document.createElement('div');
                    const description = typeof item === 'string' ? item : item?.description || '';
                    itemEl.textContent = `• ${description}`;
                    inventoryList.appendChild(itemEl);
                });
            }
        }
    }

    /**
     * Gets all form elements from the page
     * @returns {HTMLFormElement[]} Array of form elements
     */
    static getForms() {
        return Array.from(document.querySelectorAll(".step")).map((form) => form.querySelector("form"));
    }
}

export { FormHandler };