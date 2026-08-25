# Plan 07 — Print by upload, not by URL (`pdf_base64`)

**Phase 7 · Branch: `feature/07-printnode-base64-printing`**

> Spun out of **Step 7 of `plans/03-signature-module.md`**, which unblocked local
> printing with `PRINTNODE_FAKE=true` but left the production problem open. This plan
> closes it. Read that Step 7 first — it has the discovery context.

> 🔨 **The app is NOT live yet.** Confirmed with the user 2026-08-25: there is no
> production data to protect, so this plan is deliberately **destructive** — it goes
> straight to the final shape. No backfill, no legacy fallbacks, no phased rollout, no
> compatibility shims. Existing dev PDFs and `document_url` values are thrown away.
> **If that changes and real check-ins exist before this ships, stop and re-plan** —
> the migration below destroys data.

## Problem

`PrintNodeService::sendPrintJob()` sends `contentType: pdf_uri`. That hands PrintNode a
**URL**, and PrintNode's cloud servers fetch the PDF themselves. Three consequences,
one of them a privacy problem:

1. **The PDF must be publicly reachable on the internet.** So `PdfService` writes to the
   `public` disk and the file is served, unauthenticated, from `/storage/pdfs/*.pdf`.
   That document contains the owner's **name, phone, address**, the pet's **health
   notes and warnings**, and — since Plan 03 — the **embedded signature image**. Every
   other piece of signature handling in this codebase treats that data as private
   (`storage/app/signatures`, authenticated route only). The printed summary is the
   hole in that policy.
2. **Local printing is impossible.** `http://localhost:8080/...` is unreachable from
   PrintNode's servers, so no local drop-in can ever complete against the real API
   regardless of credentials. `PRINTNODE_FAKE=true` is the current workaround.
3. **Production needs three things true at once** — public https `APP_URL`, a resolving
   `public/storage` symlink, and publicly-served PDFs. Each is a separate way for
   printing to break, and PrintNode reports all of them as an opaque fetch failure.

`contentType: pdf_base64` uploads the bytes in the request body instead. It removes all
three at once and closes the privacy hole.

### Bug this also kills

`check_ins.document_url` stores an **absolute URL built from `APP_URL`**:

```
http://localhost:8080/storage/pdfs/drop-in-1787641808.pdf
```

`PetStaffDashboardController::reprint` feeds that straight back to PrintNode, so every
Re-Print row would break the moment the app changed domain. The column is replaced
outright below.

## Verified PrintNode API facts

From <https://www.printnode.com/en/docs/api/curl> (checked 2026-08-25). **These are
confirmed, not assumptions** — an earlier draft of this plan told the implementer to go
and look them up; that step is now done.

`POST /printjobs`, HTTP Basic auth with the API key as the username (already how
`PrintNodeService` builds its client).

| Field | Type | Notes |
|---|---|---|
| `printerId` | int | required |
| `contentType` | string | required — one of `pdf_uri`, `pdf_base64`, `raw_uri`, `raw_base64` |
| `content` | string | required — for `pdf_base64`, **raw base64 of the document, with NO `data:` URI prefix** |
| `title` | string | optional |
| `source` | string | optional |
| `options` | object | optional |
| `expireAfter` | int | optional, seconds |
| `qty` | int | optional, default 1 |
| `authentication` | object | optional — only relevant to the `*_uri` types, so **irrelevant after this plan** |

**Size limit:** the request body *"must not exceed 50MB"*; because base64 adds ~33%,
*"the maximum document size is just under 37.5MB"*.

> 📌 **Consequence: the size guard the earlier draft called for is near-pointless.**
> Check-in PDFs measure ~36 KB *with* an embedded signature (~1.9 KB without) — about
> **0.1% of the limit**. Implement a single cheap sanity check with the real number
> rather than the elaborate guard previously planned. It exists to produce a clear error
> if something pathological happens, not because it is expected to trigger.

**Response:** HTTP **201**, and the body is the **bare print-job id** (e.g. `623`), *not*
a JSON object. `json_decode('623', true)` yields the int `623`, so the existing
`'data' => $responseData` keeps working — but **`data` is a scalar, not an array**.
Anything reading `$response['data']['id']` would be wrong. Worth a test.

## Current state (verified 2026-08-25 — re-check before coding)

**Only two flows print.** Both go through `PrintNodeService::sendPrintJob()`:

