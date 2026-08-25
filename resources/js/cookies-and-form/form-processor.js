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
    SubmissionManager,
    FastCheckinManager
} from "./managers/index.js";
import { FormDataManager } from "./FormDataManager.js";
import { FormUpdater } from "./reactivitySystem/FormUpdater.js";
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

    // Clean up any empty/incomplete pet entries left in the cookie
    FormDataManager.cleanEmptyPets();

    // Initialize popup handlers FIRST (before form managers)
    // This ensures popups are ready before any form initialization
    PopupManager.initializeFeedingMedicationPopup();
    PopupManager.initializeGroomingPopup();
    PopupManager.initializeTermsPopup();

    // Note: CheckInSummaryUpdater is not used here.
    // Summary is rendered by SummaryRenderer via UIManager when entering step 6,
    // and also re-rendered on any cookie change while on step 6.

    // Initialize all form managers
    PetPillManager.addPetPillsToContainer();
    FormHandler.populateFormWithCookies();

    // Initialize fast check-in pills for existing user pets
    FastCheckinManager.initialize();

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
    // Pet form: submit (add a new pet or update the selected one)
    //------------------------------------------------
    const setPetSubmitButtonLabel = (editing) => {
        const addPetBtn = document.getElementById("addPetBtn");
        if (addPetBtn) {
            addPetBtn.textContent = editing ? "Save Pet" : "Add Pet";
        }
    };

    const petInfoForm = document.querySelector("#petInfoForm");
    if (petInfoForm) {
        petInfoForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const data = FormHandler.extractFormInputValues(petInfoForm);

            // Guard: skip saving if the pet has no meaningful data (all fields empty)
            const KEY_FIELDS = ['petName', 'petColor', 'petType', 'petBreed', 'petAge', 'petWeight', 'petGender', 'petSpayed'];
            const hasData = KEY_FIELDS.some(field => data[field] !== undefined && data[field] !== null && data[field] !== '');
            if (!hasData) {
                console.warn('[petInfoForm] Skipped saving: all pet fields are empty.');
                return;
            }

            // If a pet pill is selected, update that pet; otherwise add a new one.
            const selectedIndex = FormDataManager.getCurrentSelectedPetIndex();
            FormDataManager.handleFormStep(1, data, selectedIndex);

            // Clear the form and deselect so the next submit adds a new pet again.
            petInfoForm.reset();
            document.querySelectorAll("#petPillsContainer .pill.selected")
                .forEach((p) => p.classList.remove("selected"));
            setPetSubmitButtonLabel(false);

            scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    //------------------------------------------------
    // Delete pet confirmation modal
    //------------------------------------------------
    let pendingDeletePetIndex = null;
    const confirmDeleteModal = document.getElementById("confirmDeleteModal");
    const confirmDeletePetName = document.getElementById("confirmDeletePetName");

    document.addEventListener("pet:delete-request", function (e) {
        pendingDeletePetIndex = e.detail.index;
        if (confirmDeletePetName) confirmDeletePetName.textContent = e.detail.name;
        if (confirmDeleteModal) confirmDeleteModal.classList.remove("hidden");
    });

    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener("click", function () {
            if (pendingDeletePetIndex !== null) {
                FormDataManager.removePetFromCheckin(pendingDeletePetIndex);
            }
            pendingDeletePetIndex = null;
            if (confirmDeleteModal) confirmDeleteModal.classList.add("hidden");
        });
    }

    const confirmDeleteCancel = document.getElementById("confirmDeleteCancel");
    if (confirmDeleteCancel) {
        confirmDeleteCancel.addEventListener("click", function () {
            pendingDeletePetIndex = null;
            if (confirmDeleteModal) confirmDeleteModal.classList.add("hidden");
        });
    }

    //------------------------------------------------
    // Pet pill selection: load the selected pet into the form for editing
    //------------------------------------------------
    document.addEventListener("pet:select-request", function (e) {
        const { index, selected } = e.detail;
        const form = document.getElementById("petInfoForm");
        const currentStep = NavigationManager.getCurrentStep();

        if (selected) {
            const pets = FormDataManager.getAllPetsFromCheckin();
            const pet = pets[index];

            if (currentStep === FORM_CONFIG.STEPS.PET_INFO - 1) {
                // Pet info step: load the selected pet (info + health) into the form.
                if (pet && form) {
                    form.reset();
                    FormUpdater.updatePetForm(pet);
                    HealthFormManager.loadPetHealth(index);
                }
                setPetSubmitButtonLabel(true);
            }
        } else {
            if (form) {
                form.reset();
                HealthFormManager.loadPetHealth(null);
                setPetSubmitButtonLabel(false);
            }
        }

        NavigationManager.syncNowEditingLabel();
    });

    // Handle next step navigation
    const nextButton = document.querySelector("#nextStep");
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            const step = NavigationManager.getCurrentStep();
            const forms = FormHandler.getForms();
            const data = FormHandler.extractFormInputValues(forms[step]);

            const success = SubmissionManager.handleNextStep(step, data, null);

            // Note: Automatic submission removed. Final submission should only happen
            // when explicitly triggered from a submit button (e.g., in THANKS step)
            // The next button should only handle step navigation, not final submission.
        });
    }
});

window.debugCookies = () => {
    return FormDataManager.debugCheckinData();
};
