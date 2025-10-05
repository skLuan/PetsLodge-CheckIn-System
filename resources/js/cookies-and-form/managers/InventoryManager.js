import { CoreDataManager } from "./CoreDataManager.js";

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

        return CoreDataManager.updateCheckinData({
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

        return CoreDataManager.updateCheckinData({
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

        return CoreDataManager.updateCheckinData({
            inventory: updatedInventory
        });
    }

    /**
     * Marca el inventario como completo
     */
    static setInventoryComplete(complete) {
        return CoreDataManager.updateCheckinData({
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
}

export { InventoryManager };