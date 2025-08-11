<x-app-layout>
<div class="w-full max-w-2xl p-6 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-center">Confirmar Información de Drop In</h1>
    <form id="drop-in-form" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="name" name="info[name]" value="{{ $data['name'] ?? '' }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                required aria-required="true">
            <span class="error-message hidden text-sm">Este campo es requerido</span>
        </div>
        <div>
            <label for="order" class="block text-sm font-medium text-gray-700">Orden</label>
            <input type="text" id="order" name="info[order]" value="{{ $data['order'] ?? '' }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                required aria-required="true">
            <span class="error-message hidden text-sm">Este campo es requerido</span>
        </div>
        <button type="submit" id="send-drop-info"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            aria-label="Enviar información para impresión">
            Send Drop Info
        </button>
    </form>
    <div id="feedback" class="mt-4 hidden"></div>
</div>

<script>
    document.getElementById('drop-in-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validación en cliente
        const inputs = document.querySelectorAll('input[required]');
        let hasError = false;
        inputs.forEach(input => {
            const errorSpan = input.nextElementSibling;
            if (!input.value.trim()) {
                input.classList.add('error');
                errorSpan.classList.remove('hidden');
                hasError = true;
            } else {
                input.classList.remove('error');
                errorSpan.classList.add('hidden');
            }
        });

        if (hasError) return;

        // Deshabilitar botón y mostrar loader
        const button = document.getElementById('send-drop-info');
        button.disabled = true;
        button.textContent = 'Enviando...';

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