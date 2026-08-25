<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Signature;
use App\Models\TermsAndConditions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Capture and serve client signatures.
 *
 * Both routes sit behind `auth` + `pet.staff.only` in `routes/web.php` — NOT in
 * `routes/api.php`, whose middleware group is stateless (Sanctum's stateful
 * middleware is commented out in `Kernel.php`), so a staff session could never
 * be read there.
 */
class SignatureController extends Controller
{
    /** Largest signature we accept, decoded. A pad drawing is a few tens of KB. */
    private const MAX_BYTES = 1048576; // 1 MB

    /** PNG magic bytes. The declared mime is client-supplied; these are not. */
    private const PNG_SIGNATURE = "\x89PNG\r\n\x1a\n";

    /**
     * POST /signatures — store a signature drawn on the pad.
     *
     * The image arrives as a `data:image/png;base64,...` URL from
     * `signaturePad.toDataURL()`.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'string', $this->pngDataUrlRule()],
            'check_in_id' => ['required', 'integer', 'exists:check_ins,id'],
            'context' => ['nullable', 'string', Rule::in(Signature::CONTEXTS)],
        ]);

        $checkIn = CheckIn::findOrFail($validated['check_in_id']);
        $bytes = $this->decode($validated['image']);

        $path = 'signatures/'.now()->format('Y/m').'/'.Str::uuid().'.png';

        // Private disk: storage/app/signatures/... is not reachable over HTTP.
        Storage::disk(Signature::DISK)->put($path, $bytes);

        $signature = Signature::create([
            // The OWNER signs, not the staff member operating the tablet.
            'user_id' => $checkIn->user_id,
            'check_in_id' => $checkIn->id,
            // Pin the exact T&C text that was on screen (Plan 01's audit trail).
            'terms_and_conditions_id' => TermsAndConditions::active()->first()?->id,
            'path' => $path,
            'context' => $validated['context'] ?? 'drop-in',
        ]);

        return response()->json([
            'id' => $signature->id,
            'url' => $signature->url(),
            'signedAt' => $signature->created_at?->toIso8601String(),
        ], 201);
    }

    /**
     * GET /signatures/{signature} — stream the PNG to an authorized staff member.
     *
     * The only way to read a signature image; there is no public URL.
     */
    public function show(Signature $signature): StreamedResponse
    {
        $disk = Storage::disk(Signature::DISK);

        abort_unless($disk->exists($signature->path), 404);

        return $disk->response($signature->path, null, [
            'Content-Type' => 'image/png',
            // Personal data — keep it out of shared caches.
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    // -----------------------------------------------------------------
    // Payload handling
    // -----------------------------------------------------------------

    /**
     * Validate that `image` really is a PNG data URL we are willing to store.
     *
     * Checked in this order so a hostile payload is rejected before it is ever
     * decoded into memory:
     *   1. the data URL prefix declares `image/png`
     *   2. the encoded length can't possibly exceed the cap
     *   3. the payload is strict base64
     *   4. the DECODED bytes start with the PNG signature (the declared mime is
     *      attacker-controlled; the magic bytes are the real check)
     *   5. the decoded size is within the cap
     */
    private function pngDataUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || ! Str::startsWith($value, 'data:image/png;base64,')) {
                $fail('The signature must be a PNG data URL.');

                return;
            }

            $payload = Str::after($value, 'base64,');

            // base64 inflates by 4/3; anything longer than this cannot decode
            // under the cap, so bail before allocating the decoded string.
            if (strlen($payload) > (int) ceil(self::MAX_BYTES * 4 / 3) + 4) {
                $fail('The signature image is too large.');

                return;
            }

            $bytes = base64_decode($payload, true);

            if ($bytes === false || $bytes === '') {
                $fail('The signature image is not valid base64.');

                return;
            }

            if (! str_starts_with($bytes, self::PNG_SIGNATURE)) {
                $fail('The signature image must be a PNG.');

                return;
            }

            if (strlen($bytes) > self::MAX_BYTES) {
                $fail('The signature image is too large.');
            }
        };
    }

    /** Decode an already-validated data URL. */
    private function decode(string $dataUrl): string
    {
        return base64_decode(Str::after($dataUrl, 'base64,'), true);
    }
}
