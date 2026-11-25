{{-- One pet of a booking on /view-check-in. Inventory is booking-level and rendered by the page. --}}
@props(['checkIn', 'removable' => false])

@php
    $pet = $checkIn->pet;
    $appointmentDay = $checkIn->extraServices
        ->first(fn ($service) => $service->pivot?->grooming_appointment_day)
        ?->pivot->grooming_appointment_day;
@endphp

<div class="p-4 border-b border-green border-opacity-40 last:border-b-0">
    <div class="flex justify-between items-center mb-3">
        <h3 class="text-xl font-bold text-green-dark">
            🐾 {{ $pet->name ?? 'Unnamed Pet' }}
            ({{ $pet->kindOfPet->name ?? 'Unknown type' }})
        </h3>
        @if ($removable && ! $checkIn->canBeDeletedBy(auth()->user()))
            <span class="text-sm text-gray-500">🔒 Signed — only pet staff can delete</span>
        @elseif ($removable)
            <button type="button"
                onclick="deleteCheckIn('{{ route('delete-check-in', $checkIn->id) }}', 'Remove {{ addslashes($pet->name ?? 'this pet') }} from this check-in? This action cannot be undone.')"
                class="bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded-lg font-medium transition-colors duration-200 text-sm">
                Remove
            </button>
        @endif
    </div>

    <!-- Pet Information -->
    <div class="mb-4">
        <h4 class="font-semibold text-green-dark mb-3">Pet Information</h4>
        <div class="bg-gray-50 rounded-lg p-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="">
                    <div class="text-sm text-gray-600 space-y-1">
                        @if ($pet->race)
                            <div class="flex justify-between"><strong>Breed:</strong>
                                {{ $pet->race }}</div>
                        @endif
                        @if ($pet->color)
                            <div class="flex justify-between"><strong>Color:</strong>
                                {{ $pet->color }}</div>
                        @endif
                        @if ($pet->birth_date)
                            <div class="flex justify-between"><strong>Age:</strong>
                                {{ \Carbon\Carbon::parse($pet->birth_date)->age }}
                                years old</div>
                        @endif
                        @if ($pet->gender)
                            <div class="flex justify-between"><strong>Gender:</strong>
                                {{ $pet->gender->name }}</div>
                        @endif
                        @if ($pet->castrated)
                            <div class="flex justify-between">
                                <strong>Spayed/Neutered:</strong>
                                {{ $pet->castrated->status }}
                            </div>
                        @endif
                    </div>
                </div>
                @if ($pet->health_conditions || $pet->warnings)
                    <div>
                        <h6 class="font-semibold text-red-600 mb-2">Health Notes:</h6>
                        <div class="text-sm text-gray-600 ml-auto">
                            @if ($pet->health_conditions)
                                <p>⚠️ {{ $pet->health_conditions }}</p>
                            @endif
                            @if ($pet->warnings)
                                <p>⚠️ {{ $pet->warnings }}</p>
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
                        $time = $food->momentOfDay->name ?? $food->moment_of_day_id;
                        $feedingByTime[$time][] = $food->name . ($food->description ? ' - ' . $food->description : '');
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
                        $time = $medicine->momentOfDay->name ?? $medicine->moment_of_day_id;
                        $medByTime[$time][] = $medicine->name . ($medicine->description ? ' - ' . $medicine->description : '');
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

    <!-- Extra Services (grooming is per pet) -->
    @if ($checkIn->extraServices->count() > 0)
        <div class="mb-4">
            <h4 class="font-semibold text-green-dark mb-2">✂️ Grooming Services</h4>
            <div class="bg-purple-50 rounded-lg p-3">
                <div class="flex flex-wrap gap-2 mb-3 justify-end">
                    @foreach ($checkIn->extraServices as $service)
                        <span class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm">
                            {{ $service->name }}
                        </span>
                    @endforeach
                </div>

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
