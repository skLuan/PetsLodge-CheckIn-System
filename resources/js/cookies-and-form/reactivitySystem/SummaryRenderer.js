/**
 * Summary Renderer - Handles rendering of check-in summary/receipt
 *
 * This manager is responsible for generating and updating the detailed check-in
 * summary that appears in the final step. It creates a receipt-like display with
 * comprehensive information about the owner, pets, services, and inventory.
 *
 * Key Features:
 * - Receipt-style formatting with headers and sections
 * - Comprehensive pet information display
 * - Feeding and medication schedules
 * - Health warnings and notes
 * - Terms acceptance handling
 * - Submit button state management
 *
 * @class
 * @static
 */
class SummaryRenderer {
    /**
     * Update the check-in summary display
     *
     * Generates a comprehensive receipt-style summary of all check-in data
     * including owner information, pets with detailed schedules, services,
     * and inventory. Also handles terms acceptance and submit button state.
     *
     * @static
     * @param {Object} cookieData - Complete check-in data object
     * @param {Object} cookieData.user - User/owner information
     * @param {Array} cookieData.pets - Array of pet objects
     * @param {Object} cookieData.grooming - Grooming service selections
     * @param {Array} cookieData.inventory - Inventory items list
     * @param {boolean} cookieData.termsAccepted - Terms acceptance status
     * @returns {void}
     *
     * @example
     * const data = FormDataManager.getCheckinData();
     * SummaryRenderer.updateCheckinSummary(data);
     *
     * @sideEffects
     * - Updates #checkinSummary element with formatted HTML
     * - Manages terms checkbox event listeners
     * - Controls submit button enabled state
     * - Shows/hides terms popup trigger
     */
    static updateCheckinSummary(cookieData) {
        const summaryElement = document.getElementById('checkinSummary');
        const termsCheckbox = document.getElementById('finalTermsAccepted');
        const readTermsBtn = document.getElementById('readTermsAgainBtn');
        const finalSubmitBtn = document.getElementById('finalSubmit');

        if (!summaryElement) return;

        let summaryHTML = '';

        // Header with date and receipt ID
        summaryHTML += this.renderReceiptHeader(cookieData);

        // Owner information section
        summaryHTML += this.renderOwnerSection(cookieData.user?.info);

        // Emergency contact section
        summaryHTML += this.renderEmergencyContactSection(cookieData.user?.emergencyContact);

        // Pets information with detailed breakdown
        summaryHTML += this.renderPetsSection(cookieData.pets);


        // Grooming details section
        summaryHTML += this.renderGroomingDetailsSection(cookieData.groomingDetails);

        // Inventory information section
        summaryHTML += this.renderInventorySection(cookieData.inventory);

        summaryElement.innerHTML = summaryHTML;

        // Setup interactive elements
        this.setupTermsCheckbox(termsCheckbox, cookieData.termsAccepted);
        this.setupReadTermsButton(readTermsBtn);
        this.updateSubmitButtonState(finalSubmitBtn, cookieData.termsAccepted);
    }

    /**
     * Render receipt header with date and ID
     *
     * @static
     * @private
     * @param {Object} cookieData - Check-in data for header info
     * @returns {string} HTML string for receipt header
     */
    static renderReceiptHeader(cookieData) {
        const checkinDate = cookieData.date ? new Date(cookieData.date).toLocaleDateString() : new Date().toLocaleDateString();
        return `
            <div class="mb-4 py-2 border-b border-green">
            <div class="text-xs text-gray-500">Receipt ID: ${cookieData.id || 'N/A'}</div>
                <div class="text-sm flex justify-between pt-1 text-gray-500">Check-in Date: <strong>${checkinDate}</strong></div>
            
            </div>
        `;
    }

    /**
     * Render owner information section
     *
     * @static
     * @private
     * @param {Object} userInfo - Owner information object
     * @returns {string} HTML string for owner section
     */
    static renderOwnerSection(userInfo) {
        if (!userInfo) return '';

        let html = `
            <div class="mb-4 border-b pb-1">
                <h3 class="font-semibold text-green-dark mb-1">👤 Owner Information</h3>
                <div class="pl-4 text-sm">
                    <div class="flex justify-between"><strong>Name:</strong> ${userInfo.name || 'Not provided'}</div>
                    <div class="flex justify-between"><strong>Phone:</strong> ${userInfo.phone || 'Not provided'}</div>
                    <div class="flex justify-between"><strong>Email:</strong> ${userInfo.email || 'Not provided'}</div>`;

        if (userInfo.address) html += `<div class="flex justify-between"><strong>Address:</strong> ${userInfo.address}</div>`;
        if (userInfo.city && userInfo.zip) html += `<div class="flex justify-between"><strong>Location:</strong> ${userInfo.city}, ${userInfo.zip}</div>`;

        html += `
                </div>
            </div>`;

        return html;
    }

