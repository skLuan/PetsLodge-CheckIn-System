/**
 * NavigationManager - Manages navigation and tab bar updates
 *
 * This manager handles navigation logic, tab bar state updates, and
 * step-based UI changes throughout the form process.
 */

import Utils from "../../Utils.js";
import config from "../config.js";
import { FormDataManager } from "../FormDataManager.js";

const { FORM_CONFIG } = config;

class NavigationManager {
    /**
     * Updates the tab bar based on current step and form state
     */
    static updateTabbarForStep() {
        const currentStep = Utils.actualStep();
        const nextButton = document.querySelector("#nextStep");
        const thankYouTitle = document.getElementById('thankYouTitle');
        const petPillsContainer = document.getElementById('petPillsContainer');

        if (!nextButton) return;

        // Toggle thank you title and pet pills container
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY) { // Step 6 (0-based: 5)
            // Hide next button in final step
            nextButton.style.display = 'none';

            // Show thank you title, hide pet pills
            if (thankYouTitle) thankYouTitle.classList.remove('hidden');
            if (petPillsContainer) petPillsContainer.classList.add('hidden');

            // Trigger grooming popup after inventory step (only if not already acknowledged)
            const checkinData = FormDataManager.getCheckinData();
            if (!checkinData?.groomingAcknowledged) {
                setTimeout(() => {
                    const groomingPopup = document.getElementById('groomingPopup');
                    if (groomingPopup) {
                        groomingPopup.classList.remove('hidden');
                    }
                }, 500); // Small delay to allow UI to settle
            }
        } else {
            // Show next button for other steps
            nextButton.style.display = '';

            // Hide thank you title, show pet pills
            if (thankYouTitle) thankYouTitle.classList.add('hidden');
            if (petPillsContainer) petPillsContainer.classList.remove('hidden');

            if (currentStep === FORM_CONFIG.STEPS.INVENTORY - 1) {
                // Inventory step - change to "Complete Inventory"
                nextButton.innerHTML = 'Complete Inventory <iconify-icon class="text-3xl" icon="fluent:next-frame-20-filled"></iconify-icon>';

                // Check inventory status
                const checkinData = FormDataManager.getCheckinData();
                const hasItems = checkinData?.inventory?.length > 0;
                const isComplete = checkinData?.inventoryComplete;

                // Enable next button if items exist OR (no items AND checkbox checked)
                const shouldEnable = hasItems || (!hasItems && isComplete);

                nextButton.disabled = !shouldEnable;
                if (nextButton.disabled) {
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else {
                // Other steps - normal "Next"
                nextButton.innerHTML = 'Next <iconify-icon class="text-3xl" icon="fluent:next-frame-20-filled"></iconify-icon>';
                nextButton.disabled = false;
                nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    /**
     * Gets the current step index
     * @returns {number} Current step index
     */
    static getCurrentStep() {
        return Utils.actualStep();
    }

    /**
     * Checks if current step is the final step
     * @returns {boolean} True if current step is the final step
     */
    static isFinalStep() {
        return this.getCurrentStep() === (FORM_CONFIG.STEPS.INVENTORY - 1);
    }
}

// Make updateTabbarForStep globally available for backward compatibility
window.updateTabbarForStep = () => NavigationManager.updateTabbarForStep();

export { NavigationManager };