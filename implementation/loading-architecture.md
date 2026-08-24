# Implementation — Loading Architecture (FOUC Prevention)

**Status:** Implemented (2026-08-18)
**Date:** 2026-08-18
**Scope:** Frontend only — Blade layouts + one new Blade component.
**Goal:** Prevent the Flash-of-Unstyled-Content (FOUC) that currently makes the page
"pop" from raw HTML into styled HTML on first load.

> This file is the implementation spec for the loading architecture. It has been
> **implemented** — the "File changes" section lists the edits that were applied.

---

## 1. Purpose

When a visitor opens `/check-in` (or any page using the app/guest layouts), the browser
renders the full Blade HTML immediately, but the compiled Tailwind stylesheet
(`resources/css/app.css`) arrives *later*. During that gap the page shows unstyled
markup, then visually "snaps" into its styled form. This looks unprofessional and is
especially visible on slow or cold-cache loads.

The goal is a **loading architecture** that covers the page with an instantly-rendered,
on-brand splash loader until the styles (and page resources) are ready, then fades it out
smoothly — so the user never sees the unstyled intermediate state.

---

## 2. Problem statement (root cause)

Both layouts load styles through Vite:

```blade
{{-- resources/views/layouts/app.blade.php and guest.blade.php --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Two load paths produce the same symptom:

1. **Development (`npm run dev`).** The Vite dev server injects CSS through JavaScript
   (the Vite client), not via a `<link rel="stylesheet">`. The first paint happens before
   that JS runs, so the page is always unstyled for a moment.
2. **Production (`npm run build`).** Vite emits a hashed `<link rel="stylesheet">` in the
   `<head>`. This is render-blocking *in theory*, but on a cold or slow connection the
   HTML body can still paint before the stylesheet finishes downloading/parsing.

Aggravating factors already present in the layouts:

- A render-blocking font stylesheet from `fonts.bunny.net` in `<head>`.
- A `@import` of the Nunito webfont at the top of `resources/css/app.css`
  (`@import url('https://fonts.googleapis.com/css2?family=Nunito…')`), which adds a
  second network hop before the CSS finishes.
- The existing `[x-cloak] { display: none !important; }` rule only hides **Alpine-bound**
  elements and only takes effect *after* `app.css` loads — it cannot prevent the initial
  flash.

Because the entire check-in flow (per `AGENTS.md` §6 and the cookie architecture) relies
on the page being styled and initialized before the user interacts, the splash loader must
be completely independent of `app.css` and of the Vite bundle.

---

## 3. Design / approach

A **branded, full-screen splash loader** that renders from **inline critical CSS**, so it
paints on the very first frame regardless of network state or Vite mode.

Key principles:

1. **Zero dependency.** The loader's own markup, CSS, and hide-script are inlined into the
   HTML. It does **not** live in `app.css` (that's the resource that's slow to load) and
   does **not** wait for the Vite/JS bundle.
2. **Single source of truth.** One Blade component (`<x-app-loader />`) is shared by both
   layouts, so there is one place to change styling, copy, or timing.
3. **Graceful, un-stickable fade-out.** The loader hides on `window.load` (all
   stylesheets, scripts, and images finished) but is guarded by a **maximum-wait fallback
   timer** so a slow image can never trap the user behind a spinner.
4. **No polling, no race with init.** It uses plain DOM events and does not touch the
   cookie form-data flow or the race-condition-sensitive init sequence
   (`form-processor.js` → `FormDataManager.initialize()`). It is purely presentational.
5. **No-JS fallback.** A `<noscript>` style hides the loader for users with JS disabled so
   the page is still usable.

### Visual treatment

- Background: brand `green-lightest` (`#D7E2D1`) so it matches the app's own canvas color
  and there is no jarring color switch when the loader fades.
- Centered logo (`public/images/logo-pets-lodge.png`) + a small CSS spinner + "Loading…"
  text in brand `green-dark` (`#4D723C`).
- Text uses the system font stack (not Nunito/Figtree) so it doesn't depend on webfonts.

---

## 4. Component spec (reference implementation)

Create `resources/views/components/app-loader.blade.php`. This is the complete reference
content to use when implementing.

