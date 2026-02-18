/**
 * HealthFormManager - Manages health information form interactions
 *
 * This manager handles health-related form operations including conditional
 * field visibility, radio button handling, and checkbox interactions.
 */

class HealthFormManager {
    /**
     * Initializes health information form event handlers
     */
    static initializeHealthForm() {
        const healthInfoForm = document.getElementById('healthInfoForm');
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
}

export { HealthFormManager };