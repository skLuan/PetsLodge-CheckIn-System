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
     * Initializes grooming popup event handlers
     */
    static initializeGroomingPopup() {
        const groomingPopup = document.getElementById('groomingPopup');
        if (!groomingPopup) return;

        const groomingCheckboxes = groomingPopup.querySelectorAll('input[name="groomingOptions[]"]');
        const groomingNotesTextarea = document.getElementById('groomingNotes');
        const confirmGroomingBtn = document.getElementById('confirmGrooming');
        const closeGroomingPopupBtn = document.getElementById('closeGroomingPopup');

        // Handle grooming checkbox changes for conditional notes display
        groomingCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                    cb.checked && cb.value !== 'no'
                );

                const notesContainer = groomingPopup.querySelector('.conditional-grooming-notes-popup');
                if (hasGroomingSelected) {
                    notesContainer.style.display = '';
                } else {
                    notesContainer.style.display = 'none';
                    // Clear the notes field
                    if (groomingNotesTextarea) {
                        groomingNotesTextarea.value = '';
                    }
                }
            });
        });

        // Handle confirm button
        if (confirmGroomingBtn) {
            confirmGroomingBtn.addEventListener('click', function() {
                // Collect grooming data
                const groomingData = {};
                groomingCheckboxes.forEach(checkbox => {
                    groomingData[checkbox.value] = checkbox.checked;
                });

                const groomingNotes = groomingNotesTextarea ? groomingNotesTextarea.value.trim() : '';

                // Save to FormDataManager
                FormDataManager.updateCheckinData({
                    grooming: groomingData,
                    groomingDetails: groomingNotes,
                    groomingAcknowledged: true // Mark as acknowledged
                });

                // Update grooming summary in the receipt section
                PopupManager.updateGroomingSummary();

                // Hide grooming popup
                groomingPopup.classList.add('hidden');

                // Show terms popup after grooming confirmation (but only if not already accepted)
                const checkinData = FormDataManager.getCheckinData();
                if (!checkinData?.termsAccepted) {
                    const termsPopup = document.getElementById('termsConditionsPopup');
                    if (termsPopup) {
                        termsPopup.classList.remove('hidden');
                    }
                }
            });
        }

        // Handle close button - allow closing without acknowledgment
        if (closeGroomingPopupBtn) {
            closeGroomingPopupBtn.addEventListener('click', function() {
                groomingPopup.classList.add('hidden');
            });
        }

        // Handle outside click to close
        document.addEventListener('click', function(e) {
            if (groomingPopup && !groomingPopup.contains(e.target) && !e.target.closest('.btn-trigger-grooming') && !e.target.closest('#editGroomingBtn')) {
                groomingPopup.classList.add('hidden');
            }
        });

        // Handle edit grooming button from receipt section
        const editGroomingBtn = document.getElementById('editGroomingBtn');
        if (editGroomingBtn) {
            editGroomingBtn.addEventListener('click', function() {
                // Load existing grooming data into popup
                const checkinData = FormDataManager.getCheckinData();
                if (checkinData?.grooming) {
                    // Pre-populate checkboxes
                    groomingCheckboxes.forEach(checkbox => {
                        checkbox.checked = checkinData.grooming[checkbox.value] || false;
                    });

                    // Pre-populate notes
                    if (groomingNotesTextarea && checkinData.groomingDetails) {
                        groomingNotesTextarea.value = checkinData.groomingDetails;
                    }

                    // Show/hide notes container based on selections
                    const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                        cb.checked && cb.value !== 'no'
                    );
                    const notesContainer = groomingPopup.querySelector('.conditional-grooming-notes-popup');
                    if (notesContainer) {
                        notesContainer.style.display = hasGroomingSelected ? '' : 'none';
                    }
                }

                // Show popup
                groomingPopup.classList.remove('hidden');
            });
        }
    }

    /**
     * Updates the grooming summary in the receipt section
     */
    static updateGroomingSummary() {
        const groomingSummary = document.getElementById('groomingSummary');
        const groomingAcknowledgedCheckbox = document.getElementById('groomingAcknowledged');

        if (!groomingSummary || !groomingAcknowledgedCheckbox) return;

        const checkinData = FormDataManager.getCheckinData();
        if (!checkinData?.grooming) return;

        let summaryHTML = '';

        // Build summary of selected grooming options
        const selectedServices = Object.entries(checkinData.grooming)
            .filter(([key, value]) => value && key !== 'no')
            .map(([key]) => key.charAt(0).toUpperCase() + key.slice(1));

        if (selectedServices.length > 0) {
            summaryHTML += `<div class="font-semibold text-green-800 mb-1">🛁 Grooming Services: ${selectedServices.join(', ')}</div>`;
        } else {
            summaryHTML += `<div class="font-semibold text-gray-600 mb-1">No grooming services selected</div>`;
        }

        if (checkinData.groomingDetails) {
            summaryHTML += `<div class="pl-4 text-sm">📝 ${checkinData.groomingDetails}</div>`;
        }

        groomingSummary.innerHTML = summaryHTML;

        // Auto-check the acknowledgment checkbox if grooming is acknowledged
        if (checkinData.groomingAcknowledged) {
            groomingAcknowledgedCheckbox.checked = true;
        }
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
        const continueButton = document.getElementById('continueButton');

        // Enable checkbox and adjust opacity when scrolled to bottom
        if (termsContent && termsAcceptedCheckbox) {
            // Set initial opacity
            termsAcceptedCheckbox.style.opacity = '0.4';

            termsContent.addEventListener('scroll', function() {
                const isAtBottom = Math.abs(this.scrollHeight - this.clientHeight - this.scrollTop) < 1;
                termsAcceptedCheckbox.disabled = !isAtBottom;
                termsAcceptedCheckbox.style.opacity = isAtBottom ? '1.0' : '0.4';
            });
        }

        // Handle terms acceptance
        if (termsAcceptedCheckbox) {
            termsAcceptedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    FormDataManager.setTermsAccepted(true);
                    // Show continue button
                    if (continueButton) {
                        continueButton.classList.remove('hidden');
                    }
                } else {
                    FormDataManager.setTermsAccepted(false);
                    // Hide continue button
                    if (continueButton) {
                        continueButton.classList.add('hidden');
                    }
                }
            });
        }

        // Add initialization check for terms acceptance state on load
        // This ensures the button state persists across page reloads
        const checkinData = FormDataManager.getCheckinData();
        if (checkinData?.termsAccepted) {
            if (continueButton) {
                continueButton.classList.remove('hidden');
            }
            if (termsAcceptedCheckbox) {
                termsAcceptedCheckbox.checked = true;
            }
        } else {
            if (continueButton) {
                continueButton.classList.add('hidden');
            }
            if (termsAcceptedCheckbox) {
                termsAcceptedCheckbox.checked = false;
            }
        }

        // Handle continue button - close popup and proceed
        if (continueButton) {
            continueButton.addEventListener('click', function() {
                
                if (termsAcceptedCheckbox && termsAcceptedCheckbox.checked) {
                    // Close popup
                    termsPopup.classList.add('hidden');
                    // Update tabbar to reflect terms acceptance
                    if (typeof window.updateTabbarForStep === 'function') {
                        window.updateTabbarForStep();
                    }
                } else {
                    alert('Please read the terms and conditions and check the box to accept.');
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