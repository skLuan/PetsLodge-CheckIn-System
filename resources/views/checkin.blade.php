 <x-app-layout>
     <x-slot name="header">
         <h2 class="font-semibold text-xl text-gray-800 leading-tight">
             {{ __('Check In') }}
         </h2>
     </x-slot>

 <div class="container text-center px-4 py-8">
     <img src="{{ asset('images/pet-lodge-logo.png') }}" alt="Pet Lodge & Spa Logo" class="logo">
     <h1>Check In</h1>
     <p class="text-lg">Please enter your phone to start your process</p>
     <form id="checkInForm">
         <input type="tel" id="phone" name="phone" placeholder="321 6549 879" pattern="[0-9]{10}" required>
         <button class="" type="submit">Next</button>
     </form>
 </div>

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
