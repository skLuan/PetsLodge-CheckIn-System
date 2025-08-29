<div id="feedingMedicationPopup" class="bg-white transition-all duration-500 z-30 ease-in-out translate-y-[45vh] fixed bottom-0 left-0 flex flex-col p-4 mr-2 rounded-md shadow-lg w-full">

    <div class="flex justify-evenly pb-4 border-b border-l-gray-light mb-4">
        <button class="px-4 flex flex-row py-2 font-bold bg-gray-lightest text-gray rounded-full shadow">
            <iconify-icon icon="solar:sun-bold" class="text-2xl"></iconify-icon>

        </button>
        <button class="px-4 flex flex-row py-2 font-bold bg-gray-lightest text-gray rounded-full shadow mr-2">
            <iconify-icon icon="solar:sun-fog-bold" class="text-2xl"></iconify-icon>
        </button>
        <button class="px-4 flex flex-row py-2 font-bold bg-gray-lightest text-gray rounded-full shadow">
            <iconify-icon icon="line-md:moon-filled" class="text-2xl"></iconify-icon>
        </button>
    </div>
    <div class="flex justify-around pb-4 border-b border-l-gray-light mb-4">
        <button class="px-4 py-2 min-w-24 font-bold bg-gray-lightest text-gray rounded-full shadow mr-2">Food</button>
        <button class="px-4 py-2 min-w-24 font-bold bg-gray-lightest text-gray rounded-full shadow">Medication</button>
    </div>
    <label for="feeding-med-input">
        <span>Feeding/Medication Details</span>
        <input type="text" id="feeding-med-input" placeholder="1 cup dry food">
    </label>
</div>
