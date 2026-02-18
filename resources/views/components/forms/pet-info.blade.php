<form id="petInfoForm" action="">
    <div class="pl-input-container">
        <label for="petName">Pet Name
            <input type="text" id="petName" name="petName" placeholder="Your Pet's Name" required>
        </label>
    </div>
    <div class="pl-input-container">
        <label for="petColor">Color
            <input type="text" id="petColor" name="petColor" placeholder="Your Pet's Color" required>
        </label>
    </div>
    <div class="pl-input-container pr-3">
        <label class="w-full justify-center items-center flex" for="petType">Species
            <select class="ml-auto rounded-md" id="petType" name="petType" required>
                <option value="">Select Species</option>
                <option value="dog">Dog</option>
                <option value="cat">Cat</option>
                <option value="other">Pig</option>
                <option value="other">Other</option>
            </select>
        </label>
    </div>
    <div class="pl-input-container">
        <label for="petBreed">Breed
            <input type="text" id="petBreed" name="petBreed" placeholder="Your Pet's Breed" required>
        </label>
    </div>
    <div class="pl-input-container">
        <label for="petAge">Birth Date
            <input type="date" id="petAge" name="petAge" placeholder="Your Pet's Age" required>
        </label>
    </div>
    <div class="pl-input-container">
        <label for="petWeight">Weight - in pounds
            <input type="number" id="petWeight" name="petWeight" placeholder="Your Pet's Weight in pounds" required>
        </label>
    </div>
    <div class="pl-input-container flex  items-center pr-3">
        <label class="flex w-full items-center justify-center" for="petGender">Sex
            <span class="ml-auto">
                Male <input class="h-6 !w-6 mr-6" type="radio" id="petGenderMale" name="petGender" value="male" required>
                Female <input class="h-6 !w-6" type="radio" id="petGenderFemale" name="petGender" value="female" required>
            </span>
        </label>
    </div>
    <div class="pl-input-container flex items-center pr-3">
        <label class="flex w-full items-center justify-center" for="petSpayed">Spayed/<br>Neutered
            <span class="ml-auto">
                Yes <input class="h-6 !w-6 mr-6" type="radio" id="petSpayedYes" name="petSpayed" value="yes" required>
                No <input class="h-6 !w-6" type="radio" id="petSpayedNo" name="petSpayed" value="no" required>
            </span>
        </label>
    </div>
    <div class="pl-input-container my-10 w-full flex">
        <x-primary-button class="mx-auto bg-green-dark text-white !text-xs">Add Pet</x-primary-button>
    </div>
</form>
