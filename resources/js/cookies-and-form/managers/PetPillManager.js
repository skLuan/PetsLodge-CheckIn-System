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
     * Adds a delete-only pill to #petPillsContainer for each pet in the check-in.
     */
    static addPetPillsToContainer() {
        const pets = FormDataManager.getAllPetsFromCheckin();
        const container = document.querySelector("#petPillsContainer");

        if (!container) {
            console.warn("No #petPillsContainer found in the DOM.");
            return;
        }

        container.innerHTML = "";

        if (pets.length === 0) {
            console.log("No pets found in cookies, skipping pill creation.");
            return;
        }

        pets.forEach((pet, index) => {
            const petName = pet?.info?.petName || pet?.petName;
            const petType = pet?.info?.petType || pet?.petType;
            if (pet && petName) {
                const pill = new Pill(petName, petType, index);
                const pillElement = pill.render();
                container.appendChild(pillElement);
                
            } else {
                console.warn(
                    `Pet at index ${index} is missing petName or is invalid.`
                );
            }
        });

        console.log(`Added ${pets.length} pet pills to #petPillsContainer.`);
    }

}

export { PetPillManager };