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
</form>
