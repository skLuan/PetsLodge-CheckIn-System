<x-app-layout>
    <div class="container w-10/12 m-auto">
        <h1>Drop-in Form</h1>
        <div class="form-container">
            <div class="step" id="step1">
                <form id="processForm">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Your Name" value="{{ old('name') }}"
                        required>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Your Email"
                        value="{{ old('email') }}" required>
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="Your Phone Number"
                        value="{{ old('phone') }}" required>
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Your Address"
                        value="{{ old('address') }}" required>
                    <button type="submit" id="send-drop-info"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                        aria-label="Enviar número para verificar y generar PDF">
                        Send Drop Info
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Enviar datos via AJAX
        const formData = new FormData(e.target);

        try {
            const response = await fetch('/readyToPrint', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            });
            const data = await response.json();

            // Mostrar modal
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modal-content');
            const modalMessage = document.getElementById('modal-message');
            const modalDetails = document.getElementById('modal-details');
            const checkInBtn = document.getElementById('check-in-btn');

            modal.style.display = 'flex';
            modalMessage.textContent = data.message;

            // Restaurar botón
            button.disabled = false;
            button.textContent = 'Send Drop Info';
        } catch (error) {
            // Manejo de error genérico
            console.error('Error:', error);
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modal-content');
            const modalMessage = document.getElementById('modal-message');
            modal.style.display = 'flex';
            modalContent.classList.add('error');
            modalMessage.textContent = 'Error de conexión. Por favor, intenta de nuevo.';

        }
    </script>
</x-app-layout>
