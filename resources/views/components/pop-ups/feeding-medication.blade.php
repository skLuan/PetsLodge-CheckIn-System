<div id="feedingMedicationPopup"
    class=" transition-all duration-500 z-30 ease-in-out translate-y-[75vh] fixed bottom-0 flex flex-col p-4 rounded-md shadow-lg w-full left-0">
    <form id="feedingPopupForm" class="bg-white p-6 rounded-md pb-16 z-30 border border-gray-lightest">
        <h3 class="font-bold text-lg mb-4">Adding Feeding/Medication</h3>
        <h5 class="pb-2">Time of the day (select one or more)</h5>
        <div class="flex justify-evenly border-b pb-4 border-l-gray-light mb-4">
            <label for="day_time_morning" class="inline cursor-pointer">
                <input type="checkbox" name="day_time[]" id="day_time_morning" value="morning" class="hidden">
                <div
                    class="btn-day-time px-4 py-2 flex items-center font-bold rounded-full shadow bg-gray-100 text-gray-700 transition-colors">
                    <iconify-icon icon="solar:sun-bold" class="text-2xl"></iconify-icon>
                </div>
            </label>

            <label for="day_time_afternoon" class="inline cursor-pointer">
                <input type="checkbox" name="day_time[]" id="day_time_afternoon" value="afternoon" class="hidden">
                <div
                    class="btn-day-time px-4 py-2 flex items-center font-bold rounded-full shadow bg-gray-100 text-gray-700 transition-colors">
                    <iconify-icon icon="solar:sun-fog-bold" class="text-2xl"></iconify-icon>
                </div>
            </label>

            <label for="day_time_night" class="inline cursor-pointer">
                <input type="checkbox" name="day_time[]" id="day_time_night" value="night" class="hidden">
                <div
                    class="btn-day-time px-4 py-2 flex items-center font-bold rounded-full shadow bg-gray-100 text-gray-700 transition-colors">
                    <iconify-icon icon="line-md:moon-filled" class="text-2xl"></iconify-icon>
                </div>
            </label>
        </div>
        <div class="flex justify-around pb-4 border-b border-l-gray-light mb-4">
            <label for="type_food" class="inline cursor-pointer">
                <input type="radio" name="type" id="type_food" value="food" class="hidden">
                <div class="transition-all px-4 py-2 min-w-24 font-bold text-center bg-gray-lightest text-gray rounded-full shadow mr-2">
                    Food
                </div>
            </label>
            <label for="type_medication" class="inline cursor-pointer">
                <input type="radio" name="type" id="type_medication" value="medication" class="hidden">
                <div class="transition-all px-4 py-2 min-w-24 font-bold bg-gray-lightest text-gray rounded-full shadow">
                    Medication
                </div>
            </label>
        </div>
        <label for="feeding-med-input">
            <span>Feeding/Medication Details</span>
            <input type="text" id="feeding-med-input" name="feeding_med_details" placeholder="1 cup dry food" value="">
        </label>
        <button type="submit"
            class="transition-all px-4 py-2 w-full min-w-24 font-bold bg-gray-lightest text-gray rounded-full shadow-inner mr-2">Add</button>
    </form>

</div>
