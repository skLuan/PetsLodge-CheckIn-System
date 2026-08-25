<?php

namespace App\Services;

use App\Models\Signature;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generatePdf(array $data)
    {
        // Instanciar Dompdf
        $dompdf = new Dompdf;

        // Generar HTML para el PDF (puedes usar una vista Blade)
        // The view expects 'checkinData' variable, not 'data'
        $html = view('pdf-for-print', [
            'checkinData' => $data,
            'signature' => $this->signatureFor($data['id'] ?? null),
        ])->render();

        // Cargar HTML en Dompdf
        $dompdf->loadHtml($html);

        // Configurar opciones (e.g., tamaño de papel)
        $dompdf->setPaper('letter', 'portrait');

        // Renderizar PDF
        $dompdf->render();

        // Obtener el contenido del PDF
        $pdfContent = $dompdf->output();

        // Generar nombre único para el archivo
        $fileName = 'pdfs/drop-in-'.time().'.pdf';

        // Almacenar en el disco public
        Storage::disk('public')->put($fileName, $pdfContent);

        // Devolver la URL pública del PDF
        return Storage::url($fileName);
    }

    /**
     * The signature to print on this summary — the most recent drop-in one.
     *
     * dompdf cannot follow the authenticated `signatures.show` route, so the
     * template embeds the bytes inline instead (see Signature::dataUri()).
     */
    private function signatureFor($checkInId): ?Signature
    {
        if (! $checkInId) {
            return null;
        }

        return Signature::where('check_in_id', $checkInId)
            ->forDropIn()
            ->latest('id')
            ->first();
    }
}
