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
import { CoreDataManager } from "./managers/CoreDataManager.js";
import { FormDataManager } from "./FormDataManager.js";
import { CheckInSummaryUpdater } from "./managers/CheckInSummaryUpdater.js";
import config from "./config.js";

const { FORM_CONFIG } = config;

document.addEventListener("DOMContentLoaded", async function () {
     // ========================================
     // PHASE 2.1: EDITING MODE INITIALIZATION
     // ========================================
     // CRITICAL FIX #1: Extract editing mode flags BEFORE FormDataManager.initialize()
     // This ensures editing mode is preserved during cookie initialization
     
     const stepContainer = document.querySelector('[data-session-checkin]');
     let isEditingMode = false;
     let editingCheckInId = null;
     let sessionData = null;

     if (stepContainer) {
         // Check for editing mode flags
         const editingModeAttr = stepContainer.getAttribute('data-editing-mode');
         const editingCheckInIdAttr = stepContainer.getAttribute('data-editing-check-in-id');
         
         isEditingMode = editingModeAttr === 'true' || editingModeAttr === '1';
         editingCheckInId = editingCheckInIdAttr;

         // Extract session data from DOM data attribute (for editing existing check-ins)
         const sessionDataStr = stepContainer.getAttribute('data-session-checkin');
         if (sessionDataStr && sessionDataStr !== 'null' && sessionDataStr.trim() !== '') {
             try {
                 // Decode HTML entities that may have been escaped
                 const decodedStr = sessionDataStr
                     .replace(/&quot;/g, '"')
                     .replace(/&#039;/g, "'")
                     .replace(/&amp;/g, '&')
                     .replace(/&lt;/g, '<')
                     .replace(/&gt;/g, '>');
                 
                 sessionData = JSON.parse(decodedStr);
             } catch (e) {
                 console.warn('Session data not available or invalid:', e.message);
             }
         }
     }

     // CRITICAL FIX #1: Initialize FormDataManager FIRST (creates cookie if needed)
     // This must happen before merging session data or setting editing mode
     await FormDataManager.initialize();

     // CRITICAL FIX #1: THEN merge session data and set editing mode
     // This ensures editing mode is preserved after initialization
     if (sessionData && Object.keys(sessionData).length > 0) {
         FormDataManager.mergeSessionDataIntoCookie(sessionData);
         console.log('Session data merged into cookie for pre-population:', sessionData);

         // If in editing mode, enable editing mode tracking
         if (isEditingMode && editingCheckInId) {
             FormDataManager.setEditingMode(editingCheckInId, sessionData);
             console.log(`✏️ Editing mode enabled for check-in #${editingCheckInId}`);
         }
     }

     // Check if phone number from URL differs from stored cookie data
     const urlParams = new URLSearchParams(window.location.search);
     const phoneFromUrl = urlParams.get('phone');
     const existingData = FormDataManager.getCheckinData();

     // If phone from URL differs from phone in cookie (if cookie exists), clear the form data
     // UNLESS we're in editing mode (in which case we want to keep the pre-populated data)
     if (phoneFromUrl && existingData && existingData.user && existingData.user.info &&
         existingData.user.info.phone !== phoneFromUrl && !isEditingMode) {
             console.log(existingData);
         FormDataManager.clearCheckinData();
     }

    // Initialize popup handlers FIRST (before form managers)
    // This ensures popups are ready before any form initialization
    PopupManager.initializeFeedingMedicationPopup();
    PopupManager.initializeGroomingPopup();
    PopupManager.initializeTermsPopup();

    // Initialize check-in summary updater
    // This ensures the summary component stays synchronized with cookie data
    CheckInSummaryUpdater.initialize();

    // Initialize all form managers
    PetPillManager.addPetPillsToContainer();
    FormHandler.populateFormWithCookies();

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
            SubmissionManager.submitSequentialCheckIn();
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
