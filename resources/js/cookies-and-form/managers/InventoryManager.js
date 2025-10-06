import { CoreDataManager } from "./CoreDataManager.js";
import { FormDataManager } from "../FormDataManager.js";
import { UtilitiesManager } from "./UtilitiesManager.js";

/**
 * Inventory Manager - Handles inventory-related operations
 */
class InventoryManager {
    /**
     * Agrega un item al inventario
     */
    static addInventoryItem(itemText) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData) return false;

        const inventory = currentData.inventory || [];
        const updatedInventory = [...inventory, itemText];

        return FormDataManager.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Elimina un item del inventario por índice
     */
    static removeInventoryItem(itemIndex) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.inventory) return false;

        const updatedInventory = currentData.inventory.filter((_, index) => index !== itemIndex);

        return FormDataManager.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Actualiza un item del inventario por índice
     */
    static updateInventoryItem(itemIndex, newText) {
        const currentData = CoreDataManager.getCheckinData();
        if (!currentData || !currentData.inventory) return false;

        const updatedInventory = [...currentData.inventory];
        updatedInventory[itemIndex] = newText;

        return FormDataManager.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Marca el inventario como completo
     */
    static setInventoryComplete(complete) {
        return FormDataManager.updateCheckinData({
            inventoryComplete: complete
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
     * Crea un elemento de item de inventario usando la plantilla estandarizada
     */
    static createInventoryItemElement(itemText, itemIndex, container) {
        // Use the standardized template from UtilitiesManager with callbacks
        UtilitiesManager.createInventoryItemElement(
            container,
            itemText,
            itemIndex,
            (index, newText) => this.updateInventoryItem(index, newText), // onUpdate
            (index) => this.removeInventoryItem(index) // onDelete
        );
    }
}

export { InventoryManager };