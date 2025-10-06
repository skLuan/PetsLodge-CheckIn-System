<form id="feedingAndMedication" action="" class="bg-green-lightest rounded-xl relative pt-12">
    <div id="sameFeedingContainer" class="mb-4 flex items-center hidden">
        <label class="inline-flex items-center text-gray font-semibold w-full">
            <input type="checkbox" id="sameFeedingForAll" name="sameFeedingForAll"
                class="p-0 my-0 mr-2 w-5 h-5 !rounded-sm">
            <span>Same feeding for all</span>
        </label>
    </div>
    <!------------------------------------------------------------------------ Morning Section -->
    <div class="container-day mb-8 hidden" data-time-slot="morning">
        <div class="flex items-center mb-2">
            <h3 class="text-2xl font-bold text-gray mr-2">Morning</h3>
            <iconify-icon icon="solar:sun-bold" class="text-2xl mr-2"></iconify-icon>
            <button type="button"
                class="btn-add-feeding-med text-orange-600 hover:text-orange-400 add-food-med-btn relative ml-auto pr-1"
                data-period="morning">
                <iconify-icon icon="ph:plus-circle" class="text-4xl font-bold"></iconify-icon>
            </button>
        </div>

        <div class="mb-2">
            <div class="flex items-center">
                <iconify-icon icon="ic:outline-cookie" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray pl-2">Food</div>
            </div>
            <div id="morning-food-list" class="border-t mt-1 border-green-30">
                <!-- Food items will be inserted here -->
            </div>
        </div>
        <div>
            <div class="flex items-center">
                <iconify-icon icon="ph:pill-bold" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray border-b border-green-30 pl-2">Medication</div>
            </div>
            <div id="morning-med-list">
                <!-- Medication items will be inserted here -->
            </div>
        </div>
    </div>
    <!------------------------------------------------------------------------ Afternoon Section -->
    <div class="container-day mb-8 hidden" data-time-slot="afternoon">
        <div class="flex items-center mb-2">
            <h3 class="text-2xl font-bold text-gray mr-2">Afternoon</h3>
            <iconify-icon icon="solar:sun-fog-bold" class="text-2xl mr-2"></iconify-icon>
            <button type="button"
                class="btn-add-feeding-med text-orange-600 hover:text-orange-400 add-food-med-btn relative ml-auto pr-1"
                data-period="afternoon">
                <iconify-icon icon="ph:plus-circle" class="text-4xl font-bold"></iconify-icon>
            </button>
        </div>

        <div class="mb-2">
            <div class="flex items-center">
                <iconify-icon icon="ic:outline-cookie" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray pl-2">Food</div>
            </div>
            <div id="afternoon-food-list" class="border-t mt-1 border-green-30">
                <!-- Food items will be inserted here -->
            </div>
        </div>
        <div>
            <div class="flex items-center">
                <iconify-icon icon="ph:pill-bold" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray border-b border-green-30 pl-2">Medication</div>
            </div>
            <div id="afternoon-med-list">
                <!-- Medication items will be inserted here -->
            </div>
        </div>
    </div>
    <!------------------------------------------------------------------------ Night Section -->
    <div class="container-day mb-8 hidden" data-time-slot="night">
        <div class="flex items-center mb-2">
            <h3 class="text-2xl font-bold text-gray mr-2">Night</h3>
            <iconify-icon icon="line-md:moon-filled" class="text-2xl mr-2"></iconify-icon>
            <button type="button"
                class="btn-add-feeding-med text-orange-600 hover:text-orange-400 add-food-med-btn relative ml-auto pr-1"
                data-period="night">
                <iconify-icon icon="ph:plus-circle" class="text-4xl font-bold"></iconify-icon>
            </button>
        </div>

        <div class="mb-2">
            <div class="flex items-center">
                <iconify-icon icon="ic:outline-cookie" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray pl-2">Food</div>
            </div>
            <div id="night-food-list" class="border-t mt-1 border-green-30">
                <!-- Food items will be inserted here -->
            </div>
        </div>
        <div>
            <div class="flex items-center">
                <iconify-icon icon="ph:pill-bold" class="text-2xl"></iconify-icon>
                <div class="font-semibold text-gray border-b border-green-30 pl-2">Medication</div>
            </div>
            <div id="night-med-list">
                <!-- Medication items will be inserted here -->
            </div>
        </div>
    </div>
    <div class="flex justify-center mb-8">
        <button type="button"
            class="btn-add-feeding-med text-orange-600 hover:text-orange-400 add-food-med-btn relative"
            data-period="night">
            <iconify-icon icon="ph:plus-circle" class="text-4xl font-bold"></iconify-icon>
        </button>
    </div>
</form>
