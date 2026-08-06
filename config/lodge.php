<?php

/**
 * Business details shown to clients in transactional emails.
 *
 * These are intentionally empty by default — the email footer only renders the
 * lines that are actually filled in, so nothing fake ever reaches a client.
 * Set them in `.env` (see docs/DEPLOYMENT_GUIDE.md).
 */
return [
    'name' => env('LODGE_NAME', 'Pet Lodge & Spa'),

    'address' => env('LODGE_ADDRESS', ''),

    'phone' => env('LODGE_PHONE', ''),

    'email' => env('LODGE_EMAIL', ''),

    'website' => env('LODGE_WEBSITE', ''),
];
