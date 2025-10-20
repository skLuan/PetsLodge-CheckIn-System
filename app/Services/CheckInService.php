<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Item;
use App\Models\ExtraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling check-in, inventory, and extra services operations
 */
class CheckInService
{
    /**
     * Process check-in record for a pet
     *
     * @param \App\Models\User $user The user instance
     * @param \App\Models\Pet $pet The pet instance
     * @param array $checkinData The check-in data
     * @return CheckIn The processed check-in instance
     */
    public function processCheckIn($user, $pet, array $checkinData): CheckIn
    {
        // Check if there's already an active check-in for this pet
        $existingCheckIn = CheckIn::where('pet_id', $pet->id)
                                 ->whereNull('check_out')
                                 ->first();

        if ($existingCheckIn) {
            // Update existing check-in
            $existingCheckIn->update([
                'check_in' => now(),
            ]);
            return $existingCheckIn;
        } else {
            // Create new check-in
            return CheckIn::create([
                'check_in' => now(),
                'pet_id' => $pet->id,
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Process inventory items for check-ins
     *
     * @param array $checkIns Array of CheckIn instances
     * @param array $inventoryData The inventory data
     * @return void
     */
    public function processInventory(array $checkIns, array $inventoryData): void
    {
        if (!is_array($inventoryData)) {
            return;
        }

        foreach ($checkIns as $checkIn) {
            foreach ($inventoryData as $itemData) {
                Item::create([
                    'name' => $itemData['name'] ?? 'Item',
                    'pet_id' => $checkIn->pet_id,
                    'check_in_id' => $checkIn->id,
                ]);
            }
        }
    }

    /**
     * Process extra services (grooming) for check-ins
     *
     * @param array $checkIns Array of CheckIn instances
     * @param array $groomingData The grooming/extra services data
     * @return void
     */
    public function processExtraServices(array $checkIns, array $groomingData): void
    {
        if (!is_array($groomingData)) {
            return;
        }

        foreach ($checkIns as $checkIn) {
            foreach ($groomingData as $service => $enabled) {
                if ($enabled) {
                    $extraService = ExtraService::firstOrCreate(['name' => $service]);

                    // Attach service to check-in (using pivot table)
                    $checkIn->extraServices()->syncWithoutDetaching([$extraService->id]);
                }
            }
        }
    }

    /**
     * Submit complete check-in data using database transaction
     *
     * @param array $checkinData The complete check-in data
     * @param CheckInUserService $userService The user service instance
     * @param CheckInPetService $petService The pet service instance
     * @return array Response data with success status and details
     * @throws \Exception If submission fails
     */
    public function submitCheckIn(array $checkinData, CheckInUserService $userService, CheckInPetService $petService): array
    {
        return DB::transaction(function () use ($checkinData, $userService, $petService) {
            Log::info('CheckInService: Starting check-in submission', ['pet_count' => count($checkinData['pets'] ?? [])]);

            // 1. Process User
            $user = $userService->processUser($checkinData['user']);
            Log::info('CheckInService: User processed', ['user_id' => $user->id]);

            // 2. Process Emergency Contact
            if (isset($checkinData['user']['emergencyContact'])) {
                $userService->processEmergencyContact($user, $checkinData['user']['emergencyContact']);
                Log::info('CheckInService: Emergency contact processed');
            }

            // 3. Process Pets and Check-ins
            $checkIns = [];
            foreach ($checkinData['pets'] as $index => $petData) {
                Log::info('CheckInService: Processing pet', ['pet_index' => $index, 'pet_name' => $petData['info']['petName'] ?? 'unnamed']);

                $pet = $petService->processPet($user, $petData);
                Log::info('CheckInService: Pet processed', ['pet_id' => $pet->id, 'pet_name' => $pet->name]);

                $checkIn = $this->processCheckIn($user, $pet, $checkinData);
                Log::info('CheckInService: Check-in processed', ['checkin_id' => $checkIn->id]);

                // 4. Process Feeding and Medication for this pet
                $petService->processFeedingAndMedication($pet, $checkIn, $petData);
                Log::info('CheckInService: Feeding and medication processed for pet', ['pet_id' => $pet->id]);

                $checkIns[] = $checkIn;
            }

            // 5. Process Inventory Items
            if (isset($checkinData['inventory'])) {
                $this->processInventory($checkIns, $checkinData['inventory']);
                Log::info('CheckInService: Inventory processed', ['inventory_count' => count($checkinData['inventory'])]);
            }

            // 6. Process Extra Services (Grooming)
            if (isset($checkinData['grooming'])) {
                $this->processExtraServices($checkIns, $checkinData['grooming']);
                Log::info('CheckInService: Extra services processed');
            }

            $result = [
                'user_id' => $user->id,
                'checkin_ids' => collect($checkIns)->pluck('id'),
                'pet_count' => count($checkinData['pets']),
                'pet_ids' => collect($checkIns)->pluck('pet_id')
            ];

            Log::info('CheckInService: Check-in submission completed successfully', $result);
            return $result;
        });
    }
}