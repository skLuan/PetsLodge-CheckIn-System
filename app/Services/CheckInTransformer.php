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
     * PHASE 2.4: Enhanced to include all missing fields (city, zip, petWeight)
     * and properly handle all data transformations
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
                'petWeight' => $pet->weight ?? '', // PHASE 2.4: Added from model if available
                'petGender' => $pet->gender->name ?? '',
                'petSpayed' => $pet->castrated->status ?? '',
            ],
            'health' => [
                'unusualHealthBehavior' => !empty($pet->health_conditions) || !empty($pet->warnings),
                'healthBehaviors' => $pet->health_conditions ?? '',
                'warnings' => $pet->warnings ?? '',
            ],
            'feeding' => [],
            'medication' => [],
        ];

        // Add feeding data
        foreach ($checkIn->foods as $food) {
            $petData['feeding'][] = [
                'day_time' => $food->moment_of_day->name ?? 'morning',
                'feeding_med_details' => $food->name,
                'description' => $food->description ?? '',
            ];
        }

        // Add medication data
        foreach ($checkIn->medicines as $medicine) {
            $petData['medication'][] = [
                'day_time' => $medicine->moment_of_day->name ?? 'morning',
                'feeding_med_details' => $medicine->name,
                'description' => $medicine->description ?? '',
            ];
        }

         // Build user data structure
         // PHASE 2.4: Added city and zip fields
         // CRITICAL FIX #5: Added null checks for emergency contact
         $userData = [
             'info' => [
                 'name' => $user->name ?? '',
                 'phone' => $user->phone ?? '',
                 'email' => $user->email ?? '',
                 'address' => $user->address ?? '',
                 'city' => $user->city ?? '', // PHASE 2.4: Added
                 'zip' => $user->zip ?? '', // PHASE 2.4: Added
             ],
             'emergencyContact' => [
                 'name' => $user->emergencyContacts?->first()?->name ?? '',
                 'phone' => $user->emergencyContacts?->first()?->phone ?? '',
             ],
         ];

        // Build inventory data
        $inventoryData = [];
        foreach ($checkIn->items as $item) {
            $inventoryData[] = $item->name;
        }

        // Build grooming data
        $groomingData = [];
        $appointmentDay = null;
        
        foreach ($checkIn->extraServices as $service) {
            $groomingData[$service->name] = true;
            
            // Get appointment day from pivot table if available
            if ($service->pivot && $service->pivot->grooming_appointment_day) {
                $appointmentDay = $service->pivot->grooming_appointment_day;
            }
        }
        
        // Add appointment day to grooming data if it exists
        if ($appointmentDay) {
            $groomingData['appointmentDay'] = $appointmentDay;
        }

        return [
            'user' => $userData,
            'pets' => [$petData], // Single pet per check-in
            'inventory' => $inventoryData,
            'grooming' => $groomingData,
            'groomingDetails' => '', // Not stored separately
            'inventoryComplete' => count($inventoryData) > 0,
            'termsAccepted' => false,
            'date' => $checkIn->check_in ? $checkIn->check_in->toISOString() : now()->toISOString(),
            'id' => $checkIn->id,
        ];
    }

    /**
     * Transform cookie format data to check-in data structure
     *
     * PHASE 2.4: Implemented reverse transformation for submitting edited check-ins
     * Converts the cookie format back to the database format for storage
     *
     * @param array $cookieData The data in cookie format
     * @return array The transformed data in check-in format for database storage
     */
    public function transformCookieToCheckInFormat(array $cookieData): array
    {
        // Extract the first pet (single pet per check-in)
        $petData = $cookieData['pets'][0] ?? [];
        $userData = $cookieData['user'] ?? [];
        $inventoryData = $cookieData['inventory'] ?? [];
        $groomingData = $cookieData['grooming'] ?? [];

        return [
            'user' => [
                'name' => $userData['info']['name'] ?? '',
                'phone' => $userData['info']['phone'] ?? '',
                'email' => $userData['info']['email'] ?? '',
                'address' => $userData['info']['address'] ?? '',
                'city' => $userData['info']['city'] ?? '',
                'zip' => $userData['info']['zip'] ?? '',
                'emergencyContact' => [
                    'name' => $userData['emergencyContact']['name'] ?? '',
                    'phone' => $userData['emergencyContact']['phone'] ?? '',
                ],
            ],
            'pet' => [
                'name' => $petData['info']['petName'] ?? '',
                'kindOfPet' => $petData['info']['petType'] ?? '',
                'race' => $petData['info']['petBreed'] ?? '',
                'color' => $petData['info']['petColor'] ?? '',
                'birth_date' => $petData['info']['petAge'] ?? '',
                'weight' => $petData['info']['petWeight'] ?? '',
                'gender' => $petData['info']['petGender'] ?? '',
                'castrated' => $petData['info']['petSpayed'] ?? '',
                'health_conditions' => $petData['health']['healthBehaviors'] ?? '',
                'warnings' => $petData['health']['warnings'] ?? '',
            ],
            'feeding' => $petData['feeding'] ?? [],
            'medication' => $petData['medication'] ?? [],
            'inventory' => $inventoryData,
            'grooming' => $groomingData,
            'groomingDetails' => $cookieData['groomingDetails'] ?? '',
        ];
    }
}