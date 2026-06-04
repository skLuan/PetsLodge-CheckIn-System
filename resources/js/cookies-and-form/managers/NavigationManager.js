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
import { CookieReactivityManager } from "../reactivitySystem/CookieReactivityManager.js";
import { SummaryRenderer } from "../reactivitySystem/SummaryRenderer.js";

const { FORM_CONFIG } = config;

class NavigationManager {
    /**
     * Initialize navigation reactivity
     */
    static initializeNavigationReactivity() {
        // Register listener for cookie changes to update navigation state
        CookieReactivityManager.addListener((cookieData) => {
            this.updateNavigationState(cookieData);
        });

        // Start observing pet pills container so label stays in sync automatically
        this.initializePetPillsLabelObserver();
    }

    /**
     * Update navigation state based on cookie data changes
     * @param {Object} cookieData - Updated cookie data
     */
    static updateNavigationState(cookieData) {
        // Only update navigation if we're on the inventory step
        const currentStep = Utils.actualStep();
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY - 1) {
            this.updateInventoryStepNavigation(cookieData);
        }
    }

    /**
     * Update navigation for inventory step based on cookie data
     * @param {Object} cookieData - Current cookie data
     */
    static updateInventoryStepNavigation(cookieData) {
        const nextButton = document.querySelector("#nextStep");
        if (!nextButton) return;

        // Check inventory status from cookie data
        const hasItems = cookieData?.inventory?.length > 0;
        const isComplete = cookieData?.inventoryComplete;

        // Enable next button if items exist OR (no items AND checkbox checked)
        const inventoryReady = hasItems || (!hasItems && isComplete);
        const shouldEnable = inventoryReady;

        nextButton.disabled = !shouldEnable;
        if (nextButton.disabled) {
            nextButton.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * Syncs the visibility of #petPillsLabel with whether #petPillsContainer has children.
     */
    static syncPetPillsLabel() {
        const container = document.getElementById('petPillsContainer');
        const label = document.getElementById('petPillsLabel');
        if (!label) return;

        const hasChildren = container && container.children.length > 0;
        label.classList.toggle('hidden', !hasChildren);
    }

    /**
     * Syncs #nowEditContainer and #nowEditingName with the currently selected pill.
     * Shows the container with the pet name when a pill is selected; hides it otherwise.
     */
    static syncNowEditingLabel() {
        const nowEditContainer = document.getElementById('nowEditContainer');
        const nowEditingName = document.getElementById('nowEditingName');
        if (!nowEditContainer || !nowEditingName) return;

        const selectedPill = document.querySelector('#petPillsContainer .pill.selected');
        if (selectedPill) {
            const nameSpan = selectedPill.querySelector('span');
            nowEditingName.textContent = nameSpan ? nameSpan.textContent : '';
            nowEditContainer.classList.remove('hidden');
        } else {
            nowEditingName.textContent = '';
            nowEditContainer.classList.add('hidden');
        }
    }

    /**
     * Observes #petPillsContainer for child additions/removals and class changes
     * on pill elements, keeping #petPillsLabel and #nowEditContainer in sync automatically.
     */
    static initializePetPillsLabelObserver() {
        const container = document.getElementById('petPillsContainer');
        if (!container) return;

        const observer = new MutationObserver(() => {
            this.syncPetPillsLabel();
            this.syncNowEditingLabel();
        });

        // childList: detects pills added/removed
        // subtree + attributes + attributeFilter: detects .selected toggled on any pill
        observer.observe(container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class'],
        });

        // Run once immediately to set the initial state
        this.syncPetPillsLabel();
        this.syncNowEditingLabel();
    }

    /**
     * Updates the tab bar based on current step and form state
     */
    static updateTabbarForStep() {
        const currentStep = Utils.actualStep();
        const nextButton = document.querySelector("#nextStep");
        const thankYouTitle = document.getElementById('thankYouTitle');
        const petPillsContainer = document.getElementById('petPillsContainer');
        const petPillsLabel = document.getElementById('petPillsLabel');

        if (!nextButton) return;

        // Update grooming summary when reaching final step
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY) {
            PopupManager.updateGroomingSummary();
        }

        // Toggle thank you title and pet pills container
        if (currentStep === FORM_CONFIG.STEPS.INVENTORY) { // Step 6 (0-based: 5)
            // Hide next button in final step
            nextButton.style.display = 'none';

            // Show thank you title, hide pet pills, label, and now-editing banner
            if (thankYouTitle) thankYouTitle.classList.remove('hidden');
            if (petPillsContainer) petPillsContainer.classList.add('hidden');
            if (petPillsLabel) petPillsLabel.classList.add('hidden'); // Always hidden on final step
            const nowEditContainer = document.getElementById('nowEditContainer');
            if (nowEditContainer) nowEditContainer.classList.add('hidden');

            // Update grooming summary and check grooming acknowledgment
            PopupManager.updateGroomingSummary();

            // Render check-in summary from cookie data (no cookie change fires on step entry)
            const cookieData = FormDataManager.getCheckinData();
            if (cookieData) {
                SummaryRenderer.updateCheckinSummary(cookieData);
            }
        } else {
            // Show next button for other steps

            // Hide thank you title, show pet pills container; label visibility
            // is managed dynamically by syncPetPillsLabel via MutationObserver
            if (thankYouTitle) thankYouTitle.classList.add('hidden');
            if (petPillsContainer) petPillsContainer.classList.remove('hidden');
            this.syncPetPillsLabel();
            this.syncNowEditingLabel();
            
            if (currentStep === FORM_CONFIG.STEPS.INVENTORY - 1) {
                // Inventory step - change to "Complete Inventory"
                nextButton.innerHTML = 'Complete Inventory <iconify-icon class="text-3xl" icon="fluent:next-frame-20-filled"></iconify-icon>';

                // Populate inventory UI from cookie data on step entry
                const invData = FormDataManager.getCheckinData();
                if (invData) {
                    FormDataManager.updateInventoryUI(invData.inventory, invData.inventoryComplete);
                }

                // Check inventory status, grooming acknowledgment, and terms acceptance
                const checkinData = FormDataManager.getCheckinData();
                const hasItems = checkinData?.inventory?.length > 0;
                const isComplete = checkinData?.inventoryComplete;
                const groomingAcknowledged = checkinData?.groomingAcknowledged;
                const termsAccepted = checkinData?.termsAccepted;

                // Enable next button if items exist OR (no items AND checkbox checked)
                // Grooming and terms checks are only for the final submit button in step 6
                const inventoryReady = hasItems || (!hasItems && isComplete);
                const shouldEnable = inventoryReady;

                console.log('🔍 [NavigationManager] Inventory step check:', {
                    inventoryReady,
                    groomingAcknowledged,
                    termsAccepted,
                    hasItems,
                    isComplete
                });

                // Show grooming popup first if inventory is ready but grooming not acknowledged
                if (inventoryReady && !groomingAcknowledged) {
                    console.log('📢 [NavigationManager] Showing grooming popup');
                    setTimeout(() => {
                        const groomingPopup = document.getElementById('groomingPopup');
                        if (groomingPopup) {
                            console.log('✅ [NavigationManager] Grooming popup found and showing');
                            groomingPopup.classList.remove('hidden');
                        } else {
                            console.error('❌ [NavigationManager] Grooming popup not found in DOM');
                        }
                    }, 50); // Reduced delay for faster popup display
                }
                // Show terms popup only after grooming is acknowledged but terms not accepted
                else if (inventoryReady && groomingAcknowledged && !termsAccepted) {
                    console.log('📢 [NavigationManager] Showing terms popup');
                    setTimeout(() => {
                        const termsPopup = document.getElementById('termsConditionsPopup');
                        if (termsPopup) {
                            console.log('✅ [NavigationManager] Terms popup found and showing');
                            termsPopup.classList.remove('hidden');
                        } else {
                            console.error('❌ [NavigationManager] Terms popup not found in DOM');
                        }
                    }, 50); // Reduced delay for faster popup display
                }

                nextButton.disabled = !shouldEnable;
                if (nextButton.disabled) {
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else if (currentStep === FORM_CONFIG.STEPS.INVENTORY) { // Step 6 - Final step
                // In final step, check grooming acknowledgment and terms acceptance
                const checkinData = FormDataManager.getCheckinData();
                const groomingAcknowledged = checkinData?.groomingAcknowledged;
                const termsAccepted = checkinData?.termsAccepted;

                // Update submit button state based on grooming acknowledgment AND terms acceptance
                const finalSubmitButton = document.querySelector("#finalSubmit");
                if (finalSubmitButton) {
                    finalSubmitButton.disabled = !groomingAcknowledged || !termsAccepted;
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