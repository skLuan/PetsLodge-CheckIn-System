/**
 * SubmissionManager - Manages sequential form submission and check-in process
 *
 * This manager handles the sequential submission of user and pet data to the server,
 * including validation, API calls, cookie management, and comprehensive debug logging.
 * The process is divided into 5 steps for better error handling and user experience.
 */

import { FormDataManager } from "../FormDataManager.js";
import { CookieManager } from "../CookieManager.js";

class SubmissionManager {
    /**
     * Main sequential submission method - orchestrates all 5 steps
     * @returns {Promise<void>}
     */
    static async submitSequentialCheckIn() {
        const startTime = new Date().toISOString();
        console.log(`🚀 [${startTime}] Starting sequential check-in submission...`);

        try {
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

            // Step 1: Send User Info
            console.log(`📝 [${new Date().toISOString()}] Step 1: Sending user info...`);
            const userResult = await this.submitUserInfo(checkinData.user.info);
            if (!userResult.success) {
                throw new Error(`Step 1 failed: ${userResult.message}`);
            }

            // Store user ID in cookie
            CookieManager.setCookie("user.id", userResult.data.user_id);
            console.log(`💾 [${new Date().toISOString()}] User ID stored in cookie: ${userResult.data.user_id}`);

            // Step 2: Send Pet Info (assuming first pet for now)
            const petData = checkinData.pets[0]; // TODO: Handle multiple pets
            console.log(`🐾 [${new Date().toISOString()}] Step 2: Sending pet info for ${petData.info.petName}...`);
            const petResult = await this.submitPetInfo(userResult.data.user_id, petData.info);
            if (!petResult.success) {
                throw new Error(`Step 2 failed: ${petResult.message}`);
            }

            // Store pet ID in cookie
            CookieManager.setCookie("pet.info.petid", petResult.data.pet_id);
            console.log(`💾 [${new Date().toISOString()}] Pet ID stored in cookie: ${petResult.data.pet_id}`);

            // Step 3: Send Pet Health, Feeding, and Medication
            console.log(`🏥 [${new Date().toISOString()}] Step 3: Sending pet health data...`);
            const healthResult = await this.submitPetHealth(
                petResult.data.pet_id,
                petData.health || {},
                petData.feeding || [],
                petData.medication || []
            );
            if (!healthResult.success) {
                throw new Error(`Step 3 failed: ${healthResult.message}`);
            }

            // Step 4: Send Check-In Data
            console.log(`📋 [${new Date().toISOString()}] Step 4: Sending check-in data...`);
            const checkinResult = await this.submitCheckInData(petResult.data.pet_id, checkinData);
            if (!checkinResult.success) {
                throw new Error(`Step 4 failed: ${checkinResult.message}`);
            }

            // Store check-in ID in cookie
            CookieManager.setCookie("dbCheckInId", checkinResult.data.checkin_id);
            console.log(`💾 [${new Date().toISOString()}] Check-in ID stored in cookie: ${checkinResult.data.checkin_id}`);

            // Step 5: Send Extra Info
            console.log(`📦 [${new Date().toISOString()}] Step 5: Sending extra info...`);
            const extraResult = await this.submitExtraInfo(checkinResult.data.checkin_id, {
                inventory: checkinData.inventory || [],
                grooming: checkinData.grooming || {}
            });
            if (!extraResult.success) {
                throw new Error(`Step 5 failed: ${extraResult.message}`);
            }

            const endTime = new Date().toISOString();
            console.log(`✅ [${endTime}] Sequential check-in completed successfully!`);

            // Clear the cookie after successful submission
            FormDataManager.clearCheckinData();

            // Show success message
            alert("Check-in completed successfully!");

            // Redirect to success page or dashboard
            window.location.href = `/view-check-in?phone=${checkinData.user?.info?.phone}`;

        } catch (error) {
            const errorTime = new Date().toISOString();
            console.error(`❌ [${errorTime}] Sequential submission failed:`, error);

            // Re-enable button
            const submitButton = document.querySelector("#finalSubmit") || document.querySelector("#nextStep");
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = "Submit Check-in";
            }

            alert(`An error occurred during submission: ${error.message}`);
        }
    }

    /**
     * Step 1: Submit User Info (create or verify user)
     * @param {Object} userInfo - User information object
     * @returns {Promise<Object>} API response
     */
    static async submitUserInfo(userInfo) {
        const timestamp = new Date().toISOString();
        console.log(`👤 [${timestamp}] Step 1 - Submitting user info:`, {
            phone: userInfo.phone,
            name: userInfo.name,
            email: userInfo.email
        });

        try {
            const response = await fetch('/api/checkin/step1/user-info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    user_info: userInfo
                })
            });

            const result = await response.json();
            console.log(`📨 [${new Date().toISOString()}] Step 1 API Response:`, result);

            if (!result.success) {
                console.error(`❌ [${new Date().toISOString()}] Step 1 failed:`, result);
            }

            return result;
        } catch (error) {
            console.error(`❌ [${new Date().toISOString()}] Step 1 network error:`, error);
            throw error;
        }
    }

    /**
     * Step 2: Submit Pet Info
     * @param {number} userId - User ID from step 1
     * @param {Object} petInfo - Pet information object
     * @returns {Promise<Object>} API response
     */
    static async submitPetInfo(userId, petInfo) {
        const timestamp = new Date().toISOString();
        console.log(`🐕 [${timestamp}] Step 2 - Submitting pet info:`, {
            user_id: userId,
            pet_name: petInfo.petName,
            pet_type: petInfo.petType,
            pet_breed: petInfo.petBreed
        });

        try {
            const response = await fetch('/api/checkin/step2/pet-info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    user_id: userId,
                    pet_info: petInfo
                })
            });

            const result = await response.json();
            console.log(`📨 [${new Date().toISOString()}] Step 2 API Response:`, result);

            if (!result.success) {
                console.error(`❌ [${new Date().toISOString()}] Step 2 failed:`, result);
            }

            return result;
        } catch (error) {
            console.error(`❌ [${new Date().toISOString()}] Step 2 network error:`, error);
            throw error;
        }
    }

    /**
     * Step 3: Submit Pet Health, Feeding, and Medication
     * @param {number} petId - Pet ID from step 2
     * @param {Object} healthData - Health information
     * @param {Array} feedingData - Feeding schedule
     * @param {Array} medicationData - Medication schedule
     * @returns {Promise<Object>} API response
     */
    static async submitPetHealth(petId, healthData, feedingData, medicationData) {
        const timestamp = new Date().toISOString();
        console.log(`💊 [${timestamp}] Step 3 - Submitting pet health:`, {
            pet_id: petId,
            feeding_count: feedingData.length,
            medication_count: medicationData.length,
            has_warnings: !!healthData.warnings
        });

        try {
            const response = await fetch('/api/checkin/step3/pet-health', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    pet_id: petId,
                    health_data: healthData,
                    feeding_data: feedingData,
                    medication_data: medicationData
                })
            });

            const result = await response.json();
            console.log(`📨 [${new Date().toISOString()}] Step 3 API Response:`, result);

            if (!result.success) {
                console.error(`❌ [${new Date().toISOString()}] Step 3 failed:`, result);
            }

            return result;
        } catch (error) {
            console.error(`❌ [${new Date().toISOString()}] Step 3 network error:`, error);
            throw error;
        }
    }

    /**
     * Step 4: Submit Check-In Data
     * @param {number} petId - Pet ID from step 2
     * @param {Object} checkinData - Complete check-in data
     * @returns {Promise<Object>} API response
     */
    static async submitCheckInData(petId, checkinData) {
        const timestamp = new Date().toISOString();
        console.log(`🏨 [${timestamp}] Step 4 - Submitting check-in data:`, {
            pet_id: petId,
            has_inventory: !!(checkinData.inventory && checkinData.inventory.length),
            has_grooming: !!(checkinData.grooming && Object.keys(checkinData.grooming).length)
        });

        try {
            const response = await fetch('/api/checkin/step4/checkin-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    pet_id: petId,
                    checkin_data: checkinData
                })
            });

            const result = await response.json();
            console.log(`📨 [${new Date().toISOString()}] Step 4 API Response:`, result);

            if (!result.success) {
                console.error(`❌ [${new Date().toISOString()}] Step 4 failed:`, result);
            }

            return result;
        } catch (error) {
            console.error(`❌ [${new Date().toISOString()}] Step 4 network error:`, error);
            throw error;
        }
    }

    /**
     * Step 5: Submit Extra Info
     * @param {number} checkinId - Check-in ID from step 4
     * @param {Object} extraData - Extra information (inventory, grooming)
     * @returns {Promise<Object>} API response
     */
    static async submitExtraInfo(checkinId, extraData) {
        const timestamp = new Date().toISOString();
        console.log(`📋 [${timestamp}] Step 5 - Submitting extra info:`, {
            checkin_id: checkinId,
            inventory_count: extraData.inventory ? extraData.inventory.length : 0,
            grooming_services: extraData.grooming ? Object.keys(extraData.grooming) : []
        });

        try {
            const response = await fetch('/api/checkin/step5/extra-info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    checkin_id: checkinId,
                    extra_data: extraData
                })
            });

            const result = await response.json();
            console.log(`📨 [${new Date().toISOString()}] Step 5 API Response:`, result);

            if (!result.success) {
                console.error(`❌ [${new Date().toISOString()}] Step 5 failed:`, result);
            }

            return result;
        } catch (error) {
            console.error(`❌ [${new Date().toISOString()}] Step 5 network error:`, error);
            throw error;
        }
    }

    /**
     * Legacy method for backward compatibility - redirects to new sequential method
     * @returns {Promise<void>}
     */
    static async submitFinalCheckIn() {
        console.warn("⚠️ submitFinalCheckIn is deprecated. Using new sequential submission method.");
        return this.submitSequentialCheckIn();
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