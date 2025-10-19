<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use App\Services\CheckInService;
use App\Services\CheckInUserService;
use App\Services\CheckInPetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling API-based check-in operations
 */
class CheckInApiController extends Controller
{
    protected CheckInService $checkInService;
    protected CheckInUserService $userService;
    protected CheckInPetService $petService;

    public function __construct(
        CheckInService $checkInService,
        CheckInUserService $userService,
        CheckInPetService $petService
    ) {
        $this->checkInService = $checkInService;
        $this->userService = $userService;
        $this->petService = $petService;
    }

    /**
     * Check if user exists and has active check-in
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUser(Request $request)
    {
        $phone = $request->input('phone');

        if (!$phone) {
            return response()->json([
                'error' => 'Phone number is required'
            ], 400);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['userExists' => false]);
        }

        $hasCheckIn = CheckIn::where('user_id', $user->id)->whereNull('check_out')->exists();

        return response()->json([
            'userExists' => true,
            'hasCheckIn' => $hasCheckIn,
            'userId' => $user->id,
            'userName' => $user->name,
            'userEmail' => $user->email,
            'userAddress' => $user->address,
        ]);
    }

    /**
     * Submit complete check-in data from cookie (legacy method)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitCheckIn(Request $request)
    {
        try {
            Log::info('CheckInApiController: Starting check-in submission', ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'checkin_data' => 'required|array',
                'checkin_data.user' => 'required|array',
                'checkin_data.user.info' => 'required|array',
                'checkin_data.pets' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::error('CheckInApiController: Validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid check-in data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkinData = $request->input('checkin_data');
            Log::info('CheckInApiController: Validation passed, processing check-in data', [
                'user_info' => $checkinData['user']['info'] ?? null,
                'pet_count' => count($checkinData['pets'] ?? [])
            ]);

            // Submit check-in using service
            $result = $this->checkInService->submitCheckIn($checkinData, $this->userService, $this->petService);

            Log::info('CheckInApiController: Check-in submitted successfully', $result);

            return response()->json([
                'success' => true,
                'message' => 'Check-in submitted successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('CheckInApiController: Check-in submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 1: Submit User Info (create or verify user)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitUserInfo(Request $request)
    {
        try {
            $timestamp = now()->toISOString();
            Log::info("CheckInApiController: Step 1 - Submit User Info [{$timestamp}]", ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'user_info' => 'required|array',
                'user_info.phone' => 'required|string',
                'user_info.name' => 'required|string',
                'user_info.email' => 'required|email',
            ]);

            if ($validator->fails()) {
                Log::error("CheckInApiController: Step 1 - User Info validation failed [{$timestamp}]", ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user info data',
                    'errors' => $validator->errors(),
                    'timestamp' => $timestamp
                ], 422);
            }

            $userInfo = $request->input('user_info');
            Log::info("CheckInApiController: Step 1 - Processing user info [{$timestamp}]", [
                'phone' => $userInfo['phone'],
                'name' => $userInfo['name'],
                'email' => $userInfo['email']
            ]);

            // Process user using service
            $user = $this->userService->processUser(['info' => $userInfo]);

            // Process emergency contact if provided
            if (isset($userInfo['emergencyContact'])) {
                $this->userService->processEmergencyContact($user, $userInfo['emergencyContact']);
                Log::info("CheckInApiController: Step 1 - Emergency contact processed [{$timestamp}]", ['user_id' => $user->id]);
            }

            Log::info("CheckInApiController: Step 1 - User info submitted successfully [{$timestamp}]", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User info submitted successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_phone' => $user->phone
                ],
                'timestamp' => $timestamp
            ]);

        } catch (\Exception $e) {
            $timestamp = now()->toISOString();
            Log::error("CheckInApiController: Step 1 - User info submission failed [{$timestamp}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit user info: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ], 500);
        }
    }

    /**
     * Step 2: Submit Pet Info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitPetInfo(Request $request)
    {
        try {
            $timestamp = now()->toISOString();
            Log::info("CheckInApiController: Step 2 - Submit Pet Info [{$timestamp}]", ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'pet_info' => 'required|array',
                'pet_info.petName' => 'required|string',
                'pet_info.petType' => 'required|string',
                'pet_info.petBreed' => 'required|string',
                'pet_info.petColor' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::error("CheckInApiController: Step 2 - Pet Info validation failed [{$timestamp}]", ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pet info data',
                    'errors' => $validator->errors(),
                    'timestamp' => $timestamp
                ], 422);
            }

            $userId = $request->input('user_id');
            $petInfo = $request->input('pet_info');

            Log::info("CheckInApiController: Step 2 - Processing pet info [{$timestamp}]", [
                'user_id' => $userId,
                'pet_name' => $petInfo['petName'],
                'pet_type' => $petInfo['petType'],
                'pet_breed' => $petInfo['petBreed']
            ]);

            // Get user
            $user = \App\Models\User::findOrFail($userId);

            // Process pet using service (only basic info, no health yet)
            $pet = $this->petService->processPet($user, ['info' => $petInfo]);

            Log::info("CheckInApiController: Step 2 - Pet info submitted successfully [{$timestamp}]", [
                'pet_id' => $pet->id,
                'pet_name' => $pet->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pet info submitted successfully',
                'data' => [
                    'pet_id' => $pet->id,
                    'pet_name' => $pet->name,
                    'pet_type' => $pet->kindOfPet->name ?? $petInfo['petType'],
                    'pet_breed' => $pet->race,
                    'pet_color' => $pet->color
                ],
                'timestamp' => $timestamp
            ]);

        } catch (\Exception $e) {
            $timestamp = now()->toISOString();
            Log::error("CheckInApiController: Step 2 - Pet info submission failed [{$timestamp}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit pet info: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ], 500);
        }
    }

    /**
     * Step 3: Submit Pet Health, Feeding, and Medication
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitPetHealth(Request $request)
    {
        try {
            $timestamp = now()->toISOString();
            Log::info("CheckInApiController: Step 3 - Submit Pet Health [{$timestamp}]", ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'pet_id' => 'required|integer|exists:pets,id',
                'health_data' => 'required|array',
                'feeding_data' => 'array',
                'medication_data' => 'array',
            ]);

            if ($validator->fails()) {
                Log::error("CheckInApiController: Step 3 - Pet Health validation failed [{$timestamp}]", ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pet health data',
                    'errors' => $validator->errors(),
                    'timestamp' => $timestamp
                ], 422);
            }

            $petId = $request->input('pet_id');
            $healthData = $request->input('health_data', []);
            $feedingData = $request->input('feeding_data', []);
            $medicationData = $request->input('medication_data', []);

            Log::info("CheckInApiController: Step 3 - Processing pet health data [{$timestamp}]", [
                'pet_id' => $petId,
                'feeding_count' => count($feedingData),
                'medication_count' => count($medicationData),
                'has_health_warnings' => !empty($healthData['warnings'])
            ]);

            // Get pet
            $pet = \App\Models\Pet::findOrFail($petId);

            // Update pet health info
            $pet->update([
                'health_conditions' => $healthData['unusualHealthBehavior'] ?? null,
                'warnings' => $healthData['warnings'] ?? null,
            ]);

            // Create a temporary check-in for feeding/medication (will be updated in step 4)
            $tempCheckIn = \App\Models\CheckIn::create([
                'check_in' => now(),
                'pet_id' => $petId,
                'user_id' => $pet->user_id,
            ]);

            // Process feeding and medication
            $this->petService->processFeedingAndMedication($pet, $tempCheckIn, [
                'feeding' => $feedingData,
                'medication' => $medicationData
            ]);

            Log::info("CheckInApiController: Step 3 - Pet health submitted successfully [{$timestamp}]", [
                'pet_id' => $petId,
                'temp_checkin_id' => $tempCheckIn->id,
                'feeding_count' => count($feedingData),
                'medication_count' => count($medicationData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pet health data submitted successfully',
                'data' => [
                    'pet_id' => $petId,
                    'temp_checkin_id' => $tempCheckIn->id,
                    'feeding_count' => count($feedingData),
                    'medication_count' => count($medicationData)
                ],
                'timestamp' => $timestamp
            ]);

        } catch (\Exception $e) {
            $timestamp = now()->toISOString();
            Log::error("CheckInApiController: Step 3 - Pet health submission failed [{$timestamp}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit pet health data: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ], 500);
        }
    }

    /**
     * Step 4: Submit Check-In Data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitCheckInData(Request $request)
    {
        try {
            $timestamp = now()->toISOString();
            Log::info("CheckInApiController: Step 4 - Submit Check-In Data [{$timestamp}]", ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'pet_id' => 'required|integer|exists:pets,id',
                'checkin_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::error("CheckInApiController: Step 4 - Check-In Data validation failed [{$timestamp}]", ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid check-in data',
                    'errors' => $validator->errors(),
                    'timestamp' => $timestamp
                ], 422);
            }

            $petId = $request->input('pet_id');
            $checkinData = $request->input('checkin_data');

            Log::info("CheckInApiController: Step 4 - Processing check-in data [{$timestamp}]", [
                'pet_id' => $petId,
                'grooming_services' => array_keys($checkinData['grooming'] ?? []),
                'inventory_items' => count($checkinData['inventory'] ?? [])
            ]);

            // Get pet and user
            $pet = \App\Models\Pet::findOrFail($petId);
            $user = $pet->user;

            // Create or update check-in
            $checkIn = $this->checkInService->processCheckIn($user, $pet, $checkinData);

            // Process inventory and grooming
            if (isset($checkinData['inventory'])) {
                $this->checkInService->processInventory([$checkIn], $checkinData['inventory']);
            }

            if (isset($checkinData['grooming'])) {
                $this->checkInService->processExtraServices([$checkIn], $checkinData['grooming']);
            }

            Log::info("CheckInApiController: Step 4 - Check-in data submitted successfully [{$timestamp}]", [
                'checkin_id' => $checkIn->id,
                'pet_id' => $petId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in data submitted successfully',
                'data' => [
                    'checkin_id' => $checkIn->id,
                    'pet_id' => $petId,
                    'user_id' => $user->id,
                    'checkin_time' => $checkIn->check_in->toISOString()
                ],
                'timestamp' => $timestamp
            ]);

        } catch (\Exception $e) {
            $timestamp = now()->toISOString();
            Log::error("CheckInApiController: Step 4 - Check-in data submission failed [{$timestamp}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit check-in data: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ], 500);
        }
    }

    /**
     * Step 5: Submit Extra Info (Inventory, Grooming, etc.)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitExtraInfo(Request $request)
    {
        try {
            $timestamp = now()->toISOString();
            Log::info("CheckInApiController: Step 5 - Submit Extra Info [{$timestamp}]", ['request_data' => $request->all()]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'checkin_id' => 'required|integer|exists:check_ins,id',
                'extra_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::error("CheckInApiController: Step 5 - Extra Info validation failed [{$timestamp}]", ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid extra info data',
                    'errors' => $validator->errors(),
                    'timestamp' => $timestamp
                ], 422);
            }

            $checkinId = $request->input('checkin_id');
            $extraData = $request->input('extra_data');

            Log::info("CheckInApiController: Step 5 - Processing extra info [{$timestamp}]", [
                'checkin_id' => $checkinId,
                'inventory_count' => count($extraData['inventory'] ?? []),
                'grooming_services' => array_keys($extraData['grooming'] ?? [])
            ]);

            // Get check-in
            $checkIn = \App\Models\CheckIn::findOrFail($checkinId);

            // Process additional inventory if provided
            if (isset($extraData['inventory']) && is_array($extraData['inventory'])) {
                $this->checkInService->processInventory([$checkIn], $extraData['inventory']);
            }

            // Process additional grooming services if provided
            if (isset($extraData['grooming']) && is_array($extraData['grooming'])) {
                $this->checkInService->processExtraServices([$checkIn], $extraData['grooming']);
            }

            Log::info("CheckInApiController: Step 5 - Extra info submitted successfully [{$timestamp}]", [
                'checkin_id' => $checkinId,
                'processed_inventory' => count($extraData['inventory'] ?? []),
                'processed_grooming' => count($extraData['grooming'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Extra info submitted successfully',
                'data' => [
                    'checkin_id' => $checkinId,
                    'processed_inventory' => count($extraData['inventory'] ?? []),
                    'processed_grooming' => count($extraData['grooming'] ?? [])
                ],
                'timestamp' => $timestamp
            ]);

        } catch (\Exception $e) {
            $timestamp = now()->toISOString();
            Log::error("CheckInApiController: Step 5 - Extra info submission failed [{$timestamp}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit extra info: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ], 500);
        }
    }

    /**
     * Auto-save check-in data (for future implementation)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
