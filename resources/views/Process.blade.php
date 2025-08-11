<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Parent Info') }}
        </h2>
    </x-slot>

    <div class="container px-4 py-8">
        <h1 class="text-center">Your Information</h1>
        <p class="text-lg text-center">Please follow the instructions to complete your process</p>
        <div id="stepContainer" class="py-4">
            <div id="step1" class="step active">
                <form id="processForm">
                    <label class="" for="phone">Phone Number</label>
                    <input class="" type="tel" id="phone" name="phone" placeholder="Your Phone Number" value="{{ $user->phone ?? request()->get('phone') }}" pattern="[0-9]{10}" required>
                    
                    <label class="" for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Your Name" value="{{ old('name') }}" required>
                    <label class="" for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Your Email" value="{{ old('email') }}" required>
                    <label class="" for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Your Address" value="{{ old('address') }}" required>
                    <button type="button" id="nextStep">Next</button>
                </form>
            </div>
            <!-- Additional steps will be added here dynamically -->
        </div>
        <div id="stepProgress"></div>
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

            $('#nextStep').on('click', function() {
                // Validate form
                if (!$('#processForm')[0].checkValidity()) {
                    $('#processForm')[0].reportValidity();
                    return;
                }

                // Save data to cookies
                formData.name = $('#name').val();
                formData.email = $('#email').val();
                formData.phone = $('#phone').val();
                formData.address = $('#address').val();
                saveFormDataToCookies(formData);

                // Proceed to next step (e.g., Pet Info)
                // For now, we'll add a placeholder
                alert('Proceeding to next step (Pet Info). This will be expanded.');
                // Here you can dynamically load the next step or redirect
            });

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