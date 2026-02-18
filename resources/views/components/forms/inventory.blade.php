<form id="inventoryForm" action="">
    <div class="pl-input-container">
        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray">Inventory Items</h3>
            <p>List and describe all the items you bring, for example: Blue leash with bones.</p>
            <p class="font-bold">(We won’t be responsible for items not listed)</p>
        </div>
        <!-- Inventory Items List -->
        <div id="inventoryItemsList" class="space-y-2 h-full min-h-44 border-y border-green py-2 mb-4 overflow-y-auto flex flex-col justify-end">
            <!-- Items will be dynamically added here -->
        </div>
        <!-- Add Item Form -->
        <div class="mb-6 flex flex-col">
            <h4 class="font-bold mb-3">Add Inventory Item</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="itemName" class="block text-sm font-medium text-gray mb-1">Item Name</label>
                    <input type="text" id="itemName" name="itemName" placeholder="e.g., Dog Food" class="w-full">
                </div>
            </div>
            <p>Hint: Use the Key "enter" to add a new item</p>
            <button type="button" id="addInventoryItem"
                class="bg-green-dark self-end text-white px-4 py-2 rounded-full hover:bg-green-900">
                Add Item
            </button>
        </div>

        <!-- Confirmation Checkbox -->
        <div class="mt-8 pt-4 border-t border-gray-200">
            <label class="flex items-center justify-center">
                <input type="checkbox" id="inventoryComplete" name="inventoryComplete" class="mr-3 w-5 h-5 mb-0">
                <span class="text-sm font-bold">Im not leaving anything in the inventory</span>
            </label>
        </div>
    </div>
</form>
