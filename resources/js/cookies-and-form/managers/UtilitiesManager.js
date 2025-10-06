import { CookieManager } from "../CookieManager.js";
import { CoreDataManager } from "./CoreDataManager.js";
import { PetManager } from "./PetManager.js";

/**
 * Utilities Manager - Helper functions, debugging, and utilities
 */
class UtilitiesManager {
    /**
     * Método de debugging
     */
    static debugCheckinData() {
        const checkinData = CoreDataManager.getCheckinData();
        console.group("🍪 Checkin Debug Information");
        console.log("Full checkin data:", checkinData);
        console.log("Pets count:", checkinData?.pets?.length || 0);
        console.log("Cookie size:", CookieManager.getCookieSize(), "bytes");
        console.log("Auto-save active:", !!this.autoSaveTimer);
        console.groupEnd();
        return checkinData;
    }

    /**
     * Obtiene el tamaño total de las cookies en bytes
     */
    static getCookieSize() {
        return CookieManager.getCookieSize();
    }

    /**
     * Verifica si una cookie puede ser establecida (límite de 4KB por cookie)
     */
    static canSetCookie(name, value) {
        return CookieManager.canSetCookie(name, value);
    }

    /**
     * Obtiene todas las cookies que coincidan con un patrón
     */
    static getCookiesByPattern(pattern) {
        return CookieManager.getCookiesByPattern(pattern);
    }

    /**
     * Limpia todas las cookies que coincidan con un patrón
     */
    static clearCookiesByPattern(pattern) {
        return CookieManager.clearCookiesByPattern(pattern);
    }

    /**
     * Verifica si existe una cookie con el nombre dado
     */
    static hasCookie(name) {
        return CookieManager.hasCookie(name);
    }

    /**
     * Obtiene el índice de la mascota actualmente seleccionada
     */
    static getCurrentSelectedPetIndex() {
        const selectedPill = document.querySelector(".pill.selected");
        return selectedPill ? parseInt(selectedPill.dataset.index, 10) : null;
    }

    /**
     * Alterna el modo de edición del input
     */
    static toggleEdit(input, editBtn) {
        const isEditing = !input.disabled;
        input.disabled = isEditing;

        if (isEditing) {
            // Save changes
            const newValue = input.value.trim();
            if (newValue !== '') {
                const petIndex = parseInt(input.dataset.petIndex);
                const type = input.dataset.type;
                const itemIndex = parseInt(input.dataset.itemIndex);

                // Update the item in the data
                PetManager.updateFeedingMedicationItem(petIndex, type, itemIndex, {
                    feeding_med_details: newValue
                });
            }
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-green hover:text-blue-700 focus:outline focus:outline-2 focus:outline-green w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Edit item');
        } else {
            // Enter edit mode
            input.focus();
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:check" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-green-500 hover:text-green-700 focus:outline focus:outline-2 focus:outline-green-500 w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Save changes');
        }
    }

    /**
     * Crea un elemento editable para alimentación/medicación
     */
    static createEditableItem(container, item, petIndex, type, itemIndex, petName) {
        if (!container) return;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'flex items-center rounded-b-sm py-1';

        // Edit button
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'ml-2 text-green-dark hover:text-blue-700 focus:outline focus:outline-2 focus:outline-green w-11 h-11 flex items-center justify-center rounded';
        editBtn.setAttribute('aria-label', 'Edit item');
        editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
        editBtn.onclick = () => this.toggleEdit(input, editBtn);

        // Input field
        const input = document.createElement('input');
        input.type = 'text';
        input.value = item.feeding_med_details || '';
        input.disabled = true;
        input.className = 'flex-1 mb-0';
        input.dataset.petIndex = petIndex;
        input.dataset.type = type;
        input.dataset.itemIndex = itemIndex;

        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'ml-2 text-orange-600 hover:text-red-700 focus:outline focus:outline-2 focus:outline-orange-600 w-11 h-11 flex items-center justify-center rounded';
        deleteBtn.setAttribute('aria-label', 'Delete item');
        deleteBtn.innerHTML = '<iconify-icon icon="material-symbols:delete" class="text-lg"></iconify-icon>';
        deleteBtn.onclick = () => {
            if (confirm('Are you sure you want to delete this item?')) {
                PetManager.removeFeedingMedicationItem(petIndex, type, itemIndex);
            }
        };

        itemDiv.appendChild(input);
        itemDiv.appendChild(editBtn);
        itemDiv.appendChild(deleteBtn);
        container.appendChild(itemDiv);
    }

    /**
     * Crea un elemento editable para items de inventario
     */
    static createInventoryItemElement(container, itemText, itemIndex, onUpdate, onDelete) {
        if (!container) return;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'flex items-center justify-between mb-2';

        // Input field (initially as span for display)
        const textContainer = document.createElement('div');
        textContainer.className = 'bg-white h-full w-full pl-3 py-2 rounded-md border flex flex-row';

        const textSpan = document.createElement('span');
        textSpan.className = 'flex-1 font-medium';
        textSpan.textContent = itemText;
        textContainer.appendChild(textSpan);


        // Edit button
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'ml-2 text-green-dark hover:text-blue-700 focus:outline focus:outline-2 focus:outline-green w-11 h-11 flex items-center justify-center rounded';
        editBtn.setAttribute('aria-label', 'Edit item');
        editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
        editBtn.onclick = () => this.toggleInventoryEdit(textSpan, editBtn, itemIndex, onUpdate);

        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'text-orange-600 hover:text-red-700 w-11 h-11 flex items-center justify-center rounded ml-2';
        deleteBtn.setAttribute('aria-label', 'Delete item');
        deleteBtn.innerHTML = '<iconify-icon icon="material-symbols:delete" class="text-lg"></iconify-icon>';
        deleteBtn.onclick = () => {
            if (confirm('Are you sure you want to delete this item?')) {
                onDelete(itemIndex);
            }
        };

        itemDiv.appendChild(textContainer);
        itemDiv.appendChild(editBtn);
        itemDiv.appendChild(deleteBtn);
        container.appendChild(itemDiv);
    }

    /**
     * Alterna el modo de edición para items de inventario
     */
    static toggleInventoryEdit(textSpan, editBtn, itemIndex, onUpdate) {
        const isEditing = textSpan.contentEditable === 'true';
        textSpan.contentEditable = !isEditing;

        if (isEditing) {
            // Save changes
            const newValue = textSpan.textContent.trim();
            if (newValue !== '') {
                onUpdate(itemIndex, newValue);
            }
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:edit" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-green hover:text-blue-700 focus:outline focus:outline-2 focus:outline-green w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Edit item');
        } else {
            // Enter edit mode
            textSpan.focus();
            editBtn.innerHTML = '<iconify-icon icon="material-symbols:check" class="text-lg"></iconify-icon>';
            editBtn.className = 'mr-2 text-green-500 hover:text-green-700 focus:outline focus:outline-2 focus:outline-green-500 w-11 h-11 flex items-center justify-center rounded';
            editBtn.setAttribute('aria-label', 'Save changes');
        }
    }
}

export { UtilitiesManager };