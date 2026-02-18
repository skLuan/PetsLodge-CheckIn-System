/**
 * @fileoverview CheckInSummaryUpdater - Dynamic check-in summary component updater
 *
 * This module handles real-time updates to the check-in summary component by observing
 * cookie changes and re-rendering the summary with the latest data from cookies instead
 * of stale server-side session data.
 *
 * The component is rendered server-side with session data on initial page load, but this
 * updater ensures it stays synchronized with client-side cookie data as the user fills
 * out the form.
 *
 * @author Kilo Code
 * @version 1.0.0
 * @since 2026-01-30
 */

import { FormDataManager } from '../FormDataManager.js';
import { CookieReactivityManager } from '../reactivitySystem/index.js';

/**
 * CheckInSummaryUpdater - Manages dynamic updates to check-in summary component
 *
 * This class observes cookie changes and updates the check-in summary display
 * to reflect the current state of form data stored in cookies.
 *
 * @class
 * @static
 */
class CheckInSummaryUpdater {
    /**
     * Container element for the check-in summary
     * @static
     * @type {HTMLElement|null}
     * @private
     */
    static summaryContainer = null;

    /**
     * Flag to track if updater is initialized
     * @static
     * @type {boolean}
     * @private
     */
    static initialized = false;

    /**
     * Initialize the check-in summary updater
     *
     * Sets up the observer to watch for cookie changes and updates the summary
     * whenever the check-in data changes.
     *
     * @static
     * @returns {void}
     *
     * @example
     * CheckInSummaryUpdater.initialize();
     *
     * @sideEffects
     * - Finds and stores reference to summary container
     * - Registers listener with CookieReactivityManager
     * - Performs initial update with current cookie data
     */
    static initialize() {
        if (this.initialized) {
            console.warn('CheckInSummaryUpdater already initialized');
            return;
        }

        // Find the check-in summary container
        this.summaryContainer = document.querySelector('[data-check-in-summary]');
        
        if (!this.summaryContainer) {
            console.warn('CheckInSummaryUpdater: No summary container found with [data-check-in-summary] attribute');
            return;
        }

        // Register listener for cookie changes
        // CookieReactivityManager.addListener expects a callback function, not a cookie name
        CookieReactivityManager.addListener(() => {
            this.updateSummary();
        });

        // Perform initial update
        this.updateSummary();

        this.initialized = true;
        console.log('CheckInSummaryUpdater initialized and listening for changes');
    }

    /**
     * Update the check-in summary display with current cookie data
     *
     * Retrieves the latest check-in data from cookies and re-renders the summary
     * component with the updated information.
     *
     * @static
     * @returns {void}
     *
     * @sideEffects
     * - Updates DOM elements within summary container
     * - Preserves component structure while updating content
     * - Logs update operations to console
     */
    static updateSummary() {
        if (!this.summaryContainer) {
            return;
        }

        const checkinData = FormDataManager.getCheckinData();
        
        if (!checkinData) {
            console.warn('CheckInSummaryUpdater: No check-in data available');
            return;
        }

        // Update receipt header
        this.updateReceiptHeader(checkinData);

        // Update owner information
        this.updateOwnerInfo(checkinData);

        // Update emergency contact
        this.updateEmergencyContact(checkinData);

        // Update pet details
        this.updatePetDetails(checkinData);

        // Update grooming details
        this.updateGroomingDetails(checkinData);

        // Update inventory
        this.updateInventory(checkinData);

        console.log('CheckInSummary updated with latest cookie data');
    }

