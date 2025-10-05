<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use App\Models\Pet;
use App\Models\Food;
use App\Models\Medicine;
use App\Models\Item;
use App\Models\EmergencyContact;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\Castrated;
use App\Models\MomentOfDay;
use App\Models\ExtraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class CheckInController extends Controller
{
    public function checkUser(Request $request)
    {
        $phone = $request->input('phone');

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['userExists' => false]);
        }

        $hasCheckIn = CheckIn::where('user_id', $user->id)->whereNull('check_out')->exists();

        return response()->json([
            'userExists' => true,
            'hasCheckIn' => $hasCheckIn
        ]);
    }

    public function newForm(Request $request)
    {
        $phone = $request->get('phone');
        $user = null;
        if ($phone) {
            $user = User::where('phone', $phone)->first();
        }
        return view('Process', compact('user'));
    }

    public function newFormPreFilled(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();
        // Pre-fill form with user data
        return view('Process', compact('user'));
    }

    public function viewCheckIn(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();
        $checkIns = CheckIn::where('user_id', $user->id)->whereNull('check_out')->with('pet')->get();
        return view('view-check-in', compact('checkIns'));
    }

    /**
     * Submit complete check-in data from cookie
     */
    public function submitCheckIn(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'checkin_data' => 'required|array',
                'checkin_data.user' => 'required|array',
                'checkin_data.user.info' => 'required|array',
                'checkin_data.pets' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid check-in data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkinData = $request->input('checkin_data');

            // Process check-in in database transaction
            DB::beginTransaction();

            try {
                // 1. Process User
                $user = $this->processUser($checkinData['user']);

                // 2. Process Emergency Contact
                if (isset($checkinData['user']['emergencyContact'])) {
                    $this->processEmergencyContact($user, $checkinData['user']['emergencyContact']);
                }

                // 3. Process Pets and Check-ins
                $checkIns = [];
                foreach ($checkinData['pets'] as $petData) {
                    $pet = $this->processPet($user, $petData);
                    $checkIn = $this->processCheckIn($user, $pet, $checkinData);

                    // 4. Process Feeding and Medication for this pet
                    $this->processFeedingAndMedication($pet, $checkIn, $petData);

                    $checkIns[] = $checkIn;
                }

                // 5. Process Inventory Items
                if (isset($checkinData['inventory'])) {
                    $this->processInventory($checkIns, $checkinData['inventory']);
                }

                // 6. Process Extra Services (Grooming)
                if (isset($checkinData['grooming'])) {
                    $this->processExtraServices($checkIns, $checkinData['grooming']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in submitted successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'checkin_ids' => collect($checkIns)->pluck('id'),
                        'pet_count' => count($checkinData['pets'])
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process or create user
     */
    private function processUser($userData)
    {
        $userInfo = $userData['info'];

        // Check if user exists by phone or email
        $user = User::where('phone', $userInfo['phone'])
                   ->orWhere('email', $userInfo['email'])
                   ->first();

        if ($user) {
            // Update existing user
            $user->update([
                'name' => $userInfo['name'] ?? $user->name,
                'email' => $userInfo['email'] ?? $user->email,
                'phone' => $userInfo['phone'] ?? $user->phone,
                'address' => $userInfo['address'] ?? $user->address,
            ]);
        } else {
            // Create new user
            $user = User::create([
                'name' => $userInfo['name'],
                'email' => $userInfo['email'],
                'phone' => $userInfo['phone'],
                'address' => $userInfo['address'],
                'password' => bcrypt('default_password'), // Should be changed later
            ]);
        }

        return $user;
    }

    /**
     * Process emergency contact
     */
    private function processEmergencyContact($user, $emergencyData)
    {
        if (empty($emergencyData['name']) || empty($emergencyData['phone'])) {
            return;
        }

        EmergencyContact::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $emergencyData['name'],
                'phone' => $emergencyData['phone']
            ]
        );
    }

    /**
     * Process pet data
     */
    private function processPet($user, $petData)
    {
        $petInfo = $petData['info'];

        // Get or create related entities
        $gender = Gender::firstOrCreate(['name' => $petInfo['petGender'] ?? 'unknown']);
        $kindOfPet = KindOfPet::firstOrCreate(['name' => $petInfo['petType'] ?? 'unknown']);
        $castrated = Castrated::firstOrCreate(['status' => $petInfo['petSpayed'] ?? 'unknown']);

        // Check if pet exists (by name and user)
        $pet = Pet::where('user_id', $user->id)
                 ->where('race', $petInfo['petBreed'])
                 ->where('color', $petInfo['petColor'])
                 ->first();

        if ($pet) {
            // Update existing pet
            $pet->update([
                'birth_date' => $petInfo['petAge'] ? date('Y-m-d', strtotime($petInfo['petAge'])) : null,
                'race' => $petInfo['petBreed'],
                'color' => $petInfo['petColor'],
                'health_conditions' => $petData['health']['unusualHealthBehavior'] ?? null,
                'warnings' => $petData['health']['warnings'] ?? null,
                'gender_id' => $gender->id,
                'kind_of_pet_id' => $kindOfPet->id,
                'castrated_id' => $castrated->id,
            ]);
        } else {
            // Create new pet
            $pet = Pet::create([
                'birth_date' => $petInfo['petAge'] ? date('Y-m-d', strtotime($petInfo['petAge'])) : null,
                'race' => $petInfo['petBreed'],
                'color' => $petInfo['petColor'],
                'health_conditions' => $petData['health']['unusualHealthBehavior'] ?? null,
                'warnings' => $petData['health']['warnings'] ?? null,
                'gender_id' => $gender->id,
                'kind_of_pet_id' => $kindOfPet->id,
                'castrated_id' => $castrated->id,
                'user_id' => $user->id,
            ]);
        }

        return $pet;
    }

    /**
     * Process check-in record
     */
    private function processCheckIn($user, $pet, $checkinData)
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
     * Process feeding and medication
     */
    private function processFeedingAndMedication($pet, $checkIn, $petData)
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

    /**
     * Process inventory items
     */
    private function processInventory($checkIns, $inventoryData)
    {
        if (!is_array($inventoryData)) return;

        foreach ($checkIns as $checkIn) {
            foreach ($inventoryData as $itemData) {
                Item::create([
                    'name' => $itemData['name'] ?? 'Item',
                    'description' => $itemData['description'] ?? null,
                    'pet_id' => $checkIn->pet_id,
                    'check_in_id' => $checkIn->id,
                ]);
            }
        }
    }

    /**
     * Process extra services (grooming)
     */
    private function processExtraServices($checkIns, $groomingData)
    {
        if (!is_array($groomingData)) return;

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
     * Auto-save check-in data (for future implementation)
     */
    public function autoSaveCheckIn(Request $request)
    {
        // This could be implemented later for periodic saving
        return response()->json([
            'success' => true,
            'message' => 'Auto-save not implemented yet'
        ]);
    }
}
