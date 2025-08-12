<x-app-layout>
    <div class="w-full max-w-2xl p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Confirmar Drop In</h1>
        <form id="drop-in-form" class="space-y-4">
            @csrf
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Número de Teléfono</label>
                <input type="tel" id="phone" name="info[phone]" pattern="[0-9]{10}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    required aria-required="true" placeholder="Ej: 1234567890">
                <span class="error-message hidden text-sm">Ingresa un número de 10 dígitos</span>
            </div>
            <!-- Modal para feedback -->
            <div id="modal" class="border border-color-gray-300">
                <div id="modal-content" class="modal-content">
                    <p id="modal-message"></p>
                    <div id="modal-details" class="mt-4"></div>
                    <a href="/new-form?phone=" id="check-in-btn"
                        class="mt-4 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 hidden">Hacer check
                        in</a>
                </div>
            </div>
            <button type="submit" id="send-drop-info"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                aria-label="Enviar número para verificar y generar PDF">
                Send Drop Info
            </button>
        </form>
    </div>

    <script>
        document.getElementById('drop-in-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validación en cliente
            const phoneInput = document.getElementById('phone');
            const errorSpan = phoneInput.nextElementSibling;
            const phoneRegex = /^[0-9]{10}$/;

            if (!phoneRegex.test(phoneInput.value)) {
                phoneInput.classList.add('error');
                errorSpan.classList.remove('hidden');
                return;
            } else {
                phoneInput.classList.remove('error');
                errorSpan.classList.add('hidden');
            }

            // Deshabilitar botón y mostrar loader
            const button = document.getElementById('send-drop-info');
            button.disabled = true;
            button.textContent = 'Verificando...';

            // Enviar datos via AJAX
            const formData = new FormData(e.target);

            try {
                const response = await fetch('/drop-in/check-user', {
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

                if (data.status === 'found') {
                    modalContent.classList.add('success');
                    modalDetails.innerHTML = `
                           <p>Cliente: ${data.data.client_name}</p>
                           <p>Perro: ${data.data.dog_name}</p>
                           <p>Raza: ${data.data.breed}</p>
                       `;

                    // Countdown y redirección
                    let seconds = 3;
                    const countdownInterval = setInterval(() => {
                        modalMessage.textContent = `Redirigiendo en ${seconds}...`;
                        seconds--;
                        if (seconds < 0) {
                            clearInterval(countdownInterval);
                            window.location.href = data.redirect_url;
                        }
                    }, 1000);
                } else if (data.status === 'not_found') {
                    modalContent.classList.add('error');
                    checkInBtn.classList.remove('hidden');

                    modalDetails.innerHTML = `
                           <p>No se encontró registro para el número: ${phoneInput.value}</p>
                       `;
                    const checkInBtnValue = checkInBtn.getAttribute('href');
                    checkInBtn.setAttribute('href', checkInBtnValue + phoneInput.value);
                    console.log(checkInBtn.getAttribute('href'));
                }

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
                button.disabled = false;
                button.textContent = 'Send Drop Info';

            }
        });

        // Cerrar modal al clic fuera (opcional, para UX)
        document.getElementById('modal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('modal')) {
                document.getElementById('modal').style.display = 'none';
            }
        });
    </script>
</x-app-layout>
