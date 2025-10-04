import { CookieManager } from "./CookieManager.js";
import {
    FORM_CONFIG,
    DEFAULT_CHECKIN_STRUCTURE,
    DEFAULT_PET_STRUCTURE,
} from "./config.js";
import Utils from "./Utils.js";

class FormDataManager {
    static CHECKIN_COOKIE_NAME = "checkin_data";
    static AUTO_SAVE_INTERVAL = 30000; // 30 segundos
    static autoSaveTimer = null;

    /**
     * Inicializa el sistema de manejo de datos
     * Debe llamarse al inicio de la aplicación
     */
    static initialize() {
        // Crear cookie checkin si no existe
        if (!this.getCheckinData()) {
            this.createInitialCheckin();
        }

        // Iniciar auto-save
        this.startAutoSave();

        console.log(
            "FormDataManager initialized with checkin data:",
            this.getCheckinData()
        );
    }

    /**
     * Crea el cookie checkin inicial con la estructura completa
     */
    static createInitialCheckin() {
        const initialCheckin = {
            ...DEFAULT_CHECKIN_STRUCTURE,
            date: new Date().toISOString(),
            id: this.generateCheckinId(),
        };

        const success = CookieManager.setCookie(
            this.CHECKIN_COOKIE_NAME,
            initialCheckin,
            FORM_CONFIG.DEFAULT_COOKIE_DAYS
        );

        if (success) {
            console.log("Initial checkin data created:", initialCheckin);
        } else {
            console.error("Failed to create initial checkin data");
        }

        return success;
    }

    /**
     * Obtiene los datos completos del checkin desde la cookie
     */
    static getCheckinData() {
        return CookieManager.getCookie(this.CHECKIN_COOKIE_NAME);
    }

    /**
     * Actualiza los datos del checkin en la cookie
     */
    static updateCheckinData(updates) {
        const currentData = this.getCheckinData();
        if (!currentData) {
            console.warn("No checkin data found, creating initial data");
            this.createInitialCheckin();
            return this.updateCheckinData(updates);
        }

        const updatedData = this.deepMerge(currentData, updates);
        updatedData.lastUpdated = new Date().toISOString();

        const success = CookieManager.setCookie(
            this.CHECKIN_COOKIE_NAME,
            updatedData,
            FORM_CONFIG.DEFAULT_COOKIE_DAYS
        );

        if (success) {
            console.log("Checkin data updated:", updatedData);
        }

        return success;
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

        return this.updateCheckinData(userUpdate);
    }

