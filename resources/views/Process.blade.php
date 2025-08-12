<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Parent Info') }}
        </h2>
    </x-slot>

    <div class="container px-4 py-8">
        <x-progress.bar />
        <div id="stepContainer" class="py-4 overflow-hidden relative">
            <div id="step1" class="step w-full active">
                <h1 class="text-center">Your Information</h1>
                <p class="text-lg text-center">Please follow the instructions to complete your process</p>
                <form id="ownerInfoForm" class="">
                    <label class="" for="phone">Phone Number</label>
                    <input class="" type="tel" id="phone" name="phone" placeholder="Your Phone Number"
                        value="{{ $user->phone ?? request()->get('phone') }}" pattern="[0-9]{10}" required>

                    <label class="" for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Your Name"
                        value="{{ old('name') }}" required>
                    <label class="" for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Your Email"
                        value="{{ old('email') }}" required>
                    <label class="" for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Your Address"
                        value="{{ old('address') }}" required>
                    <label class="" for="city">City</label>
                    <input type="text" id="city" name="city" placeholder="Your City"
                        value="{{ old('city') }}" required>
                    <label class="" for="zip">Zip Code</label>
                    <input type="number" id="zip" name="zip" placeholder="Your Zip Code"
                        value="{{ old('zip') }}" required>
                </form>
            </div>
            <div id="step2" class="step w-full">
                <h2>Pet Information</h2>
                <p>General Information of your best friend</p>
                <form id="petInfoForm" action="">
                    <label for="petName">Pet Name</label>
                    <input type="text" id="petName" name="petName" placeholder="Your Pet's Name" required>
                    <label for="petColor">Color</label>
                    <input type="text" id="petColor" name="petColor" placeholder="Your Pet's Color" required>

                    <label for="petType">Species</label>
                    <select id="petType" name="petType" required>
                        <option value="">Select Species</option>
                        <option value="dog">Dog</option>
                        <option value="cat">Cat</option>
                        <option value="other">Pig</option>
                        <option value="other">Other</option>
                    </select><br>
                    <label for="petBreed">Breed</label>
                    <input type="text" id="petBreed" name="petBreed" placeholder="Your Pet's Breed" required>

                    <label for="petAge">Birth Date</label>
                    <input type="date" id="petAge" name="petAge" placeholder="Your Pet's Age" required>
                    <label for="petWeight">Weight - in pounds</label>
                    <input type="number" id="petWeight" name="petWeight" placeholder="Your Pet's Weight in pounds" required>
                    <label for="petGender">Gender</label>
                    Male <input class="h-6 !w-6" type="radio" id="petGenderMale" name="petGender" value="male" required>
                    Female <input class="h-6 !w-6" type="radio" id="petGenderFemale" name="petGender" value="female" required>

                </form>
            </div>
            <div id="step3" class="step w-full">
                <form id="feedingAndMedication" action=""></form>
            </div>
            <div id="step4" class="step w-full">
                <form id="health" action=""></form>
            </div>
            <div id="step5" class="step w-full">
                <form id="Inventory" action=""></form>
            </div>
            <div id="step6" class="step w-full">
                <form id="Thanks" action=""></form>
            </div>
            <!-- Additional steps will be added here dynamically -->
        </div>
        <x-tabbar />
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let formData = getFormDataFromCookies() || {};

            // Load saved data into form
            $('#name').val(formData.name || '');
            $('#email').val(formData.email || '');

            const urlParams = new URLSearchParams(window.location.search);
            $('#phone').val(urlParams.get('phone') || formData.phone || '');

            $('#address').val(formData.address || '');

            function saveFormDataToCookies(data) {
                document.cookie = `formData=${JSON.stringify(data)}; path=/; max-age=3600`; // 1-hour expiration
            }

            function getFormDataFromCookies() {
                const cookie = document.cookie.split('; ').find(row => row.startsWith('formData='));
                return cookie ? JSON.parse(cookie.split('=')[1]) : null;
            }
        });
    </script>
</x-app-layout>
