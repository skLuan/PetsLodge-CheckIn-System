/**
 * NavigationManager - Manages navigation and tab bar updates
 *
 * This manager handles navigation logic, tab bar state updates, and
 * step-based UI changes throughout the form process.
 */

import Utils from "../../Utils.js";
import config from "../config.js";
import { FormDataManager } from "../FormDataManager.js";
import { PopupManager } from "./PopupManager.js";

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

        // Update grooming summary when reaching final step
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY) {
            PopupManager.updateGroomingSummary();
        }

        // Toggle thank you title and pet pills container
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY) { // Step 6 (0-based: 5)
            // Hide next button in final step
            nextButton.style.display = 'none';

            // Show thank you title, hide pet pills
            if (thankYouTitle) thankYouTitle.classList.remove('hidden');
            if (petPillsContainer) petPillsContainer.classList.add('hidden');

            // Update grooming summary and check grooming acknowledgment
            PopupManager.updateGroomingSummary();
        } else {
            // Show next button for other steps
            nextButton.style.display = '';

            // Hide thank you title, show pet pills
            if (thankYouTitle) thankYouTitle.classList.add('hidden');
            if (petPillsContainer) petPillsContainer.classList.remove('hidden');

            if (currentStep === FORM_CONFIG.STEPS.INVENTORY - 1) {
                // Inventory step - change to "Complete Inventory"
                nextButton.innerHTML = 'Complete Inventory <iconify-icon class="text-3xl" icon="fluent:next-frame-20-filled"></iconify-icon>';

                // Check inventory status and terms acceptance
                const checkinData = FormDataManager.getCheckinData();
                const hasItems = checkinData?.inventory?.length > 0;
                const isComplete = checkinData?.inventoryComplete;
                const termsAccepted = checkinData?.termsAccepted;

                // Enable next button if items exist OR (no items AND checkbox checked) AND terms are accepted
                const inventoryReady = hasItems || (!hasItems && isComplete);
                const shouldEnable = inventoryReady && termsAccepted;

                // If inventory is ready but terms not accepted, show terms popup
                if (inventoryReady && !termsAccepted) {
                    setTimeout(() => {
                        const termsPopup = document.getElementById('termsConditionsPopup');
                        if (termsPopup) {
                            termsPopup.classList.remove('hidden');
                        }
                    }, 100); // Small delay
                }

                nextButton.disabled = !shouldEnable;
                if (nextButton.disabled) {
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else if (currentStep === FORM_CONFIG.STEPS.INVENTORY) { // Step 6 - Final step
                // In final step, check grooming acknowledgment
                const checkinData = FormDataManager.getCheckinData();
                const groomingAcknowledged = checkinData?.groomingAcknowledged;

                // Update submit button state based on grooming acknowledgment
                const finalSubmitButton = document.querySelector("#finalSubmit");
                if (finalSubmitButton) {
                    finalSubmitButton.disabled = !groomingAcknowledged;
                    if (finalSubmitButton.disabled) {
                        finalSubmitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        finalSubmitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
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