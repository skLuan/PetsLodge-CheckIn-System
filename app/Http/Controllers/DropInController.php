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
        // Load check-in data from session or database
        $checkinData = session('checkin_data', []);

        // If no session data, try to get from user's latest check-in
        if (empty($checkinData) && $request->user()) {
            $latestCheckIn = $request->user()->checkIns()->latest()->first();
            if ($latestCheckIn) {
                $transformer = new CheckInTransformer();
                $checkinData = $transformer->transformCheckInToCookieFormat($latestCheckIn);
            }
        }

        return view('Drop-in-confirmation', compact('checkinData'));
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
            // Genera PDF via service
            $pdfService = new PdfService();
            $pdfUri = $pdfService->generatePdf($validated['info']);  // Devuelve URI (e.g., S3 URL)

            if (!$pdfUri) {
                return response()->json(['error' => 'Error generating PDF'], 500);
            }

            // Llama a PrintNode via service
            $printService = new PrintNodeService();
            $response = $printService->sendPrintJob($pdfUri, $validated['info']);

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
