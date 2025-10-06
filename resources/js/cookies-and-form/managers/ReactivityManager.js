import { CoreDataManager } from "./CoreDataManager.js";
import { InventoryManager } from "./InventoryManager.js";
import { PetManager } from "./PetManager.js";
import { UtilitiesManager } from "./UtilitiesManager.js";
import { NavigationManager } from "./NavigationManager.js";
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
            console.log("Previous cookie size:", this.lastCookieValue ? JSON.stringify(this.lastCookieValue).length : 0);
            console.log("New cookie size:", currentValue ? JSON.stringify(currentValue).length : 0);
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

            // Update check-in summary in THANKS step
            this.updateCheckinSummary(cookieData);

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
        if (!Array.isArray(pets)) return;

        // Track which time slots have items
        const timeSlotsWithItems = new Set();

        // Check all pets for feeding/medication items
        pets.forEach(pet => {
            if (pet?.feeding && Array.isArray(pet.feeding)) {
                pet.feeding.forEach(item => {
                    if (item.day_time) {
                        timeSlotsWithItems.add(item.day_time);
                    }
                });
            }

            if (pet?.medication && Array.isArray(pet.medication)) {
                pet.medication.forEach(item => {
                    if (item.day_time) {
                        timeSlotsWithItems.add(item.day_time);
                    }
                });
            }
        });

        // Show/hide container-day sections based on whether they have items
        const timeSlots = ['morning', 'afternoon', 'night'];
        timeSlots.forEach(timeSlot => {
            const container = document.querySelector(`.container-day[data-time-slot="${timeSlot}"]`);
            if (container) {
                if (timeSlotsWithItems.has(timeSlot)) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        });

        // Update the actual feeding/medication displays
        this.updateFeedingMedicationDisplays(pets);
    }

    static updateFeedingMedicationDisplays(pets) {
        // Clear all existing displays
        const timeSlots = ['morning', 'afternoon', 'night'];
        timeSlots.forEach(timeSlot => {
            const foodContainer = document.querySelector(`#${timeSlot}-food-list`);
            const medContainer = document.querySelector(`#${timeSlot}-med-list`);

            if (foodContainer) foodContainer.innerHTML = '';
            if (medContainer) medContainer.innerHTML = '';
        });

        // Populate displays with current data
        if (!Array.isArray(pets)) return;

        pets.forEach((pet, petIndex) => {
            if (pet?.feeding && Array.isArray(pet.feeding)) {
                pet.feeding.forEach((feed, itemIndex) => {
                    if (feed.day_time) {
                        const container = document.querySelector(`#${feed.day_time}-food-list`);
                        if (container) {
                            UtilitiesManager.createEditableItem(
                                container,
                                feed,
                                petIndex,
                                'feeding',
                                itemIndex,
                                pet.info?.petName || 'Pet'
                            );
                        }
                    }
                });
            }

            if (pet?.medication && Array.isArray(pet.medication)) {
                pet.medication.forEach((med, itemIndex) => {
                    if (med.day_time) {
                        const container = document.querySelector(`#${med.day_time}-med-list`);
                        if (container) {
                            UtilitiesManager.createEditableItem(
                                container,
                                med,
                                petIndex,
                                'medication',
                                itemIndex,
                                pet.info?.petName || 'Pet'
                            );
                        }
                    }
                });
            }
        });
    }

    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        // Simplified implementation
        console.log("Update health info UI");
    }

    static updateSameFeedingCheckbox(pets) {
        const sameFeedingContainer = document.getElementById('sameFeedingContainer');
        if (!sameFeedingContainer) return;

        // Show the checkbox only if there are multiple pets
        const hasMultiplePets = Array.isArray(pets) && pets.length > 1;
        if (hasMultiplePets) {
            sameFeedingContainer.classList.remove('hidden');
        } else {
            sameFeedingContainer.classList.add('hidden');
        }
    }

    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        // Simplified implementation
        console.log("Update grooming and inventory UI");
    }

    static updateCheckinSummary(cookieData) {
        const summaryElement = document.getElementById('checkinSummary');
        if (!summaryElement) return;

        let summaryHTML = '';

        // Owner information
        if (cookieData.user?.info) {
            const user = cookieData.user.info;
            summaryHTML += `<div class="mb-3"><strong>Owner:</strong> ${user.name || 'Not provided'}<br>`;
            summaryHTML += `<strong>Phone:</strong> ${user.phone || 'Not provided'}<br>`;
            summaryHTML += `<strong>Email:</strong> ${user.email || 'Not provided'}</div>`;
        }

        // Pets information
        if (cookieData.pets && cookieData.pets.length > 0) {
            summaryHTML += `<div class="mb-3"><strong>Pets:</strong><br>`;
            cookieData.pets.forEach((pet, index) => {
                if (pet?.info) {
                    summaryHTML += `• ${pet.info.petName || 'Unnamed'} (${pet.info.petType || 'Unknown type'})<br>`;
                }
            });
            summaryHTML += `</div>`;
        }

        // Inventory information
        if (cookieData.inventory && cookieData.inventory.length > 0) {
            summaryHTML += `<div class="mb-3"><strong>Items to leave:</strong><br>`;
            cookieData.inventory.forEach(item => {
                summaryHTML += `• ${item}<br>`;
            });
            summaryHTML += `</div>`;
        } else {
            summaryHTML += `<div class="mb-3"><strong>Items to leave:</strong> None</div>`;
        }

        // Grooming services
        if (cookieData.grooming) {
            const services = Object.entries(cookieData.grooming)
                .filter(([key, value]) => value)
                .map(([key]) => key.charAt(0).toUpperCase() + key.slice(1));

            if (services.length > 0) {
                summaryHTML += `<div class="mb-3"><strong>Grooming Services:</strong> ${services.join(', ')}</div>`;
            } else {
                summaryHTML += `<div class="mb-3"><strong>Grooming Services:</strong> None requested</div>`;
            }
        }

        summaryElement.innerHTML = summaryHTML;
    }

    static getCurrentStep() {
        return NavigationManager.getCurrentStep();
    }
}

export { CookieReactivityManager, UIManager };