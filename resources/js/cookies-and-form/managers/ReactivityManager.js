import { CoreDataManager } from "./CoreDataManager.js";
import { InventoryManager } from "./InventoryManager.js";
import { PetManager } from "./PetManager.js";
import { UtilitiesManager } from "./UtilitiesManager.js";
import config from "../config.js";

const { FORM_CONFIG } = config;

/**
 * Reactivity Manager - Handles cookie change listeners and UI updates
 */
class CookieReactivityManager {
    static listeners = new Set();
    static isListening = false;
    static lastCookieValue = null;

    /**
     * Start listening for cookie changes
     */
    static startListening() {
        if (this.isListening) return;

        this.isListening = true;
        this.lastCookieValue = CoreDataManager.getCheckinData();

        // Use MutationObserver to detect programmatic cookie changes
        this.setupMutationObserver();

        console.log("🍪 Cookie reactivity listening started (MutationObserver only)");
    }

    /**
     * Setup MutationObserver for cookie changes
     */
    static setupMutationObserver() {
        // Create a dummy element to observe cookie changes
        const cookieObserver = document.createElement('div');
        cookieObserver.id = 'cookie-observer';
        cookieObserver.style.display = 'none';
        document.body.appendChild(cookieObserver);

        const observer = new MutationObserver(() => {
            this.checkForCookieChanges();
        });

        observer.observe(cookieObserver, {
            attributes: true,
            attributeFilter: ['data-cookie-check']
        });

        // Store observer reference for cleanup
        this.observer = observer;
        this.cookieObserver = cookieObserver;
    }

    /**
     * Check if cookie has changed and notify listeners
     */
    static checkForCookieChanges() {
        const currentValue = CoreDataManager.getCheckinData();

        if (this.hasCookieChanged(currentValue)) {
            console.log("🍪 Cookie change detected, notifying listeners");
            this.lastCookieValue = currentValue;
            this.notifyListeners(currentValue);
        }
    }

    /**
     * Check if cookie value has actually changed
     */
    static hasCookieChanged(newValue) {
        if (this.lastCookieValue === null && newValue === null) return false;
        if (this.lastCookieValue === null || newValue === null) return true;

        // Deep compare JSON objects
        try {
            const lastParsed = JSON.parse(JSON.stringify(this.lastCookieValue));
            const newParsed = JSON.parse(JSON.stringify(newValue));
            return JSON.stringify(lastParsed) !== JSON.stringify(newParsed);
        } catch {
            return JSON.stringify(this.lastCookieValue) !== JSON.stringify(newValue);
        }
    }

    /**
     * Add a listener for cookie changes
     */
    static addListener(callback) {
        this.listeners.add(callback);
    }

    /**
     * Remove a listener
     */
    static removeListener(callback) {
        this.listeners.delete(callback);
    }

    /**
     * Notify all listeners of cookie change
     */
    static notifyListeners(cookieData) {
        console.log("🍪 Cookie changed, notifying listeners:", cookieData);
        this.listeners.forEach(callback => {
            try {
                callback(cookieData);
            } catch (error) {
                console.error("Error in cookie change listener:", error);
            }
        });
    }

    /**
     * Stop listening for changes
     */
    static stopListening() {
        if (!this.isListening) return;

        this.isListening = false;

        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.cookieObserver) {
            this.cookieObserver.remove();
        }

        this.listeners.clear();
        console.log("🍪 Cookie reactivity listening stopped");
    }

    /**
     * Manually trigger cookie check (useful after programmatic changes)
     */
    static triggerCheck() {
        // Update the observer element to trigger MutationObserver
        if (this.cookieObserver) {
            this.cookieObserver.setAttribute('data-cookie-check', Date.now());
        }
        // Also do immediate check
        this.checkForCookieChanges();
    }
}

/**
 * UI Manager - Handles UI updates based on cookie data changes
 */
class UIManager {
    /**
     * Registra listener para actualizar UI automáticamente cuando cambia la cookie
     */
    static registerUIUpdateListener() {
        CookieReactivityManager.addListener((cookieData) => {
            this.updateUIFromCookieData(cookieData);
        });
    }