    /**
     * Update receipt header with check-in ID and date
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updateReceiptHeader(checkinData) {
        const receiptIdEl = this.summaryContainer.querySelector('[data-receipt-id]');
        const receiptDateEl = this.summaryContainer.querySelector('[data-receipt-date]');

        if (receiptIdEl && checkinData.id) {
            receiptIdEl.textContent = checkinData.id;
        }

        if (receiptDateEl && checkinData.date) {
            const date = new Date(checkinData.date);
            receiptDateEl.textContent = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }

    /**
     * Update owner information section
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updateOwnerInfo(checkinData) {
        if (!checkinData.user || !checkinData.user.info) {
            return;
        }

        const ownerSection = this.summaryContainer.querySelector('[data-owner-info]');
        if (!ownerSection) {
            return;
        }

        const info = checkinData.user.info;

        // Update name
        const nameEl = ownerSection.querySelector('[data-owner-name]');
        if (nameEl && info.name) {
            nameEl.textContent = info.name;
        }

        // Update phone
        const phoneEl = ownerSection.querySelector('[data-owner-phone]');
        if (phoneEl && info.phone) {
            phoneEl.textContent = info.phone;
        }

        // Update email
        const emailEl = ownerSection.querySelector('[data-owner-email]');
        if (emailEl && info.email) {
            emailEl.textContent = info.email;
        }

        // Update address
        const addressEl = ownerSection.querySelector('[data-owner-address]');
        if (addressEl && info.address) {
            addressEl.textContent = info.address;
        }

        // Update location (city, zip)
        const locationEl = ownerSection.querySelector('[data-owner-location]');
        if (locationEl && info.city && info.zip) {
            locationEl.textContent = `${info.city}, ${info.zip}`;
        }
    }

    /**
     * Update emergency contact section
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updateEmergencyContact(checkinData) {
        if (!checkinData.user || !checkinData.user.emergencyContact) {
            return;
        }

        const emergencySection = this.summaryContainer.querySelector('[data-emergency-contact]');
        if (!emergencySection) {
            return;
        }

        const contact = checkinData.user.emergencyContact;

        // Update emergency contact name
        const nameEl = emergencySection.querySelector('[data-emergency-name]');
        if (nameEl && contact.name) {
            nameEl.textContent = contact.name;
        }

        // Update emergency contact phone
        const phoneEl = emergencySection.querySelector('[data-emergency-phone]');
        if (phoneEl && contact.phone) {
            phoneEl.textContent = contact.phone;
        }

        // Show/hide section based on data availability
        if (contact.name || contact.phone) {
            emergencySection.classList.remove('hidden');
        } else {
            emergencySection.classList.add('hidden');
        }
    }

    /**
     * Update pet details section
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updatePetDetails(checkinData) {
        if (!checkinData.pets || !Array.isArray(checkinData.pets) || checkinData.pets.length === 0) {
            return;
        }

        const petsSection = this.summaryContainer.querySelector('[data-pets-section]');
        if (!petsSection) {
            return;
        }

        // Update pet count
        const petCountEl = petsSection.querySelector('[data-pet-count]');
        if (petCountEl) {
            petCountEl.textContent = checkinData.pets.length;
        }

        // Update each pet's details
        const petDetailsContainer = petsSection.querySelector('[data-pet-details-container]');
        if (petDetailsContainer) {
            // Clear existing pet details
            const existingPets = petDetailsContainer.querySelectorAll('[data-pet-item]');
            existingPets.forEach(el => el.remove());

            // Add updated pet details
            checkinData.pets.forEach((pet, index) => {
                this.updatePetItem(petDetailsContainer, pet, index);
            });
        }

        petsSection.classList.remove('hidden');
    }

    /**
     * Update individual pet item
     *
     * @static
     * @param {HTMLElement} container - Container for pet items
     * @param {Object} pet - Pet data object
     * @param {number} index - Pet index
     * @returns {void}
     *
     * @private
     */
    static updatePetItem(container, pet, index) {
        if (!pet.info) {
            return;
        }

        const petEl = document.createElement('div');
        petEl.setAttribute('data-pet-item', index);
        petEl.className = 'mb-3 py-1 pl-4 border-l-2 border-yellow';

        let petHTML = `
            <div class="font-bold text-base pb-2">
                ${pet.info.petName || 'Unnamed'} (${pet.info.petType || 'Unknown type'})
            </div>
            <div class="text-sm text-gray-600 pl-2">
        `;

        // Add pet attributes
        if (pet.info.petColor) {
            petHTML += `<div class="flex justify-between"><strong>Color:</strong> ${pet.info.petColor}</div>`;
        }
        if (pet.info.petBreed) {
            petHTML += `<div class="flex justify-between"><strong>Breed:</strong> ${pet.info.petBreed}</div>`;
        }
        if (pet.info.petAge) {
            const ageDate = new Date(pet.info.petAge);
            petHTML += `<div class="flex justify-between"><strong>Age:</strong> ${ageDate.toLocaleDateString()}</div>`;
        }
        if (pet.info.petWeight) {
            petHTML += `<div class="flex justify-between"><strong>Weight:</strong> ${pet.info.petWeight} lbs</div>`;
        }
        if (pet.info.petGender) {
            petHTML += `<div class="flex justify-between"><strong>Gender:</strong> ${pet.info.petGender}</div>`;
        }
        if (pet.info.petSpayed) {
            petHTML += `<div class="flex justify-between"><strong>Spayed/Neutered:</strong> ${pet.info.petSpayed}</div>`;
        }

        petHTML += '</div>';

        // Add feeding schedule
        if (pet.feeding && Array.isArray(pet.feeding) && pet.feeding.length > 0) {
            petHTML += '<div class="mt-2 pb-1"><strong>🍽️ Feeding Schedule:</strong></div>';
            const feedingByTime = {};
            pet.feeding.forEach(feed => {
                if (!feedingByTime[feed.day_time]) {
                    feedingByTime[feed.day_time] = [];
                }
                feedingByTime[feed.day_time].push(feed.feeding_med_details);
            });
            Object.entries(feedingByTime).forEach(([time, items]) => {
                petHTML += `<p class="pl-2 flex justify-between text-xs"><strong>${time.charAt(0).toUpperCase() + time.slice(1)}:</strong> ${items.join(', ')}</p>`;
            });
        }

        // Add medication schedule
        if (pet.medication && Array.isArray(pet.medication) && pet.medication.length > 0) {
            petHTML += '<div class="mt-2 pb-1"><strong>💊 Medication Schedule:</strong></div>';
            const medByTime = {};
            pet.medication.forEach(med => {
                if (!medByTime[med.day_time]) {
                    medByTime[med.day_time] = [];
                }
                medByTime[med.day_time].push(med.feeding_med_details);
            });
            Object.entries(medByTime).forEach(([time, items]) => {
                petHTML += `<p class="pl-2 flex justify-between text-xs"><strong>${time.charAt(0).toUpperCase() + time.slice(1)}:</strong> ${items.join(', ')}</p>`;
            });
        }

        // Add health notes
        if (pet.health) {
            const healthNotes = [];
            if (pet.health.unusualHealthBehavior) {
                healthNotes.push('Unusual behavior reported');
            }
            if (pet.health.healthBehaviors) {
                healthNotes.push(`<strong>⚠️ Health:</strong> ${pet.health.healthBehaviors}`);
            }
            if (pet.health.warnings) {
                healthNotes.push(`<strong>⚠️ Warnings:</strong> ${pet.health.warnings}`);
            }

            if (healthNotes.length > 0) {
                petHTML += '<div class="mt-2"><strong>🏥 Health Notes:</strong></div>';
                healthNotes.forEach(note => {
                    petHTML += `<div class="pl-2 text-xs text-red-600 flex justify-between">${note}</div>`;
                });
            }
        }

        petEl.innerHTML = petHTML;
        container.appendChild(petEl);
    }

