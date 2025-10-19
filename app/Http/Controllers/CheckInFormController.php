<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use App\Services\CheckInTransformer;
use Illuminate\Http\Request;

/**
 * Controller for handling web form-based check-in operations
 */
class CheckInFormController extends Controller
{
    protected CheckInTransformer $transformer;

    public function __construct(CheckInTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Display the new check-in form
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function newForm(Request $request)
    {
        $phone = $request->get('phone');
        $user = null;
        if ($phone) {
            $user = User::where('phone', $phone)->first();
        }
        return view('Process', compact('user'));
    }

    /**
     * Display the new check-in form pre-filled with user data
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function newFormPreFilled(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return redirect()->route('new-form')->with('error', 'User not found');
        }

        // Pre-fill form with user data
        return view('Process', compact('user'));
    }

    /**
     * Display check-in view for a user
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function viewCheckIn(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return redirect()->route('CheckIn')->with('error', 'User not found');
        }

        $checkIns = CheckIn::where('user_id', $user->id)
                           ->whereNull('check_out')
                           ->with('pet')
                           ->get();

        return view('view-check-in', compact('checkIns'));
    }

    /**
     * Prepare check-in data for editing
     *
     * @param Request $request
     * @param int $checkInId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editCheckIn(Request $request, $checkInId)
    {
        $checkIn = CheckIn::with([
            'pet.gender',
            'pet.kindOfPet',
            'pet.castrated',
            'user.emergencyContact',
            'foods.moment_of_day',
            'medicines.moment_of_day',
            'items',
            'extraServices'
        ])->findOrFail($checkInId);

        $cookieData = $this->transformer->transformCheckInToCookieFormat($checkIn);

        // Store in session for pre-population
        session(['checkin_data' => $cookieData]);

        return redirect()->route('new-form-pre-filled', ['phone' => $checkIn->user->phone]);
    }
}
