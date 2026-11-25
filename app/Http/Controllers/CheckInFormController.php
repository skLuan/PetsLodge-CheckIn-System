<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use App\Services\CheckInService;
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
            $user = User::where('phone', $phone)->with('pets.kindOfPet', 'pets.gender', 'pets.castrated')->first();
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
        $user = User::where('phone', $phone)->with('pets.kindOfPet', 'pets.gender', 'pets.castrated')->first();

        if (!$user) {
            return redirect()->route('new-form', ['phone' => $phone])->with('error', 'User not found');
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
        $user = User::where('phone', $phone)->with('emergencyContacts')->first();

        if (!$user) {
            return redirect()->route('CheckIn')->with('error', 'User not found');
        }

        // One row per pet, shown as ONE check-in: the client brought them together.
        $checkIns = CheckIn::openBookingOf($user->id)
                            ->with(CheckIn::SUMMARY_RELATIONS)
                            ->withExists('signatures')
                            ->get();

        // Step 5 copies the booking's inventory onto every pet's row; list it once.
        $bookingItems = $checkIns->flatMap->items
                            ->unique(fn ($item) => $item->name.'|'.$item->description)
                            ->values();

        return view('view-check-in', compact('checkIns', 'user', 'bookingItems'));
    }

     /**
      * Prepare check-in data for editing
      *
      * PHASE 2.2: Enhanced with editing mode flags for new data flow
      *
      * @param Request $request
      * @param int $checkInId
      * @return \Illuminate\Http\RedirectResponse
      */
     public function editCheckIn(Request $request, $checkInId)
     {
         $checkIn = CheckIn::with('user')->findOrFail($checkInId);

         // Edit the whole booking: every pet the client brought opens in the wizard.
         // A closed row (checked out / cancelled) is not part of one — edit it alone.
         $booking = CheckIn::openBookingOf($checkIn->user_id)
             ->with(CheckIn::SUMMARY_RELATIONS)
             ->get();

         if (! $booking->contains('id', $checkIn->id)) {
             $booking = CheckIn::with(CheckIn::SUMMARY_RELATIONS)->whereKey($checkIn->id)->get();
         }

         $cookieData = $this->transformer->transformBookingForWizard($booking);
         // The booking's id; SubmissionManager prunes the pets removed from it.
         $checkInId = $booking->first()->id;

         // Hand the check-in to the wizard for ONE request (the redirect below).
         // form-processor.js copies it into the browser draft, which is the source of
         // truth from then on. A persistent session key was re-sent on every reload
         // and merged over the draft again, wiping feeding added since.
         // Own key, not `checkin_data`: the drop-in flow writes that one too.
         session()->flash('wizard', [
             'prefill' => $cookieData,
             'editing_check_in_id' => $checkInId,
         ]);

         return redirect()->route('new-form-pre-filled', ['phone' => $checkIn->user->phone]);
     }

    /**
     * Delete a check-in and its related records
     *
     * A signed check-in is legal evidence: only pet staff (or above) may delete it.
     *
     * @param Request $request
     * @param int $checkInId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function deleteCheckIn(Request $request, $checkInId)
    {
        $checkIn = CheckIn::findOrFail($checkInId);
        $phone = $checkIn->user->phone;

        if (! $checkIn->canBeDeletedBy($request->user())) {
            return $this->signedCheckInForbidden($request, $phone);
        }

        app(CheckInService::class)->deleteCheckIn($checkIn);

        return redirect()->route('view-check-in', ['phone' => $phone])
            ->with('success', 'Check-in deleted successfully.');
    }

    /**
     * Delete the whole booking the check-in belongs to — every pet's row.
     *
     * All or nothing: if any pet is signed and the user is not pet staff,
     * nothing is deleted.
     *
     * @param Request $request
     * @param int $checkInId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function deleteBooking(Request $request, $checkInId)
    {
        $checkIn = CheckIn::findOrFail($checkInId);
        $phone = $checkIn->user->phone;
        $booking = CheckIn::openBookingOf($checkIn->user_id)->withExists('signatures')->get();

        if ($booking->contains(fn (CheckIn $row) => ! $row->canBeDeletedBy($request->user()))) {
            return $this->signedCheckInForbidden($request, $phone);
        }

        $service = app(CheckInService::class);

        foreach ($booking as $row) {
            $service->deleteCheckIn($row);
        }

        return redirect()->route('view-check-in', ['phone' => $phone])
            ->with('success', 'Check-in deleted successfully.');
    }

    private function signedCheckInForbidden(Request $request, ?string $phone)
    {
        $message = 'This check-in has been signed. Only pet staff can delete it.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('view-check-in', ['phone' => $phone])->with('error', $message);
    }
}
