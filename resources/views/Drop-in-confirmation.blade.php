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

    <div class="container px-4 pb-8 max-w-screen-sm mx-auto">
        <div class="py-6">
            <!-- Pre-Print State -->
            <div id="pre-print-state">
                <div class="bg-white p-4 rounded-md shadow-md mx-auto mb-6">
                    <h1 class="text-2xl font-bold text-center">Check-in Summary</h1>
                    <p class="text-lg text-center pt-1 pb-6">Please review your check-in details before proceeding.</p>
                    <!-- CheckInSummary Component -->
                    <x-check-in-summary :checkinData="session('checkin_data', $checkinData ?? [])" />
                </div>

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
                    <div id="print-success"
                        class="hidden mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        <p>✓ Print job sent successfully!</p>
                    </div>

                    <!-- Error message -->
                    <div id="print-error" class="hidden mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <p id="error-message"></p>
                    </div>
                </div>
            </div>

            <!-- Post-Print State -->
            <div id="post-print-state" class="hidden">
                <div class="bg-white p-4 rounded-md shadow-md mx-auto mb-6">
                    <h1 class="text-2xl font-bold text-center">Drop in created</h1>
                    <p class="text-lg text-center pt-1 pb-6">Please proceed to the drop in area for:</p>
                    <!-- CheckInSummary Component - Pet Details Only -->
                    <div id="pet-details-only" class="text-left text-sm text-gray-600" data-check-in-summary>
                        @if ($checkinData && !empty($checkinData))
                            @if (isset($checkinData['pets']) && is_array($checkinData['pets']) && count($checkinData['pets']) > 0)
                                <div class="mb-4" data-pets-section>
                                    <h3 class="font-semibold text-green-dark mb-2">🐾 Pet Details (<span
                                            data-pet-count>{{ count($checkinData['pets']) }}</span>
                                        pet<span data-pet-plural>{{ count($checkinData['pets']) > 1 ? 's' : '' }}</span>)</h3>
                                    <div data-pet-details-container>
                                        @foreach ($checkinData['pets'] as $pet)
                                            @if (isset($pet['info']))
                                                <div class="mb-3 py-1 pl-2 border-l-2 border-yellow" data-pet-item>
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <div class="font-bold text-base pb-2">
                                                                {{ $pet['info']['petName'] ?? 'Unnamed' }}
                                                                ({{ $pet['info']['petType'] ?? 'Unknown type' }})
                                                            </div>
                                                            <div class="text-sm text-gray-600 pl-2">
                                                                @if (isset($pet['info']['petColor']))
                                                                    <div class="flex justify-between"><strong>Color:</strong>
                                                                        {{ $pet['info']['petColor'] }}</div>
                                                                @endif
                                                                @if (isset($pet['info']['petBreed']))
                                                                    <div class="flex justify-between"><strong>Breed:</strong>
                                                                        {{ $pet['info']['petBreed'] }}</div>
                                                                @endif
                                                                @if (isset($pet['info']['petAge']))
                                                                    <div class="flex justify-between"><strong>Age:</strong>
                                                                        {{ \Carbon\Carbon::parse($pet['info']['petAge'])->toFormattedDateString() }}
                                                                    </div>
                                                                @endif
                                                                @if (isset($pet['info']['petWeight']))
                                                                    <div class="flex justify-between"><strong>Weight:</strong>
                                                                        {{ $pet['info']['petWeight'] }} lbs</div>
                                                                @endif
                                                                @if (isset($pet['info']['petGender']))
                                                                    <div class="flex justify-between"><strong>Gender:</strong>
                                                                        {{ $pet['info']['petGender'] }}</div>
                                                                @endif
                                                                @if (isset($pet['info']['petSpayed']))
                                                                    <div class="flex justify-between"><strong>Spayed/Neutered:</strong>
                                                                        {{ $pet['info']['petSpayed'] }}</div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Health Notes -->
                                                        @if (isset($pet['health']))
                                                            @php
                                                                $healthNotes = [];
                                                                if (
                                                                    isset($pet['health']['unusualHealthBehavior']) &&
                                                                    $pet['health']['unusualHealthBehavior']
                                                                ) {
                                                                    $healthNotes[] = 'Unusual behavior reported';
                                                                }
                                                                if (isset($pet['health']['healthBehaviors'])) {
                                                                    $healthNotes[] =
                                                                        '<strong>⚠️ Health:</strong> ' . $pet['health']['healthBehaviors'];
                                                                }
                                                                if (isset($pet['health']['warnings'])) {
                                                                    $healthNotes[] =
                                                                        '<strong>⚠️ Warnings:</strong> ' . $pet['health']['warnings'];
                                                                }
                                                            @endphp
                                                            @if (count($healthNotes) > 0)
                                                                <div>
                                                                    <div class="pb-2 text-base"><strong>🏥 Health Notes:</strong></div>
                                                                    @foreach ($healthNotes as $note)
                                                                        <div class="pl-2 text-sm text-red-600 flex justify-between">
                                                                            {!! $note !!}
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Redirect Counter -->
                <div class="mt-8 text-center bg-white p-4 rounded-md shadow-md">
                    <p class="text-lg text-gray-600 mb-4">Redirecting to drop-in area in:</p>
                    <p class="text-4xl font-bold text-green-600" id="countdown-timer">30</p>
                    <p class="text-sm text-gray-500 mt-2">seconds</p>
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
                const prePrintState = document.getElementById('pre-print-state');
                const postPrintState = document.getElementById('post-print-state');
                const countdownTimer = document.getElementById('countdown-timer');

                let countdownInterval;

                function startCountdown() {
                    let secondsRemaining = 30;
                    countdownTimer.textContent = secondsRemaining;

                    countdownInterval = setInterval(() => {
                        secondsRemaining--;
                        countdownTimer.textContent = secondsRemaining;

                        if (secondsRemaining <= 0) {
                            clearInterval(countdownInterval);
                            // Redirect to drop-in area
                            window.location.href = '/drop-in';
                        }
                    }, 1000);
                }

                function showPostPrintState() {
                    prePrintState.classList.add('hidden');
                    postPrintState.classList.remove('hidden');
                    startCountdown();
                }

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
                        const response = await fetch('/api/readyToPrint', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                            },
                            body: JSON.stringify({
                                info: checkinData
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.message) {
                            printLoading.classList.add('hidden');
                            printSuccess.classList.remove('hidden');
                            console.log(data.printResponse);

                            // Transition to post-print state after 2 seconds
                            setTimeout(() => {
                                showPostPrintState();
                            }, 2000);
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