    /**
     * Actualiza todos los elementos UI basándose en los datos de la cookie
     */
    static updateUIFromCookieData(cookieData) {
        if (!cookieData) return;

        console.log("🔄 Updating UI from cookie data (conservative mode)");

        try {
            // Update owner info form globally
            this.updateOwnerInfoForm(cookieData.user?.info);

            // Update pet pills and forms
            this.updatePetPillsAndForms(cookieData.pets);

            // Update feeding/medication displays
            this.updateFeedingMedicationUI(cookieData.pets);

            // Update health info UI
            this.updateHealthInfoUI(cookieData.pets, cookieData.grooming, cookieData.groomingDetails);

            // Update inventory UI
            InventoryManager.updateInventoryUI(cookieData.inventory, cookieData.inventoryComplete);

            // Update same feeding checkbox visibility
            this.updateSameFeedingCheckbox(cookieData.pets);

            // Update grooming checkboxes
            this.updateGroomingAndInventoryUI(cookieData.grooming, cookieData.inventory, cookieData.groomingDetails);

        } catch (error) {
            console.error("Error updating UI from cookie:", error);
        }
    }

    /**
     * Actualiza el formulario de información del propietario
     */
    static updateOwnerInfoForm(userInfo) {
        if (!userInfo) return;

        const fields = ['phone', 'name', 'email', 'address', 'city', 'zip'];
        fields.forEach(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            if (element && userInfo[field]) {
                element.value = userInfo[field];
            }
        });
    }

    /**
     * Actualiza las pills de mascotas y formularios relacionados
     */
    static updatePetPillsAndForms(pets) {
        if (!Array.isArray(pets)) return;

        const currentStep = this.getCurrentStep();
        const isPetInfoStep = currentStep === (FORM_CONFIG.STEPS.PET_INFO - 1); // step 1

        // Solo actualizar pills completamente en el paso de mascotas
        if (isPetInfoStep) {
            const container = document.querySelector("#petPillsContainer");
            if (container) {
                container.innerHTML = "";

                // Recordar cuál estaba seleccionado antes de limpiar
                const previouslySelectedIndex = UtilitiesManager.getCurrentSelectedPetIndex();

                pets.forEach((pet, index) => {
                    if (pet && pet.info?.petName) {
                        // This would need Pill import - simplified for now
                        console.log(`Would add pill for pet ${index}: ${pet.info.petName}`);
                    }
                });
            }
        }

        // Siempre actualizar el formulario de la mascota seleccionada si existe
        const currentPetIndex = UtilitiesManager.getCurrentSelectedPetIndex();
        if (currentPetIndex !== null && pets[currentPetIndex]) {
            this.updatePetForm(pets[currentPetIndex]);
        }
    }

    /**
     * Actualiza el formulario de una mascota específica
     */
    static updatePetForm(petData) {
        if (!petData?.info) return;

        const petFields = ['petName', 'petColor', 'petType', 'petBreed', 'petAge', 'petWeight', 'petGender', 'petSpayed'];
        petFields.forEach(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            if (element && petData.info[field]) {
                if (element.type === 'radio') {
                    const radioButton = document.querySelector(`[name="${field}"][value="${petData.info[field]}"]`);
                    if (radioButton) radioButton.checked = true;
                } else {
                    element.value = petData.info[field];
                }
            }
        });
    }

    // Additional UI update methods would go here...
    // For brevity, including simplified versions

    static updateFeedingMedicationUI(pets) {
        // Simplified implementation
        console.log("Update feeding/medication UI for pets:", pets);
    }

    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        // Simplified implementation
        console.log("Update health info UI");
    }

    static updateSameFeedingCheckbox(pets) {
        // Simplified implementation
        console.log("Update same feeding checkbox");
    }

    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        // Simplified implementation
        console.log("Update grooming and inventory UI");
    }

    static getCurrentStep() {
        // This would need to be implemented based on your step tracking logic
        return 0; // Placeholder
    }
}

export { CookieReactivityManager, UIManager };