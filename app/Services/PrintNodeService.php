<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PrintNodeService
{
    protected $client;
    protected $apiKey;
    protected $printerId;

    public function __construct()
    {
        $this->apiKey = config('services.printnode.api_key');
        $this->printerId = config('services.printnode.printer_id');
        
        $this->client = new Client([
            'base_uri' => 'https://api.printnode.com',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send a print job to PrintNode
     *
     * @param string $pdfUri - URL to the PDF file
     * @param array $info - Additional information about the print job
     * @return array - Response from PrintNode API
     */
    public function sendPrintJob($pdfUri, $info = [])
    {
        try {
            // Add host URL to PDF URI if it's a relative path
            $fullPdfUri = $this->getFullPdfUri($pdfUri);
            
            $response = $this->client->post('/printjobs', [
                'json' => [
                    'printerId' => (int) $this->printerId,
                    'title' => $info['title'] ?? 'Drop-in Print Job',
                    'contentType' => 'pdf_uri',
                    'content' => $fullPdfUri,
                    'source' => 'PetsLodge Drop-in System',
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'message' => 'Print job sent successfully',
                'data' => $responseData,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => 'Error sending print job: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert relative PDF URI to full URL with host
     *
     * @param string $pdfUri - Relative or absolute PDF URI
     * @return string - Full URL with host
     */
    private function getFullPdfUri($pdfUri)
    {
        // If it's already a full URL, return as is
        if (filter_var($pdfUri, FILTER_VALIDATE_URL)) {
            return $pdfUri;
        }

        // If it's a relative path, prepend the application URL
        $appUrl = config('app.url');
        return rtrim($appUrl, '/') . '/' . ltrim($pdfUri, '/');
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
                'message' => 'Error fetching printers: ' . $e->getMessage(),
            ];
        }
    }
}