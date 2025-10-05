import { CookieManager } from "./CookieManager.js";
import config from "./config.js";
import Utils from "../Utils.js";
import Pill from "../Pill.js";

const { FORM_CONFIG, DEFAULT_CHECKIN_STRUCTURE, DEFAULT_PET_STRUCTURE } = config;

/**
 * Cookie reactivity system to listen for cookie changes and update UI
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
        this.lastCookieValue = CookieManager.getCookie(FormDataManager.CHECKIN_COOKIE_NAME);

        // Use MutationObserver to detect programmatic cookie changes
        this.setupMutationObserver();

        // Note: Removed polling to prevent performance issues
        // Cookie changes are primarily programmatic, so MutationObserver + manual triggers suffice

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
     * Setup polling fallback for cookie changes (less frequent)
     */
    static setupPolling() {
        this.pollingInterval = setInterval(() => {
            this.checkForCookieChanges();
        }, 5000); // Check every 5 seconds instead of 1
    }

    /**
     * Check if cookie has changed and notify listeners
     */
    static checkForCookieChanges() {
        const currentValue = CookieManager.getCookie(FormDataManager.CHECKIN_COOKIE_NAME);

        if (this.hasCookieChanged(currentValue)) {
            console.log("🍪 Cookie change detected via polling, notifying listeners");
            this.lastCookieValue = currentValue;
            this.notifyListeners(currentValue);
        }
        // If no change, do nothing (no logging to avoid spam)
    }

    /**
     * Check if cookie value has actually changed
     */
    static hasCookieChanged(newValue) {
        if (this.lastCookieValue === null && newValue === null) return false;
        if (this.lastCookieValue === null || newValue === null) return true;

        // Deep compare JSON objects
        try {
            const lastParsed = JSON.parse(this.lastCookieValue);
            const newParsed = JSON.parse(newValue);
            return JSON.stringify(lastParsed) !== JSON.stringify(newParsed);
        } catch {
            return this.lastCookieValue !== newValue;
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

class FormDataManager {
    static CHECKIN_COOKIE_NAME = "pl_checkin_data";
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

        // Auto-save disabled for now
        // this.startAutoSave();

        // Iniciar sistema de reactividad de cookies
        CookieReactivityManager.startListening();

        // Registrar listener para actualizar UI automáticamente
        this.registerUIUpdateListener();

        // Trigger initial UI update with existing data
        this.updateUIFromCookieData(this.getCheckinData());

        console.log(
            "FormDataManager initialized with checkin data:",
            this.getCheckinData()
        );
    }

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
     * Solo actualiza elementos que no están siendo editados activamente por el usuario
     */
    static updateUIFromCookieData(cookieData) {
        if (!cookieData) return;

        console.log("🔄 Updating UI from cookie data (conservative mode)");

        try {
            // Populate owner info form globally (regardless of current step)
            this.updateOwnerInfoForm(cookieData.user?.info);

            // Update pet pills and forms (these are display-only, not user input)
            this.updatePetPillsAndForms(cookieData.pets);

            // Update feeding/medication displays (these are display-only)
            this.updateFeedingMedicationUI(cookieData.pets);

            // Update health info UI
            this.updateHealthInfoUI(cookieData.pets, cookieData.grooming, cookieData.groomingDetails);

            // Update inventory UI
            this.updateInventoryUI(cookieData.inventory, cookieData.inventoryComplete);

            // Update same feeding checkbox visibility
            this.updateSameFeedingCheckbox(cookieData.pets);

            // Update grooming checkboxes and inventory (less likely to be actively edited)
            this.updateGroomingAndInventoryUI(cookieData.grooming, cookieData.inventory, cookieData.groomingDetails);

            // NOTE: Intentionally NOT updating text input fields automatically
            // to prevent clearing user input. Use populateFormWithCookies() explicitly
            // when needed (e.g., on page load or step changes)

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
     * Actualiza el formulario de contacto de emergencia
     */
    static updateEmergencyContactForm(emergencyContact) {
        if (!emergencyContact) return;

        const nameField = document.querySelector('[name="emergencyContactName"]') || document.getElementById('emergencyContactName');
        const phoneField = document.querySelector('[name="emergencyContactPhone"]') || document.getElementById('emergencyContactPhone');

        if (nameField) nameField.value = emergencyContact.name || '';
        if (phoneField) phoneField.value = emergencyContact.phone || '';
    }

    /**
     * Actualiza las pills de mascotas y formularios relacionados
     * Solo recrea pills en el paso de información de mascotas para evitar manipulaciones DOM redundantes
     */
    static updatePetPillsAndForms(pets) {
        if (!Array.isArray(pets)) return;

        const currentStep = Utils.actualStep();
        const isPetInfoStep = currentStep === (FORM_CONFIG.STEPS.PET_INFO - 1); // step 1

        // Solo actualizar pills completamente en el paso de mascotas (donde se agregan nuevas)
        if (isPetInfoStep) {
            const container = document.querySelector("#petPillsContainer");
            if (container) {
                container.innerHTML = "";

                // Recordar cuál estaba seleccionado antes de limpiar
                const previouslySelectedIndex = this.getCurrentSelectedPetIndex();

                pets.forEach((pet, index) => {
                    if (pet && pet.info?.petName) {
                        const pill = new Pill(pet.info.petName, pet.info.petType, index);
                        container.appendChild(pill.render());

                        // Si era el seleccionado previamente, re-seleccionarlo
                        if (index === previouslySelectedIndex) {
                            pill.select();
                        }
                    }
                });
            }
        }

        // Siempre actualizar el formulario de la mascota seleccionada si existe
        const currentPetIndex = this.getCurrentSelectedPetIndex();
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

    /**
     * Actualiza la UI de alimentación y medicación
     */
    static updateFeedingMedicationUI(pets) {
        if (!Array.isArray(pets)) return;

        // Update feeding/medication displays for each time slot
        const timeSlots = ['morning', 'noon', 'afternoon', 'night'];
        timeSlots.forEach(timeSlot => {
            this.updateTimeSlotDisplay(timeSlot, pets);
        });

        // Update day section visibility
        this.updateDaySectionVisibility(pets);
    }

    /**
     * Actualiza la visualización de un slot de tiempo específico
     */
    static updateTimeSlotDisplay(timeSlot, pets) {
        const foodContainer = document.querySelector(`#${timeSlot}-food-list`);
        const medContainer = document.querySelector(`#${timeSlot}-med-list`);

        if (foodContainer) foodContainer.innerHTML = '';
        if (medContainer) medContainer.innerHTML = '';

        if (!Array.isArray(pets)) return;

        pets.forEach((pet, petIndex) => {
            if (pet?.feeding) {
                pet.feeding.forEach((feed, feedIndex) => {
                    if (feed.day_time === timeSlot && foodContainer) {
                        this.createEditableItem(foodContainer, feed, petIndex, 'feeding', feedIndex, pet.info?.petName);
                    }
                });
            }

            if (pet?.medication) {
                pet.medication.forEach((med, medIndex) => {
                    if (med.day_time === timeSlot && medContainer) {
                        this.createEditableItem(medContainer, med, petIndex, 'medication', medIndex, pet.info?.petName);
                    }
                });
            }
        });

        // Hide/show food section based on content
        if (foodContainer) {
            const foodSection = foodContainer.parentElement;
            if (foodContainer.children.length === 0) {
                foodSection.style.display = 'none';
            } else {
                foodSection.style.display = '';
            }
        }

        // Hide/show medication section based on content
        if (medContainer) {
            const medSection = medContainer.parentElement;
            if (medContainer.children.length === 0) {
                medSection.style.display = 'none';
            } else {
                medSection.style.display = '';
            }
        }
    }

    /**
     * Crea un elemento editable para alimentación/medicación
     */
    static createEditableItem(container, item, petIndex, type, itemIndex, petName) {
        if (!container) return;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'flex items-center rounded-b-sm pl-2 py-1';

        // Edit button
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'ml-2 text-blue-500 hover:text-blue-700 focus:outline focus:outline-2 focus:outline-blue-500 w-11 h-11 flex items-center justify-center rounded';
        editBtn.setAttribute('aria-label', 'Edit item');
        editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
        editBtn.onclick = () => this.toggleEdit(input, editBtn);

        // Input field
        const input = document.createElement('input');
        input.type = 'text';
        input.value = item.feeding_med_details || '';
        input.disabled = true;
        input.className = 'flex-1 mb-0 mx-1';
        input.dataset.petIndex = petIndex;
        input.dataset.type = type;
        input.dataset.itemIndex = itemIndex;

        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'ml-2 text-red-500 hover:text-red-700 focus:outline focus:outline-2 focus:outline-red-500 w-11 h-11 flex items-center justify-center rounded';
        deleteBtn.setAttribute('aria-label', 'Delete item');
        deleteBtn.innerHTML = '<iconify-icon icon="material-symbols:delete" class="text-lg"></iconify-icon>';
        deleteBtn.onclick = () => {
            if (confirm('Are you sure you want to delete this item?')) {
                this.removeFeedingMedicationItem(petIndex, type, itemIndex);
                itemDiv.remove();
            }
        };

        itemDiv.appendChild(input);
        itemDiv.appendChild(editBtn);
        itemDiv.appendChild(deleteBtn);
        container.appendChild(itemDiv);
    }

    /**
     * Alterna el modo de edición del input
     */
    static toggleEdit(input, editBtn) {
        const isEditing = !input.disabled;
        input.disabled = isEditing;

        if (isEditing) {
            // Save changes
            const newValue = input.value.trim();
            if (newValue !== '') {
                this.updateFeedingMedicationItem(
                    parseInt(input.dataset.petIndex),
                    input.dataset.type,
                    parseInt(input.dataset.itemIndex),
                    { feeding_med_details: newValue }
                );
            }
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-blue-500 hover:text-blue-700 focus:outline focus:outline-2 focus:outline-blue-500 w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Edit item');
        } else {
            // Enter edit mode
            input.focus();
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:check" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-green-500 hover:text-green-700 focus:outline focus:outline-2 focus:outline-green-500 w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Save changes');
        }
    }

    /**
     * Actualiza un elemento específico de alimentación/medicación
     */
    static updateFeedingMedicationItem(petIndex, type, itemIndex, updates) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) return false;

        const pet = { ...currentData.pets[petIndex] };
        const array = type === 'feeding' ? pet.feeding : pet.medication;

        if (array && array[itemIndex]) {
            array[itemIndex] = { ...array[itemIndex], ...updates };
            pet.lastUpdated = new Date().toISOString();

            const updatedPets = [...currentData.pets];
            updatedPets[petIndex] = pet;

            return this.updateCheckinData({ pets: updatedPets });
        }

        return false;
    }

    /**
     * Elimina un elemento específico de alimentación/medicación
     */
    static removeFeedingMedicationItem(petIndex, type, itemIndex) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) return false;

        const pet = { ...currentData.pets[petIndex] };
        const array = type === 'feeding' ? pet.feeding : pet.medication;

        if (array && array[itemIndex]) {
            array.splice(itemIndex, 1); // Remove the item
            pet.lastUpdated = new Date().toISOString();

            const updatedPets = [...currentData.pets];
            updatedPets[petIndex] = pet;

            return this.updateCheckinData({ pets: updatedPets });
        }

        return false;
    }

    /**
     * Actualiza la visibilidad de las secciones de día
     */
    static updateDaySectionVisibility(pets) {
        const timeSlots = ['morning', 'noon', 'afternoon', 'night'];

        timeSlots.forEach(timeSlot => {
            const dayContainer = document.querySelector(`[data-time-slot="${timeSlot}"]`);

            if (dayContainer) {
                let hasData = false;

                if (Array.isArray(pets)) {
                    pets.forEach(pet => {
                        if (pet?.feeding?.some(f => f.day_time === timeSlot) ||
                            pet?.medication?.some(m => m.day_time === timeSlot)) {
                            hasData = true;
                        }
                    });
                }

                if (hasData) {
                    dayContainer.classList.remove('hidden');
                } else {
                    dayContainer.classList.add('hidden');
                }
            }
        });
    }

    /**
     * Actualiza la visibilidad y estado del checkbox "Same feeding for all"
     */
    static updateSameFeedingCheckbox(pets) {
        const container = document.getElementById('sameFeedingContainer');
        const checkbox = document.getElementById('sameFeedingForAll');

        if (!container || !checkbox) return;

        const petCount = Array.isArray(pets) ? pets.length : 0;

        if (petCount > 1) {
            container.classList.remove('hidden');
            checkbox.checked = true; // Default checked for multiple pets
        } else {
            container.classList.add('hidden');
            checkbox.checked = false;
        }
    }

    /**
     * Actualiza la UI de información de salud
     */
    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        const healthForm = document.getElementById('healthInfoForm');
        if (!healthForm) return;

        // Get selected pet or first pet
        const selectedPetIndex = this.getCurrentSelectedPetIndex();
        const petIndex = selectedPetIndex !== null ? selectedPetIndex : 0;
        const petData = pets && pets[petIndex] ? pets[petIndex] : null;

        if (petData && petData.health) {
            // Populate unusual health behavior
            const unusualRadio = healthForm.querySelector(`input[name="unusualHealthBehavior"][value="${petData.health.unusualHealthBehavior ? 'yes' : 'no'}"]`);
            if (unusualRadio) unusualRadio.checked = true;

            // Show/hide conditional details
            const detailsContainer = healthForm.querySelector('.conditional-health-details');
            const detailsField = healthForm.querySelector('#healthBehaviorDetails');
            if (petData.health.unusualHealthBehavior) {
                if (detailsContainer) detailsContainer.style.display = '';
                if (detailsField) detailsField.value = petData.health.healthBehaviors || '';
            } else {
                if (detailsContainer) detailsContainer.style.display = 'none';
                if (detailsField) detailsField.value = '';
            }

            // Populate warnings
            const warningsField = healthForm.querySelector('#warnings');
            if (warningsField) warningsField.value = petData.health.warnings || '';
        }

        // Populate grooming
        if (grooming) {
            Object.keys(grooming).forEach(service => {
                const checkbox = healthForm.querySelector(`input[name="grooming[]"][value="${service}"]`);
                if (checkbox) {
                    checkbox.checked = grooming[service] || false;
                }
            });
        }

        // Populate grooming details
        const groomingDetailsField = healthForm.querySelector('#groomingDetails');
        if (groomingDetailsField) {
            groomingDetailsField.value = groomingDetails || '';
        }

        // Show/hide grooming notes based on selection
        const groomingNotesContainer = healthForm.querySelector('.conditional-grooming-notes');
        const hasGroomingSelected = grooming && Object.values(grooming).some(val => val && val !== 'no');
        if (groomingNotesContainer) {
            groomingNotesContainer.style.display = hasGroomingSelected ? '' : 'none';
        }
    }

    /**
     * Actualiza la UI de grooming e inventory
     */
    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        if (grooming) {
            Object.keys(grooming).forEach(service => {
                const checkbox = document.querySelector(`[name="${service}"]`) || document.getElementById(service);
                if (checkbox && checkbox.type === 'checkbox') {
                    checkbox.checked = grooming[service] || false;
                }
            });
        }

        // Update grooming details
        const detailsField = document.querySelector('[name="groomingDetails"]') || document.getElementById('groomingDetails');
        if (detailsField) {
            detailsField.value = details || '';
        }

        // Update inventory (this would need specific implementation based on your inventory structure)
        console.log("Inventory update needed:", inventory);
    }

    /**
     * Obtiene el índice de la mascota actualmente seleccionada
     */
    static getCurrentSelectedPetIndex() {
        const selectedPill = document.querySelector(".pill.selected");
        return selectedPill ? parseInt(selectedPill.dataset.index, 10) : null;
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
            FORM_CONFIG.DEFAULT_COOKIE_DAYS,
            {
                secure: window.location.protocol === 'https:',
                sameSite: 'Lax',
                obfuscate: false // Set to true for sensitive data in production
            }
        );

        if (success) {
            console.log("Checkin data updated:", updatedData);
            // Trigger reactivity check
            CookieReactivityManager.triggerCheck();
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
     * Elimina una mascota específica del checkin
     */
    static removePetFromCheckin(petIndex) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found for removal`);
            return false;
        }

        const updatedPets = currentData.pets.filter((_, index) => index !== petIndex);

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
     * Actualiza información de salud de una mascota
     */
    static updatePetHealthInfo(petIndex, healthData) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found for health update`);
            return false;
        }

        const pet = { ...currentData.pets[petIndex] };
        pet.health = { ...pet.health, ...healthData };
        pet.lastUpdated = new Date().toISOString();

        const updatedPets = [...currentData.pets];
        updatedPets[petIndex] = pet;

        return this.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Agrega un item al inventario
     */
    static addInventoryItem(itemText) {
        const currentData = this.getCheckinData();
        if (!currentData) return false;

        const inventory = currentData.inventory || [];
        const updatedInventory = [...inventory, itemText];

        return this.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Elimina un item del inventario por índice
     */
    static removeInventoryItem(itemIndex) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.inventory) return false;

        const updatedInventory = currentData.inventory.filter((_, index) => index !== itemIndex);

        return this.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Actualiza un item del inventario por índice
     */
    static updateInventoryItem(itemIndex, newText) {
        const currentData = this.getCheckinData();
        if (!currentData || !currentData.inventory) return false;

        const updatedInventory = [...currentData.inventory];
        updatedInventory[itemIndex] = newText;

        return this.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Marca el inventario como completo
     */
    static setInventoryComplete(complete) {
        return this.updateCheckinData({
            inventoryComplete: complete
        });
    }

    /**
     * Marca los términos como aceptados
     */
    static setTermsAccepted(accepted) {
        return this.updateCheckinData({
            termsAccepted: accepted
        });
    }

    /**
     * Actualiza la UI del inventario
     */
    static updateInventoryUI(inventory, inventoryComplete) {
        const itemsList = document.getElementById('inventoryItemsList');
        const completeCheckbox = document.getElementById('inventoryComplete');
        const completeContainer = completeCheckbox ? completeCheckbox.closest('.mt-8') : null;

        if (itemsList && Array.isArray(inventory)) {
            itemsList.innerHTML = '';
            inventory.forEach((itemText, index) => {
                this.createInventoryItemElement(itemText, index, itemsList);
            });
        }

        // Handle checkbox visibility and state
        if (completeCheckbox && completeContainer) {
            if (inventory && inventory.length > 0) {
                // Hide checkbox when items exist
                completeContainer.style.display = 'none';
                completeCheckbox.checked = false;
            } else {
                // Show checkbox when no items
                completeContainer.style.display = '';
                completeCheckbox.checked = inventoryComplete || false;
            }
        }

        // Update tabbar if we're on inventory step
        if (typeof window.updateTabbarForStep === 'function') {
            window.updateTabbarForStep();
        }
    }

    /**
     * Crea un elemento de item de inventario
     */
    static createInventoryItemElement(itemText, itemIndex, container) {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'flex items-center justify-between bg-white p-3 rounded-md border mb-2';

        const textSpan = document.createElement('span');
        textSpan.className = 'flex-1 font-medium';
        textSpan.textContent = itemText;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'text-red-500 hover:text-red-700 px-2 py-1 ml-2';
        deleteBtn.setAttribute('aria-label', 'Delete item');
        deleteBtn.innerHTML = '<iconify-icon icon="material-symbols:delete" class="text-lg"></iconify-icon>';
        deleteBtn.onclick = () => {
            if (confirm('Are you sure you want to delete this item?')) {
                this.removeInventoryItem(itemIndex);
            }
        };

        itemDiv.appendChild(textSpan);
        itemDiv.appendChild(deleteBtn);
        container.appendChild(itemDiv);
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
                    const checkinData = this.getCheckinData();
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
                this.updateGroomingAndInventory(groomingData, [], formData.groomingDetails || '');

                return true;

            case FORM_CONFIG.STEPS.INVENTORY - 1: // step 4 = INVENTORY
                // Inventory is managed separately via add/remove and checkbox
                // Validate both inventory completion and terms acceptance
                const checkinData = this.getCheckinData();
                const hasItems = checkinData?.inventory?.length > 0;
                const isComplete = checkinData?.inventoryComplete;
                const termsAccepted = checkinData?.termsAccepted;

                if (!hasItems && !isComplete) {
                    alert('Please add inventory items or confirm you are not leaving anything in inventory.');
                    return false;
                }

                if (!termsAccepted) {
                    // Show terms popup if not accepted yet
                    this.showTermsPopup();
                    return false;
                }

                return true;

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

        console.warn(
            "saveFormDataToStep is deprecated, use handleFormStep instead"
        );

        // For backward compatibility, try to determine the step and handle it
        const currentStep = step || Utils.actualStep() + 1;

        // Since we now use a single cookie, we'll try to add this as pet data
        // This is a fallback for legacy code
        if (data.petName || data.petType || data.petBreed) {
            // This looks like pet data, add it to the checkin
            return this.addPetToCheckin(data);
        } else {
            // For other data, try to handle it with the new system
            return this.handleFormStep(currentStep - 1, data); // Adjust for 0-based indexing
        }
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
