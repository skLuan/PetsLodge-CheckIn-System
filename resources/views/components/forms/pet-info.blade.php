<form id="petInfoForm" action="">
    <x-ui.input label="Pet Name" name="petName" placeholder="Your Pet's Name" required />

    <x-ui.input label="Color" name="petColor" placeholder="Your Pet's Color" required />
    <x-ui.select label="Species" name="petType" required>
        <option value="">Select Species</option>
        <option value="dog">Dog</option>
        <option value="cat">Cat</option>
        <option value="other">Pig</option>
        <option value="other">Other</option>
    </x-ui.select>
    <x-ui.input label="Breed" name="petBreed" placeholder="Your Pet's Breed" required />
    <x-ui.date-picker name="petAge" id="petAge" label="Birth Date" />
    <x-ui.input label="Weight - in pounds" name="petWeight" type="number" placeholder="Your Pet's Weight in pounds" required />
    <div class="pl-input-container flex  items-center pr-3">
        <label class="flex w-full items-center justify-center" for="petGender">Sex
            <span class="ml-auto">
                Male <input class="h-6 !w-6 mr-6 cursor-pointer" type="radio" id="petGenderMale" name="petGender" value="male" required>
                Female <input class="h-6 !w-6 cursor-pointer" type="radio" id="petGenderFemale" name="petGender" value="female" required>
            </span>
        </label>
    </div>
    <div class="pl-input-container flex items-center pr-3">
        <label class="flex w-full items-center justify-center" for="petSpayed">Spayed/<br>Neutered
            <span class="ml-auto">
                Yes <input class="h-6 !w-6 mr-6 cursor-pointer" type="radio" id="petSpayedYes" name="petSpayed" value="yes" required>
                No <input class="h-6 !w-6 cursor-pointer" type="radio" id="petSpayedNo" name="petSpayed" value="no" required>
            </span>
        </label>
    </div>
    <div class="pl-input-container my-10 w-full flex">
        <x-primary-button id="addPetBtn" class="mx-auto bg-green-dark text-white !text-xs">Add Pet</x-primary-button>
    </div>

    {{-- Health Information (per-pet) --}}
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-bold text-gray mb-4">Health Information</h3>

        <div class="pl-input-container">
            <h3 class="text-base font-bold text-gray mb-2">Did you notice unusual health behavior such as Vomiting, Diarrhea, Heart Conditions, Physical Condition, Seizure, others?</h3>
            <div class="flex items-center gap-6">
                <x-ui.radio name="unusualHealthBehavior" value="yes" input-class="mr-2">Yes</x-ui.radio>
                <x-ui.radio name="unusualHealthBehavior" value="no" input-class="mr-2">No</x-ui.radio>
            </div>
        </div>

        <div class="pl-input-container conditional-health-details" style="display: none;">
            <label for="healthBehaviorDetails">Which?
                <input type="text" id="healthBehaviorDetails" name="healthBehaviorDetails" placeholder="Diarrhea, Vomiting, etc.">
            </label>
        </div>

        <div class="pl-input-container">
            <label for="warnings"><span class="!text-red-700">Warnings</span></label>
            <p class="text-sm text-gray mb-2">Health Matters, Special Care, Behavioral (Friendly with other dogs, Food Aggressive, Anxiety, Others)</p>
            <textarea class="w-full" id="warnings" name="warnings" rows="3" placeholder="Enter any warnings or special notes"></textarea>
        </div>
    </div>
</form>