```blade
{{-- resources/views/components/app-loader.blade.php --}}
{{--
    Full-screen splash loader to prevent FOUC (Flash of Unstyled Content).
    Rendered inline so it paints on the first frame, before app.css / Vite load.
    Shared by layouts/app.blade.php and layouts/guest.blade.php.
--}}

{{-- Critical inline styles: self-contained, no dependency on app.css. --}}
<style>
    #app-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        background: #D7E2D1; /* brand green-lightest */
        color: #4D723C;      /* brand green-dark */
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }

    #app-loader .app-loader__logo {
        width: 12rem;
        max-width: 60vw;
        height: auto;
    }

    #app-loader .app-loader__spinner {
        width: 2.5rem;
        height: 2.5rem;
        border: 0.25rem solid rgba(126, 175, 103, 0.3); /* brand green @30% */
        border-top-color: #7EAF67; /* brand green */
        border-radius: 50%;
        animation: app-loader-spin 0.8s linear infinite;
    }

    #app-loader .app-loader__text {
        font-size: 1rem;
        letter-spacing: 0.02em;
    }

    @keyframes app-loader-spin {
        to { transform: rotate(360deg); }
    }

    /* Fade-out state added by the inline script below. */
    #app-loader.app-loader--hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
</style>

<div id="app-loader" role="status" aria-live="polite" aria-label="Loading">
    <img class="app-loader__logo"
         src="{{ asset('images/logo-pets-lodge.png') }}"
         alt="PetsLodge logo" />
    <div class="app-loader__spinner" aria-hidden="true"></div>
    <span class="app-loader__text">Loading…</span>
</div>

{{-- Hide it once the page is ready, with a hard fallback so it can never stick. --}}
<script>
    (function () {
        var FALLBACK_MS = 2000; // absolute maximum the splash may stay visible
        var REMOVE_MS   = 600;  // buffer after fade-out before removing the node

        function hideAppLoader() {
            var el = document.getElementById('app-loader');
            if (!el || el.dataset.hidden === 'true') {
                return;
            }
            el.dataset.hidden = 'true';

            el.classList.add('app-loader--hidden');
            el.addEventListener('transitionend', function () {
                el.remove();
            }, { once: true });

            // Safety net in case the transitionend event never fires.
            setTimeout(function () {
                if (el.parentNode) {
                    el.remove();
                }
            }, REMOVE_MS);
        }

        // Primary trigger: everything (CSS + JS + images + fonts) has loaded.
        if (document.readyState === 'complete') {
            hideAppLoader();
        } else {
            window.addEventListener('load', hideAppLoader);
        }

        // Hard fallback: never trap the user behind the splash, even on a slow image.
        setTimeout(hideAppLoader, FALLBACK_MS);
    })();
</script>

{{-- Users without JS should never be blocked. --}}
<noscript>
    <style>#app-loader { display: none !important; }</style>
</noscript>
```

> The `logo-pets-lodge.png` image is small and already referenced on `/check-in`
> (`checkIn.blade.php`), so reusing it adds no meaningful extra load and keeps branding
> consistent.

---

## 5. Integration points

### 5.1 `resources/views/layouts/app.blade.php`

Insert the component as the **first child of `<body>`**, before the page-heading header
and main content:

```blade
<body class="antialiased min-h-screen">
    <x-app-loader />

    {{-- @include('layouts.navigation') --}}

    <!-- Page Heading -->
    ...
```

Covers: `/check-in` (`checkIn.blade.php`), `/new-form` and `/new-form-pre-filled`
(`Process.blade.php` via `<x-app-layout>`), `view-check-in.blade.php`, the staff/admin
dashboards, and the profile pages.

### 5.2 `resources/views/layouts/guest.blade.php`

Same placement, first child of `<body>`:

```blade
<body class="font-sans text-gray-900 antialiased">
    <x-app-loader />
    <div class="min-h-screen flex ...">
```

Covers the Breeze auth pages (login, register, verify-email, password reset/confirm).

### 5.3 Intentionally excluded

- `resources/views/pdf-for-print.blade.php` — a dompdf template, not rendered as an
  interactive page; a splash loader is meaningless there.
- No change to `resources/css/app.css`, `resources/js/app.js`, the cookie managers, or the
  reactivity system — the loader is fully self-contained.

---

## 6. File changes (proposed)

| Action | File | Notes |
|--------|------|-------|
| **Add** | `resources/views/components/app-loader.blade.php` | Loader markup + inline CSS + inline hide script (§4). |
| **Edit** | `resources/views/layouts/app.blade.php` | Add `<x-app-loader />` as first child of `<body>`. |
| **Edit** | `resources/views/layouts/guest.blade.php` | Add `<x-app-loader />` as first child of `<body>`. |

No backend, migration, CSS, or JS-manager changes are required.

---

## 7. Tradeoffs & gotchas

