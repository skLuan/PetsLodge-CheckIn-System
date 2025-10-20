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
use Illuminate\Support\Facades\Log;

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
                  ->where('name', $petInfo['petName'])
                  ->where('race', $petInfo['petBreed'])
                  ->where('color', $petInfo['petColor'])
                  ->first();

        $petAttributes = [
            'name' => $petInfo['petName'] ?? null, // Add pet name
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
     * Process pet info for sequential submission (always creates new pet)
     *
     * @param \App\Models\User $user The user instance
     * @param array $petInfo The pet information array
     * @return Pet The created pet instance
     * @throws \Exception If pet data is invalid
     */
    public function processPetInfo($user, array $petInfo): Pet
    {
        // Validate required fields
        if (empty($petInfo['petName']) || empty($petInfo['petType']) || empty($petInfo['petBreed']) || empty($petInfo['petColor'])) {
            throw new \Exception('Pet name, type, breed, and color are required');
        }
        // Get or create related entities
        $gender = Gender::firstOrCreate(['name' => $petInfo['petGender'] ?? 'unknown']);
        $kindOfPet = KindOfPet::firstOrCreate(['name' => $petInfo['petType']]);
        $castrated = Castrated::firstOrCreate(['status' => $petInfo['petSpayed'] ?? 'unknown']);

        // Always create a new pet for sequential submissions
        $pet = Pet::create([
            'user_id' => $user->id,
            'name' => $petInfo['petName'],
            'birth_date' => isset($petInfo['petAge']) ? date('Y-m-d', strtotime($petInfo['petAge'])) : null,
            'race' => $petInfo['petBreed'],
            'color' => $petInfo['petColor'],
            'gender_id' => $gender->id,
            'kind_of_pet_id' => $kindOfPet->id,
            'castrated_id' => $castrated->id,
            // Health data will be added in step 3
            'health_conditions' => null,
            'warnings' => null,
        ]);

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
        Log::info('CheckInPetService: Processing feeding and medication', [
            'pet_id' => $pet->id,
            'checkin_id' => $checkIn->id,
            'feeding_count' => isset($petData['feeding']) ? count($petData['feeding']) : 0,
            'medication_count' => isset($petData['medication']) ? count($petData['medication']) : 0
        ]);

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
            Log::info('CheckInPetService: Created feeding entries', ['count' => count($petData['feeding'])]);
        } else {
            // Create a default food entry if no feeding data provided
            // This ensures the pet_id is properly set even with minimal data
            $momentOfDay = MomentOfDay::firstOrCreate(['name' => 'morning']);
            Food::create([
                'name' => 'Food',
                'description' => 'Standard food',
                'pet_id' => $pet->id,
                'moment_of_day_id' => $momentOfDay->id,
                'check_in_id' => $checkIn->id,
            ]);
            Log::info('CheckInPetService: Created default food entry');
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
            Log::info('CheckInPetService: Created medication entries', ['count' => count($petData['medication'])]);
        } else {
            // Create a default medicine entry if no medication data provided
            // This ensures the pet_id is properly set even with minimal data
            $momentOfDay = MomentOfDay::firstOrCreate(['name' => 'morning']);
            Medicine::create([
                'name' => 'None',
                'description' => '',
                'pet_id' => $pet->id,
                'moment_of_day_id' => $momentOfDay->id,
                'check_in_id' => $checkIn->id,
            ]);
            Log::info('CheckInPetService: Created default medicine entry');
        }
    }
}