<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\Food;
use App\Models\Medicine;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\Castrated;
use App\Models\MomentOfDay;
use App\Models\CheckIn;

/**
 * Service class for handling pet and feeding/medication operations during check-in
 */
class CheckInPetService
{
    /**
     * Process pet data from check-in form
     *
     * @param \App\Models\User $user The user instance
     * @param array $petData The pet data from the check-in form
     * @return Pet The processed pet instance
     * @throws \Exception If pet data is invalid
     */
    public function processPet($user, array $petData): Pet
    {
        if (!isset($petData['info'])) {
            throw new \Exception('Pet info is required');
        }

        $petInfo = $petData['info'];

        // Validate required fields
        if (empty($petInfo['petBreed']) || empty($petInfo['petColor'])) {
            throw new \Exception('Pet breed and color are required');
        }

        // Get or create related entities
        $gender = Gender::firstOrCreate(['name' => $petInfo['petGender'] ?? 'unknown']);
        $kindOfPet = KindOfPet::firstOrCreate(['name' => $petInfo['petType'] ?? 'unknown']);
        $castrated = Castrated::firstOrCreate(['status' => $petInfo['petSpayed'] ?? 'unknown']);

        // Check if pet exists (by user and basic info)
        $pet = Pet::where('user_id', $user->id)
                 ->where('race', $petInfo['petBreed'])
                 ->where('color', $petInfo['petColor'])
                 ->first();

        $petAttributes = [
            'birth_date' => isset($petInfo['petAge']) ? date('Y-m-d', strtotime($petInfo['petAge'])) : null,
            'race' => $petInfo['petBreed'],
            'color' => $petInfo['petColor'],
            'health_conditions' => $petData['health']['unusualHealthBehavior'] ?? null,
            'warnings' => $petData['health']['warnings'] ?? null,
            'gender_id' => $gender->id,
            'kind_of_pet_id' => $kindOfPet->id,
            'castrated_id' => $castrated->id,
        ];

        if ($pet) {
            // Update existing pet
            $pet->update($petAttributes);
        } else {
            // Create new pet
            $petAttributes['user_id'] = $user->id;
            $pet = Pet::create($petAttributes);
        }

        return $pet;
    }

    /**
     * Process feeding and medication for a pet
     *
     * @param Pet $pet The pet instance
     * @param CheckIn $checkIn The check-in instance
     * @param array $petData The pet data containing feeding and medication info
     * @return void
     */
    public function processFeedingAndMedication(Pet $pet, CheckIn $checkIn, array $petData): void
    {
        // Process feeding
        if (isset($petData['feeding']) && is_array($petData['feeding'])) {
            foreach ($petData['feeding'] as $feedData) {
                $momentOfDay = MomentOfDay::firstOrCreate(['name' => $feedData['day_time'] ?? 'morning']);

                Food::create([
                    'name' => $feedData['feeding_med_details'] ?? 'Food',
                    'description' => $feedData['feeding_med_details'] ?? null,
                    'pet_id' => $pet->id,
                    'moment_of_day_id' => $momentOfDay->id,
                    'check_in_id' => $checkIn->id,
                ]);
            }
        }

        // Process medication
        if (isset($petData['medication']) && is_array($petData['medication'])) {
            foreach ($petData['medication'] as $medData) {
                $momentOfDay = MomentOfDay::firstOrCreate(['name' => $medData['day_time'] ?? 'morning']);

                Medicine::create([
                    'name' => $medData['feeding_med_details'] ?? 'Medicine',
                    'description' => $medData['feeding_med_details'] ?? null,
                    'pet_id' => $pet->id,
                    'moment_of_day_id' => $momentOfDay->id,
                    'check_in_id' => $checkIn->id,
                ]);
            }
        }
    }
}