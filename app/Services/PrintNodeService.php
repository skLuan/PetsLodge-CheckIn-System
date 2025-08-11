<?php

namespace App\Services;

use GuzzleHttp\Client;

class PrintNodeService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.printnode.com',
            'headers' => ['Authorization' => 'Basic ' . base64_encode(env('PRINTNODE_API_KEY'))],
        ]);
    }

    public function sendPrintJob($pdfUri, $info)
    {
        $response = $this->client->post('/printJob', [
            'json' => [
                'pdf_uri' => $pdfUri,
                'info' => $info,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }
}