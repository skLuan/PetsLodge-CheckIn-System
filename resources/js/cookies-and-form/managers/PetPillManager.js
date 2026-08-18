/**
 * PetPillManager - Manages pet pill display and interactions
 *
 * This manager handles the creation, display, and management of pet pills
 * in the UI, including adding pills to containers and managing their state.
 */

import Pill from "../../Pill.js";
import { FormDataManager } from "../FormDataManager.js";

class PetPillManager {
    /**
     * Adds pet pill elements to the #petPillsContainer for each pet
     * 
     * Automatically selects the first pill to ensure a pet is always "current"
     * This fixes the issue where the feeding/medication popup wouldn't populate
     * because no pet index was selected.
     */
    static addPetPillsToContainer(preserveSelection = true) {
        const pets = FormDataManager.getAllPetsFromCheckin();
        const container = document.querySelector("#petPillsContainer");
        console.log("addPetPillsToContainer called");

        if (!container) {
            console.warn("No #petPillsContainer found in the DOM.");
            return;
        }

        // Remember the selected pet BEFORE clearing so the rebuild can restore it
        // (avoids snapping the highlight back to the first pet).
        const selectedIndex = this.getSelectedPetIndex();

        container.innerHTML = "";

        if (pets.length === 0) {
            console.log("No pets found in cookies, skipping pill creation.");
            return;
        }

        let firstPill = null;
        pets.forEach((pet, index) => {
            const petName = pet?.info?.petName || pet?.petName;
            const petType = pet?.info?.petType || pet?.petType;
            if (pet && petName) {
                const pill = new Pill(petName, petType, index);
                const pillElement = pill.render();
                container.appendChild(pillElement);
                
                // Store the first pill to select it automatically
                if (index === 0) {
                    firstPill = pillElement;
                }
            } else {
                console.warn(
                    `Pet at index ${index} is missing petName or is invalid.`
                );
            }
        });

        // Restore the previous selection when possible, otherwise auto-select the
        // first pill so a pet is always "current" for feeding/medication operations.
        let pillToSelect = null;
        if (preserveSelection && selectedIndex !== null && selectedIndex < pets.length) {
            pillToSelect = container.querySelector(`[data-index="${selectedIndex}"]`);
        }
        if (pillToSelect) {
            pillToSelect.classList.add("selected");
        } else if (firstPill) {
            console.log("[PetPillManager] Auto-selecting first pet pill");
            firstPill.classList.add("selected");
        }

        console.log(`Added ${pets.length} pet pills to #petPillsContainer.`);
    }

    /**
     * Gets the currently selected pet pill index
     * @returns {number|null} Selected pet index or null
     */
    static getSelectedPetIndex() {
        const selectedPill = document.querySelector(".pill.selected");
        return selectedPill ? parseInt(selectedPill.dataset.index, 10) : null;
    }

    /**
     * Deselects every pet pill in the container.
     */
    static deselectAll() {
        document.querySelectorAll("#petPillsContainer .pill.selected")
            .forEach((pill) => pill.classList.remove("selected"));
    }
}

export { PetPillManager };