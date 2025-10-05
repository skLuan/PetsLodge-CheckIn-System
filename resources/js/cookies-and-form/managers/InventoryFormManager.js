/**
 * InventoryFormManager - Manages inventory form interactions and state
 *
 * This manager handles all inventory-related form operations including
 * adding items, handling keyboard input, and managing completion status.
 */

import { FormDataManager } from "../FormDataManager.js";

class InventoryFormManager {
    /**
     * Initializes inventory form event handlers
     */
    static initializeInventoryForm() {
        const inventoryForm = document.getElementById('inventoryForm');
        if (!inventoryForm) return;

        const itemInput = document.getElementById('itemName');
        const addItemBtn = document.getElementById('addInventoryItem');

        // Function to add item
        function addInventoryItem() {
            const itemText = itemInput.value.trim();
            if (!itemText) {
                alert('Please enter an item name');
                return;
            }

            FormDataManager.addInventoryItem(itemText);

            // Clear input
            itemInput.value = '';
            itemInput.focus();
        }

        // Add item on button click
        if (addItemBtn) {
            addItemBtn.addEventListener('click', addInventoryItem);
        }

        // Add item on Enter key
        if (itemInput) {
            itemInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addInventoryItem();
                }
            });
        }

        // Handle inventory complete checkbox
        const completeCheckbox = document.getElementById('inventoryComplete');
        if (completeCheckbox) {
            completeCheckbox.addEventListener('change', function() {
                FormDataManager.setInventoryComplete(this.checked);
            });
        }
    }
}

export { InventoryFormManager };