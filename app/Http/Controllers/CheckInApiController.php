<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use App\Services\CheckInService;
use App\Services\CheckInUserService;
use App\Services\CheckInPetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'hasCheckIn' => $hasCheckIn
        ]);
    }

    /**
     * Submit complete check-in data from cookie
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

            // Submit check-in using service
            $result = $this->checkInService->submitCheckIn($checkinData, $this->userService, $this->petService);

            return response()->json([
                'success' => true,
                'message' => 'Check-in submitted successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit check-in: ' . $e->getMessage()
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
