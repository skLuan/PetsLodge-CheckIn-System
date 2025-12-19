<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use App\Services\CheckInTransformer;

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
        $checkInController = new CheckInController();
        $userExists = $checkInController->checkUser($request)->getData()->userExists;
        if ($userExists === true) {

            // Preparar datos para el frontend/modal
            $data = [
                'client_name' => $dropIn->client_name ?? 'N/A',
                'dog_name' => $dropIn->dog_name ?? 'N/A',
                'breed' => $dropIn->breed ?? 'N/A',
            ];

            return response()->json([
                'status' => 'found',
                'data' => $data,
                'message' => 'Registro encontrado. Redirigiendo en 3...',
                'redirect_url' => route('drop-in.check'), // Asumiendo ruta /check-info
            ], 200);
        } else {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Lo siento, no tenemos registro',
            ], 404);
        }

        // Preparar datos para el frontend/modal
        $data = [
            'client_name' => $dropIn->client_name ?? 'N/A',
            'dog_name' => $dropIn->dog_name ?? 'N/A',
            'breed' => $dropIn->breed ?? 'N/A',
        ];

        return response()->json([
            'status' => 'found',
            'data' => $data,
            'message' => 'Registro encontrado. Redirigiendo en 3...',
            'redirect_url' => route('check-info'), // Asumiendo ruta /check-info
        ], 200);
    }

    public function readyToPrint(Request $request)
    {
        // Valida inputs (puedes usar Request class personalizada)
        $validated = $request->validate([
            'info' => 'required|array',  // Datos confirmados
        ]);

        // Genera PDF via service
        $pdfService = new PdfService();
        $pdfUri = $pdfService->generatePdf($validated['info']);  // Devuelve URI (e.g., S3 URL)

        // Llama a PrintNode via service
        // $printService = new PrintNodeService();
        // $response = $printService->sendPrintJob($pdfUri, $validated['info']);

        // // Respuesta JSON para frontend (éxito/error)
        // if ($response['success']) {
        //     return response()->json(['message' => 'Impresión enviada!'], 200);
        // } else {
        //     return response()->json(['error' => 'Error en impresión'], 500);
        // }
        // Respuesta JSON para frontend (éxito/error)
        if ($pdfUri) {
            return response()->json(['message' => 'PDF Creado! -> ' . $pdfUri], 200);
        } else {
            return response()->json(['error' => 'Error en impresión'], 500);
        }
    }
}
