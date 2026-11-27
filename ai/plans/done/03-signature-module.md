# Plan 03 — Signature module for Drop-in ("Feature - Crear firma mode")

**Phase 4 · Branch: `feature/03-signature-module`**

## Problem

Clients must sign (agree to T&C / drop-in) digitally. Nothing exists today. Notion subtasks: install a signature library, migration to store signature reference, save image to project storage, store URL in table.

## Design decision (important deviation from Notion wording)

Notion says "guardar firma en user table". Recommendation: store signatures on **`check_ins`** (or a dedicated `signatures` table), NOT a single column on `users`:
- A signature is per-visit consent, tied to a T&C version (Plan 01), not a permanent user attribute.
- A user column would be overwritten each visit → loses legal audit trail.

Proposed: dedicated table (cleanest, supports future signature points):

```php
Schema::create('signatures', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('check_in_id')->nullable()->constrained();
    $table->foreignId('terms_and_conditions_id')->nullable()->constrained('terms_and_conditions');
    $table->string('path');            // storage path, e.g. signatures/2026/08/xyz.png
    $table->string('context')->default('drop-in'); // 'check-in' | 'drop-in' | ...
    $table->timestamps();
});
```

If the team insists on the simple Notion version: `$table->string('signature_url')->nullable()` on `users` — but raise the audit concern first.

## Library

**`signature_pad`** (szimek/signature_pad, MIT, canvas-based, no framework deps — fits the vanilla JS + Alpine stack).

```bash
npm install signature_pad
```

## Implementation

### 1. Frontend — `resources/js/components/SignaturePad.js`

- Wraps `signature_pad` on a `<canvas>`; handles devicePixelRatio resize (known gotcha: canvas must be re-scaled on resize or strokes offset).
- Buttons: Clear, Accept. On Accept → `signaturePad.toDataURL('image/png')` → POST base64 to API.
- Blade component `resources/views/components/signature-pad.blade.php`, embedded in the drop-in flow (`Drop-in.blade.php` / `Drop-in-confirmation.blade.php` — confirm exact insertion point with design).

### 2. Backend — `SignatureController` (or method on `DropInController`)

`POST /api/signatures` (auth: pet staff session or same guard as drop-in flow):

```php
$request->validate(['image' => 'required|string', 'check_in_id' => 'required|exists:check_ins,id']);
// decode data URL, enforce PNG, max ~1MB decoded
$data = base64_decode(Str::after($request->image, 'base64,'));
$path = 'signatures/'.now()->format('Y/m').'/'.Str::uuid().'.png';
Storage::disk('local')->put($path, $data);   // PRIVATE disk — signatures are PII
Signature::create([...., 'path' => $path, 'terms_and_conditions_id' => TermsAndConditions::active()->first()?->id]);
```

- **Private storage** (`storage/app/signatures`), served only via authenticated route `GET /signatures/{signature}` using `Storage::response()` + policy (owner or staff). Do NOT put in `public/` — signatures are personal data.
- Store `path`; build URL on read (`route('signatures.show', $id)`), don't persist absolute URLs.

### 3. Integration points

- Drop-in confirmation flow: require signature before completing drop-in (frontend gate + backend check).
- Check-in summary / PDF (dompdf already in stack): optionally embed the signature image in the printed summary — stretch goal, note in Execution notes if deferred.

### 4. Tests FIRST — `tests/Feature/SignatureTest.php`

- POST with valid base64 PNG → 201, file exists on fake disk (`Storage::fake('local')`), DB row links user + check_in + active T&C version.
- Invalid payloads (not base64, huge, wrong mime) → 422.
- Signature image route: staff can view, guest cannot.
- Drop-in cannot complete without signature (once gated).

## Step-by-step execution order

1. Confirm with team: signatures table vs users column (default: table).
2. Tests (red) → migration + model.
3. API endpoint + private storage + show route.
4. `npm install signature_pad`, JS component, blade partial, wire into drop-in.
5. Gate drop-in completion on signature.
6. Docs (`docs/API_REFERENCE.md`) + move to `plans/`.
7. **Printing config — local stand-in now, production work still OPEN.** See below.

## Step 7 — Printing: local stand-in + the production configuration problem

