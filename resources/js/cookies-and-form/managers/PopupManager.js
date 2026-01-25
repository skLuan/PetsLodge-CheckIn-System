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

        // Handle grooming checkbox changes for mutual exclusivity and conditional notes display
        groomingCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    if (this.value === 'no') {
                        // If "no" is checked, uncheck all other checkboxes
                        groomingCheckboxes.forEach(cb => {
                            if (cb !== this) {
                                cb.checked = false;
                            }
                        });
                    } else {
                        // If any other option is checked, uncheck "no"
                        const noCheckbox = Array.from(groomingCheckboxes).find(cb => cb.value === 'no');
                        if (noCheckbox) {
                            noCheckbox.checked = false;
                        }
                    }
                }

                const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                    cb.checked && cb.value !== 'no'
                );

                const notesContainer = groomingPopup.querySelector('.conditional-grooming-notes-popup');
                const appointmentContainer = groomingPopup.querySelector('.conditional-grooming-appointment-popup');
                
                if (hasGroomingSelected) {
                    notesContainer.style.display = '';
                    appointmentContainer.style.display = '';
                } else {
                    notesContainer.style.display = 'none';
                    appointmentContainer.style.display = 'none';
                    // Clear the notes field
                    if (groomingNotesTextarea) {
                        groomingNotesTextarea.value = '';
                    }
                    // Clear the appointment day selection
                    const appointmentRadios = groomingPopup.querySelectorAll('input[name="groomingAppointmentDay"]');
                    appointmentRadios.forEach(radio => {
                        radio.checked = false;
                    });
                }
            });
        });

        // Handle confirm button
         if (confirmGroomingBtn) {
             confirmGroomingBtn.addEventListener('click', function() {
                 // Check if any grooming service is selected
                 const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                     cb.checked && cb.value !== 'no'
                 );

                 // If grooming is selected, validate that appointment day is selected
                 if (hasGroomingSelected) {
                     const appointmentRadios = groomingPopup.querySelectorAll('input[name="groomingAppointmentDay"]');
                     const appointmentDaySelected = Array.from(appointmentRadios).some(radio => radio.checked);
                     
                     if (!appointmentDaySelected) {
                         alert('Please select a preferred appointment day for grooming services');
                         return;
                     }
                 }

                 // Collect grooming data
                 const groomingData = {};
                 groomingCheckboxes.forEach(checkbox => {
                     groomingData[checkbox.value] = checkbox.checked;
                 });

                 // Get selected appointment day if grooming is selected
                 if (hasGroomingSelected) {
                     const appointmentRadios = groomingPopup.querySelectorAll('input[name="groomingAppointmentDay"]');
                     const selectedAppointment = Array.from(appointmentRadios).find(radio => radio.checked);
                     if (selectedAppointment) {
                         groomingData.appointmentDay = selectedAppointment.value;
                     }
                 }

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
                 // Use a small delay to ensure popup is properly rendered
                 const checkinData = FormDataManager.getCheckinData();
                 if (!checkinData?.termsAccepted) {
                     setTimeout(() => {
                         const termsPopup = document.getElementById('termsConditionsPopup');
                         if (termsPopup) {
                             termsPopup.classList.remove('hidden');
                         }
                     }, 50);
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

                    // Pre-populate appointment day
                    if (checkinData.grooming.appointmentDay) {
                        const appointmentRadios = groomingPopup.querySelectorAll('input[name="groomingAppointmentDay"]');
                        const selectedRadio = Array.from(appointmentRadios).find(radio => radio.value === checkinData.grooming.appointmentDay);
                        if (selectedRadio) {
                            selectedRadio.checked = true;
                        }
                    }

                    // Show/hide notes and appointment containers based on selections
                    const hasGroomingSelected = Array.from(groomingCheckboxes).some(cb =>
                        cb.checked && cb.value !== 'no'
                    );
                    const notesContainer = groomingPopup.querySelector('.conditional-grooming-notes-popup');
                    const appointmentContainer = groomingPopup.querySelector('.conditional-grooming-appointment-popup');
                    if (notesContainer) {
                        notesContainer.style.display = hasGroomingSelected ? '' : 'none';
                    }
                    if (appointmentContainer) {
                        appointmentContainer.style.display = hasGroomingSelected ? '' : 'none';
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
            .filter(([key, value]) => value && key !== 'no' && key !== 'appointmentDay')
            .map(([key]) => key.charAt(0).toUpperCase() + key.slice(1));

        if (selectedServices.length > 0) {
            summaryHTML += `<div class="font-semibold text-green-800 mb-1">🛁 Grooming Services: ${selectedServices.join(', ')}</div>`;
            
            // Add appointment day if grooming services are selected
            if (checkinData.grooming.appointmentDay) {
                summaryHTML += `<div class="pl-4 text-sm">📅 Appointment Day: ${checkinData.grooming.appointmentDay}</div>`;
            }
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
                    // Update the finalTermsAccepted checkbox in the receipt section
                    const finalTermsCheckbox = document.getElementById('finalTermsAccepted');
                    if (finalTermsCheckbox) {
                        finalTermsCheckbox.checked = true;
                    }
                    // Show continue button
                    if (continueButton) {
                        continueButton.classList.remove('hidden');
                    }
                } else {
                    FormDataManager.setTermsAccepted(false);
                    // Update the finalTermsAccepted checkbox in the receipt section
                    const finalTermsCheckbox = document.getElementById('finalTermsAccepted');
                    if (finalTermsCheckbox) {
                        finalTermsCheckbox.checked = false;
                    }
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
            // Also update the finalTermsAccepted checkbox in the receipt section
            const finalTermsCheckbox = document.getElementById('finalTermsAccepted');
            if (finalTermsCheckbox) {
                finalTermsCheckbox.checked = true;
            }
        } else {
            if (continueButton) {
                continueButton.classList.add('hidden');
            }
            if (termsAcceptedCheckbox) {
                termsAcceptedCheckbox.checked = false;
            }
            // Also update the finalTermsAccepted checkbox in the receipt section
            const finalTermsCheckbox = document.getElementById('finalTermsAccepted');
            if (finalTermsCheckbox) {
                finalTermsCheckbox.checked = false;
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

        // Handle "Read Terms Again" button from receipt section
        const readTermsAgainBtn = document.getElementById('readTermsAgainBtn');
        if (readTermsAgainBtn) {
            readTermsAgainBtn.addEventListener('click', function() {
                // Show popup
                termsPopup.classList.remove('hidden');
                
                // Reset scroll position to top
                if (termsContent) {
                    termsContent.scrollTop = 0;
                }
                
                // Reset checkbox and button states
                if (termsAcceptedCheckbox) {
                    termsAcceptedCheckbox.checked = false;
                    termsAcceptedCheckbox.disabled = true;
                    termsAcceptedCheckbox.style.opacity = '0.4';
                }
                
                if (continueButton) {
                    continueButton.classList.add('hidden');
                }
            });
        }

        // Handle finalTermsAccepted checkbox from receipt section
        const finalTermsCheckbox = document.getElementById('finalTermsAccepted');
        if (finalTermsCheckbox) {
            finalTermsCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    FormDataManager.setTermsAccepted(true);
                    // Update the popup checkbox as well
                    if (termsAcceptedCheckbox) {
                        termsAcceptedCheckbox.checked = true;
                    }
                    // Show continue button in popup
                    if (continueButton) {
                        continueButton.classList.remove('hidden');
                    }
                } else {
                    FormDataManager.setTermsAccepted(false);
                    // Update the popup checkbox as well
                    if (termsAcceptedCheckbox) {
                        termsAcceptedCheckbox.checked = false;
                    }
                    // Hide continue button in popup
                    if (continueButton) {
                        continueButton.classList.add('hidden');
                    }
                }
            });
        }
    }
}

export { PopupManager };