    /**
     * Render emergency contact section
     *
     * @static
     * @private
     * @param {Object} emergencyContact - Emergency contact information
     * @returns {string} HTML string for emergency contact section
     */
    static renderEmergencyContactSection(emergencyContact) {
        if (!emergencyContact || (!emergencyContact.name && !emergencyContact.phone)) return '';

        return `
            <div class="mb-4">
                <div class="font-semibold text-green-dark mb-1">🚨 Emergency Contact</div>
                <div class="pl-4 text-sm">
                    <div class="flex justify-between"><strong>Name:</strong> ${emergencyContact.name || 'Not provided'}</div>
                    <div class="flex justify-between"><strong>Phone:</strong> ${emergencyContact.phone || 'Not provided'}</div>
                </div>
            </div>`;
    }

    /**
     * Render pets section with detailed information
     *
     * @static
     * @private
     * @param {Array} pets - Array of pet objects
     * @returns {string} HTML string for pets section
     */
    static renderPetsSection(pets) {
        if (!Array.isArray(pets) || pets.length === 0) return '';

        let html = `
            <div class="mb-4">
                <h3 class="font-semibold text-green-dark mb-2">🐾 Pet Details (${pets.length} pet${pets.length > 1 ? 's' : ''})</h3>`;

        pets.forEach((pet, index) => {
            if (pet?.info) {
                html += `
                    <div class="mb-3 py-1 pl-4 border-l-2 border-yellow">
                        <div class="font-bold text-base pb-2">${pet.info.petName || 'Unnamed'} (${pet.info.petType || 'Unknown type'})</div>
                        <div class="text-sm text-gray-600 pl-2">`;

                // Basic info
                if (pet.info.petColor) html += `<div class="flex justify-between"><strong>Color:</strong> ${pet.info.petColor}</div>`;
                if (pet.info.petBreed) html += `<div class="flex justify-between"><strong>Breed:</strong> ${pet.info.petBreed}</div>`;
                if (pet.info.petAge) html += `<div class="flex justify-between"><strong>Age:</strong> ${new Date(pet.info.petAge).toLocaleDateString()}</div>`;
                if (pet.info.petWeight) html += `<div class="flex justify-between"><strong>Weight:</strong> ${pet.info.petWeight} lbs</div>`;
                if (pet.info.petGender) html += `<div class="flex justify-between"><strong>Gender:</strong> ${pet.info.petGender}</div>`;
                if (pet.info.petSpayed) html += `<div class="flex justify-between"><strong>Spayed/Neutered:</strong> ${pet.info.petSpayed}</div>`;

                html += `
                        </div>`;

                // Feeding schedule
                html += this.renderFeedingSchedule(pet.feeding);

                // Medication schedule
                html += this.renderMedicationSchedule(pet.medication);

                // Health information
                html += this.renderHealthNotes(pet.health);

                html += `
                    </div>`;
            }
        });

        html += `
            </div>`;

        return html;
    }

    /**
     * Render feeding schedule for a pet
     *
     * @static
     * @private
     * @param {Array} feeding - Feeding schedule array
     * @returns {string} HTML string for feeding schedule
     */
    static renderFeedingSchedule(feeding) {
        if (!feeding || feeding.length === 0) return '';

        let html = `<div class="mt-2 pb-1"><strong>🍽️ Feeding Schedule:</strong></div>`;
        const feedingByTime = {};

        feeding.forEach(feed => {
            if (!feedingByTime[feed.day_time]) feedingByTime[feed.day_time] = [];
            feedingByTime[feed.day_time].push(feed.feeding_med_details);
        });

        Object.entries(feedingByTime).forEach(([time, items]) => {
            html += `<p class="pl-2 flex justify-between text-xs"><strong>${time.charAt(0).toUpperCase() + time.slice(1)}:</strong> ${items.join(', ')}</p>`;
        });

        return html;
    }

