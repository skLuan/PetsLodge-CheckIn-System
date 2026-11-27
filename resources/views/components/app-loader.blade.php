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
