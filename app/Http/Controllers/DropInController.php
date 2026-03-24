<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use App\Services\CheckInTransformer;
use App\Services\CheckInService;
use App\Services\CheckInUserService;
use App\Services\CheckInPetService;

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


        // Initialize checkinData with user info
        $checkinData = session('checkin_data', []);

        // If no session data, populate from user's latest check-in
        if (empty($checkinData) && $user) {
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
                    'extraServices'
                ]);

                $transformer = new CheckInTransformer();
                $checkinData = $transformer->transformCheckInToCookieFormat($latestCheckIn);

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
                        'emergencyContacts' => $user->emergencyContacts->toArray()
                    ]
                ];
            }
        }

        return view('Drop-in-confirmation', compact('checkinData', 'user'));
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
                'message' => 'User not found'
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
                    'extraServices'
                ]);

                // Transform and store in session
                $transformer = new CheckInTransformer();
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
            'error' => 'Unexpected error'
        ], 500);
    }

    public function readyToPrint(Request $request)
    {
        // Valida inputs (puedes usar Request class personalizada)
        $validated = $request->validate([
            'info' => 'required|array',  // Datos confirmados
        ]);

        try {
            // Extract check-in ID from info if available
            $checkInId = $validated['info']['id'] ?? null;
            $pdfUri = null;

            // Check if document_url already exists for this check-in
            if ($checkInId) {
                $checkIn = \App\Models\CheckIn::find($checkInId);
                if ($checkIn && $checkIn->document_url) {
                    $pdfUri = $checkIn->document_url;
                }
            }

            // Generate PDF only if not already stored
            if (!$pdfUri) {
                $pdfService = new PdfService();
                $pdfUri = $pdfService->generatePdf($validated['info']);  // Devuelve URI (e.g., S3 URL)

                if (!$pdfUri) {
                    return response()->json(['error' => 'Error generating PDF'], 500);
                }

                // Save document_url to database if check-in ID is available
                if ($checkInId) {
                    $checkIn = \App\Models\CheckIn::find($checkInId);
                    if ($checkIn) {
                        $checkIn->update(['document_url' => $pdfUri]);
                    }
                }
            }
            // Llama a PrintNode via service
            $printService = new PrintNodeService();
            $response = $printService->sendPrintJob($pdfUri, $validated['info']);
            // $response = $printService->getPrinters();

            // Respuesta JSON para frontend (éxito/error)
            if ($response['success']) {
                return response()->json([
                    'message' => 'Print job sent successfully!',
                    'pdfUri' => $pdfUri,
                    'printResponse' => $response['data'] ?? null
                ], 200);
            } else {
                return response()->json([
                    'error' => $response['message'] ?? 'Error sending print job',
                    'pdfUri' => $pdfUri
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unexpected error: ' . $e->getMessage()
            ], 500);
        }
    }
}
