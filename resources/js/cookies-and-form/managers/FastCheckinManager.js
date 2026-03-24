/**
 * FastCheckinManager - Manages fast check-in pills for existing user pets
 *
 * This manager displays a list of the user's existing pets from the database
 * as quick-select "pills" that allow them to add pets to the current check-in
 * without re-typing all information. Clicking a pill immediately adds the pet
 * to the check-in cookie and populates the pet form.
 *
 * Features:
 * - Reads DB pets from data-db-pets DOM attribute
 * - Filters out pets already in the check-in (in edit mode)
 * - Renders pill elements matching the existing .pill style
 * - On click: adds pet to cookie + updates UI
 * - Stays in sync with cookie changes via reactivity
 *
 * @class
 * @static
 *
 * @example
 * // Initialize on page load
 * FastCheckinManager.initialize();
 *
 * // Re-initialize after cookie changes
 * FastCheckinManager.refresh();
 */

import { FormDataManager } from "../FormDataManager.js";
import { PetPillManager } from "./PetPillManager.js";
import { FormHandler } from "./FormHandler.js";

class FastCheckinManager {
    /**
     * Container element for fast check-in pills
     * @static
     * @type {HTMLElement|null}
     * @private
     */
    static container = null;

    /**
     * Section element that holds the data-db-pets attribute
     * @static
     * @type {HTMLElement|null}
     * @private
     */
    static section = null;

    /**
     * Array of DB pets parsed from data-db-pets
     * @static
     * @type {Array}
     * @private
     */
    static dbPets = [];

    /**
     * Initializes the fast check-in manager
     *
     * Reads the user's DB pets from the DOM data attribute, filters out
     * pets already in the check-in (in edit mode), and renders pills for
     * the remaining pets. Safe to call multiple times.
     *
     * @static
     * @returns {void}
     *
     * @example
     * FastCheckinManager.initialize();
     *
     * @sideEffects
     * - Reads and parses data-db-pets from DOM
     * - Renders pill elements in #fastCheckinPillsContainer
     * - Sets up click handlers for each pill
     * - Logs initialization status
     */
    static initialize() {
        this.section = document.querySelector("#fastCheckinPillsSection");
        this.container = document.querySelector("#fastCheckinPillsContainer");

        if (!this.section || !this.container) {
            console.warn("FastCheckinManager: #fastCheckinPillsSection or #fastCheckinPillsContainer not found in DOM");
            return;
        }

        // Parse DB pets from data attribute
        const dbPetsStr = this.section.getAttribute("data-db-pets");
        if (!dbPetsStr) {
            console.log("FastCheckinManager: No DB pets available");
            this.dbPets = [];
            return;
        }

        try {
            this.dbPets = JSON.parse(dbPetsStr);
            console.log(`FastCheckinManager: Loaded ${this.dbPets.length} DB pets`);
        } catch (error) {
            console.error("FastCheckinManager: Failed to parse data-db-pets:", error);
            this.dbPets = [];
            return;
        }

        // Render the filtered pills
        this.render();
    }

    /**
     * Renders fast check-in pills for eligible DB pets
     *
     * Filters the DB pets to show only those not already in the check-in,
     * then creates and appends pill elements for each eligible pet.
     *
     * @static
     * @private
     * @returns {void}
     *
     * @sideEffects
     * - Clears #fastCheckinPillsContainer
     * - Appends pill elements
     * - Sets up click handlers
     * - Logs render status
     */
    static render() {
        if (!this.container) return;

        // Clear existing pills
        this.container.innerHTML = "";

        if (this.dbPets.length === 0) {
            console.log("FastCheckinManager: No DB pets to render");
            return;
        }

        // Get pets currently in the check-in
        const checkInPets = FormDataManager.getAllPetsFromCheckin();
        const checkInPetNames = checkInPets.map(p => p?.info?.petName || p?.petName).filter(Boolean);

        // Filter out pets already in the check-in (in edit mode)
        const eligiblePets = this.dbPets.filter(dbPet => {
            const isAlreadyAdded = checkInPetNames.includes(dbPet.petName);
            return !isAlreadyAdded;
        });

        console.log(
            `FastCheckinManager: Rendering ${eligiblePets.length} eligible pets ` +
            `(${this.dbPets.length} total - ${checkInPetNames.length} already in check-in)`
        );

        // Render a pill for each eligible pet
        eligiblePets.forEach((dbPet, index) => {
            this.createAndAppendPill(dbPet, index);
        });

        // If no eligible pets, hide the section
        if (eligiblePets.length === 0) {
            this.section.style.display = "none";
        } else {
            this.section.style.display = "block";
        }
    }

