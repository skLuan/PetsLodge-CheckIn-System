import Pill from "../Pill.js";
import CookieHandler from "./CookieHandler.js";
import { FormDataManager } from "./FormDataManager.js";
import { CookieManager } from "./CookieManager.js";
import Utils from "../Utils.js";
import config from "./config.js";

const { FORM_CONFIG } = config;
document.addEventListener("DOMContentLoaded", function () {
    let forms = document.querySelectorAll(".step");
    forms = Array.from(forms).map((form) => form.querySelector("form"));
    // console.log(forms);

    /**
     * Adds .pill elements to #petPillsContainer for each pet, with a close icon.
     */
    function addPetPillsToContainer() {
        const pets = FormDataManager.getAllPetsFromCheckin();
        const container = document.querySelector("#petPillsContainer");
        console.log("addPetPillsToContainer called");

        if (container) {
            container.innerHTML = "";
        } else {
            console.warn("No #petPillsContainer found in the DOM.");
            return;
        }

        if (pets.length === 0) {
            console.log("No pets found in cookies, skipping pill creation.");
            return;
        }

        pets.forEach((pet, index) => {
            if (pet && pet.petName) {
                const pill = new Pill(pet.petName, pet.petType, index);
                container.appendChild(pill.render());
            } else {
                console.warn(
                    `Pet at index ${index} is missing petName or is invalid.`
                );
            }
        });

        console.log(`Added ${pets.length} pet pills to #petPillsContainer.`);
    }

    addPetPillsToContainer();

    function populateFormWithCookies() {
        // Only populate the current step's form, and only if it's empty or has minimal data
        // This prevents overwriting user input during active editing
        const currentStep = Utils.actualStep();
        const currentForm = forms[currentStep];

        if (!currentForm) return;

        const checkinData = FormDataManager.getCheckinData();
        console.log("Populating current form with cookies (step " + currentStep + "):", checkinData);

        if (!checkinData) return;

        let tempData = {};

        if (currentStep === 0) { // Owner info step
            tempData = checkinData.user?.info || {};
        } else if (currentStep === 1) { // Pet info step
            const selectedPetIndex = document.querySelector(".pill.selected") ? parseInt(document.querySelector(".pill.selected").dataset.index, 10) : 0;
            tempData = checkinData.pets?.[selectedPetIndex]?.info || {};
        } else {
            // For other steps, no population from cookies for now
            return;
        }

        console.log("Temp data for population:", tempData);

        // Only populate fields that are empty to avoid overwriting user input
        Object.keys(tempData).forEach((key) => {
            const input = currentForm.querySelector(`[name="${key}"]`);
            if (input && (!input.value || input.value.trim() === '')) {
                // Only set value if the field is empty
                if (input.type === "checkbox") {
                    input.checked = tempData[key];
                } else {
                    input.value = tempData[key] || '';
                }
                console.log("Setting", key, "to", tempData[key]);
            }
        });
    }

    // Function to extract input values for a given form
    function extractFormInputValues(formElementOrSelector) {
        console.log("Extracting form input values...");
        let formElement = formElementOrSelector;
        if (typeof formElementOrSelector === "string") {
            formElement = document.querySelector(formElementOrSelector);
        }
        const inputs = formElement.querySelectorAll("input, select, textarea");
        const data = {};

        inputs.forEach((input) => {
            if (input.name) {
                if (input.type === "checkbox") {
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
        console.log("Extracted form input values:");
        console.log(data);
        return data;
    }
    //------------------------------------------------
    //------------------------------------------------
    // Save form data on submit
    const form = document.querySelector("#petInfoForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const data = extractFormInputValues(form);

            // Use the same method as the "next" button for consistency
            FormDataManager.handleFormStep(1, data, null); // step 1 = PET_INFO, selectedPetIndex = null to add new
            form.reset();
            scrollTo({ top: 0, behavior: "smooth" });
            setTimeout(() => {
                addPetPillsToContainer();
            }, 500);
        });
    }

    populateFormWithCookies();
    // ------------------------------------------------- Start button feeding/medication
    const addFeedMedButtons = document.querySelectorAll(".btn-add-feeding-med");
    const popup = document.querySelector("#feedingMedicationPopup");
    const popupForm = popup.querySelector("#feedingPopupForm");
    const popButtons = popup.querySelectorAll("button");
    const submitPopBtn = popup.querySelector("button[type='submit']");

    addFeedMedButtons.forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            if (popup) {
                popup.classList.toggle("translate-y-[75vh]");
                popup.classList.toggle("translate-y-0");
            }
        });

        btn.classList.contains("btn-day-time");
    });
    submitPopBtn.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("Submit button clicked");

        const data = extractFormInputValues("#feedingPopupForm");

        // Estructura correcta para feeding/medication
        const feedingMedData = {
            [data.type === "food" ? "feeding" : "medication"]: [data],
        };

        console.log("Saving feeding/medication data:", feedingMedData);

        popupForm.reset();

        // Update the selected pet or the first pet if none selected
        const selectedPill = document.querySelector(".pill.selected");
        const petIndex = selectedPill ? parseInt(selectedPill.dataset.index, 10) : 0;
        FormDataManager.updatePetInCheckin(petIndex, feedingMedData);

        // Cerrar popup
        popup.classList.add("translate-y-[75vh]");
        popup.classList.remove("translate-y-0");
    });
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
    // ------------------------------------------------- End button feeding/medication

    // Save form data on next step
    const nextButton = document.querySelector("#nextStep");
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            const step = Utils.actualStep();
            const data = extractFormInputValues(forms[step]);

            const selectedPill = document.querySelector(".pill.selected");
            const selectedPetIndex = selectedPill
                ? parseInt(selectedPill.dataset.index, 10)
                : null;

            let success = false;

            if (step === FORM_CONFIG.STEPS.PET_INFO - 1) { // Pet info step
                if (selectedPetIndex !== null) {
                    // Update existing pet
                    success = FormDataManager.handleFormStep(step, data, selectedPetIndex);
                } else if (FormDataManager.getAllPetsFromCheckin().length === 0) {
                    // Only add new pet if no pets exist
                    success = FormDataManager.handleFormStep(step, data, selectedPetIndex);
                } else {
                    // Do nothing if pets exist and none selected
                    success = true; // Allow navigation
                }

                if (success) {
                    addPetPillsToContainer();
                }
            } else {
                // For other steps, proceed normally
                success = FormDataManager.handleFormStep(step, data, selectedPetIndex);
            }

            // Check if this is the final step (before Thanks)
            if (step === FORM_CONFIG.STEPS.INVENTORY - 1) {
                // Trigger final submission
                setTimeout(() => {
                    submitFinalCheckIn();
                }, 500);
            }
        });
    }

    // Final check-in submission function
    async function submitFinalCheckIn() {
        try {
            console.log("🚀 Starting final check-in submission...");

            // Get complete check-in data
            const checkinData = FormDataManager.getCheckinData();

            if (!checkinData) {
                alert("No check-in data found. Please complete the form first.");
                return;
            }

            // Validate minimum required data
            if (!checkinData.user?.info?.phone || !checkinData.pets?.length) {
                alert("Please complete owner information and add at least one pet.");
                return;
            }

            // Show loading state
            const submitButton = document.querySelector("#finalSubmit") || document.querySelector("#nextStep");
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Submitting...";
            }

            // Submit to API
            const response = await fetch('/api/checkin/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    checkin_data: checkinData
                })
            });

            const result = await response.json();

            if (result.success) {
                console.log("✅ Check-in submitted successfully:", result);

                // Clear the cookie after successful submission
                FormDataManager.clearCheckinData();

                // Show success message
                alert("Check-in completed successfully!");

                // Redirect to success page or dashboard
                window.location.href = '/dashboard';

            } else {
                console.error("❌ Submission failed:", result);
                alert("Failed to submit check-in: " + (result.message || "Unknown error"));

                // Re-enable button
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = "Submit Check-in";
                }
            }

        } catch (error) {
            console.error("❌ Submission error:", error);
            alert("An error occurred while submitting. Please try again.");

            // Re-enable button
            const submitButton = document.querySelector("#finalSubmit") || document.querySelector("#nextStep");
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = "Submit Check-in";
            }
        }
    }
});

window.debugCookies = () => {
    return CookieHandler.debugCookies
        ? CookieHandler.debugCookies()
        : "Debug method not available";
};
