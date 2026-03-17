<div id="groomingPopup" class="w-full">
    <!-- Header -->
    <div class="flex justify-between items-center p-4 border-b bg-gray-50 rounded-t-lg">
        <h3 class="text-lg font-bold text-gray-800">Grooming Options</h3>
    </div>

    <!-- Content -->
    <div class="p-4">
        <div class="mb-4">
            <div>
                <h4 class="text-base font-semibold text-gray-700">Want grooming before picking up?</h4>
                <h3 class="mt-1 mb-3 text-sm">Take the service with 10% discount!</h3>
            </div>
            <div class="flex flex-col gap-3">
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

        <!-- Conditional grooming appointment day -->
        <div class="conditional-grooming-appointment-popup" style="display: none;">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Preferred appointment day:
            </label>
            <div class="flex flex-col gap-2">
                <label class="flex items-center">
                    <input type="radio" name="groomingAppointmentDay" value="Monday" class="mr-3 w-5 h-5">
                    <span class="text-sm font-medium">Monday</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="groomingAppointmentDay" value="Wednesday" class="mr-3 w-5 h-5">
                    <span class="text-sm font-medium">Wednesday</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="groomingAppointmentDay" value="Friday" class="mr-3 w-5 h-5">
                    <span class="text-sm font-medium">Friday</span>
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
                rows="3"
                placeholder="Enter grooming instructions and any special notes"
            ></textarea>
        </div>
    </div>
</div>