    /**
     * Agrega una nueva mascota al checkin
     */
    static addPetToCheckin(petData) {
        const currentData = this.getCheckinData();
        if (!currentData) return false;

        const newPet = {
            ...DEFAULT_PET_STRUCTURE,
            info: {
                ...DEFAULT_PET_STRUCTURE.info,
                ...petData,
            },
            id: this.generatePetId(),
        };

        const updatedPets = [...(currentData.pets || []), newPet];

        return this.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza una mascota específica en el checkin
     */
    static updatePetInCheckin(petIndex, petData) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found`);
            return false;
        }

        const updatedPets = [...currentData.pets];

        // Merge de datos de la mascota
        updatedPets[petIndex] = this.deepMerge(updatedPets[petIndex], {
            info: { ...petData },
            lastUpdated: new Date().toISOString(),
        });

        return this.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Agrega alimentación o medicamento a una mascota específica
     */
    static addPetFeedingOrMedication(petIndex, type, data) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found`);
            return false;
        }

        const updatedPets = [...currentData.pets];
        const pet = { ...updatedPets[petIndex] };

        if (type === "feeding") {
            pet.feeding = [...(pet.feeding || []), data];
        } else if (type === "medication") {
            pet.medication = [...(pet.medication || []), data];
        }

        pet.lastUpdated = new Date().toISOString();
        updatedPets[petIndex] = pet;

        return this.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza información de salud de una mascota
     */
    static updatePetHealth(petIndex, healthData) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found`);
            return false;
        }

        const updatedPets = [...currentData.pets];
        updatedPets[petIndex] = this.deepMerge(updatedPets[petIndex], {
            health: { ...healthData },
            lastUpdated: new Date().toISOString(),
        });

        return this.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza información de grooming e inventory
     */
    static updateGroomingAndInventory(
        groomingData,
        inventoryData,
        groomingDetails = ""
    ) {
        return this.updateCheckinData({
            grooming: { ...groomingData },
            inventory: inventoryData || [],
            groomingDetails: groomingDetails,
        });
    }

    /**
     * Obtiene todas las mascotas en formato compatible con el código legacy
     */
    static getAllPetsFromCheckin() {
        const checkinData = this.getCheckinData();
        if (!checkinData || !checkinData.pets) return [];

        return checkinData.pets.map((pet, index) => ({
            index: index,
            // Propiedades planas para compatibilidad
            petName: pet.info?.petName || "",
            petType: pet.info?.petType || "",
            petColor: pet.info?.petColor || "",
            petBreed: pet.info?.petBreed || "",
            petAge: pet.info?.petAge || "",
            petWeight: pet.info?.petWeight || "",
            petGender: pet.info?.petGender || "",
            petSpayed: pet.info?.petSpayed || "",
            // Estructura completa
            ...pet,
        }));
    }

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
                if (selectedPetIndex !== null) {
                    return this.updatePetHealth(selectedPetIndex, formData);
                } else {
                    // Aplicar a todas las mascotas si no hay una seleccionada
                    const checkinData = this.getCheckinData();
                    if (checkinData && checkinData.pets) {
                        checkinData.pets.forEach((_, index) => {
                            this.updatePetHealth(index, formData);
                        });
                    }
                    return true;
                }

            case FORM_CONFIG.STEPS.INVENTORY - 1: // step 4 = INVENTORY
                return this.updateGroomingAndInventory(
                    formData.grooming,
                    formData.inventory,
                    formData.groomingDetails
                );

            default:
                console.log("No specific handler for step:", step);
                return true;
        }
    }

    /**
     * Inicia el auto-guardado automático
     */
    static startAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }

        this.autoSaveTimer = setInterval(() => {
            this.performAutoSave();
        }, this.AUTO_SAVE_INTERVAL);

        console.log(
            `Auto-save started with ${this.AUTO_SAVE_INTERVAL / 1000}s interval`
        );
    }

    /**
     * Detiene el auto-guardado automático
     */
    static stopAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
            this.autoSaveTimer = null;
            console.log("Auto-save stopped");
        }
    }

    /**
     * Realiza el auto-guardado (preparado para futura implementación con BD)
     */
    static performAutoSave() {
        const checkinData = this.getCheckinData();
        if (!checkinData) return;

        console.log("🔄 Auto-save triggered at:", new Date().toISOString());

        // TODO: Implementar envío a base de datos
        /*
        try {
            // Ejemplo de implementación futura:
            const response = await fetch('/api/checkins/autosave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    checkin_id: checkinData.id,
                    data: checkinData,
                    is_autosave: true
                })
            });
            
            if (response.ok) {
                console.log('✅ Auto-save successful');
            } else {
                console.warn('⚠️ Auto-save failed:', response.status);
            }
        } catch (error) {
            console.error('❌ Auto-save error:', error);
        }
        */

        // Por ahora solo actualizar timestamp
        this.updateCheckinData({ autoSavedAt: new Date().toISOString() });
    }

    /**
     * Finaliza el checkin y prepara para envío final
     */
    static finalizeCheckin() {
        const checkinData = this.getCheckinData();
        if (!checkinData) {
            console.error("No checkin data to finalize");
            return null;
        }

        // Detener auto-save
        this.stopAutoSave();

        // Marcar como completado
        const finalData = {
            ...checkinData,
            completedAt: new Date().toISOString(),
            status: "completed",
        };

        this.updateCheckinData(finalData);

        console.log("✅ Checkin finalized:", finalData);
        return finalData;
    }

    /**
     * Limpia todos los datos del checkin
     */
    static clearCheckinData() {
        this.stopAutoSave();
        CookieManager.deleteCookie(this.CHECKIN_COOKIE_NAME);
        console.log("🗑️ Checkin data cleared");
    }

    // Métodos de utilidad
    static generateCheckinId() {
        return (
            "checkin_" +
            Date.now() +
            "_" +
            Math.random().toString(36).substr(2, 9)
        );
    }

    static generatePetId() {
        return (
            "pet_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
        );
    }

    static deepMerge(target, source) {
        const result = { ...target };

        for (const key in source) {
            if (source.hasOwnProperty(key)) {
                if (
                    source[key] &&
                    typeof source[key] === "object" &&
                    !Array.isArray(source[key])
                ) {
                    result[key] = this.deepMerge(
                        result[key] || {},
                        source[key]
                    );
                } else {
                    result[key] = source[key];
                }
            }
        }

        return result;
    }

    /**
     * Métodos de compatibilidad con código legacy
     */
    static getFormDataFromStep(step) {
        console.warn(
            "getFormDataFromStep is deprecated, use getCheckinData instead"
        );
        return this.getCheckinData();
    }

    static saveFormDataToStep(data, step = null) {
        if (!data || typeof data !== "object") {
            console.warn("Invalid data provided for saving");
            return false;
        }

        const currentStep = step || Utils.actualStep() + 1;
        const cookieKey = this.getStepCookieKey(currentStep);

        // Validar tamaño antes de guardar
        if (!CookieManager.canSetCookie(cookieKey, data)) {
            console.error("Data too large for cookie storage");
            return false;
        }
        console.warn(
            "saveFormDataToStep is deprecated, use handleFormStep instead"
        );
        return this.handleFormStep(currentStep, data);
    }

    static getPetsFromCookies() {
        console.warn(
            "getPetsFromCookies is deprecated, use getAllPetsFromCheckin instead"
        );
        return this.getAllPetsFromCheckin();
    }

    static updatePetInCookies(petIndex, data) {
        console.warn(
            "updatePetInCookies is deprecated, use updatePetInCheckin instead"
        );
        return this.updatePetInCheckin(petIndex, data);
    }

    /**
     * Método de debugging
     */
    static debugCheckinData() {
        const checkinData = this.getCheckinData();
        console.group("🍪 Checkin Debug Information");
        console.log("Full checkin data:", checkinData);
        console.log("Pets count:", checkinData?.pets?.length || 0);
        console.log("Cookie size:", CookieManager.getCookieSize(), "bytes");
        console.log("Auto-save active:", !!this.autoSaveTimer);
        console.groupEnd();
        return checkinData;
    }
}

// Inicializar automáticamente cuando se carga el módulo
document.addEventListener("DOMContentLoaded", () => {
    FormDataManager.initialize();
});

// Exponer método de debug globalmente
window.debugCheckin = () => FormDataManager.debugCheckinData();

export { FormDataManager };
