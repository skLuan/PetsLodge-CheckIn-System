<?php

namespace App\Http\Controllers;

use App\Events\PetDroppedIn;
use App\Events\PetDroppedOut;
use App\Models\CheckIn;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * Update check-in status to DROPPED_IN with dropIn date
     */
    public function dropped_in(Request $request, $id)
    {
        $checkIn = CheckIn::findOrFail($id);
        $checkedOutStatus = Status::where('name', 'DROPPED_IN')->first();

        $checkIn->update([
            'status_id' => $checkedOutStatus->id,
            'check_out' => now(),
        ]);

        $this->announce(fn () => PetDroppedIn::dispatch($checkIn), 'drop-in', $checkIn->id);

        return redirect()->route('pet-staff.dashboard')
            ->with('success', 'Pet Dropped In successfully!');
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

        $this->announce(fn () => PetDroppedOut::dispatch($checkIn), 'drop-out', $checkIn->id);

        return redirect()->route('pet-staff.dashboard')
            ->with('success', 'Pet checked out successfully!');
    }

    /**
     * Dispatch a client-notification event without ever letting it break the
     * staff action that triggered it.
     *
     * A pet really was dropped in / picked up; a failing mail server must not
     * turn that into an error page for the front desk.
     */
    private function announce(callable $dispatch, string $context, int $checkInId): void
    {
        try {
            $dispatch();
        } catch (\Throwable $e) {
            Log::warning("PetStaffDashboardController: {$context} notification failed to dispatch", [
                'check_in_id' => $checkInId,
                'error' => $e->getMessage(),
            ]);
        }
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

    /**
     * Re-print a check-in document using the stored document_url
     */
    public function reprint(Request $request, $id)
    {
        $checkIn = CheckIn::findOrFail($id);

        // Check if document_url exists
        if (! $checkIn->document_url) {
            return redirect()->route('pet-staff.dashboard')
                ->with('error', 'No document URL found for this check-in.');
        }

        try {
            // Send to PrintNode using stored document_url
            $printService = new \App\Services\PrintNodeService;
            $response = $printService->sendPrintJob($checkIn->document_url, [
                'title' => 'Re-Print: '.$checkIn->pet->name.' - '.$checkIn->user->name,
            ]);

            if ($response['success']) {
                return redirect()->route('pet-staff.dashboard')
                    ->with('success', 'Document re-printed successfully!');
            } else {
                return redirect()->route('pet-staff.dashboard')
                    ->with('error', 'Error re-printing document: '.($response['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            return redirect()->route('pet-staff.dashboard')
                ->with('error', 'Unexpected error: '.$e->getMessage());
        }
    }
}
