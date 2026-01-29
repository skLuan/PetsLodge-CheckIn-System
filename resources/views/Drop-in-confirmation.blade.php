<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row gap-6 items-center">
            <figure>
                <picture><img height="48" width="48" src="/images/logo-pets-lodge.png" alt="Pets lodge Logo">
                </picture>
            </figure>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Drop-in Confirmation') }}
            </h2>
        </div>
    </x-slot>

    <div class="container px-4 pb-8 max-w-screen-lg mx-auto">
        <div class="py-6">
            <div class="mx-auto mb-6">
                <h1 class="text-2xl font-bold text-center">Check-in Summary</h1>
                <p class="text-lg text-center pt-1 pb-6">Please review your check-in details before proceeding.</p>
            </div>

            <!-- CheckInSummary Component -->
            <x-check-in-summary :checkinData="$checkinData" />

            <div class="mt-8 text-center">
                <p class="text-lg text-gray-600 mb-4">Ready to drop off your pet? Our team has been notified.</p>
                <p class="text-lg">See you later alligator 😄 🐊</p>
                
                <!-- Print Button -->
                <div class="mt-6">
                    <button id="print-button"
                        class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                        aria-label="Send to printer">
                        🖨️ Print Check-in
                    </button>
                </div>
                
                <!-- Loading indicator -->
                <div id="print-loading" class="hidden mt-4">
                    <p class="text-gray-600">Sending to printer...</p>
                </div>
                
                <!-- Success message -->
                <div id="print-success" class="hidden mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <p>✓ Print job sent successfully!</p>
                </div>
                
                <!-- Error message -->
                <div id="print-error" class="hidden mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <p id="error-message"></p>
                </div>
            </div>
        </div>
    </div>
    
    <x-slot name="scripts">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const printButton = document.getElementById('print-button');
                const printLoading = document.getElementById('print-loading');
                const printSuccess = document.getElementById('print-success');
                const printError = document.getElementById('print-error');
                const errorMessage = document.getElementById('error-message');

                printButton.addEventListener('click', async function() {
                    // Reset messages
                    printSuccess.classList.add('hidden');
                    printError.classList.add('hidden');
                    printLoading.classList.remove('hidden');
                    printButton.disabled = true;

                    try {
                        // Get check-in data from the page
                        const checkinData = @json($checkinData);

                        // Send to backend
                        const response = await fetch('{{ route("drop-in.ready-to-print") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                info: checkinData
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.message) {
                            printLoading.classList.add('hidden');
                            printSuccess.classList.remove('hidden');
                            
                            // Reset button after 3 seconds
                            setTimeout(() => {
                                printButton.disabled = false;
                                printSuccess.classList.add('hidden');
                            }, 3000);
                        } else {
                            throw new Error(data.error || 'Unknown error occurred');
                        }
                    } catch (error) {
                        printLoading.classList.add('hidden');
                        printError.classList.remove('hidden');
                        errorMessage.textContent = error.message || 'Error sending print job';
                        printButton.disabled = false;
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>