Discovered while verifying step 5 end to end: completing a drop-in locally is
**impossible against the real PrintNode API**, for a structural reason rather than a
credential one.

### The problem

`PrintNodeService::sendPrintJob()` sends `contentType: pdf_uri` — it hands PrintNode a
**URL** and PrintNode's cloud servers fetch the PDF themselves. The URL is built from
`config('app.url')`:

```php
// PrintNodeService::getFullPdfUri()
return rtrim(config('app.url'), '/').'/'.ltrim($pdfUri, '/');
```

So the PDF must be reachable **from the public internet**, and three things have to be
true at once in production:

| Requirement | Why it breaks otherwise |
|---|---|
| `APP_URL` is the public **https** URL | `http://localhost:8080/...` is unreachable by PrintNode. This is the same `APP_URL` the email templates need for their `asset()` logo links — one variable, two features. |
| `public/storage` symlink exists **and resolves** | `PdfService` writes to the `public` disk. On shared hosting the symlink is a common casualty of deploy/rsync. |
| PDFs under `storage/app/public/pdfs/` are publicly served | If they 404, PrintNode fails on fetch, not on auth — the error message will not say so. |

> ⚠️ **Privacy note, not yet decided.** Making the PDF publicly fetchable means the
> printed check-in summary — owner name, phone, address, pet health notes, **and now the
> embedded signature image** — sits behind an unguessable-but-public URL. That is at
> odds with the private-disk treatment signatures get everywhere else in this plan.
> Options if this matters: switch PrintNode to `contentType: pdf_base64` (upload the
> bytes instead of a URL, no public exposure at all), or serve PDFs from a signed,
> expiring route. **Recommend `pdf_base64`** — it removes all three requirements above
> in one move. Not done here: out of scope for the signature plan, and it changes how
> printing works for every flow.
>
> ➡️ **Now scoped as its own plan: `AIplans/07-printnode-base64-printing.md`**, which
> takes the `pdf_base64` route and covers both print flows with tests. It also picks up
> a latent bug found while scoping it: `document_url` stores an absolute `APP_URL`-based
> URL, so every existing Re-Print row breaks the moment the app changes domain.

### What was built (local only)

`PRINTNODE_FAKE=true` swaps `PrintNodeService` for `FakePrintNodeService` via a
container binding in `AppServiceProvider::bindPrinter()`. The fake logs the job at
**warning** level and returns the real response shape with `fake: true`, so the whole
drop-in completes locally with no printer and no API key.

It is **opt-in and ignored when `APP_ENV=production`** on purpose: auto-enabling
whenever the API key is missing would let production report "printed successfully"
forever while nothing came out of the printer.

Covers re-prints too — `PetStaffDashboardController::reprint` was changed from
`new PrintNodeService` to `app(PrintNodeService::class)`.

### Also fixed here: the dangling `public/storage` symlink

The first real drop-in died with `Unable to create a directory at
/var/www/public/storage`. The symlink pointed at `/mnt/host/c/DockerWorkspace/...`, a
path from an older Docker mount layout. `docker-entrypoint.sh` had a repair step, but
its guard was `[ ! -L public/storage ]` — **`-L` only asks "is this a symlink?", and a
dangling symlink still is one**, so the repair never ran. Now tested with `-e`/`-d`
(which follow the link) plus an `rm -f`, since `storage:link` cannot overwrite an
existing path.

### ❗ Still open for production

- [ ] Obtain the **PrintNode API key** (the account credentials are held, the key is not).
- [ ] Set `PRINTNODE_PRINTER_ID` to the real printer.
- [ ] Set `APP_URL` to the public https URL and confirm a PDF is fetchable from outside.
- [ ] Confirm `public/storage` resolves on the Hostinger box after deploy.
- [ ] Keep `PRINTNODE_FAKE=false` in `.env.production`.
- [ ] **Decide the privacy question above** before going live with public PDF URLs.

## Acceptance criteria

- [ ] Client can sign on canvas at drop-in (mouse + touch), clear and retry.
- [ ] PNG stored under private storage, row in DB with T&C version captured.
- [ ] Signature retrievable only by authorized users.
- [ ] Tests green including storage fake.


