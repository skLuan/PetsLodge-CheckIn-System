<div id="step5" class="step w-full">
    <h2 class="text-center font-bold">Grooming</h2>
    <p class="text-lg text-center">Optional — choose grooming services and which mascot(s) take them.</p>
    <div id="groomingPopup" class="grooming bg-white p-4 rounded-lg border border-gray-300">
        <h3 class="font-bold text-lg mb-4">Grooming Options</h3>
        <div class="mb-4">
            <div>
                <h4 class="text-base font-semibold text-gray-700">Want grooming before picking up?</h4>
                <h3 class="mt-1 mb-3 text-sm">Take the service with 10% discount!</h3>
            </div>
            <div class="flex flex-col gap-3">
                <x-ui.checkbox name="groomingOptions[]" value="bath"><span class="text-sm font-medium">Bath</span></x-ui.checkbox>
                <x-ui.checkbox name="groomingOptions[]" value="nails"><span class="text-sm font-medium">Nails</span></x-ui.checkbox>
                <x-ui.checkbox name="groomingOptions[]" value="grooming"><span class="text-sm font-medium">Grooming</span></x-ui.checkbox>
                <x-ui.checkbox name="groomingOptions[]" value="no"><span class="text-sm font-medium">No</span></x-ui.checkbox>
            </div>
        </div>

        <!-- Which mascot(s) take the service -->
        <div class="mb-4">
            <h4 class="text-base font-semibold text-gray-700 mb-2">Which mascot(s) take the service?</h4>
            <div id="groomingPetSelector" class="flex flex-col gap-2">
                <!-- Populated dynamically per pet -->
            </div>
        </div>

        <!-- Conditional grooming appointment day -->
        <div class="conditional-grooming-appointment-popup mb-4" style="display: none;">
            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred appointment day:</label>
            <div class="flex flex-col gap-2">
                <x-ui.radio name="groomingAppointmentDay" value="Monday"><span class="text-sm font-medium">Monday</span></x-ui.radio>
                <x-ui.radio name="groomingAppointmentDay" value="Wednesday"><span class="text-sm font-medium">Wednesday</span></x-ui.radio>
                <x-ui.radio name="groomingAppointmentDay" value="Friday"><span class="text-sm font-medium">Friday</span></x-ui.radio>
            </div>
        </div>

        <!-- Conditional grooming notes textarea -->
        <div class="conditional-grooming-notes-popup mb-4" style="display: none;">
            <label for="groomingNotes" class="block text-sm font-medium text-gray-700 mb-2">Grooming instructions & notes:</label>
            <textarea class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent"
                id="groomingNotes" name="groomingNotes" rows="3"
                placeholder="Enter grooming instructions and any special notes"></textarea>
        </div>

        <div class="flex flex-col items-start justify-between mb-3">
            <label for="groomingAcknowledged" class="flex items-center cursor-pointer">
                <input type="checkbox" id="groomingAcknowledged" class="mr-3 h-4 w-4 focus:ring-green border-gray-300 rounded">
                <span class="text-sm text-gray-700">I confirm my grooming preferences above</span>
            </label>
        </div>
        <p class="text-xs text-gray-500 mt-2">Grooming is optional — leave all options blank to skip.</p>
    </div>
</div>