    /**
     * Render medication schedule for a pet
     *
     * @static
     * @private
     * @param {Array} medication - Medication schedule array
     * @returns {string} HTML string for medication schedule
     */
    static renderMedicationSchedule(medication) {
        if (!medication || medication.length === 0) return '';

        let html = `<div class="mt-2 pb-1"><strong>💊 Medication Schedule:</strong></div>`;
        const medByTime = {};

        medication.forEach(med => {
            if (!medByTime[med.day_time]) medByTime[med.day_time] = [];
            medByTime[med.day_time].push(med.feeding_med_details);
        });

        Object.entries(medByTime).forEach(([time, items]) => {
            html += `<p class="pl-2 flex justify-between text-xs"><strong>${time.charAt(0).toUpperCase() + time.slice(1)}:</strong> ${items.join(', ')}</p>`;
        });

        return html;
    }

    /**
     * Render health notes for a pet
     *
     * @static
     * @private
     * @param {Object} health - Health information object
     * @returns {string} HTML string for health notes
     */
    static renderHealthNotes(health) {
        if (!health) return '';

        const healthNotes = [];
        if (health.unusualHealthBehavior) {
            healthNotes.push('Unusual behavior reported');
        }
        if (health.healthBehaviors) {
            healthNotes.push(`Behavior: ${health.healthBehaviors}`);
        }
        if (health.warnings) {
            healthNotes.push(`Warnings: ${health.warnings}`);
        }

        if (healthNotes.length === 0) return '';

        let html = `<div class="mt-2"><strong>🏥 Health Notes:</strong></div>`;
        healthNotes.forEach(note => {
            html += `<div class="pl-2 text-xs text-red-600">⚠️ ${note}</div>`;
        });

        return html;
    }

    /**
     * Render grooming details section
     *
     * @static
     * @private
     * @param {string} groomingDetails - Grooming detail notes
     * @returns {string} HTML string for grooming details section
     */
    static renderGroomingDetailsSection(groomingDetails) {
        if (!groomingDetails) return '';

        return `
            <div class="mb-4">
                <div class="font-semibold text-yellow-800 mb-1">📝 Grooming Instructions</div>
                <div class="pl-4 text-sm">${groomingDetails}</div>
            </div>`;
    }

    /**
     * Render inventory section
     *
     * @static
     * @private
     * @param {Array} inventory - Inventory items array
     * @returns {string} HTML string for inventory section
     */
    static renderInventorySection(inventory) {
        if (!inventory || inventory.length === 0) {
            return `
                <div class="mb-4">
                    <div class="font-semibold text-yellow-800 mb-1">🎒 Items to Store</div>
                    <div class="pl-4 text-sm text-gray-500">No items to store</div>
                </div>`;
        }

        let html = `
            <div class="mb-4">
                <div class="font-semibold text-yellow-800 mb-1">🎒 Items to Store (${inventory.length} item${inventory.length > 1 ? 's' : ''})</div>
                <div class="pl-4 text-sm">`;

        inventory.forEach((item, index) => {
            html += `• ${item}<br>`;
        });

        html += `
                </div>
            </div>`;

        return html;
    }

    /**
     * Setup terms checkbox event listener and initial state
     *
     * @static
     * @private
     * @param {HTMLElement} checkbox - Terms acceptance checkbox element
     * @param {boolean} termsAccepted - Current terms acceptance state from cookie
     * @returns {void}
     */
    static setupTermsCheckbox(checkbox, termsAccepted = false) {
        if (!checkbox) return;

        // Set checkbox state based on cookie data
        checkbox.checked = termsAccepted;

        // Remove existing event listeners to avoid duplicates
        const newCheckbox = checkbox.cloneNode(true);
        checkbox.parentNode.replaceChild(newCheckbox, checkbox);

        // Add event listener to the new checkbox
        newCheckbox.addEventListener('change', function() {
            // Import FormDataManager dynamically to avoid circular imports
            import('../FormDataManager.js').then(({ FormDataManager }) => {
                FormDataManager.setTermsAccepted(this.checked);
            });
        });
    }

    /**
     * Setup read terms button event listener
     *
     * @static
     * @private
     * @param {HTMLElement} button - Read terms button element
     * @returns {void}
     */
    static setupReadTermsButton(button) {
        if (!button) return;

        button.addEventListener('click', function() {
            // Import ValidationManager dynamically
            import('../managers/ValidationManager.js').then(({ ValidationManager }) => {
                ValidationManager.showTermsPopup();
            });
        });
    }

    /**
     * Update submit button enabled state
     *
     * @static
     * @private
     * @param {HTMLElement} button - Submit button element
     * @param {boolean} termsAccepted - Whether terms have been accepted
     * @returns {void}
     */
    static updateSubmitButtonState(button, termsAccepted) {
        if (!button) return;

        button.disabled = !termsAccepted;
        if (button.disabled) {
            button.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            button.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

export { SummaryRenderer };