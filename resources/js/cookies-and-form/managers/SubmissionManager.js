/**
 * SubmissionManager - Manages form submission and final check-in process
 *
 * This manager handles the final form submission to the server, including
 * validation, API calls, and success/error handling.
 */

import { FormDataManager } from "../FormDataManager.js";

class SubmissionManager {
    /**
     * Submits the final check-in data to the server
     * @returns {Promise<void>}
     */
    static async submitFinalCheckIn() {
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

    /**
     * Handles next step navigation and form processing
     * @param {number} step - Current step index
     * @param {Object} data - Form data from current step
     * @param {number|null} selectedPetIndex - Index of selected pet
     * @returns {boolean} True if navigation should proceed
     */
    static handleNextStep(step, data, selectedPetIndex) {
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
                PetPillManager.addPetPillsToContainer();
            }
        } else {
            // For other steps, proceed normally
            success = FormDataManager.handleFormStep(step, data, selectedPetIndex);
        }

        return success;
    }
}

// Import required modules for handleNextStep
import config from "../config.js";
import { PetPillManager } from "./PetPillManager.js";

const { FORM_CONFIG } = config;

export { SubmissionManager };