---

## Execution notes (2026-08-24, branch `feature/03-signature-module`)

**Status: complete.** 23 tests green; full suite 85 passed / 4 failed — the same 4
pre-existing failures as the documented baseline (62 + 23 = 85), no new ones.

### Decisions taken

| Question | Decision |
|---|---|
| `signatures` table vs `users.signature_url` | **Table**, as the plan recommended. The audit argument won: a user column is overwritten every visit. |
| Where the routes live | **`routes/web.php`**, not `routes/api.php` — see deviation 1. |
| Who is `user_id` | The **pet owner** (`$checkIn->user_id`), not the authenticated staff member operating the tablet. There is a test asserting exactly this, because it is the easy thing to get wrong. |
| Clear + Accept buttons | Clear kept; **Accept merged into the existing Print button** — see deviation 2. |
| PDF embed (stretch goal) | **Done**, not deferred. |

### Deviations from the plan

1. **`POST /api/signatures` → `POST /signatures` in `web.php`.** The plan's route
   could not have worked: the `api` middleware group in `Kernel.php` is stateless
   (`EnsureFrontendRequestsAreStateful` is commented out), so `pet.staff.only` would
   never see a staff session there. Both signature routes therefore sit in the
   existing `['auth','pet.staff.only']` group in `web.php`.

2. **No separate "Accept" button.** The plan wanted Clear + Accept on the pad. The
   drop-in page already has one terminal action — "Print Check-in" — so a second
   confirm button would have meant two ways to say yes. Instead the print button is
   **disabled until the pad has strokes** and saves the signature as its first step.
   `savedSignatureId` guards against storing a duplicate if the print then fails and
   the user retries.

3. **Authorization is staff-only, not "owner or staff".** The plan suggested a policy
   allowing the owner too, but clients have no login in this app (role `CLIENT` users
   are created by staff and never authenticate) — an owner branch would be dead code.
   `auth` + `pet.staff.only` on both routes.

4. **`readyToPrint` also rejects a request with no `check_in_id` at all**, not just
   one whose check-in is unsigned. Without an id there is nothing a signature could
   be attached to, so accepting it would be a hole in the gate.

### Things worth knowing

- **The declared mime is attacker-controlled.** `data:image/png;base64,` in front of
  JPEG bytes is trivial to send, so validation checks the **decoded PNG magic number**
  (`PNG

`) as well as the prefix, and caps the encoded length *before*
  decoding so a huge payload is rejected without being allocated. Cap: 1 MB decoded.
- **The pad draws on a white background on purpose.** signature_pad defaults to a
  transparent PNG, which dompdf renders as black-on-black in the printed summary.
- **dompdf cannot fetch `signatures.show`** (it is authenticated), so
  `pdf-for-print.blade.php` inlines the bytes via `Signature::dataUri()`.
- **`PdfService` and `PrintNodeService` are now resolved with `app()` instead of
  `new`** in `DropInController::readyToPrint`, so the gate test can mock them instead
  of hitting the real PrintNode API. Behaviour is unchanged.
- **Pint reformats the whole `app/` directory** if you point it at `app/`. It touched
  17 files unrelated to this plan; those were reverted with `git checkout --`.
  Only `DropInController` and `PdfService` keep Pint's incidental reformatting,
  because this plan edited them anyway.

### Not verified

- **The pad was never drawn on in a real browser.** `/drop-in/confirmation` needs a
  staff login, and this session did not create one. A feature test asserts the page
  renders the canvas, the store URL and the correct `data-check-in-id`, and that the
  print button starts as "Sign to Print Check-in" — but *stroke rendering, touch
  input, and the devicePixelRatio scaling were not eyeballed.* Worth 5 minutes on a
  tablet before this ships.
- Nothing committed; changes sit on `feature/03-signature-module`.
- Notion card still needs a human.

### Acceptance criteria

- [x] Client can sign on canvas at drop-in (mouse + touch), clear and retry — *code
      complete; touch not manually exercised, see above.*
- [x] PNG stored under private storage, row in DB with T&C version captured.
- [x] Signature retrievable only by authorized users.
- [x] Tests green including storage fake.
