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

## Acceptance criteria

- [ ] Client can sign on canvas at drop-in (mouse + touch), clear and retry.
- [ ] PNG stored under private storage, row in DB with T&C version captured.
- [ ] Signature retrievable only by authorized users.
- [ ] Tests green including storage fake.
