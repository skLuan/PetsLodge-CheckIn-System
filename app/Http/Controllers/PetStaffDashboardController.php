<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Status;
use Illuminate\Http\Request;

class PetStaffDashboardController extends Controller
{
    /**
     * Display the pet staff dashboard with checked-in and dropped-in pets
     */
    public function index()
    {
        $checkedInStatus = Status::where('name', 'CHECKED_IN')->first();
        $droppedInStatus = Status::where('name', 'DROPPED_IN')->first();

        $checkedInPets = CheckIn::where('status_id', $checkedInStatus->id)
            ->with(['pet', 'pet.kindOfPet', 'user'])
            ->orderBy('check_in', 'desc')
            ->get();

        $droppedInPets = CheckIn::where('status_id', $droppedInStatus->id)
            ->with(['pet', 'pet.kindOfPet', 'user'])
            ->orderBy('check_in', 'desc')
            ->get();

        return view('pet-staff.dashboard', [
            'checkedInPets' => $checkedInPets,
            'droppedInPets' => $droppedInPets,
        ]);
    }

    /**
     * Update check-in status to CHECKED_OUT with checkout date
     */
    public function checkout(Request $request, $id)
    {
        $checkIn = CheckIn::findOrFail($id);
        $checkedOutStatus = Status::where('name', 'CHECKED_OUT')->first();

        $checkIn->update([
            'status_id' => $checkedOutStatus->id,
            'check_out' => now(),
        ]);

        return redirect()->route('pet-staff.dashboard')
            ->with('success', 'Pet checked out successfully!');
    }

    /**
     * Cancel a check-in by updating status to CANCELLED
     */
    public function cancel(Request $request, $id)
    {
        $checkIn = CheckIn::findOrFail($id);
        $cancelledStatus = Status::where('name', 'CANCELLED')->first();

        $checkIn->update([
            'status_id' => $cancelledStatus->id,
        ]);

        return redirect()->route('pet-staff.dashboard')
            ->with('success', 'Check-in cancelled successfully!');
    }
}