| # | Flow | Entry point | How it gets the PDF |
|---|---|---|---|
| 1 | **Drop-in** | `POST /api/readyToPrint` → `DropInController::readyToPrint` | Reuses `check_ins.document_url` if set, else `PdfService::generatePdf()` then saves the URL back |
| 2 | **Re-print** | `POST /petstaff/reprint/{id}` → `PetStaffDashboardController::reprint` | Reads `check_ins.document_url` |

Supporting facts that shape the design:

- `PdfService::generatePdf()` writes to `Storage::disk('public')` under `pdfs/` and
  returns `Storage::url($fileName)` — an absolute URL.
- **Nothing in the frontend consumes the PDF URL.** `Drop-in-confirmation.blade.php`
  only `console.log`s `data.printResponse`; there is no link, no preview, no download.
- `pet-staff/dashboard.blade.php` uses `document_url` **only as a boolean** to decide
  whether to render the Re-Print button. It is never used as an href.
- `PdfService::generatePdf()` already receives the signature via `signatureFor()` and
  the template inlines it as a data URI — no change needed there.
- `AppServiceProvider::bindPrinter()` swaps in `FakePrintNodeService` when
  `PRINTNODE_FAKE=true`. **Both services must keep the same contract** after this plan.
- Dev data to be destroyed: 26 files in `storage/app/public/pdfs/`, 1 check-in row with
  a `document_url`.

## Target architecture

### 1. `PdfService` returns a path, not a URL

```php
// Writes to the PRIVATE disk; returns a relative path, e.g. pdfs/2026/08/{uuid}.pdf
public function generatePdf(array $data): string
public function pdfBytes(string $path): ?string   // null if the file is gone
```

- Disk becomes `local` (private), matching `Signature::DISK`.
- Path gains `Y/m` sharding and a uuid, mirroring the signatures layout — the current
  `time()`-based filename collides if two drop-ins land in the same second.
- **Never** return or store an absolute URL again.

### 2. Schema: replace `document_url` with `document_path` (destructive)

One migration: **drop `document_url`, add `document_path`** (nullable string). No
backfill — the single dev row is worthless and the app is not live.

`down()` must restore `document_url` so the migration is reversible in structure, even
though the *values* are gone for good. Say so in a comment; a reversible-schema test
should not imply recoverable data.

Update `CheckIn::$fillable` and the dashboard's `@if`.

Also delete the 26 stale public PDFs (`storage/app/public/pdfs/`) — they are
unreferenced after this and sit on a public disk, which is the exact thing this plan
exists to stop.

### 3. `PrintNodeService::sendPrintJob()` takes bytes

```php
public function sendPrintJob(string $pdfContents, array $info = []): array
// 'contentType' => 'pdf_base64',
// 'content'     => base64_encode($pdfContents),   // raw base64, NO data: prefix
```

- **Delete `getFullPdfUri()`** — dead once nothing sends a URL.
- Sanity-check the encoded size against the documented 50MB body limit; return the
  standard `['success' => false, 'message' => ...]` shape rather than throwing.
- **Keep the return shape identical** (`success`, `message`, `data`) — `FakePrintNodeService`
  and both controllers depend on it. Remember `data` is now a scalar job id.
- No back-compat: signature changes from a URL to bytes, and **both** call sites are
  updated in the same commit. There are only two and we own both.

### 4. Both flows read bytes from the private disk

- `readyToPrint`: generate → store `document_path` → `pdfBytes($path)` → print.
- `reprint`: `document_path` → `pdfBytes()` → print. **No legacy fallback** — rows
  without a path simply show no Re-Print button, as they do today.
- A missing file on disk must produce a clean user-facing error, not a 500.

### 5. Authenticated PDF viewing (replaces the public URL)

`GET /petstaff/check-in/{checkIn}/document` → `signatures.show`-style streamed response
(`application/pdf`), behind `auth` + `pet.staff.only`. Needed because staff lose the
(accidental) ability to open the PDF once it leaves the public disk.

Dashboard: point the Re-Print `@if` at `document_path` and add a "View PDF" link.

## Tests FIRST — cover **every** print flow

New file `tests/Feature/PrintingTest.php`, plus additions to the existing suites. The
brief is explicit: **no print path ships untested.**

### Payload shape (the core of this plan)

- [ ] `sendPrintJob()` posts `contentType: pdf_base64` — assert on a mocked Guzzle
      handler, not the network.
