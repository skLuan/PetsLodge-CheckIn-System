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
    <div class="pl-input-container" x-data="datePicker()">
        <label for="petAge">Birth Date
            <input type="text" id="petAge" name="petAge" placeholder="Select Birth Date" required readonly
                   x-bind:value="formatSelected()"
                   x-on:click="open = true"
                   class="cursor-pointer">
        </label>

        {{-- Calendar Modal --}}
        <div x-show="open" x-transition.opacity x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             x-on:click.self="open = false"
             x-on:keydown.escape.window="open = false">
            <div class="bg-white rounded-2xl shadow-xl w-80 p-5" x-on:click.stop>
                {{-- Header --}}
                <div class="flex items-center justify-between mb-4">
                    <button type="button" x-on:click="prevMonth()" class="p-1 rounded-lg hover:bg-green-lightest text-green-dark font-bold text-lg">&larr;</button>
                    <span class="font-bold text-green-dark" x-text="monthNames[month] + ' ' + year"></span>
                    <button type="button" x-on:click="nextMonth()" class="p-1 rounded-lg hover:bg-green-lightest text-green-dark font-bold text-lg">&rarr;</button>
                </div>

                {{-- Day-of-week headers --}}
                <div class="grid grid-cols-7 text-center text-xs font-bold text-gray mb-1">
                    <template x-for="d in dayNames" :key="d">
                        <span x-text="d"></span>
                    </template>
                </div>

                {{-- Days grid --}}
                <div class="grid grid-cols-7 text-center gap-y-1">
                    {{-- Empty cells before first day --}}
                    <template x-for="blank in firstDayOfWeek()" :key="'b'+blank">
                        <span></span>
                    </template>
                    {{-- Day buttons --}}
                    <template x-for="day in daysInMonth()" :key="day">
                        <button type="button"
                                x-on:click="selectDay(day)"
                                x-bind:class="{
                                    'bg-green text-white font-bold': isSelected(day),
                                    'hover:bg-green-lightest': !isSelected(day),
                                    'text-gray-light pointer-events-none': isFuture(day)
                                }"
                                class="w-9 h-9 mx-auto rounded-full text-sm transition-colors duration-200"
                                x-bind:disabled="isFuture(day)"
                                x-text="day">
                        </button>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="flex justify-between mt-4">
                    <button type="button" x-on:click="clearDate()" class="text-sm text-gray hover:text-green-dark transition-colors">Clear</button>
                    <button type="button" x-on:click="open = false" class="text-sm font-bold text-green-dark hover:underline">Done</button>
                </div>
            </div>
        </div>
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
