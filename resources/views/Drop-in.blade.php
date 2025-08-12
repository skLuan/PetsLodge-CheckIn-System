<x-app-layout>
<div class="w-full max-w-2xl p-6 bg-white rounded-lg shadow-md">
           <h1 class="text-2xl font-bold mb-6 text-center">Confirmar Drop In</h1>
           <form id="drop-in-form" class="space-y-4">
               @csrf
               <div>
                   <label for="phone" class="block text-sm font-medium text-gray-700">Número de Teléfono</label>
                   <input type="tel" id="phone" name="info[phone]" 
                          pattern="[0-9]{10}" 
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                          required aria-required="true" placeholder="Ej: 1234567890">
                   <span class="error-message hidden text-sm">Ingresa un número de 10 dígitos</span>
               </div>
               <button type="submit" id="send-drop-info" 
                       class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50" 
                       aria-label="Enviar número para verificar y generar PDF">
                   Send Drop Info
               </button>
           </form>
           <div id="feedback" class="mt-4 hidden"></div>
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
                   const response = await fetch('/readyToPrint', {
                       method: 'POST',
                       body: formData,
                       headers: {
                           'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                       }
                   });
                   const data = await response.json();

                   // Mostrar feedback
                   const feedbackDiv = document.getElementById('feedback');
                   feedbackDiv.classList.remove('hidden');
                   feedbackDiv.classList.remove('success-message', 'error-message');
                   feedbackDiv.classList.add(response.ok ? 'success-message' : 'error-message');
                   feedbackDiv.textContent = data.message || data.error;

                   // Restaurar botón
                   button.disabled = false;
                   button.textContent = 'Send Drop Info';
               } catch (error) {
                   const feedbackDiv = document.getElementById('feedback');
                   feedbackDiv.classList.remove('hidden');
                   feedbackDiv.classList.add('error-message');
                   feedbackDiv.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
                   button.disabled = false;
                   button.textContent = 'Send Drop Info';
               }
           });
       </script>
</x-app-layout>