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
                    'name' => $itemData ?? 'Item',
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
}