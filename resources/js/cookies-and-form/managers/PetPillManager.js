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
     */
    static addPetPillsToContainer() {
        const pets = FormDataManager.getAllPetsFromCheckin();
        const container = document.querySelector("#petPillsContainer");
        console.log("addPetPillsToContainer called");

        if (container) {
            container.innerHTML = "";
        } else {
            console.warn("No #petPillsContainer found in the DOM.");
            return;
        }

        if (pets.length === 0) {
            console.log("No pets found in cookies, skipping pill creation.");
            return;
        }

        pets.forEach((pet, index) => {
            if (pet && pet.petName) {
                const pill = new Pill(pet.petName, pet.petType, index);
                container.appendChild(pill.render());
            } else {
                console.warn(
                    `Pet at index ${index} is missing petName or is invalid.`
                );
            }
        });

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
}

export { PetPillManager };