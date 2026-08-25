<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * A stand-in for PrintNode, so the drop-in flow can be exercised end to end
 * without a real printer or a real API key.
 *
 * Why this exists: PrintNode fetches the PDF **from their cloud servers**, so a
 * `pdfUri` built from a local `APP_URL` (http://localhost:8080/...) is
 * unreachable by definition. Local development therefore cannot complete a
 * drop-in against the real API, no matter how valid the credentials are.
 *
 * Enabled with `PRINTNODE_FAKE=true`. It is **opt-in and defaults to false** on
 * purpose: auto-enabling whenever the API key is missing would let production
 * report "printed successfully" forever while nothing ever came out of the
 * printer. Every call is logged at warning level so a fake print is never
 * mistaken for a real one in the logs, and the returned payload carries
 * `fake: true` so the browser console shows it too.
 *
 * Extends PrintNodeService (rather than implementing an interface) so existing
 * `PrintNodeService` type hints keep working. The parent constructor is
 * deliberately NOT called — it builds a Guzzle client we must never use.
 */
class FakePrintNodeService extends PrintNodeService
{
    public function __construct()
    {
        // No parent::__construct() — no HTTP client, no credentials needed.
    }

    /**
     * Pretend to print, and record exactly what would have been sent.
     *
     * @return array Same shape as PrintNodeService::sendPrintJob().
     */
    public function sendPrintJob($pdfUri, $info = [])
    {
        $jobId = 'fake-'.now()->format('YmdHis').'-'.random_int(1000, 9999);

        Log::warning('PRINTNODE FAKE MODE — no physical print job was sent.', [
            'jobId' => $jobId,
            'title' => $info['title'] ?? 'Drop-in Print Job',
            'checkInId' => $info['id'] ?? null,
            // The operator can open this URL to see what would have printed.
            'pdfUri' => $pdfUri,
        ]);

        return [
            'success' => true,
            'message' => 'Print job simulated (PRINTNODE_FAKE=true) — nothing was printed.',
            'fake' => true,
            'data' => [
                'id' => $jobId,
                'pdfUri' => $pdfUri,
            ],
        ];
    }

    /** A single obviously-fake printer, so printer pickers still have something to show. */
    public function getPrinters()
    {
        return [
            'success' => true,
            'message' => 'Printers simulated (PRINTNODE_FAKE=true).',
            'fake' => true,
            'data' => [
                [
                    'id' => 0,
                    'name' => 'Fake Printer (PRINTNODE_FAKE=true)',
                    'state' => 'online',
                ],
            ],
        ];
    }
}
