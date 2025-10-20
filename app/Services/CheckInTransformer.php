<?php

namespace App\Services;

use App\Models\CheckIn;

/**
 * Service class for transforming check-in data between different formats
 */
class CheckInTransformer
{
    /**
     * Transform check-in data to cookie format for editing
     *
     * @param CheckIn $checkIn The check-in instance with loaded relationships
     * @return array The transformed data in cookie format
     */
    public function transformCheckInToCookieFormat(CheckIn $checkIn): array
    {
        $pet = $checkIn->pet;
        $user = $checkIn->user;

        // Build pet data structure
        $petData = [
            'info' => [
                'petName' => $pet->name ?? 'Unnamed Pet',
                'petType' => $pet->kindOfPet->name ?? 'Unknown',
                'petBreed' => $pet->race ?? '',
                'petColor' => $pet->color ?? '',
                'petAge' => $pet->birth_date ? date('Y-m-d', strtotime($pet->birth_date)) : '',
                'petWeight' => '', // Not stored in current model
                'petGender' => $pet->gender->name ?? '',
                'petSpayed' => $pet->castrated->status ?? '',
            ],
            'health' => [
                'unusualHealthBehavior' => $pet->health_conditions ?? '',
                'warnings' => $pet->warnings ?? '',
                'healthBehaviors' => '', // Not stored separately
            ],
            'feeding' => [],
            'medication' => [],
        ];

        // Add feeding data
        foreach ($checkIn->foods as $food) {
            $petData['feeding'][] = [
                'day_time' => $food->moment_of_day->name ?? 'morning',
                'feeding_med_details' => $food->name,
            ];
        }

        // Add medication data
        foreach ($checkIn->medicines as $medicine) {
            $petData['medication'][] = [
                'day_time' => $medicine->moment_of_day->name ?? 'morning',
                'feeding_med_details' => $medicine->name . ($medicine->description ? ' - ' . $medicine->description : ''),
            ];
        }

        // Build user data structure
        $userData = [
            'info' => [
                'name' => $user->name ?? '',
                'phone' => $user->phone ?? '',
                'email' => $user->email ?? '',
                'address' => $user->address ?? '',
                'city' => '', // Not stored separately
                'zip' => '', // Not stored separately
            ],
            'emergencyContact' => [
                'name' => $user->emergencyContact->name ?? '',
                'phone' => $user->emergencyContact->phone ?? '',
            ],
        ];

        // Build inventory data
        $inventoryData = [];
        foreach ($checkIn->items as $item) {
            $inventoryData[] = $item->name;
        }

        // Build grooming data
        $groomingData = [];
        foreach ($checkIn->extraServices as $service) {
            $groomingData[$service->name] = true;
        }

        return [
            'user' => $userData,
            'pets' => [$petData], // Single pet per check-in
            'inventory' => $inventoryData,
            'grooming' => $groomingData,
            'groomingDetails' => '', // Not stored separately
            'date' => $checkIn->check_in ? $checkIn->check_in->toISOString() : now()->toISOString(),
            'id' => $checkIn->id,
        ];
    }

    /**
     * Transform cookie format data to check-in data structure
     *
     * @param array $cookieData The data in cookie format
     * @return array The transformed data in check-in format
     */
    public function transformCookieToCheckInFormat(array $cookieData): array
    {
        // This would be the reverse transformation if needed
        // For now, return as-is since the cookie format is already the check-in format
        return $cookieData;
    }
}