/**
 * Form Processor - Main entry point for form interactions
 *
 * This module initializes all form-related managers and coordinates their interactions.
 * It has been refactored from a monolithic 500+ line file into modular components for
 * better maintainability and separation of concerns.
 */

import {
    FormHandler,
    PetPillManager,
    PopupManager,
    InventoryFormManager,
    HealthFormManager,
    NavigationManager,
    SubmissionManager
} from "./managers/index.js";
import { FormDataManager } from "./FormDataManager.js";
import config from "./config.js";

const { FORM_CONFIG } = config;

document.addEventListener("DOMContentLoaded", function () {
    // Check if phone number from URL differs from stored cookie data
    const urlParams = new URLSearchParams(window.location.search);
    const phoneFromUrl = urlParams.get('phone');
    const existingData = FormDataManager.getCheckinData();

    // If phone from URL differs from phone in cookie (if cookie exists), clear the form data
    if (phoneFromUrl && existingData && existingData.user && existingData.user.info &&
        existingData.user.info.phone !== phoneFromUrl) {
            console.log(existingData);
        FormDataManager.clearCheckinData();
    }

    // Check for session data to pre-populate (for editing existing check-ins)
    // Note: This is handled server-side in the Blade template, so no client-side logic needed here

    // Initialize all form managers
    PetPillManager.addPetPillsToContainer();
    FormHandler.populateFormWithCookies();

    // Initialize popup handlers
    PopupManager.initializeFeedingMedicationPopup();
    PopupManager.initializeGroomingPopup();
    PopupManager.initializeTermsPopup();

    // Initialize form-specific handlers
    InventoryFormManager.initializeInventoryForm();
    HealthFormManager.initializeHealthForm();

    // Initialize navigation
    NavigationManager.updateTabbarForStep();

    // Initialize navigation reactivity for dynamic button state updates
    NavigationManager.initializeNavigationReactivity();

    // Handle final submission from THANKS step
    const finalSubmitButton = document.querySelector("#finalSubmit");
    if (finalSubmitButton) {
        finalSubmitButton.addEventListener("click", function () {
            SubmissionManager.submitFinalCheckIn();
        });
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

    // Handle pet info form submission
    const petInfoForm = document.querySelector("#petInfoForm");
    if (petInfoForm) {
        petInfoForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const data = FormHandler.extractFormInputValues(petInfoForm);

            // Use the same method as the "next" button for consistency
            FormDataManager.handleFormStep(1, data, null); // step 1 = PET_INFO, selectedPetIndex = null to add new
            petInfoForm.reset();
            scrollTo({ top: 0, behavior: "smooth" });
            setTimeout(() => {
                PetPillManager.addPetPillsToContainer();
            }, 500);
        });
    }

    // Handle next step navigation
    const nextButton = document.querySelector("#nextStep");
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            const step = NavigationManager.getCurrentStep();
            const forms = FormHandler.getForms();
            const data = FormHandler.extractFormInputValues(forms[step]);

            const selectedPetIndex = PetPillManager.getSelectedPetIndex();

            const success = SubmissionManager.handleNextStep(step, data, selectedPetIndex);

            // Note: Automatic submission removed. Final submission should only happen
            // when explicitly triggered from a submit button (e.g., in THANKS step)
            // The next button should only handle step navigation, not final submission.
        });
    }
});

window.debugCookies = () => {
    return FormDataManager.debugCheckinData();
};
