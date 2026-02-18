<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-In Summary PDF</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #000;
            line-height: 1.4;
            background-color: white;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px;
            background-color: white;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header h1 {
            color: #000;
            font-size: 20px;
            margin-bottom: 3px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #000;
        }

        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #000;
            color: white;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
            border-radius: 0;
        }

        .section-content {
            padding-left: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #ccc;
            font-size: 11px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #000;
            min-width: 120px;
        }

        .info-value {
            flex: 1;
            text-align: right;
            color: #000;
        }

        .pet-card {
            background-color: #fff;
            border-left: 3px solid #000;
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 0;
        }

        .pet-name {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }

        .pet-details {
            font-size: 11px;
            color: #000;
        }

        .pet-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            margin-left: 8px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
            margin-left: 8px;
        }

        .schedule-table th {
            background-color: #ccc;
            padding: 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
        }

        .schedule-table td {
            padding: 4px;
            border: 1px solid #000;
        }

        .schedule-table tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .schedule-label {
            font-weight: bold;
            color: #000;
            min-width: 70px;
        }

        .health-warning {
            background-color: #fff;
            border-left: 3px solid #000;
            padding: 4px 8px;
            margin: 4px 0 4px 8px;
            font-size: 10px;
            color: #000;
            border-radius: 0;
        }

        .inventory-list {
            margin-left: 10px;
            font-size: 11px;
        }

        .inventory-item {
            padding: 2px 0;
            color: #000;
        }

        .inventory-item:before {
            content: "• ";
            color: #000;
            font-weight: bold;
            margin-right: 5px;
        }

        .grooming-services {
            background-color: #fff;
            padding: 6px;
            border-radius: 0;
            font-size: 11px;
            margin-left: 8px;
            border: 1px solid #ccc;
        }

        .no-data {
            color: #000;
            font-style: italic;
            font-size: 11px;
            padding: 4px 0;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 9px;
            color: #000;
        }

        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            .container {
                padding: 8px;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>🐾 Check-In Summary</h1>
            <div class="header-info">
                @if (isset($checkinData['id']))
                    <span><strong>Receipt ID:</strong> {{ $checkinData['id'] }}</span>
                @endif
                @if (isset($checkinData['date']))
                    <span><strong>Date:</strong> {{ \Carbon\Carbon::parse($checkinData['date'])->toFormattedDateString() }}</span>
                @else
                    <span><strong>Date:</strong> {{ now()->toFormattedDateString() }}</span>
                @endif
            </div>
        </div>

        {{-- Owner Information --}}
        @if (isset($checkinData['user']['info']))
            <div class="section">
                <div class="section-title">👤 Owner Information</div>
                <div class="section-content">
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $checkinData['user']['info']['name'] ?? 'Not provided' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $checkinData['user']['info']['phone'] ?? 'Not provided' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $checkinData['user']['info']['email'] ?? 'Not provided' }}</span>
                    </div>
                    @if (isset($checkinData['user']['info']['address']))
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value">{{ $checkinData['user']['info']['address'] }}</span>
                        </div>
                    @endif
                    @if (isset($checkinData['user']['info']['city']) && isset($checkinData['user']['info']['zip']))
                        <div class="info-row">
                            <span class="info-label">Location:</span>
                            <span class="info-value">{{ $checkinData['user']['info']['city'] }}, {{ $checkinData['user']['info']['zip'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Emergency Contact --}}
        @if (isset($checkinData['user']['emergencyContact']) &&
                (!empty($checkinData['user']['emergencyContact']['name']) ||
                    !empty($checkinData['user']['emergencyContact']['phone'])))
            <div class="section">
                <div class="section-title">🚨 Emergency Contact</div>
                <div class="section-content">
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $checkinData['user']['emergencyContact']['name'] ?? 'Not provided' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $checkinData['user']['emergencyContact']['phone'] ?? 'Not provided' }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pet Details --}}
        @if (isset($checkinData['pets']) && is_array($checkinData['pets']) && count($checkinData['pets']) > 0)
            <div class="section">
                <div class="section-title">🐾 Pet Details ({{ count($checkinData['pets']) }} pet{{ count($checkinData['pets']) > 1 ? 's' : '' }})</div>
                <div class="section-content">
                    @foreach ($checkinData['pets'] as $pet)
                        @if (isset($pet['info']))
                            <div class="pet-card">
                                <div class="pet-name">
                                    {{ $pet['info']['petName'] ?? 'Unnamed' }} ({{ $pet['info']['petType'] ?? 'Unknown type' }})
                                </div>
                                <div class="pet-details">
                                    @if (isset($pet['info']['petColor']))
                                        <div class="pet-detail-row">
                                            <strong>Color:</strong>
                                            <span>{{ $pet['info']['petColor'] }}</span>
                                        </div>
                                    @endif
                                    @if (isset($pet['info']['petBreed']))
                                        <div class="pet-detail-row">
                                            <strong>Breed:</strong>
                                            <span>{{ $pet['info']['petBreed'] }}</span>
                                        </div>
                                    @endif
                                    @if (isset($pet['info']['petAge']))
                                        <div class="pet-detail-row">
                                            <strong>Age:</strong>
                                            <span>{{ \Carbon\Carbon::parse($pet['info']['petAge'])->toFormattedDateString() }}</span>
                                        </div>
                                    @endif
                                    @if (isset($pet['info']['petWeight']))
                                        <div class="pet-detail-row">
                                            <strong>Weight:</strong>
                                            <span>{{ $pet['info']['petWeight'] }} lbs</span>
                                        </div>
                                    @endif
                                    @if (isset($pet['info']['petGender']))
                                        <div class="pet-detail-row">
                                            <strong>Gender:</strong>
                                            <span>{{ $pet['info']['petGender'] }}</span>
                                        </div>
                                    @endif
                                    @if (isset($pet['info']['petSpayed']))
                                        <div class="pet-detail-row">
                                            <strong>Spayed/Neutered:</strong>
                                            <span>{{ $pet['info']['petSpayed'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Feeding Schedule --}}
                                @if (isset($pet['feeding']) && is_array($pet['feeding']) && count($pet['feeding']) > 0)
                                    <div style="margin-top: 6px;">
                                        <strong style="color: #000; font-size: 11px;">🍽️ Feeding Schedule:</strong>
                                        @php
                                            $feedingByTime = [];
                                            foreach ($pet['feeding'] as $feed) {
                                                if (!isset($feedingByTime[$feed['day_time']])) {
                                                    $feedingByTime[$feed['day_time']] = [];
                                                }
                                                $feedingByTime[$feed['day_time']][] = $feed['feeding_med_details'];
                                            }
                                        @endphp
                                        <table class="schedule-table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($feedingByTime as $time => $items)
                                                    <tr>
                                                        <td class="schedule-label">{{ ucfirst($time) }}</td>
                                                        <td>{{ implode(', ', $items) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                {{-- Medication Schedule --}}
                                @if (isset($pet['medication']) && is_array($pet['medication']) && count($pet['medication']) > 0)
                                    <div style="margin-top: 6px;">
                                        <strong style="color: #000; font-size: 11px;">💊 Medication Schedule:</strong>
                                        @php
                                            $medByTime = [];
                                            foreach ($pet['medication'] as $med) {
                                                if (!isset($medByTime[$med['day_time']])) {
                                                    $medByTime[$med['day_time']] = [];
                                                }
                                                $medByTime[$med['day_time']][] = $med['feeding_med_details'];
                                            }
                                        @endphp
                                        <table class="schedule-table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($medByTime as $time => $items)
                                                    <tr>
                                                        <td class="schedule-label">{{ ucfirst($time) }}</td>
                                                        <td>{{ implode(', ', $items) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
                                            $healthNotes[] = $pet['health']['healthBehaviors'];
                                        }
                                        if (isset($pet['health']['warnings'])) {
                                            $healthNotes[] = $pet['health']['warnings'];
                                        }
                                    @endphp
                                    @if (count($healthNotes) > 0)
                                        <div style="margin-top: 6px;">
                                            <strong style="color: #000; font-size: 11px;">🏥 Health Notes:</strong>
                                            @foreach ($healthNotes as $note)
                                                <div class="health-warning">{{ $note }}</div>
                                            @endforeach
                                        </div>
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
                <div class="section">
                    <div class="section-title">🛁 Grooming Services</div>
                    <div class="section-content">
                        @php
                            $services = [];
                            foreach ($checkinData['grooming'] as $key => $value) {
                                if ($value && $key !== 'no' && $key !== 'appointmentDay') {
                                    $services[] = ucfirst($key);
                                }
                            }
                        @endphp
                        <div class="grooming-services">
                            {{ implode(', ', $services) }}
                        </div>
                        
                        @if (isset($checkinData['grooming']['appointmentDay']) && $checkinData['grooming']['appointmentDay'])
                            <div class="info-row" style="margin-top: 10px;">
                                <span class="info-label">📅 Appointment Day:</span>
                                <span class="info-value">{{ $checkinData['grooming']['appointmentDay'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- Grooming Instructions --}}
        @if (isset($checkinData['groomingDetails']))
            <div class="section">
                <div class="section-title">📝 Grooming Instructions</div>
                <div class="section-content">
                    <div style="font-size: 11px; color: #000; line-height: 1.4;">
                        {{ $checkinData['groomingDetails'] }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Inventory --}}
        @if (isset($checkinData['inventory']) && is_array($checkinData['inventory']))
            <div class="section">
                <div class="section-title">🎒 Items to Store ({{ count($checkinData['inventory']) }} item{{ count($checkinData['inventory']) > 1 ? 's' : '' }})</div>
                <div class="section-content">
                    @if (count($checkinData['inventory']) > 0)
                        <div class="inventory-list">
                            @foreach ($checkinData['inventory'] as $item)
                                <div class="inventory-item">{{ $item }}</div>
                            @endforeach
                        </div>
                    @else
                        <div class="no-data">No items to store</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>This document was generated for PetsLodge staff. Please keep for records.</p>
            <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>

</html>
