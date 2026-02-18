<div class="text-left text-sm text-gray-600" data-check-in-summary>
    @if ($checkinData)
        {{-- Receipt Header --}}
        <div class="mb-4 py-2 border-b border-green">
            <div class="text-xs text-gray-500">Receipt ID: <span data-receipt-id>{{ $checkinData['id'] ?? 'N/A' }}</span></div>
            <div class="text-sm flex justify-between pt-1 text-gray-500">
                Check-in Date:
                <strong><span data-receipt-date>{{ $checkinData['date'] ? \Carbon\Carbon::parse($checkinData['date'])->toFormattedDateString() : now()->toFormattedDateString() }}</span></strong>
            </div>
        </div>

        {{-- Owner Information --}}
        @if (isset($checkinData['user']['info']))
            <div class="mb-4 border-b pb-1" data-owner-info>
                <h3 class="font-semibold text-green-dark mb-1">👤 Owner Information</h3>
                <div class="pl-4 text-sm">
                    <div class="flex justify-between"><strong>Name:</strong>
                        <span data-owner-name>{{ $checkinData['user']['info']['name'] ?? 'Not provided' }}</span></div>
                    <div class="flex justify-between"><strong>Phone:</strong>
                        <span data-owner-phone>{{ $checkinData['user']['info']['phone'] ?? 'Not provided' }}</span></div>
                    <div class="flex justify-between"><strong>Email:</strong>
                        <span data-owner-email>{{ $checkinData['user']['info']['email'] ?? 'Not provided' }}</span></div>
                    @if (isset($checkinData['user']['info']['address']))
                        <div class="flex justify-between"><strong>Address:</strong>
                            <span data-owner-address>{{ $checkinData['user']['info']['address'] }}</span></div>
                    @endif
                    @if (isset($checkinData['user']['info']['city']) && isset($checkinData['user']['info']['zip']))
                        <div class="flex justify-between"><strong>Location:</strong>
                            <span data-owner-location>{{ $checkinData['user']['info']['city'] }}, {{ $checkinData['user']['info']['zip'] }}</span></div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Emergency Contact --}}
        @if (isset($checkinData['user']['emergencyContact']) &&
                (!empty($checkinData['user']['emergencyContact']['name']) ||
                    !empty($checkinData['user']['emergencyContact']['phone'])))
            <div class="mb-4" data-emergency-contact>
                <div class="font-semibold text-green-dark mb-1">🚨 Emergency Contact</div>
                <div class="pl-4 text-sm">
                    <div class="flex justify-between"><strong>Name:</strong>
                        <span data-emergency-name>{{ $checkinData['user']['emergencyContact']['name'] ?? 'Not provided' }}</span></div>
                    <div class="flex justify-between"><strong>Phone:</strong>
                        <span data-emergency-phone>{{ $checkinData['user']['emergencyContact']['phone'] ?? 'Not provided' }}</span></div>
                </div>
            </div>
        @endif

        {{-- Pet Details --}}
        @if (isset($checkinData['pets']) && is_array($checkinData['pets']) && count($checkinData['pets']) > 0)
            <div class="mb-4" data-pets-section>
                <h3 class="font-semibold text-green-dark mb-2">🐾 Pet Details (<span data-pet-count>{{ count($checkinData['pets']) }}</span>
                    pet<span data-pet-plural>{{ count($checkinData['pets']) > 1 ? 's' : '' }}</span>)</h3>
                <div data-pet-details-container>
                    @foreach ($checkinData['pets'] as $pet)
                        @if (isset($pet['info']))
                            <div class="mb-3 py-1 pl-4 border-l-2 border-yellow" data-pet-item>
                                <div class="font-bold text-base pb-2">{{ $pet['info']['petName'] ?? 'Unnamed' }}
                                    ({{ $pet['info']['petType'] ?? 'Unknown type' }})</div>
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

                                {{-- Feeding Schedule --}}
                                @if (isset($pet['feeding']) && is_array($pet['feeding']) && count($pet['feeding']) > 0)
                                    <div class="mt-2 pb-1"><strong>🍽️ Feeding Schedule:</strong></div>
                                    @php
                                        $feedingByTime = [];
                                        foreach ($pet['feeding'] as $feed) {
                                            if (!isset($feedingByTime[$feed['day_time']])) {
                                                $feedingByTime[$feed['day_time']] = [];
                                            }
                                            $feedingByTime[$feed['day_time']][] = $feed['feeding_med_details'];
                                        }
                                    @endphp
                                    @foreach ($feedingByTime as $time => $items)
                                        <p class="pl-2 flex justify-between text-xs"><strong>{{ ucfirst($time) }}:</strong>
                                            {{ implode(', ', $items) }}</p>
                                    @endforeach
                                @endif

                                {{-- Medication Schedule --}}
                                @if (isset($pet['medication']) && is_array($pet['medication']) && count($pet['medication']) > 0)
                                    <div class="mt-2 pb-1"><strong>💊 Medication Schedule:</strong></div>
                                    @php
                                        $medByTime = [];
                                        foreach ($pet['medication'] as $med) {
                                            if (!isset($medByTime[$med['day_time']])) {
                                                $medByTime[$med['day_time']] = [];
                                            }
                                            $medByTime[$med['day_time']][] = $med['feeding_med_details'];
                                        }
                                    @endphp
                                    @foreach ($medByTime as $time => $items)
                                        <p class="pl-2 flex justify-between text-xs"><strong>{{ ucfirst($time) }}:</strong>
                                            {{ implode(', ', $items) }}</p>
                                    @endforeach
                                @endif

                                {{-- Health Notes --}}
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
                                            $healthNotes[] = '<strong>⚠️ Warnings:</strong> ' . $pet['health']['warnings'];
                                        }
                                    @endphp
                                    @if (count($healthNotes) > 0)
                                        <div class="mt-2"><strong>🏥 Health Notes:</strong></div>
                                        @foreach ($healthNotes as $note)
                                            <div class="pl-2 text-xs text-red-600 flex justify-between">
                                                {!! $note !!}</div>
                                        @endforeach
                                    @endif
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Grooming Details --}}
        @if (isset($checkinData['grooming']) && is_array($checkinData['grooming']))
            @php
                $hasGroomingServices = false;
                foreach ($checkinData['grooming'] as $key => $value) {
                    if ($value && $key !== 'no' && $key !== 'appointmentDay') {
                        $hasGroomingServices = true;
                        break;
                    }
                }
            @endphp
            
            @if ($hasGroomingServices)
                <div class="mb-4" data-grooming-section>
                    <div class="font-semibold text-yellow-800 mb-1">🛁 Grooming Services</div>
                    <div class="pl-4 text-sm">
                        @php
                            $services = [];
                            foreach ($checkinData['grooming'] as $key => $value) {
                                if ($value && $key !== 'no' && $key !== 'appointmentDay') {
                                    $services[] = ucfirst($key);
                                }
                            }
                        @endphp
                        <span data-grooming-services>{{ implode(', ', $services) }}</span>
                    </div>
                    
                    @if (isset($checkinData['grooming']['appointmentDay']) && $checkinData['grooming']['appointmentDay'])
                        <div class="pl-4 text-sm mt-1">
                            <strong>📅 Appointment Day:</strong> <span data-grooming-appointment>{{ $checkinData['grooming']['appointmentDay'] }}</span>
                        </div>
                    @endif
                </div>
            @endif
        @endif

        @if (isset($checkinData['groomingDetails']))
            <div class="mb-4" data-grooming-instructions-section>
                <div class="font-semibold text-yellow-800 mb-1">📝 Grooming Instructions</div>
                <div class="pl-4 text-sm" data-grooming-instructions>{{ $checkinData['groomingDetails'] }}</div>
            </div>
        @endif

        {{-- Inventory --}}
        @if (isset($checkinData['inventory']) && is_array($checkinData['inventory']))
            <div class="mb-4" data-inventory-section>
                <div class="font-semibold text-yellow-800 mb-1">🎒 Items to Store
                    (<span data-inventory-count>{{ count($checkinData['inventory']) }}</span> item<span data-inventory-plural>{{ count($checkinData['inventory']) > 1 ? 's' : '' }}</span>)
                </div>
                <div class="pl-4 text-sm" data-inventory-list>
                    @if (count($checkinData['inventory']) > 0)
                        @foreach ($checkinData['inventory'] as $item)
                            <div>• {{ $item }}</div>
                        @endforeach
                    @else
                        <span class="text-gray-500">No items to store</span>
                    @endif
                </div>
            </div>
        @endif
    @else
        <!-- Summary will be populated by JavaScript -->
    @endif
</div>
