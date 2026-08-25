<?php

namespace App\Services;

use App\Models\Signature;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfService
{
    /**
     * The PRIVATE disk printed summaries live on.
     *
     * Was `public` until Plan 07. The summary carries the owner's name, phone
     * and address, the pet's health notes and warnings, and the client's
     * signature — it is the same class of data as `Signature::DISK`, and it is
     * only public here because the old print flow made PrintNode fetch it over
     * HTTP. Printing now uploads the bytes, so nothing needs a public URL.
     */
    public const DISK = 'local';

    /**
     * Render a check-in summary to PDF and store it privately.
     *
     * @return string Relative path on the private disk, e.g. `pdfs/2026/08/{uuid}.pdf`.
     *                NEVER a URL — an absolute APP_URL-based value is what broke
     *                re-prints across domains before Plan 07.
     */
    public function generatePdf(array $data): string
    {
        $dompdf = new Dompdf;

        // The view expects 'checkinData', not 'data'.
        $html = view('pdf-for-print', [
            'checkinData' => $data,
            'signature' => $this->signatureFor($data['id'] ?? null),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        // Sharded + uuid, mirroring the signatures layout. The old
        // `drop-in-{time()}.pdf` scheme collided when two drop-ins landed in the
        // same second.
        $path = 'pdfs/'.now()->format('Y/m').'/'.Str::uuid().'.pdf';

        Storage::disk(self::DISK)->put($path, $dompdf->output());

        return $path;
    }

    /**
     * Raw PDF bytes for upload to the printer.
     *
     * @return string|null null when the file is missing, so callers can report a
     *                     clean error instead of throwing.
     */
    public function pdfBytes(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->get($path) : null;
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
