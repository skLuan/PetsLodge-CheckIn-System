{{-- Delete pet confirmation modal --}}
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl w-80 p-6">
        <h3 class="text-lg font-bold text-gray mb-2">Delete Pet</h3>
        <p class="text-sm text-gray mb-4">
            Are you sure you want to delete <strong id="confirmDeletePetName"></strong>?
        </p>
        <div class="flex justify-end gap-2">
            <button type="button" id="confirmDeleteCancel"
                    class="px-4 py-2 bg-gray-lightest text-gray font-bold rounded-full transition-colors hover:bg-gray-light">
                Cancel
            </button>
            <button type="button" id="confirmDeleteBtn"
                    class="px-4 py-2 bg-red-600 text-white font-bold rounded-full transition-colors hover:bg-red-700">
                Delete
            </button>
        </div>
    </div>
</div>
