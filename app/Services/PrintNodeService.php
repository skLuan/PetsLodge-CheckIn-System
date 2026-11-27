<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PrintNodeService
{
    /**
     * Largest document we will upload.
     *
     * PrintNode documents a 50MB cap on the request body; base64 inflates by
     * ~33%, putting the document ceiling "just under 37.5MB". A check-in summary
     * is ~36KB even with an embedded signature — roughly 0.1% of this — so the
     * check is a sanity guard against something pathological, not a limit we
     * expect to meet.
     *
     * @see https://www.printnode.com/en/docs/api/curl
     */
    public const MAX_DOCUMENT_BYTES = 37_500_000;

    protected $client;

    protected $apiKey;

    protected $printerId;

    /**
     * @param  Client|null  $client  Injectable so tests can supply a mock handler
     *                               instead of reaching api.printnode.com.
     */
    public function __construct(?Client $client = null)
    {
        $this->apiKey = config('services.printnode.api_key');
        $this->printerId = config('services.printnode.printer_id');

        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.printnode.com',
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($this->apiKey.':'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send a print job to PrintNode by UPLOADING the document.
     *
     * Plan 07 switched this from `pdf_uri` to `pdf_base64`. The old mode made
     * PrintNode's cloud servers fetch the PDF from a public URL, which forced
     * the printed summary (owner PII, health notes, the client's signature) onto
     * a publicly readable disk and made printing impossible from localhost.
     *
     * @param  string  $pdfContents  Raw PDF bytes — NOT a path and NOT a URL.
     * @param  array  $info  Optional job metadata (`title`).
     * @return array{success: bool, message: string, data?: mixed, error?: string}
     */
    public function sendPrintJob($pdfContents, $info = [])
    {
        if (! is_string($pdfContents) || $pdfContents === '') {
            return [
                'success' => false,
                'message' => 'Error sending print job: no document contents were supplied.',
            ];
        }

        if (strlen($pdfContents) > self::MAX_DOCUMENT_BYTES) {
            return [
                'success' => false,
                'message' => 'Error sending print job: the document is too large to upload ('
                    .round(strlen($pdfContents) / 1_048_576, 1).'MB).',
            ];
        }

        try {
            $response = $this->client->post('/printjobs', [
                'json' => [
                    'printerId' => (int) $this->printerId,
                    'title' => $info['title'] ?? 'Drop-in Print Job',
                    'contentType' => 'pdf_base64',
                    // Raw base64 — PrintNode does NOT want a `data:` URI prefix.
                    'content' => base64_encode($pdfContents),
                    'source' => 'PetsLodge Drop-in System',
                ],
            ]);

            // A successful POST /printjobs answers 201 with the BARE job id
            // (e.g. `623`), not a JSON object — so this is an int, not an array.
            $responseData = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'message' => 'Print job sent successfully',
                'data' => $responseData,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => 'Error sending print job: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available printers
     *
     * @return array - List of available printers
     */
    public function getPrinters()
    {
        try {
            $response = $this->client->get('/printers');
            $responseData = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'message' => 'Printers fetched successfully',
                'data' => $responseData,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching printers: '.$e->getMessage(),
            ];
        }
    }
}
