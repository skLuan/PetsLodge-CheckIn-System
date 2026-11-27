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
     * Stream a stored check-in summary to an authorized staff member.
     *
     * Plan 07 moved printed summaries onto the private disk, so this is now the
     * only way to read one. Previously they sat on the public disk purely so
     * PrintNode could fetch them over HTTP — which meant owner PII, health notes
     * and the client's signature were served to anyone with the URL.
     */
    public function document(CheckIn $checkIn)
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(\App\Services\PdfService::DISK);

        abort_unless($checkIn->document_path && $disk->exists($checkIn->document_path), 404);

        return $disk->response($checkIn->document_path, null, [
            'Content-Type' => 'application/pdf',
            // Personal data — keep it out of shared caches.
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Re-print a check-in document using the stored document_path
     */
    public function reprint(Request $request, $id)
    {
        $checkIn = CheckIn::findOrFail($id);

        if (! $checkIn->document_path) {
            return redirect()->route('pet-staff.dashboard')
                ->with('error', 'No document found for this check-in.');
        }

        try {
            // Plan 07: re-read the stored PDF and upload the bytes. The old flow
            // handed PrintNode `document_url` — an absolute APP_URL-based link
            // that broke as soon as the app changed domain.
            $pdfContents = app(\App\Services\PdfService::class)->pdfBytes($checkIn->document_path);

            if ($pdfContents === null) {
                return redirect()->route('pet-staff.dashboard')
                    ->with('error', 'The stored document for this check-in is missing.');
            }

            // Resolved from the container so PRINTNODE_FAKE covers re-prints too.
            $printService = app(\App\Services\PrintNodeService::class);
            $response = $printService->sendPrintJob($pdfContents, [
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
