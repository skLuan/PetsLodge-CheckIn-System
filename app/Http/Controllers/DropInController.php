<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\User;
use App\Services\CheckInTransformer;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DropInController extends Controller
{
    //

    public function show(Request $request)
    {
        // Carga datos prellenados (e.g., de sesión o BD)
        $data = $request->user()->dropInData ?? [];  // Asumiendo modelo User con datos

        return view('drop-in', compact('data'));
    }

    public function showDropConfirmation(Request $request)
    {
        $phone = $request->validate(['phone' => 'required|string|regex:/^[0-9]{10}$/']);
        $user = User::where('phone', $phone['phone'])->first();

        // The pet being dropped in is whatever is CURRENTLY checked in, so the
        // database decides — not `session('checkin_data')`, which can still hold
        // a previous booking from the check-in wizard. Trusting the session here
        // meant the page could render a stale (or absent) check-in id, and the
        // signature pad would then post `check_in_id: ''` and 422 silently.
        $latestCheckIn = $user?->checkIns()->whereNull('check_out')->latest()->first();

        $checkinData = $latestCheckIn ? [] : session('checkin_data', []);

        // DIAGNOSTIC LOG
        \Log::info('DropInController::showDropConfirmation', [
            'phone' => $phone['phone'],
            'user_id' => $user->id ?? null,
            'active_check_in_id' => $latestCheckIn->id ?? 'NONE',
            'fell_back_to_session' => $latestCheckIn === null && ! empty($checkinData),
        ]);

        if (empty($checkinData) && $user) {
            if ($latestCheckIn) {
                // A booking is one row PER PET, so drop-in works on the whole set:
                // the client signs once and gets one summary listing every pet.
                $booking = $latestCheckIn->bookingSiblings()
                    ->with(CheckIn::SUMMARY_RELATIONS)
                    ->get();

                $transformer = new CheckInTransformer;
                $checkinData = $transformer->transformCheckInsToCookieFormat($booking);

                // Store in session for consistency with Process flow
                session(['checkin_data' => $checkinData]);
            } else {
                // If no latest check-in but user exists, populate with user info
                $user->load('emergencyContacts');
                $checkinData = [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'address' => $user->address,
                        'emergencyContacts' => $user->emergencyContacts->toArray(),
                    ],
                ];
            }
        }

        // Drives whether the page offers signing + printing at all. Without an
        // active check-in there is nothing a signature could be attached to, so
        // the page must say so rather than render a pad that cannot save.
        $canSign = $latestCheckIn !== null;

        return view('Drop-in-confirmation', compact('checkinData', 'user', 'canSign'));
    }

    public function checkInfo(Request $request)
    {
        return view('Drop-in.check');
    }

    public function checkUser(Request $request)
    {
        $checkInApiController = app(CheckInApiController::class);
        $response = $checkInApiController->checkUser($request);
        $responseData = $response->getData();

        if ($responseData->userExists === false) {
            return response()->json([
                'userExists' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($responseData->userExists === true && $responseData->hasCheckIn === false) {
            return response()->json([
                'userExists' => true,
                'hasCheckIn' => false,
                'userId' => $responseData->userId,
                'userName' => $responseData->userName,
                'userEmail' => $responseData->userEmail,
                'userAddress' => $responseData->userAddress,
            ], 200);
        }

        if ($responseData->userExists === true && $responseData->hasCheckIn === true) {
            // Get the user and their latest check-in to populate session data
            $user = User::findOrFail($responseData->userId);
            $latestCheckIn = $user->checkIns()->latest()->first();

            if ($latestCheckIn) {
                // Eager load all relationships needed by the transformer
                $latestCheckIn->load([
                    'pet.kindOfPet',
                    'pet.gender',
                    'pet.castrated',
                    'user.emergencyContacts',
                    'foods.momentOfDay',
                    'medicines.momentOfDay',
                    'items',
                    'extraServices',
                ]);

                // Transform and store in session
                $transformer = new CheckInTransformer;
                $checkinData = $transformer->transformCheckInToCookieFormat($latestCheckIn);
                session(['checkin_data' => $checkinData]);
            }

            return response()->json([
                'userExists' => true,
                'hasCheckIn' => true,
                'userId' => $responseData->userId,
                'userName' => $responseData->userName,
                'userEmail' => $responseData->userEmail,
                'userAddress' => $responseData->userAddress,
            ], 200);
        }

        return response()->json([
            'error' => 'Unexpected error',
        ], 500);
    }

    /**
     * The data the printed summary is rendered from.
     *
     * Built SERVER-SIDE from the check-in, not from the browser's `info`
     * payload. The summary is a legal record — it carries the client's
     * signature — so a thin, stale or partial payload must not be able to
     * produce a near-empty document that still looks signed and official.
     *
     * `$fallback` (the posted payload) is used only if the check-in cannot be
     * read, so a transformer problem degrades instead of failing the drop-in.
     */
    private function summaryData(?\App\Models\CheckIn $checkIn, array $fallback): array
    {
        if (! $checkIn) {
            return $fallback;
        }

        try {
            // The whole booking, so a multi-pet drop-in prints ONE summary
            // listing every pet rather than naming only the anchor row's.
            $booking = $checkIn->bookingSiblings()->with(CheckIn::SUMMARY_RELATIONS)->get();

            $data = (new CheckInTransformer)->transformCheckInsToCookieFormat($booking);

            return ! empty($data) ? $data : $fallback;
        } catch (\Throwable $e) {
            \Log::warning('Could not build the check-in summary from the database; falling back to the posted payload.', [
                'checkInId' => $checkIn->id,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    public function readyToPrint(Request $request)
    {
        // Valida inputs (puedes usar Request class personalizada)
        $validated = $request->validate([
            'info' => 'required|array',  // Datos confirmados
        ]);

        // Extract check-in ID from info if available
        $checkInId = $validated['info']['id'] ?? null;

        // Signature gate (Plan 03). A drop-in is a signed agreement, so it can
        // never be completed unsigned — checked here as well as in the UI,
        // because the browser gate alone is trivially bypassed.
        if (! $checkInId || ! \App\Models\Signature::where('check_in_id', $checkInId)->forDropIn()->exists()) {
            return response()->json([
                'error' => 'A signature is required before completing the drop-in.',
                'requiresSignature' => true,
            ], 422);
        }

        try {
            // Resolved from the container (not `new`) so tests can swap them out.
            $pdfService = app(PdfService::class);
            $checkIn = \App\Models\CheckIn::find($checkInId);

            // Generate the summary once and remember where it went; a re-print
            // of the same drop-in must reuse the identical document.
            $documentPath = $checkIn?->document_path;

            if (! $documentPath) {
                $documentPath = $pdfService->generatePdf($this->summaryData($checkIn, $validated['info']));

                // One booking, one document — recorded on EVERY row it covers, so
                // each pet's dashboard entry can re-print and view the same PDF.
                if ($checkIn) {
                    $checkIn->bookingSiblings()->update(['document_path' => $documentPath]);
                    $checkIn->refresh();
                }
            }

            // Plan 07: PrintNode is sent the BYTES, not a URL. Nothing about this
            // flow is publicly reachable any more.
            $pdfContents = $pdfService->pdfBytes($documentPath);

            if ($pdfContents === null) {
                return response()->json([
                    'error' => 'The check-in document could not be found. Please try again.',
                ], 422);
            }

            $printService = app(PrintNodeService::class);
            $response = $printService->sendPrintJob($pdfContents, $validated['info']);

            // Respuesta JSON para frontend (éxito/error)
            if ($response['success']) {
                return response()->json([
                    'message' => 'Print job sent successfully!',
                    // Authenticated staff-only link; there is no public PDF URL.
                    'documentUrl' => $checkIn ? route('pet-staff.check-in.document', $checkIn) : null,
                    'printResponse' => $response['data'] ?? null,
                ], 200);
            } else {
                return response()->json([
                    'error' => $response['message'] ?? 'Error sending print job',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unexpected error: '.$e->getMessage(),
            ], 500);
        }
    }
}
