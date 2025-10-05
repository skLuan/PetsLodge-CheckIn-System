/**
 * PopupManager - Manages popup interactions for feeding/medication and terms
 *
 * This manager handles all popup-related functionality including opening/closing
 * popups, form submissions, and user interactions within popups.
 */

import { FormDataManager } from "../FormDataManager.js";
import { FormHandler } from "./FormHandler.js";

class PopupManager {
    /**
     * Initializes feeding/medication popup event handlers
     */
    static initializeFeedingMedicationPopup() {
        const addFeedMedButtons = document.querySelectorAll(".btn-add-feeding-med");
        const popup = document.querySelector("#feedingMedicationPopup");
        const popupForm = popup.querySelector("#feedingPopupForm");
        const popButtons = popup.querySelectorAll("button");
        const submitPopBtn = popup.querySelector("button[type='submit']");

        // Handle popup opening
        addFeedMedButtons.forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                const period = btn.getAttribute('data-period') || 'morning';

                // Pre-select the time in the popup
                const timeRadio = document.querySelector(`input[name="day_time"][value="${period}"]`);
                if (timeRadio) {
                    timeRadio.checked = true;
                }

                if (popup) {
                    popup.classList.remove("translate-y-[75vh]");
                    popup.classList.add("translate-y-0");
                }
            });
        });

        // Handle popup form submission
        submitPopBtn.addEventListener("click", function (e) {
            e.preventDefault();
            console.log("Submit button clicked");

            const data = FormHandler.extractFormInputValues("#feedingPopupForm");

            // Validate required fields
            if (!data.day_time || !data.type || !data.feeding_med_details?.trim()) {
                alert("Please fill in all required fields");
                return;
            }

            const itemData = {
                day_time: data.day_time,
                type: data.type,
                feeding_med_details: data.feeding_med_details.trim()
            };

            console.log("Saving feeding/medication data:", itemData);

            popupForm.reset();

            // Get selected pet or first pet
            const selectedPill = document.querySelector(".pill.selected");
            const petIndex = selectedPill ? parseInt(selectedPill.dataset.index, 10) : 0;

            // Check if "same feeding for all" is checked and it's food
            const sameFeedingCheckbox = document.getElementById("sameFeedingForAll");
            const isSameFeedingForAll = sameFeedingCheckbox && sameFeedingCheckbox.checked && data.type === "food";

            if (isSameFeedingForAll) {
                // Add to all pets
                const allPets = FormDataManager.getAllPetsFromCheckin();
                allPets.forEach((_, index) => {
                    FormDataManager.addPetFeedingOrMedication(index, "feeding", itemData);
                });
            } else {
                // Add to selected pet
                FormDataManager.addPetFeedingOrMedication(petIndex, data.type === "food" ? "feeding" : "medication", itemData);
            }

            // Close popup
            popup.classList.add("translate-y-[75vh]");
            popup.classList.remove("translate-y-0");
        });

        // Handle popup button styling
        popButtons.forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                popButtons.forEach((b) => {
                    b.classList.add("shadow");
                    b.classList.remove("active");
                });
                btn.classList.add("active");
                btn.classList.remove("shadow");
            });
        });

        // Handle popup closing on outside click
        document.addEventListener("click", function (e) {
            if (
                popup &&
                !popup.contains(e.target) &&
                !e.target.closest(".btn-add-feeding-med")
            ) {
                popup.classList.add("translate-y-[75vh]");
                popup.classList.remove("translate-y-0");
            }
        });
    }

    /**
     * Initializes terms and conditions popup event handlers
     */
    static initializeTermsPopup() {
        const termsPopup = document.getElementById('termsConditionsPopup');
        if (!termsPopup) return;

        const termsContent = document.getElementById('termsContent');
        const termsAcceptedCheckbox = document.getElementById('termsAccepted');
        const closeTermsPopupBtn = document.getElementById('closeTermsPopup');

        // Enable checkbox and adjust opacity when scrolled to bottom
        if (termsContent && termsAcceptedCheckbox) {
            // Set initial opacity
            termsAcceptedCheckbox.style.opacity = '0.7';

            termsContent.addEventListener('scroll', function() {
                const isAtBottom = Math.abs(this.scrollHeight - this.clientHeight - this.scrollTop) < 1;
                termsAcceptedCheckbox.disabled = !isAtBottom;
                termsAcceptedCheckbox.style.opacity = isAtBottom ? '1.0' : '0.7';
            });
        }

        // Handle terms acceptance
        if (termsAcceptedCheckbox) {
            termsAcceptedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    FormDataManager.setTermsAccepted(true);
                    // Allow closing the popup
                    if (closeTermsPopupBtn) {
                        closeTermsPopupBtn.style.display = '';
                    }
                } else {
                    FormDataManager.setTermsAccepted(false);
                }
            });
        }

        // Handle close button - just close popup without proceeding
        if (closeTermsPopupBtn) {
            closeTermsPopupBtn.addEventListener('click', function() {
                if (termsAcceptedCheckbox && termsAcceptedCheckbox.checked) {
                    FormDataManager.hideTermsPopup();
                    // Update tabbar to reflect terms acceptance
                    if (typeof window.updateTabbarForStep === 'function') {
                        window.updateTabbarForStep();
                    }
                } else {
                    alert('Please read the terms and conditions and check the box to accept.');
                }
            });
        }
    }
}

export { PopupManager };