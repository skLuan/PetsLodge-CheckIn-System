<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Drop In') }}
        </h2>
    </x-slot>

    <div class="container text-center px-4 py-8 max-w-screen-sm mx-auto">
        <img src="{{ asset('images/logo-pets-lodge.png') }}" alt="Pet Lodge & Spa Logo" class="logo w-48 mx-auto">
        <h1 class="text-green-dark">Drop In</h1>
        <p class="text-lg px-2 mt-4">Please enter your phone to start the process</p>
        <form id="drop-in-form">
            @csrf
            <input type="tel" id="phone" name="phone" placeholder="321 6549 879" pattern="[0-9]{10}" required>
            <x-primary-button class="bg-yellow mt-1 !text-base shadow-md !text-brown-dark !font-bold">
                {{ __('Send Drop Info') }}
            </x-primary-button>
        </form>
    </div>
    <x-slot name="scripts">
        <script>
            // Wait for CheckInHandler to be available in global scope
            function initializeDropInHandler() {
                if (typeof window.CheckInHandler !== 'undefined') {
                    const dropInHandler = new window.CheckInHandler({
                        formSelector: '#drop-in-form',
                        apiEndpoint: '{{ route("drop-in.check-user") }}',
                        csrfToken: document.querySelector('input[name="_token"]').value,
                        newFormRoute: '{{ route("new-form") }}',
                        newFormPreFilledRoute: '{{ route("new-form") }}',
                        confirmationRoute: '{{ route("drop-in.confirmation") }}'
                    });
                } else {
                    // Retry if not yet available
                    setTimeout(initializeDropInHandler, 50);
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeDropInHandler);
            } else {
                initializeDropInHandler();
            }
        </script>
    </x-slot>
</x-app-layout>
