<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PdfService;
use App\Services\PrintNodeService;

class DropInController extends Controller
{
    //

    public function show(Request $request)
    {
        // Carga datos prellenados (e.g., de sesión o BD)
        $data = $request->user()->dropInData ?? [];  // Asumiendo modelo User con datos
        return view('drop-in', compact('data'));
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
    }
}
