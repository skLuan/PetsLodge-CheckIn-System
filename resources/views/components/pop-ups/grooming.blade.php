<div id="groomingPopup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">Grooming Options</h3>
                <button type="button" id="closeGroomingPopup" class="text-gray-400 hover:text-gray-600">
                    <iconify-icon icon="material-symbols:close" class="text-2xl"></iconify-icon>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-700 mb-4">Want grooming before picking up?</h4>
                    <div class="flex flex-col gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="groomingOptions[]" value="bath" class="mr-3 w-5 h-5">
                            <span class="text-sm font-medium">Bath</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="groomingOptions[]" value="nails" class="mr-3 w-5 h-5">
                            <span class="text-sm font-medium">Nails</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="groomingOptions[]" value="grooming" class="mr-3 w-5 h-5">
                            <span class="text-sm font-medium">Grooming</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="groomingOptions[]" value="no" class="mr-3 w-5 h-5">
                            <span class="text-sm font-medium">No</span>
                        </label>
                    </div>
                </div>

                <!-- Conditional grooming notes textarea -->
                <div class="conditional-grooming-notes-popup" style="display: none;">
                    <label for="groomingNotes" class="block text-sm font-medium text-gray-700 mb-2">
                        Grooming instructions & notes:
                    </label>
                    <textarea
                        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        id="groomingNotes"
                        name="groomingNotes"
                        rows="4"
                        placeholder="Enter grooming instructions and any special notes"
                    ></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end items-center p-6 border-t bg-gray-50">
                <button
                    type="button"
                    id="confirmGrooming"
                    class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                >
                    Confirm & Continue
                </button>
            </div>
        </div>
    </div>
</div>