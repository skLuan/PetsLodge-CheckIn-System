<x-app-layout>
    <x-slot name="scripts">
        @vite(['resources/js/cookies-and-form/form-processor.js'])
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-row gap-6 items-center">
            <a href="/" class="flex flex-row items-center gap-4">
                <figure>
                    <picture><img height="48" width="48" src="/images/logo-pets-lodge.png" alt="Pets lodge Logo">
                    </picture>
                </figure>
                <h2 class="font-semibold text-xl text-white leading-tight">
                    {{ __('Pets Lodge') }}
                </h2>
            </a>
        </div>
    </x-slot>

    <div class="container px-4 pb-8 max-w-screen-lg mx-auto">
        <div class="py-6">
            <div class="mx-auto mb-6">
                <h1 class="text-2xl font-bold text-center">Your Active Check-ins</h1>
                <p class="text-lg text-center pt-1 pb-6">Thanks for using our services.</p>
                <p class="text-lg text-center">Owr team have been notified, youre ready to Drop of in ours instalations
                </p>
            </div>

            <!-- Owner Information Section -->
            @if (isset($user))
                <div class="bg-white rounded-lg shadow-md border border-green border-opacity-40 overflow-hidden mb-6">
                    <div class="p-4 border-b border-green flex flex-row gap-4 items-center">
                        <h2 class="text-xl font-bold text-green-dark flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            {{ $user->name ?? 'Not provided' }}
                        </h2>
                        <h3 class="text-lg font-semibold text-green-dark flex items-center">
                            Personal Details
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Personal Information -->
                            <div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-gray-900">{{ $user->email ?? 'Not provided' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-gray-900">{{ $user->phone ?? 'Not provided' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact Information -->
                            <div>
                                <h3 class="text-lg font-semibold text-green-dark mb-4 flex items-center">
                                    Emergency Contact
                                </h3>
                                @if ($user->emergencyContacts->count() > 0)
                                    @foreach ($user->emergencyContacts as $emergencyContact)
                                        <div class="space-y-3">
                                            <div
                                                class="flex justify-start gap-4 items-center py-2 border-b border-gray-200">
                                                <span
                                                    class="text-gray-900">{{ $emergencyContact->name ?? 'Not provided' }}</span>
                                                <span
                                                    class="text-gray-900">{{ $emergencyContact->phone ?? 'Not provided' }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-4 text-gray-500">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <p>No emergency contact information available</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($checkIns->count() > 0)
                <div class="space-y-6">
                    @foreach ($checkIns as $checkIn)
                        <div
                            class="bg-white rounded-lg shadow-md border border-green border-opacity-40 overflow-hidden">
                            <!-- Check-in Header -->
                            <div class="p-4 border-b border-green">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-lg font-semibold">
                                            Check-in ID# {{ str_pad($checkIn->id, 4, '0', STR_PAD_LEFT) }} for:
                                        </h4>
                                        <h2 class="text-xl font-bold text-green-dark">
                                            {{ $checkIn->pet->name ?? 'Unnamed Pet' }}
                                            ({{ $checkIn->pet->kindOfPet->name ?? 'Unknown type' }})
                                        </h2>
                                        <p class="text-sm text-gray-600">
                                            created at:
                                            {{ $checkIn->check_in ? $checkIn->check_in->format('M j, Y g:i A') : 'N/A' }}
                                        </p>
                                    </div>
                                    <button type="button" onclick="editCheckIn({{ $checkIn->id }})"
                                        class="edit-button bg-yellow-second hover:bg-yellow text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                        Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Pet Information -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h4 class="font-semibold text-green-dark mb-3">🐾 Pet Information</h4>
                                    <div class="bg-gray-50 rounded-lg p-2">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="">
                                                <div class="text-sm text-gray-600 space-y-1">
                                                    @if ($checkIn->pet->race)
                                                        <div class="flex justify-between"><strong>Breed:</strong>
                                                            {{ $checkIn->pet->race }}</div>
                                                    @endif
                                                    @if ($checkIn->pet->color)
                                                        <div class="flex justify-between"><strong>Color:</strong>
                                                            {{ $checkIn->pet->color }}</div>
                                                    @endif
                                                    @if ($checkIn->pet->birth_date)
                                                        <div class="flex justify-between"><strong>Age:</strong>
                                                            {{ \Carbon\Carbon::parse($checkIn->pet->birth_date)->age }}
                                                            years old</div>
                                                    @endif
                                                    @if ($checkIn->pet->gender)
                                                        <div class="flex justify-between"><strong>Gender:</strong>
                                                            {{ $checkIn->pet->gender->name }}</div>
                                                    @endif
                                                    @if ($checkIn->pet->castrated)
                                                        <div class="flex justify-between">
                                                            <strong>Spayed/Neutered:</strong>
                                                            {{ $checkIn->pet->castrated->status }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($checkIn->pet->health_conditions || $checkIn->pet->warnings)
                                                <div>
                                                    <h6 class="font-semibold text-red-600 mb-2">Health Notes:</h6>
                                                    <div class="text-sm text-gray-600 ml-auto">
                                                        @if ($checkIn->pet->health_conditions)
                                                            <p>⚠️ {{ $checkIn->pet->health_conditions }}</p>
                                                        @endif
                                                        @if ($checkIn->pet->warnings)
                                                            <p>⚠️ {{ $checkIn->pet->warnings }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Feeding Schedule -->
                                @if ($checkIn->foods->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-green-dark mb-2">🍽️ Feeding Schedule</h4>
                                        <div class="bg-blue-50 rounded-lg p-3">
                                            @php
                                                $feedingByTime = [];
                                                foreach ($checkIn->foods as $food) {
                                                    $time = $food->moment_of_day->name ?? 'morning';
                                                    if (!isset($feedingByTime[$time])) {
                                                        $feedingByTime[$time] = [];
                                                    }
                                                    $feedingByTime[$time][] =
                                                        $food->name .
                                                        ($food->description ? ' - ' . $food->description : '');
                                                }
                                            @endphp
                                            @foreach ($feedingByTime as $time => $items)
                                                <div class="text-sm mb-1 flex justify-between">
                                                    <strong>{{ ucfirst($time) }}:</strong> {{ implode(', ', $items) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Medication Schedule -->
                                @if ($checkIn->medicines->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-green-dark mb-2">💊 Medication Schedule</h4>
                                        <div class="bg-red-50 rounded-lg p-3">
                                            @php
                                                $medByTime = [];
                                                foreach ($checkIn->medicines as $medicine) {
                                                    $time = $medicine->moment_of_day->name ?? 'morning';
                                                    if (!isset($medByTime[$time])) {
                                                        $medByTime[$time] = [];
                                                    }
                                                    $medByTime[$time][] =
                                                        $medicine->name .
                                                        ($medicine->description ? ' - ' . $medicine->description : '');
                                                }
                                            @endphp
                                            @foreach ($medByTime as $time => $items)
                                                <div class="text-sm mb-1 flex justify-between">
                                                    <strong>{{ ucfirst($time) }}:</strong> {{ implode(', ', $items) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Inventory Items -->
                                @if ($checkIn->items->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-green-dark mb-2">🎒 Items to Store</h4>
                                        <div class="bg-yellow-50 rounded-lg p-3">
                                            <ul class="text-sm list-disc list-inside">
                                                @foreach ($checkIn->items as $item)
                                                    <li>{{ $item->name }}{{ $item->description ? ' - ' . $item->description : '' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif

                                <!-- Extra Services -->
                                @if ($checkIn->extraServices->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-green-dark mb-2">✂️ Grooming Services</h4>
                                        <div class="bg-purple-50 rounded-lg p-3">
                                            <div class="flex flex-wrap gap-2 mb-3 justify-end">
                                                @foreach ($checkIn->extraServices as $service)
                                                    <span
                                                        class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm">
                                                        {{ $service->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            
                                            <!-- Grooming Appointment Day -->
                                            @php
                                                $appointmentDay = null;
                                                foreach ($checkIn->extraServices as $service) {
                                                    if ($service->pivot && $service->pivot->grooming_appointment_day) {
                                                        $appointmentDay = $service->pivot->grooming_appointment_day;
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            
                                            @if ($appointmentDay)
                                                <div class="border-t border-purple-200 pt-3">
                                                    <div class="text-sm">
                                                        <strong>📅 Appointment Day:</strong> {{ $appointmentDay }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500 text-lg mb-4">No active check-ins found</div>
                    <a href="{{ route('check-in-form') }}"
                        class="bg-green hover:bg-green-dark text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200">
                        Create New Check-in
                    </a>
                </div>
            @endif
        </div>
        <p class="text-lg text-center py-4">See you later alligator 😄 🐊</p>

    </div>

    <script>
        function editCheckIn(checkInId) {
            // Redirect to edit endpoint
            window.location.href = `/edit-check-in/${checkInId}`;
        }
    </script>
</x-app-layout>
