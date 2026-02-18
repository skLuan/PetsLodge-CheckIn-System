<x-app-layout>
    <div class="w-full px-4 py-8">
        <div class="max-w-2xl p-6 bg-white rounded-lg shadow-md my-auto">
            <h1 class="text-2xl font-bold mb-6 text-center">Confirmar Drop In</h1>
            <form id="drop-in-form" class="space-y-4">
                @csrf
                <div>
                    <label for="phone" class="block">Número de Teléfono</label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required aria-required="true" placeholder="Ex: 1234567890">
                    <span class="error-message hidden text-sm">Ingresa un número de 10 dígitos</span>
                </div>
                <button type="submit" id="send-drop-info"
                    class="w-full bg-green-dark text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                    aria-label="Enviar número para verificar y generar PDF">
                    Send Drop Info
                </button>
            </form>
        </div>
    </div>
    <x-slot name="scripts">
        @vite(['resources/js/components/CheckInHandler.js'])
        <script>
            // Wait for CheckInHandler to be available in global scope
            function initializeDropInHandler() {
                if (typeof window.CheckInHandler !== 'undefined') {
                    const dropInHandler = new window.CheckInHandler({
                        formSelector: '#drop-in-form',
                        apiEndpoint: '{{ route('drop-in.check-user') }}',
                        csrfToken: document.querySelector('input[name="_token"]').value,
                        newFormRoute: '{{ route('new-form') }}',
                        newFormPreFilledRoute: '{{ route('new-form') }}',
                        confirmationRoute: '{{ route('drop-in.confirmation') }}'
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