    /**
     * Update grooming details section
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updateGroomingDetails(checkinData) {
        const groomingSection = this.summaryContainer.querySelector('[data-grooming-section]');
        if (!groomingSection) {
            return;
        }

        if (!checkinData.grooming || typeof checkinData.grooming !== 'object') {
            groomingSection.classList.add('hidden');
            return;
        }

        // Check if there are any grooming services
        let hasServices = false;
        const services = [];

        Object.entries(checkinData.grooming).forEach(([key, value]) => {
            if (value && key !== 'no' && key !== 'appointmentDay') {
                hasServices = true;
                services.push(key.charAt(0).toUpperCase() + key.slice(1));
            }
        });

        if (hasServices) {
            const servicesEl = groomingSection.querySelector('[data-grooming-services]');
            if (servicesEl) {
                servicesEl.textContent = services.join(', ');
            }

            // Update appointment day if available
            if (checkinData.grooming.appointmentDay) {
                const appointmentEl = groomingSection.querySelector('[data-grooming-appointment]');
                if (appointmentEl) {
                    appointmentEl.textContent = checkinData.grooming.appointmentDay;
                    appointmentEl.parentElement.classList.remove('hidden');
                }
            }

            groomingSection.classList.remove('hidden');
        } else {
            groomingSection.classList.add('hidden');
        }

        // Update grooming instructions
        if (checkinData.groomingDetails) {
            const instructionsEl = this.summaryContainer.querySelector('[data-grooming-instructions]');
            if (instructionsEl) {
                instructionsEl.textContent = checkinData.groomingDetails;
                instructionsEl.parentElement.classList.remove('hidden');
            }
        }
    }

    /**
     * Update inventory section
     *
     * @static
     * @param {Object} checkinData - Complete check-in data object
     * @returns {void}
     *
     * @private
     */
    static updateInventory(checkinData) {
        const inventorySection = this.summaryContainer.querySelector('[data-inventory-section]');
        if (!inventorySection) {
            return;
        }

        if (!checkinData.inventory || !Array.isArray(checkinData.inventory)) {
            inventorySection.classList.add('hidden');
            return;
        }

        // Update item count
        const itemCountEl = inventorySection.querySelector('[data-inventory-count]');
        if (itemCountEl) {
            itemCountEl.textContent = checkinData.inventory.length;
        }

        // Update inventory list
        const inventoryList = inventorySection.querySelector('[data-inventory-list]');
        if (inventoryList) {
            inventoryList.innerHTML = '';

            if (checkinData.inventory.length > 0) {
                checkinData.inventory.forEach(item => {
                    const itemEl = document.createElement('div');
                    itemEl.textContent = `• ${item}`;
                    inventoryList.appendChild(itemEl);
                });
            } else {
                const emptyEl = document.createElement('span');
                emptyEl.className = 'text-gray-500';
                emptyEl.textContent = 'No items to store';
                inventoryList.appendChild(emptyEl);
            }
        }

        if (checkinData.inventory.length > 0) {
            inventorySection.classList.remove('hidden');
        }
    }
}

export { CheckInSummaryUpdater };
