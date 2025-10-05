import { CoreDataManager } from "./CoreDataManager.js";
import config from "../config.js";

const { DEFAULT_PET_STRUCTURE } = config;

/**
 * Pet Manager - Handles pet data operations
 */
class PetManager {
    /**
     * Agrega una nueva mascota al checkin
     */
    static addPetToCheckin(petData) {
        const currentData = CoreDataManager.getCheckinData();
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

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza una mascota específica en el checkin
     */
    static updatePetInCheckin(petIndex, petData) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found`);
            return false;
        }

        const updatedPets = [...currentData.pets];

        // Merge de datos de la mascota
        updatedPets[petIndex] = CoreDataManager.deepMerge(updatedPets[petIndex], {
            info: { ...petData },
            lastUpdated: new Date().toISOString(),
        });

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Elimina una mascota específica del checkin
     */
    static removePetFromCheckin(petIndex) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found for removal`);
            return false;
        }

        const updatedPets = currentData.pets.filter((_, index) => index !== petIndex);

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Agrega alimentación o medicamento a una mascota específica
     */
    static addPetFeedingOrMedication(petIndex, type, data) {
        const currentData = CoreDataManager.getCheckinData();
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

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza información de salud de una mascota
     */
    static updatePetHealth(petIndex, healthData) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found`);
            return false;
        }

        const updatedPets = [...currentData.pets];
        updatedPets[petIndex] = CoreDataManager.deepMerge(updatedPets[petIndex], {
            health: { ...healthData },
            lastUpdated: new Date().toISOString(),
        });

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza información de salud de una mascota (simplified version)
     */
    static updatePetHealthInfo(petIndex, healthData) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) {
            console.warn(`Pet at index ${petIndex} not found for health update`);
            return false;
        }

        const pet = { ...currentData.pets[petIndex] };
        pet.health = { ...pet.health, ...healthData };
        pet.lastUpdated = new Date().toISOString();

        const updatedPets = [...currentData.pets];
        updatedPets[petIndex] = pet;

        return CoreDataManager.updateCheckinData({ pets: updatedPets });
    }

    /**
     * Actualiza un elemento específico de alimentación/medicación
     */
    static updateFeedingMedicationItem(petIndex, type, itemIndex, updates) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) return false;

        const pet = { ...currentData.pets[petIndex] };
        const array = type === 'feeding' ? pet.feeding : pet.medication;

        if (array && array[itemIndex]) {
            array[itemIndex] = { ...array[itemIndex], ...updates };
            pet.lastUpdated = new Date().toISOString();

            const updatedPets = [...currentData.pets];
            updatedPets[petIndex] = pet;

            return CoreDataManager.updateCheckinData({ pets: updatedPets });
        }

        return false;
    }

    /**
     * Elimina un elemento específico de alimentación/medicación
     */
    static removeFeedingMedicationItem(petIndex, type, itemIndex) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.pets || !currentData.pets[petIndex]) return false;

        const pet = { ...currentData.pets[petIndex] };
        const array = type === 'feeding' ? pet.feeding : pet.medication;

        if (array && array[itemIndex]) {
            array.splice(itemIndex, 1); // Remove the item
            pet.lastUpdated = new Date().toISOString();

            const updatedPets = [...currentData.pets];
            updatedPets[petIndex] = pet;

            return CoreDataManager.updateCheckinData({ pets: updatedPets });
        }

        return false;
    }

    /**
     * Obtiene todas las mascotas en formato compatible con el código legacy
     */
    static getAllPetsFromCheckin() {
        const checkinData = CoreDataManager.getCheckinData();
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
     * Genera un ID único para una mascota
     */
    static generatePetId() {
        return (
            "pet_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
        );
    }
}

export { PetManager };