- [ ] `content` is valid base64 whose decoded bytes **start with `%PDF-`**.
- [ ] `content` carries **no `data:` prefix** (PrintNode wants raw base64).
- [ ] No request is ever made with a `pdf_uri`/URL-bearing payload.
- [ ] A 201 response whose body is a **bare integer** is handled — `success: true`, and
      nothing tries to index `data` as an array.
- [ ] PrintNode error response → `success: false`, and it never throws out of the service.
- [ ] Over-limit payload → `success: false` with a useful message, **no HTTP call made**.

### Flow 1 — drop-in (`POST /api/readyToPrint`)

- [ ] Signed check-in → 200; the printer receives bytes matching the generated PDF.
- [ ] **The Plan 03 signature gate still holds** — unsigned → 422 `requiresSignature`,
      and no PDF is generated. *(Regression guard: this plan touches the same method.)*
- [ ] Second call reuses the stored `document_path` — `generatePdf` is NOT called twice.
- [ ] `document_path` is persisted on the check-in.
- [ ] Generated PDF still contains the embedded signature (assert the byte-size jump or
      an image XObject, as `SignatureTest` does).
- [ ] PDF file missing from disk → clean error response, not a 500.

### Flow 2 — re-print (`POST /petstaff/reprint/{id}`)

- [ ] Row with `document_path` → prints those bytes; redirect with a success message.
- [ ] Row without one → redirects with the existing "no document" error.
- [ ] File missing on disk → error redirect, not a 500.
- [ ] Guest / non-staff cannot reach the route.

### Privacy (the reason this plan exists)

- [ ] After a full drop-in, `Storage::disk('public')` is **empty** — no PDF ever lands
      on the public disk. *(Mirrors `SignatureTest::a_signature_is_never_written_to_the_public_disk`.)*
- [ ] `document_path` is relative — contains no `http`, no `APP_URL`.
- [ ] The new document route: staff 200 + `application/pdf`; guest redirected.

### Fake printer (`PRINTNODE_FAKE=true`)

- [ ] `FakePrintNodeService::sendPrintJob()` accepts the new bytes argument and still
      returns the same shape (extend `FakePrinterTest`).
- [ ] It logs the byte size rather than a URL (the URL no longer exists).
- [ ] Still refused in production.

### Migration

- [ ] Migration is structurally reversible (`migrate:rollback` then re-migrate).
- [ ] `document_url` is gone; `document_path` exists; `CheckIn` can write it.

## Step-by-step execution order

1. Tests (red) for the payload shape + both flows.
2. Migration: drop `document_url`, add `document_path`; update `CheckIn::$fillable`.
3. `PdfService` → private disk, returns path, adds `pdfBytes()`.
4. `PrintNodeService` → `pdf_base64` + size sanity check; delete `getFullPdfUri()`.
5. Update both call sites.
6. `FakePrintNodeService` + `FakePrinterTest` updated to the new contract.
7. Authenticated document route + dashboard "View PDF" link + `@if` swap.
8. Delete the stale public PDFs.
9. Verify with `PRINTNODE_FAKE=true` end to end, then **once with a real API key**
   (this is the first time a genuine local print becomes possible).
10. Docs (`docs/API_REFERENCE.md`, `AGENTS.md` printing note, `docs/DEPLOYMENT_GUIDE.md`)
    + move plan to `plans/` with Execution notes.

> After this plan, `APP_URL` no longer affects printing at all — but it still matters
> for email `asset()` links (Plan 02). Do not treat it as fixed by this work.

## Acceptance criteria

- [ ] Printing sends `pdf_base64` with raw base64; no PDF URL is ever handed to PrintNode.
- [ ] **No PDF is written to, or served from, public storage.** Owner PII, health notes
      and the embedded signature are unreachable without a staff session.
- [ ] Both print flows (drop-in, re-print) work.
- [ ] Printing works from **localhost** with a real API key — `PRINTNODE_FAKE` is no
      longer required to complete a drop-in locally.
- [ ] The Plan 03 signature gate is intact (regression test green).
- [ ] `document_url` is gone from the schema, the model and the dashboard.
- [ ] Full suite: no new failures against the documented baseline.

## Out of scope

- Changing *what* the printed summary contains — layout is Plan 06.
- `options` / `qty` / `expireAfter` print-job parameters. Available in the API, no
  requirement for them yet.
- Queueing print jobs. Worth considering later (an upload is slower than posting a URL,
  and `readyToPrint` is synchronous), but it changes the UX contract of the print button
  and belongs in its own plan.
