<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Item;
use App\Models\ExtraService;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling check-in, inventory, and extra services operations
 */
class CheckInService
{
    /**
     * Statuses of a stay that is over. A row in one of these is history, never
     * something a new or edited submission may write into.
     */
    private const CLOSED_STATUSES = ['CHECKED_OUT', 'CANCELLED'];

    /**
     * The pet's current, still-open stay, if any.
     *
     * Steps 3 and 4 both run once per pet and must land on the SAME row. When
     * step 3 created its own row unconditionally, editing a check-in left the
     * original row in place and added a second one — the booking grew a phantom
     * pet on every save.
     *
     * `CANCELLED` is excluded explicitly: cancelling sets the status but leaves
     * `check_out` null.
     */
    public function findActiveCheckIn($pet): ?CheckIn
    {
        return CheckIn::where('pet_id', $pet->id)
            ->whereNull('check_out')
            ->whereHas('status', fn ($query) => $query->whereNotIn('name', self::CLOSED_STATUSES))
            ->orderBy('id')
            ->first();
    }

    /**
     * The pet's open stay, created as CHECKED_IN when there is none (a new
     * check-in). An edit reuses the row being edited.
     */
    public function findOrCreateActiveCheckIn($pet): CheckIn
    {
        $existing = $this->findActiveCheckIn($pet);

        if ($existing) {
            return $existing;
        }

        $checkedInStatus = Status::where('name', 'CHECKED_IN')->first();
        if (!$checkedInStatus) {
            throw new \Exception('CHECKED_IN status not found in database');
        }

        return CheckIn::create([
            'check_in' => now(),
            'pet_id' => $pet->id,
            'user_id' => $pet->user_id,
            'status_id' => $checkedInStatus->id,
        ]);
    }

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
        // Get CHECKED_IN status
        $checkedInStatus = Status::where('name', 'CHECKED_IN')->first();
        if (!$checkedInStatus) {
            throw new \Exception('CHECKED_IN status not found in database');
        }

        // Must resolve the same row step 3 attached the feeding/medication to.
        $existingCheckIn = $this->findActiveCheckIn($pet);

        if ($existingCheckIn) {
            // Update existing check-in
            $existingCheckIn->update([
                'check_in' => now(),
                'status_id' => $checkedInStatus->id,
            ]);
            return $existingCheckIn;
        } else {
            // Create new check-in with CHECKED_IN status
            return CheckIn::create([
                'check_in' => now(),
                'pet_id' => $pet->id,
                'user_id' => $user->id,
                'status_id' => $checkedInStatus->id,
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
            // The payload is the full list, not a delta: replace, so re-saving
            // an edited check-in doesn't duplicate every item.
            $checkIn->items()->delete();

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

        // Get the appointment day if it exists
        $appointmentDay = $groomingData['appointmentDay'] ?? null;

        $services = [];
        foreach ($groomingData as $service => $enabled) {
            // Skip non-service keys like 'appointmentDay' and the per-pet selector 'pets'
            if (in_array($service, ['appointmentDay', 'pets'], true) || !$enabled) {
                continue;
            }

            $extraService = ExtraService::firstOrCreate(['name' => $service]);
            $services[$extraService->id] = ['grooming_appointment_day' => $appointmentDay];
        }

        // Full set, not a delta: sync so a service unticked while editing is removed.
        foreach ($checkIns as $checkIn) {
            $checkIn->extraServices()->sync($services);
        }
    }
}