### 7.1 `window.load` vs. `DOMContentLoaded`

`window.load` waits for images as well as stylesheets/scripts. The `/check-in` page has a
large decorative background (`public/images/dog-bg@2x.jpg`) and `Process.blade.php` renders
a lot of markup, so hiding purely on `load` could keep the splash up too long on a slow
connection. This is why the component uses a **maximum-wait fallback timer**
(`FALLBACK_MS = 2000`). Result: the splash hides on the earlier of "page fully loaded" and
"2 seconds elapsed".

Alternatives considered:

- **`DOMContentLoaded` + small delay** — hides sooner, but may still flash if the
  stylesheet hasn't applied yet (defeats the purpose).
- **`document.styleSheets` polling** — more precise (hides exactly when CSS is ready) but
  reintroduces a polling pattern the project deliberately avoids for the cookie flow, and
  is more code than the simple `load` + fallback.
- **Alpine `x-init`** — couples the loader to Alpine loading, which is itself part of the
  bundle we're waiting on; a plain inline script is simpler and has no dependency.

### 7.2 Large background images

See §7.1. The fallback timer is the safety valve. If the team later lazy-loads
`dog-bg@2x.jpg` (`loading="lazy"`), the `load` trigger becomes more responsive on its own.

### 7.3 Never block real users

- `pointer-events: none` + `visibility: hidden` on the hidden state means the overlay can't
  intercept clicks even mid-fade.
- `transitionend` listener + a `REMOVE_MS` buffer both remove the node; whichever fires
  first wins (the second is a no-op guard).
- `<noscript>` guarantees JS-disabled users get the page immediately.
- The `data-hidden` guard makes `hideAppLoader()` idempotent (safe if both `load` and the
  timer fire).

### 7.4 Interaction with existing `[x-cloak]`

The existing `[x-cloak] { display: none !important; }` rule remains useful for Alpine
components *after* load. The splash loader is complementary: it covers the pre-CSS gap that
`[x-cloak]` cannot address. No change to that rule.

### 7.5 No interference with the cookie init sequence

The loader is presentational and self-contained; it does not read/write `pl_checkin_data`
and does not reorder the async init in `form-processor.js` /
`FormDataManager.initialize()`. Per `AGENTS.md` §6/§7, do **not** tie the loader's hide
logic to that sequence.

### 7.6 Flash of loader on cached visits

On a fully cached visit, the page may be styled nearly instantly, so the splash could
appear for a single frame. Mitigation: the fade-out already starts on `load`/fallback; if
this is ever noticeable, `FALLBACK_MS` can be reduced (e.g., 800ms) or a `sessionStorage`
flag can skip the loader on repeat visits within a session. Not required for v1.

---

## 8. Verification

There is **no automated JS test suite** (per `AGENTS.md` §4), so verify manually in the
browser and check the console.

1. Build assets: `npm run build` (production path) — or use `npm run dev` (dev path).
2. Open the app (`http://localhost:8080` → redirects to `/check-in`).
3. In DevTools → Network, set throttling to **Slow 3G** and disable cache, then reload.
   - Expected: the branded splash appears immediately (logo + spinner), the unstyled page
     is **never** visible behind it, then the splash fades out smoothly.
4. Confirm the loader never sticks: with throttling on, it should still disappear by
   ~2s (the fallback timer), even if `dog-bg@2x.jpg` is still downloading.
5. Test the other layouts/pages: `/login` (guest layout), `/new-form` (Process),
   staff dashboard, admin monitoring dashboard.
6. Open the browser console and confirm no errors from the inline script (e.g., missing
   element, `transitionend` issues).
7. Sanity-check accessibility: the loader exposes `role="status"`/`aria-live="polite"` and
   `aria-label`, and `aria-hidden` on the decorative spinner.

---

## 9. Rollback

The feature is additive and isolated; rollback is trivial:

1. Remove the `<x-app-loader />` line from both layouts (`app.blade.php`,
   `guest.blade.php`).
2. Delete `resources/views/components/app-loader.blade.php`.

No other file is touched, so the app returns to its previous behavior immediately.

---

## 10. Future enhancements (out of scope for v1)

- **Skeleton screens** for the multi-step form instead of a full-screen splash (larger
  effort; a branded splash is the pragmatic first step).
- **Skip-on-repeat-visit** via `sessionStorage` to avoid even a one-frame flash on cached
  visits (§7.6).
- **Preconnect/`media` hints** for `fonts.bunny.net` and the Nunito `@import` to reduce the
  underlying latency that causes FOUC in the first place.
