import Pill from "./Pill.js";
import CookieHandler from "./CookieHandler.js";
import { FormDataManager } from "./FormDataManager.js";
import { CookieManager } from "./CookieManager.js";
import Utils from "./Utils.js";
document.addEventListener("DOMContentLoaded", function () {
    let forms = document.querySelectorAll(".step");
    forms = Array.from(forms).map((form) => form.querySelector("form"));
    // console.log(forms);

    /**
     * Adds .pill elements to #petPillsContainer for each pet, with a close icon.
     */
    function addPetPillsToContainer() {
        const pets = FormDataManager.getAllPetsFromCookies();
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
        for (let index = 0; index < forms.length; index++) {
            const element = forms[index];
            let tempData = FormDataManager.getFormDataFromStep(index + 1);
            console.log("Populating form with cookies...");
            console.log(tempData);
            if (!tempData) return;
            tempData.pet ? (tempData = tempData.pet) : tempData; // Ensure we are working with the pet data
            const form = forms[Utils.actualStep()]; // Get the current form
            Object.keys(tempData).forEach((key) => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === "checkbox") {
                        input.checked = tempData[key];
                    } else {
                        input.value = tempData[key];
                    }
                }
            });
        }
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

            // const newData = { pet: data };

            CookieHandler.saveFormDataToCookies(data);
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

        // Corregir: quitar el objeto wrapper innecesario
        FormDataManager.updatePetInCookies(0, feedingMedData);

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

            // Una sola línea maneja todo!
            const success = FormDataManager.handleFormStep(
                step,
                data,
                selectedPetIndex
            );

            if (success && step === FORM_CONFIG.STEPS.PET_INFO - 1) {
                addPetPillsToContainer();
            }
        });
    }
});

window.debugCookies = () => {
    return CookieHandler.debugCookies
        ? CookieHandler.debugCookies()
        : "Debug method not available";
};
