<?php

   namespace App\Services;

   use Dompdf\Dompdf;
   use Illuminate\Support\Facades\Storage;

    class PdfService
    {
        public function generatePdf(array $data)
        {
            // Instanciar Dompdf
            $dompdf = new Dompdf();

            // Generar HTML para el PDF (puedes usar una vista Blade)
            // The view expects 'checkinData' variable, not 'data'
            $html = view('pdf-for-print', ['checkinData' => $data])->render();

            // Cargar HTML en Dompdf
            $dompdf->loadHtml($html);

            // Configurar opciones (e.g., tamaño de papel)
            $dompdf->setPaper('letter', 'portrait');

            // Renderizar PDF
            $dompdf->render();

            // Obtener el contenido del PDF
            $pdfContent = $dompdf->output();

            // Generar nombre único para el archivo
            $fileName = 'pdfs/drop-in-' . time() . '.pdf';

            // Almacenar en el disco public
            Storage::disk('public')->put($fileName, $pdfContent);
            // Devolver la URL pública del PDF
            return Storage::url($fileName);
        }
    }