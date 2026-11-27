<?php

namespace App\Http\Controllers;

use App\Models\TermsAndConditions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Terms & Conditions editor for pet staff, plus the public read endpoint.
 *
 * Editing routes live under `pet-staff/*` (see routes/web.php) and are entered from
 * the pet-staff dashboard. Saving never overwrites text: it publishes a new version.
 */
class TermsAndConditionsController extends Controller
{
    /**
     * HTML tags staff are allowed to use in the legal text.
     * Everything else is stripped before storage.
     */
    private const ALLOWED_TAGS = '<p><br><hr><strong><b><em><i><u><small>'
        .'<ul><ol><li><h1><h2><h3><h4><h5><h6>'
        .'<a><span><div><blockquote>'
        .'<table><thead><tbody><tr><th><td>';

    /** Editor form, pre-filled with the active version. */
    public function edit(): View
    {
        return view('pet-staff.terms-edit', [
            'terms' => TermsAndConditions::active()->with('updatedBy')->first(),
        ]);
    }

    /** Validate, sanitize, and publish the submitted text as the next version. */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            // ~200KB ceiling: the current text is ~10KB, so this is generous but
            // still stops someone pasting a whole site into a longText column.
            'content' => ['required', 'string', 'max:204800'],
        ]);

        $terms = TermsAndConditions::publishNewVersion(
            $this->sanitize($validated['content']),
            $request->user(),
            $validated['title'] ?? null,
        );

        return redirect()
            ->route('pet-staff.terms.edit')
            ->with('success', "Terms & Conditions saved as version {$terms->version}.");
    }

    /** Public JSON read of the active version (used by the check-in form). */
    public function show(): JsonResponse
    {
        $terms = TermsAndConditions::active()->first();

        if (! $terms) {
            return response()->json([
                'message' => 'No active Terms & Conditions version.',
            ], 404);
        }

        return response()->json([
            'title' => $terms->title,
            'content' => $terms->content,
            'version' => $terms->version,
        ]);
    }

    /**
     * Allowlist-sanitize staff-submitted HTML.
     *
     * Staff are trusted, so this is defence in depth rather than a full HTML purifier:
     * script/style blocks are dropped whole (content included), disallowed tags are
     * stripped, and event handlers / javascript: URLs are removed from what remains.
     */
    private function sanitize(string $html): string
    {
        // Drop executable blocks together with their contents — strip_tags alone would
        // leave the inner JavaScript behind as plain text.
        $html = preg_replace('#<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? '';
        $html = preg_replace('#<\s*(script|style|iframe|object|embed)\b[^>]*/?>#is', '', $html) ?? '';

        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Inline event handlers: onclick="...", onerror='...', onload=...
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';

        // javascript: / data: URLs in href/src.
        $html = preg_replace('#\s(href|src)\s*=\s*("|\')?\s*(javascript|data|vbscript):[^"\'>]*("|\')?#i', '', $html) ?? '';

        return trim($html);
    }
}
