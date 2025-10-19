<x-app-layout>
    <x-slot name="scripts">
        @vite(['resources/js/cookies-and-form/form-processor.js'])
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-row gap-6 items-center">
            <figure>
                <picture><img height="48" width="48" src="/images/logo-pets-lodge.png" alt="Pets lodge Logo">
                </picture>
            </figure>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Pets Lodge') }}
            </h2>
        </div>
    </x-slot>

    <div class="container px-4 pb-8 max-w-screen-lg mx-auto">
        <div class="py-6">
            <div class="w-10/12 mx-auto mb-6">
                <h1 class="text-2xl font-bold text-center">Your Active Check-ins</h1>
                <p class="text-lg text-center">Thanks for using our services. <br>
                    See you later alligator 😄 🐊</p>
            </div>
            @if ($checkIns->count() > 0)
                <div class="space-y-6">
                    @foreach ($checkIns as $checkIn)
                        <div
                            class="bg-white rounded-lg shadow-md border border-green border-opacity-40 overflow-hidden">
                            <!-- Check-in Header -->
                            <div class="bg-green-lightest px-6 py-4 border-b border-green">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h2 class="text-xl font-bold text-green-dark">
                                            {{ $checkIn->pet->name ?? 'Unnamed Pet' }}
                                        </h2>
                                        <h4 class="text-lg font-semibold">
                                            Check-in #{{ $checkIn->id }}
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            Checked in:
                                            {{ $checkIn->check_in ? $checkIn->check_in->format('M j, Y g:i A') : 'N/A' }}
                                        </p>
                                    </div>
                                    <button type="button" onclick="editCheckIn({{ $checkIn->id }})"
                                        class="bg-yellow-second hover:bg-yellow text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                        Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Pet Information -->
                            <div class="px-6 py-4">
                                <div class="mb-4">
                                    <h4 class="font-semibold text-green-dark mb-3">🐾 Pet Information</h4>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <h5 class="font-bold text-base mb-2">
                                                    {{ $checkIn->pet->name ?? 'Unnamed Pet' }}
                                                    ({{ $checkIn->pet->kindOfPet->name ?? 'Unknown type' }})
                                                </h5>
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
                                            <div>
                                                @if ($checkIn->pet->health_conditions || $checkIn->pet->warnings)
                                                    <h6 class="font-semibold text-red-600 mb-2">Health Notes:</h6>
                                                    <div class="text-sm text-gray-600">
                                                        @if ($checkIn->pet->health_conditions)
                                                            <div>⚠️ {{ $checkIn->pet->health_conditions }}</div>
                                                        @endif
                                                        @if ($checkIn->pet->warnings)
                                                            <div>⚠️ {{ $checkIn->pet->warnings }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
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
                                                <div class="text-sm mb-1">
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
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($checkIn->extraServices as $service)
                                                    <span
                                                        class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm">
                                                        {{ $service->name }}
                                                    </span>
                                                @endforeach
                                            </div>
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
    </div>

    <script>
        function editCheckIn(checkInId) {
            // Redirect to edit endpoint
            window.location.href = `/edit-check-in/${checkInId}`;
        }
    </script>
</x-app-layout>
