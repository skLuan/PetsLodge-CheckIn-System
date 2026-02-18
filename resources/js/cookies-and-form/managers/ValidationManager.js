import config from "../config.js";
import { CoreDataManager } from "./CoreDataManager.js";
import { PetManager } from "./PetManager.js";
import { FormDataManager } from "../FormDataManager.js";

const { FORM_CONFIG } = config;

/**
 * Validation Manager - Handles step-specific validations and form checks
 */
class ValidationManager {
    /**
     * Método principal para manejar datos de formulario según el paso actual
     */
    static handleFormStep(step, formData, selectedPetIndex = null) {
        console.log(`Handling form step ${step} with data:`, formData);

        switch (step) {
            case FORM_CONFIG.STEPS.OWNER_INFO - 1: // step 0 = OWNER_INFO
                return this.updateUserInfo(formData);

            case FORM_CONFIG.STEPS.PET_INFO - 1: // step 1 = PET_INFO
                if (selectedPetIndex !== null) {
                    return this.updatePetInCheckin(selectedPetIndex, formData);
                } else {
                    return this.addPetToCheckin(formData);
                }

            case FORM_CONFIG.STEPS.FEEDING_MEDICATION - 1: // step 2 = FEEDING_MEDICATION
                // Este paso se maneja desde los popups específicos
                return true;

            case FORM_CONFIG.STEPS.HEALTH_INFO - 1: // step 3 = HEALTH_INFO
                // Health info applies to all pets or selected pet
                const healthData = {
                    unusualHealthBehavior: formData.unusualHealthBehavior === 'yes',
                    healthBehaviors: formData.healthBehaviorDetails || '',
                    warnings: formData.warnings || '',
                };

                if (selectedPetIndex !== null) {
                    this.updatePetHealthInfo(selectedPetIndex, healthData);
                } else {
                    // Apply to all pets if none selected
                    const checkinData = CoreDataManager.getCheckinData();
                    if (checkinData && checkinData.pets) {
                        checkinData.pets.forEach((_, index) => {
                            this.updatePetHealthInfo(index, healthData);
                        });
                    }
                }

                // Handle grooming data (global, not per-pet)
                const groomingData = {};
                if (formData.grooming && Array.isArray(formData.grooming)) {
                    formData.grooming.forEach(service => {
                        groomingData[service] = true;
                    });
                }

                // Preserve existing inventory data - don't overwrite with empty array
                const existingData = CoreDataManager.getCheckinData();
                const existingInventory = existingData?.inventory || [];
                this.updateGroomingAndInventory(groomingData, existingInventory, formData.groomingDetails || '');

                return true;

            case FORM_CONFIG.STEPS.INVENTORY - 1: // step 4 = INVENTORY
                // Inventory is managed separately via add/remove and checkbox
                // Validate both inventory completion and terms acceptance
                const checkinData = CoreDataManager.getCheckinData();
                const hasItems = checkinData?.inventory?.length > 0;
                const isComplete = checkinData?.inventoryComplete;
                const groomingAcknowledged = checkinData?.groomingAcknowledged;
                const termsAccepted = checkinData?.termsAccepted;

                console.log('🔍 [ValidationManager] Inventory step validation:', {
                    hasItems,
                    isComplete,
                    groomingAcknowledged,
                    termsAccepted
                });

                if (!hasItems && !isComplete) {
                    alert('Please add inventory items or confirm you are not leaving anything in inventory.');
                    return false;
                }

                // Show grooming popup first if not acknowledged
                if (!groomingAcknowledged) {
                    console.log('📢 [ValidationManager] Showing grooming popup');
                    setTimeout(() => {
                        const groomingPopup = document.getElementById('groomingPopup');
                        if (groomingPopup) {
                            groomingPopup.classList.remove('hidden');
                        }
                    }, 50);
                    return false;
                }

                // Show terms popup only after grooming is acknowledged
                if (!termsAccepted) {
                    console.log('📢 [ValidationManager] Showing terms popup');
                    setTimeout(() => {
                        this.showTermsPopup();
                    }, 50);
                    return false;
                }

                return true;

            default:
                console.log("No specific handler for step:", step);
                return true;
        }
    }

    /**
     * Actualiza información del usuario (paso 1)
     */
    static updateUserInfo(userData) {
        const userUpdate = {
            user: {
                info: {
                    ...userData,
                },
            },
        };

        if (userData.emergencyContactName || userData.emergencyContactPhone) {
            userUpdate.user.emergencyContact = {
                name: userData.emergencyContactName || "",
                phone: userData.emergencyContactPhone || "",
            };
        }

        return FormDataManager.updateCheckinData(userUpdate);
    }

    /**
     * Muestra el popup de términos y condiciones
     */
    static showTermsPopup() {
        const popup = document.getElementById('termsConditionsPopup');
        if (popup) {
            popup.classList.remove('hidden');
        }
    }

    /**
     * Oculta el popup de términos y condiciones
     */
    static hideTermsPopup() {
        const popup = document.getElementById('termsConditionsPopup');
        if (popup) {
            popup.classList.add('hidden');
        }
    }

    /**
     * Marca los términos como aceptados
     */
    static setTermsAccepted(accepted) {
        return FormDataManager.updateCheckinData({
            termsAccepted: accepted
        });
    }

    // Delegate to other managers
    static addPetToCheckin(petData) {
        return PetManager.addPetToCheckin(petData);
    }

    static updatePetInCheckin(petIndex, petData) {
        return PetManager.updatePetInCheckin(petIndex, petData);
    }

    static updatePetHealthInfo(petIndex, healthData) {
        return PetManager.updatePetHealthInfo(petIndex, healthData);
    }

    static updateGroomingAndInventory(groomingData, inventoryData, groomingDetails) {
        return FormDataManager.updateCheckinData({
            grooming: { ...groomingData },
            inventory: inventoryData || [],
            groomingDetails: groomingDetails,
        });
    }

    // Legacy compatibility
    static saveFormDataToStep(data, step = null) {
        console.warn("saveFormDataToStep is deprecated, use handleFormStep instead");
        return this.handleFormStep(step - 1, data); // Adjust for 0-based indexing
    }
}

export { ValidationManager };