    /**
     * Creates and appends a pill element for a DB pet
     *
     * Creates a pill element matching the style of Pill.js but without
     * the close icon (since this is a template, not a check-in pet yet).
     * Sets up the click handler to add the pet to the check-in.
     *
     * @static
     * @private
     * @param {Object} dbPet - DB pet data object
     * @param {string} dbPet.petName - Pet's name
     * @param {string} dbPet.petType - Pet's type (dog, cat, etc.)
     * @param {number} index - Index in eligible pets list
     * @returns {void}
     *
     * @sideEffects
     * - Appends pill element to container
     * - Registers click event handler
     */
    static createAndAppendPill(dbPet, index) {
        const pillElement = document.createElement("div");
        pillElement.classList.add("pill");
        pillElement.dataset.dbPetId = dbPet.id;
        pillElement.dataset.petName = dbPet.petName;

        const textSpan = document.createElement("span");
        textSpan.textContent = dbPet.petName;

        const petTypeIcons = {
            dog: "mdi:dog",
            cat: "mdi:cat",
            pig: "mdi:pig-variant",
            rabbit: "mdi:rabbit",
            bird: "mdi:bird",
            fish: "mdi:fish",
            turtle: "mdi:turtle",
            hamster: "mdi:rodent",
            horse: "mdi:horse",
            other: "material-symbols:pet",
        };
        const iconName = petTypeIcons[dbPet.petType] || "material-symbols:pet";
        const petIcon = document.createElement("iconify-icon");
        petIcon.classList.add("pet-icon");
        petIcon.setAttribute("icon", iconName);
        petIcon.setAttribute("aria-label", `${dbPet.petName} (${dbPet.petType})`);

        pillElement.appendChild(textSpan);
        pillElement.appendChild(petIcon);

        // On click: add this pet to the check-in
        pillElement.addEventListener("click", () => {
            this.handlePillClick(dbPet);
        });

        this.container.appendChild(pillElement);
    }

    /**
     * Handles click event on a fast check-in pill
     *
     * Adds the pet to the check-in cookie, populates the form,
     * triggers reactivity to update the UI, and refreshes the
     * fast check-in pills to remove this pet from the list.
     *
     * @static
     * @private
     * @param {Object} dbPet - DB pet data
     * @returns {void}
     *
     * @sideEffects
     * - Adds pet to check-in cookie via FormDataManager
     * - Populates pet form fields with DB pet data
     * - Refreshes fast check-in pill display
     * - Triggers PetPillManager to rebuild pet pills
     * - Logs the action
     */
    static handlePillClick(dbPet) {
        console.log(`FastCheckinManager: Adding fast check-in pet "${dbPet.petName}"`);

        // Create form data object matching the structure expected by FormDataManager
        const formData = {
            petName: dbPet.petName,
            petType: dbPet.petType,
            petColor: dbPet.petColor,
            petBreed: dbPet.petBreed,
            petAge: dbPet.petAge,
            petWeight: dbPet.petWeight,
            petGender: dbPet.petGender,
            petSpayed: dbPet.petSpayed,
        };

        // Add the pet to the check-in (immediately, without form validation)
        // This uses the same method as the "Add Pet" form submission
        const success = FormDataManager.handleFormStep(1, formData, null);

        if (success) {
            console.log(`✅ FastCheckinManager: Pet "${dbPet.petName}" added to check-in`);

            // Populate the form with the pet data for review/editing
            FormHandler.populateFormWithData(formData);

            // Refresh the fast check-in pills to remove this pet from the list
            this.refresh();

            // Rebuild the pet pills in #petPillsContainer to show the newly added pet
            PetPillManager.addPetPillsToContainer();
        } else {
            console.error(`❌ FastCheckinManager: Failed to add pet "${dbPet.petName}"`);
        }
    }

    /**
     * Refreshes the fast check-in pill display
     *
     * Re-renders the pills, filtering out pets now in the check-in.
     * Safe to call after any cookie change. Called automatically by
     * reactivity system when pets are added/removed.
     *
     * @static
     * @returns {void}
     *
     * @example
     * FastCheckinManager.refresh();
     *
     * @sideEffects
     * - Clears and re-renders fast check-in pills
     * - May hide section if all pets are now in check-in
     */
    static refresh() {
        console.log("FastCheckinManager: Refreshing fast check-in pills");
        this.render();
    }

    /**
     * Checks if a fast check-in section exists in the DOM
     *
     * @static
     * @returns {boolean} True if the section is present
     */
    static isAvailable() {
        return !!document.querySelector("#fastCheckinPillsSection");
    }
}

export { FastCheckinManager };
