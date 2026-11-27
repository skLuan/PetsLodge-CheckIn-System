# Feature: PDF & Printing

## Purpose

Render a check-in summary to **PDF** (dompdf) and dispatch it to a **physical
printer** via the PrintNode REST API. The generated PDF is cached on the check-in
as `document_url` so it can be re-printed without regenerating.

## Data model

- `CheckIn.document_url` — added by migration
  `2026_03_14_170000_add_document_url_to_check_ins`; stores the public URL of the
  generated PDF (e.g. `storage/pdfs/drop-in-<timestamp>.pdf`).
- PrintNode credentials from config: `services.printnode.api_key`,
  `services.printnode.printer_id` (set via env `PRINTNODE_API_KEY`,
  `PRINTNODE_PRINTER_ID`).

## Routes & endpoints

- `POST /api/readyToPrint` → `DropInController@readyToPrint` (generate + send).
- `POST /pet-staff/reprint/{id}` → `PetStaffDashboardController@reprint` (send existing).
- (No dedicated view route — the PDF is served as a file asset via `storage/app/public`.)

## Backend

**`PdfService::generatePdf(array $data)`** (`app/Services/PdfService.php`):
1. Renders `view('pdf-for-print', ['checkinData' => $data])`.
2. Loads into `Dompdf`, `setPaper('letter', 'portrait')`, renders.
3. Writes to `Storage::disk('public')->put('pdfs/drop-in-<time>.pdf', ...)`.
4. Returns `Storage::url(...)` (requires `storage:link`, run by the Docker entrypoint).

**`PrintNodeService`** (`app/Services/PrintNodeService.php`):
- `sendPrintJob($pdfUri, $info)` — POSTs to `https://api.printnode.com/printjobs` with
  Basic auth (api key), `contentType: pdf_uri`, content = full URL (relative paths are
  prefixed with `app.url`), `printerId`, `title`, `source`.
- `getPrinters()` — lists available printers.
- Returns `['success' => bool, 'message' => ..., 'data' => ...]`.

## Frontend

- View: `resources/views/pdf-for-print.blade.php` — the printable summary template
  (receives `checkinData`).
- `storage:link` exposes `public/storage` so PrintNode can fetch the PDF by URL.

## Behavior & flow

1. On first print: `PdfService` renders + stores the PDF → `document_url` saved.
2. `PrintNodeService` sends a `pdf_uri` job to the configured printer.
3. Re-prints skip generation and reuse `document_url`.

> If PrintNode can't reach a relative URL, ensure `APP_URL` is set to a reachable host
> (the service prefixes `app.url` to relative PDF paths).

## Key files

`app/Services/PdfService.php`, `app/Services/PrintNodeService.php`,
`resources/views/pdf-for-print.blade.php`,
`config/services.php` (`printnode`), `app/Http/Controllers/DropInController.php`.
