 <x-app-layout>
     <x-slot name="header">
         <h2 class="font-semibold text-xl text-gray-800 leading-tight">
             {{ __('Check In') }}
         </h2>
     </x-slot>

 <div class="container text-center px-4 py-8 max-w-screen-sm mx-auto">
     <img src="{{ asset('images/logo-pets-lodge.png') }}" alt="Pet Lodge & Spa Logo" class="logo w-48 mx-auto">
     <h1 class="text-green-dark">Check In</h1>
     <p class="text-lg">Please enter your phone to start your process</p>
     <form id="checkInForm">
         <input type="tel" id="phone" name="phone" placeholder="321 6549 879" pattern="[0-9]{10}" required>
         <x-primary-button class="bg-yellow mt-6 !text-base shadow-md !text-brown-dark !font-bold">
             {{ __('Next') }}
         </x-primary-button>
     </form>
 </div>
 <figure class="fixed bottom-0 w-full -z-10">
    <picture>
        <img src="{{ asset('images/dog-bg@2x.jpg') }}" alt="Check In" class="w-full lg:w-1/3 mx-auto">
    </picture>
 </figure>

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <script>
     $(document).ready(function() {
         $('#checkInForm').on('submit', function(e) {
             e.preventDefault();
             const phone = $('#phone').val();

             $.ajax({
                 url: '{{ route('check-user') }}',
                 method: 'POST',
                 data: {
                     phone: phone,
                     _token: '{{ csrf_token() }}'
                 },
                 success: function(response) {
                     if (response.userExists === false) {
                         window.location.href = '{{ route('new-form') }}?phone=' + phone;
                     } else if (response.userExists && !response.hasCheckIn) {
                         window.location.href = '{{ route('new-form-pre-filled') }}?phone=' +
                             phone;
                     } else if (response.userExists && response.hasCheckIn) {
                         window.location.href = '{{ route('view-check-in') }}?phone=' +
                         phone;
                     }
                 },
                 error: function() {
                     alert('An error occurred. Please try again.');
                 }
             });
         });
     });
 </script>
 </x-app-layout>
