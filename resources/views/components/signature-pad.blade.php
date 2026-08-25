@props([
    // Check-in this signature is consent for. Without it nothing can be saved.
    'checkInId' => null,
    // 'drop-in' | 'check-in' — must match App\Models\Signature::CONTEXTS.
    'context' => 'drop-in',
    'title' => 'Signature',
    'instructions' => 'Please sign below to confirm you agree to the Terms & Conditions.',
])

{{--
    Signature pad (Plan 03).

    Driven by window.SignatureCapture (resources/js/components/SignaturePad.js).
    The canvas needs `touch-action: none` or a finger drags the page instead of
    drawing, and it needs an explicit CSS height — the JS sizes the bitmap from
    the element's laid-out box.
--}}
<div class="signature-pad" data-signature-pad data-check-in-id="{{ $checkInId }}" data-context="{{ $context }}">
    <h3 class="font-semibold text-green-dark mb-1">✍️ {{ $title }}</h3>
    <p class="text-sm text-gray-600 mb-3">{{ $instructions }}</p>

    <div class="relative rounded-md border-2 border-dashed border-gray-300 bg-white">
        <canvas data-signature-canvas class="block w-full h-40 rounded-md" style="touch-action: none;"></canvas>

        {{-- Hidden as soon as the first stroke lands. --}}
        <span data-signature-placeholder
            class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-gray-400">
            Sign here
        </span>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <button type="button" data-signature-clear
            class="text-sm text-gray-500 underline hover:text-gray-700 disabled:opacity-40" disabled>
            Clear
        </button>
        <span data-signature-status class="text-sm text-gray-500">Not signed yet</span>
    </div>

    <p data-signature-error class="hidden mt-2 text-sm text-red-600"></p>
</div>
