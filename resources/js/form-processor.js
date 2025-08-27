import Pill from "./Pill.js";
document.addEventListener("DOMContentLoaded", function () {
    let forms = document.querySelectorAll(".step");
    forms = Array.from(forms).map((form) => form.querySelector("form"));
    console.log(forms);

    function takeStep() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("step")) {
            const step = urlParams.get("step");
            return parseInt(step) - 1;
        } else return 0;
    }
    // Function to get form data from cookies
    function getFormDataFromCookies(index) {
        const tempCookie = document.cookie.split("; ");
        const cookie = tempCookie.find((row) =>
            row.startsWith(`formStep-${index}=`)
        );
        console.log("Getting form data from cookies...");
        console.log(cookie);
        if (!cookie) return null;
        try {
            return JSON.parse(decodeURIComponent(cookie.split("=")[1]));
        } catch (e) {
            console.error("Error parsing cookie JSON:", e);
            return null;
        }
    }
    function getPetsFromCookies() {
        const tempCookie = getFormDataFromCookies(2);
        if (!tempCookie) return [];
        const pets = Object.keys(tempCookie)
            .filter((key) => key.startsWith("pet"))
            .map((key) => tempCookie[key]);
        return pets;
    }

    /**
     * Adds .pill elements to #petPillsContainer for each pet, with a close icon.
     */
    function addPetPillsToContainer() {
        const pets = getPetsFromCookies();
        const container = document.querySelector("#petPillsContainer");

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
    /**
     * Checks if formStep-2 exists in cookies and has a .pet property.
     * @returns {boolean} True if formStep-2 exists and has a .pet property, false otherwise.
     */
    function hasFormStep2Pet() {
        const cookieKey = "formStep-2";
        const tempCookie = document.cookie.split("; ");
        const cookie = tempCookie.find((row) =>
            row.startsWith(`${cookieKey}=`)
        );

        if (!cookie) {
            console.log(`No cookie found for ${cookieKey}`);
            return false;
        }

        try {
            const cookieData = JSON.parse(
                decodeURIComponent(cookie.split("=")[1])
            );
            const hasPet =
                cookieData &&
                typeof cookieData === "object" &&
                "pet" in cookieData;
            console.log(`${cookieKey} pet property exists:`, hasPet);
            return hasPet;
        } catch (e) {
            console.error(`Error parsing ${cookieKey} cookie JSON:`, e);
            return false;
        }
    }

    function populateFormWithCookies() {
        for (let index = 0; index < forms.length; index++) {
            const element = forms[index];
            let tempData = getFormDataFromCookies(index + 1);
            console.log("Populating form with cookies...");
            console.log(tempData);
            if (!tempData) return;
            tempData.pet ? (tempData = tempData.pet) : tempData; // Ensure we are working with the pet data
            const form = forms[takeStep()]; // Get the current form
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

    let petCounter = 0;

    // Function to save form data to cookies
    function saveFormDataToCookies(data) {
        console.log("Saving form data to cookies...");
        console.log(data);
        if (!data) {
            console.log("No data provided, skipping save.");
            return;
        }

        // Parse existing cookies for the current form step
        const step = takeStep() + 1;
        const cookieKey = `formStep-${step}`;
        let existingData = {};
        try {
            const tempCookie = document.cookie.split("; ");
            const cookie = tempCookie.find((row) =>
                row.startsWith(`${cookieKey}=`)
            );
            if (cookie) {
                existingData = JSON.parse(
                    decodeURIComponent(cookie.split("=")[1])
                );
            }
        } catch (e) {
            console.error(`Error parsing ${cookieKey} cookie JSON:`, e);
            existingData = {};
        }

        // Initialize newData with existing data
        let newData = { ...existingData };

        if (step === 2) {
            // Calculate petCounter based on existing pet keys
            petCounter = 0;
            while (existingData[`pet${petCounter}`]) {
                petCounter++;
            }
            // Always add new pet with the next available key
            newData[`pet${petCounter}`] = data;
            petCounter++;
        } else {
            // For other steps, merge data without pet key logic
            newData = { ...existingData, ...data };
        }

        // Save merged data to cookies
        document.cookie = `${cookieKey}=${encodeURIComponent(
            JSON.stringify(newData)
        )}; path=/; max-age=3600`;
        console.log("Form data saved to cookies:", newData);
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

            saveFormDataToCookies(data);
            form.reset();
            scrollTo({ top: 0, behavior: "smooth"});
            setTimeout(() => {
                addPetPillsToContainer();
            }, 500);
        });
    }

    populateFormWithCookies();

    const nextButton = document.querySelector("#nextStep");
    let step = null;
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            const step = takeStep();
            console.log(step);
            const data = extractFormInputValues(forms[step]);
            saveFormDataToCookies(data);
        });
    }
});
