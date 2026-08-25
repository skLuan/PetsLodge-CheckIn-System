import SignaturePadLib from 'signature_pad';

/**
 * Client signature capture (Plan 03).
 *
 * Wraps szimek/signature_pad on a <canvas> and posts the drawing to the
 * signatures endpoint as a PNG data URL.
 *
 * Two things this class exists to get right:
 *
 * 1. **devicePixelRatio.** A canvas has a CSS size and a bitmap size. If the
 *    bitmap isn't scaled to the device ratio, strokes land offset from the pen
 *    on every retina/mobile screen — the classic signature_pad bug. `resize()`
 *    keeps them in step, and preserves the drawing across the resize by
 *    round-tripping through `toData()` / `fromData()` (a naive resize handler
 *    clears the canvas, wiping a signature when a phone rotates).
 *
 * 2. **A white background.** signature_pad defaults to a transparent PNG, which
 *    turns into black-on-black once dompdf drops it into the printed summary.
 */
export class SignatureCapture {
    /**
     * @param {Object} options
     * @param {HTMLCanvasElement} options.canvas   The pad surface.
     * @param {string}   options.endpoint          POST target (route('signatures.store')).
     * @param {string}   options.csrfToken
     * @param {number|null} options.checkInId      Check-in this signature belongs to.
     * @param {string}   [options.context]         'drop-in' | 'check-in'.
     * @param {Function} [options.onChange]        Called with (isEmpty) after every stroke/clear.
     */
    constructor({ canvas, endpoint, csrfToken, checkInId, context = 'drop-in', onChange = null }) {
        this.canvas = canvas;
        this.endpoint = endpoint;
        this.csrfToken = csrfToken;
        this.checkInId = checkInId;
        this.context = context;
        this.onChange = onChange;

        this.pad = new SignaturePadLib(canvas, {
            penColor: '#111827',
            // Opaque so the PNG stays readable wherever it is embedded.
            backgroundColor: 'rgb(255, 255, 255)',
            minWidth: 0.8,
            maxWidth: 2.2,
        });

        this.pad.addEventListener('endStroke', () => this._notify());

        this._onResize = () => this.resize();
        window.addEventListener('resize', this._onResize);
        window.addEventListener('orientationchange', this._onResize);

        this.resize();
    }

    /**
     * Match the canvas bitmap to its CSS box at the current device ratio.
     * Existing strokes are re-drawn rather than lost.
     */
    resize() {
        const width = this.canvas.offsetWidth;
        const height = this.canvas.offsetHeight;

        // Laid out at zero size (hidden container) — nothing sensible to do yet.
        if (!width || !height) {
            return;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const strokes = this.pad.toData();

        this.canvas.width = width * ratio;
        this.canvas.height = height * ratio;
        this.canvas.getContext('2d').scale(ratio, ratio);

        // Resizing the bitmap blanks it; clear() also repaints the background.
        this.pad.clear();

        if (strokes && strokes.length) {
            this.pad.fromData(strokes);
        }

        this._notify();
    }

    isEmpty() {
        return this.pad.isEmpty();
    }

    clear() {
        this.pad.clear();
        this._notify();
    }

    /** PNG data URL, exactly what the backend validates and stores. */
    toDataURL() {
        return this.pad.toDataURL('image/png');
    }

    /**
     * Persist the signature.
     *
     * @returns {Promise<{id: number, url: string, signedAt: string}>}
     * @throws {Error} with the server's validation message on failure.
     */
    async save() {
        if (this.isEmpty()) {
            throw new Error('Please sign in the box before continuing.');
        }

        const response = await fetch(this.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify({
                image: this.toDataURL(),
                check_in_id: this.checkInId,
                context: this.context,
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            // Laravel returns {message, errors:{image:[...]}} on a 422.
            const fieldError = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(fieldError || data.message || 'Could not save the signature.');
        }

        return data;
    }

    /** Detach listeners — call if the pad is ever removed from the page. */
    destroy() {
        window.removeEventListener('resize', this._onResize);
        window.removeEventListener('orientationchange', this._onResize);
        this.pad.off();
    }

    _notify() {
        if (typeof this.onChange === 'function') {
            this.onChange(this.isEmpty());
        }
    }
}

window.SignatureCapture = SignatureCapture;

export default SignatureCapture;
