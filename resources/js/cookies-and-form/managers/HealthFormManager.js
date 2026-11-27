/**
 * HealthFormManager - Manages health information form interactions
 *
 * This manager handles health-related form operations including conditional
 * field visibility, radio button handling, and checkbox interactions.
 */

import { FormDataManager } from "../FormDataManager.js";
import { FormHandler } from "./FormHandler.js";

class HealthFormManager {
    /**
     * Initializes health information form event handlers
     */
    static initializeHealthForm() {
        const healthInfoForm = document.getElementById('petInfoForm');
        if (!healthInfoForm) return;

        // Handle unusual health behavior radio buttons
        const healthBehaviorRadios = healthInfoForm.querySelectorAll('input[name="unusualHealthBehavior"]');
        healthBehaviorRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const detailsContainer = healthInfoForm.querySelector('.conditional-health-details');
                if (this.value === 'yes') {
                    detailsContainer.style.display = '';
                } else {
                    detailsContainer.style.display = 'none';
                    // Clear the details field
                    const detailsField = healthInfoForm.querySelector('#healthBehaviorDetails');
                    if (detailsField) detailsField.value = '';
                }
            });
        });

        // Handle grooming checkboxes
        const groomingCheckboxes = healthInfoForm.querySelectorAll('input[name="grooming[]"]');
        groomingCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const notesContainer = healthInfoForm.querySelector('.conditional-grooming-notes');
                const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                    cb.checked && cb.value !== 'no'
                );

                if (hasGroomingSelected) {
                    notesContainer.style.display = '';
                } else {
                    notesContainer.style.display = 'none';
                    // Clear the notes field
                    const notesField = healthInfoForm.querySelector('#groomingDetails');
                    if (notesField) notesField.value = '';
                }
            });
        });
    }

    /**
     * Resets the #healthInfoForm and repopulates it from the given pet's health.
     *
     * Always clears the form fields first (radios, "Which?" details, warnings,
     * conditional visibility), then fills in any values present on the pet.
     * Passing a null/invalid petIndex (or a pet with no health) simply leaves
     * the form cleared.
     *
     * @param {number|null} petIndex - Index of the pet to load from.
     * @returns {void}
     */
    static loadPetHealth(petIndex) {
        const healthInfoForm = document.getElementById('petInfoForm');
        if (!healthInfoForm) return;

        // --- Reset fields first ---
        const radios = healthInfoForm.querySelectorAll('input[name="unusualHealthBehavior"]');
        radios.forEach(radio => { radio.checked = false; });

        const detailsField = healthInfoForm.querySelector('#healthBehaviorDetails')
            || healthInfoForm.querySelector('[name="healthBehaviorDetails"]');
        if (detailsField) detailsField.value = '';

        const warningsField = healthInfoForm.querySelector('#warnings')
            || healthInfoForm.querySelector('[name="warnings"]');
        if (warningsField) warningsField.value = '';

        const detailsContainer = healthInfoForm.querySelector('.conditional-health-details');
        if (detailsContainer) detailsContainer.style.display = 'none';

        // --- Populate from the pet's health, if present ---
        if (petIndex === null || petIndex === undefined || isNaN(petIndex)) return;

        const checkinData = FormDataManager.getCheckinData();
        const pet = checkinData?.pets?.[petIndex];
        if (!pet?.health) return;

        const health = pet.health;

        const behaviorValue = health.unusualHealthBehavior ? 'yes' : 'no';
        const behaviorRadio = healthInfoForm.querySelector(
            `[name="unusualHealthBehavior"][value="${behaviorValue}"]`
        );
        if (behaviorRadio) {
            behaviorRadio.checked = true;
            if (detailsContainer) {
                detailsContainer.style.display = behaviorValue === 'yes' ? '' : 'none';
            }
        }

        if (detailsField && health.healthBehaviors) {
            detailsField.value = health.healthBehaviors;
        }

        if (warningsField && health.warnings) {
            warningsField.value = health.warnings;
        }
    }
}

export { HealthFormManager };