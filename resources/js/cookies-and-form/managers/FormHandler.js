/**
 * FormHandler - Manages form data extraction, population, and general form operations
 *
 * This manager handles all form-related operations including extracting input values,
 * populating forms with data, and managing form state across the multi-step process.
 */

import { FormDataManager } from "../FormDataManager.js";
import Utils from "../../Utils.js";

class FormHandler {
    /**
     * Extracts input values from a form element
     * @param {HTMLElement|string} formElementOrSelector - Form element or CSS selector
     * @returns {Object} Object containing form data
     */
    static extractFormInputValues(formElementOrSelector) {
        console.log("Extracting form input values...");
        let formElement = formElementOrSelector;
        if (typeof formElementOrSelector === "string") {
            formElement = document.querySelector(formElementOrSelector);
        }

        if (!formElement) {
            console.warn("Form element not found:", formElementOrSelector);
            return {};
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

        console.log("Extracted form input values:", data);
        return data;
    }

    /**
     * Populates the current step's form with data from cookies
     * Only populates empty fields to avoid overwriting user input
     */
    static populateFormWithCookies() {
        const currentStep = Utils.actualStep();
        const forms = Array.from(document.querySelectorAll(".step")).map((form) => form.querySelector("form"));
        const currentForm = forms[currentStep];

        if (!currentForm) return;

        const checkinData = FormDataManager.getCheckinData();
        console.log("Populating current form with cookies (step " + currentStep + "):", checkinData);

        if (!checkinData) return;

        let tempData = {};

        if (currentStep === 0) { // Owner info step
            tempData = checkinData.user?.info || {};
        } else if (currentStep === 1) { // Pet info step
            const selectedPetIndex = document.querySelector(".pill.selected")
                ? parseInt(document.querySelector(".pill.selected").dataset.index, 10)
                : 0;
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

    /**
     * Gets all form elements from the page
     * @returns {HTMLFormElement[]} Array of form elements
     */
    static getForms() {
        return Array.from(document.querySelectorAll(".step")).map((form) => form.querySelector("form"));
    }
}

export { FormHandler };