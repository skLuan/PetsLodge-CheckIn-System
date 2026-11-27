import { CookieManager } from "./CookieManager.js";

/**
 * StorageManager - Persists check-in form state in localStorage
 *
 * The form state used to live in a cookie, which capped it at 4KB (measured after
 * URL-encoding, so ~2KB of real JSON) and uploaded it on every HTTP request even
 * though the server never reads it. localStorage gives ~5MB and stays client-side.
 *
 * Values are stored in an envelope `{ v, expiresAt, data }` so drafts still expire
 * like the old cookie did. If localStorage is unavailable (very restricted browsers)
 * this falls back to CookieManager, so behaviour is never worse than before.
 */
class StorageManager {
    static ENVELOPE_VERSION = 1;
    static _available = null;

    /**
     * Whether localStorage can be used (cached after the first probe)
     */
    static isAvailable() {
        if (this._available !== null) return this._available;

        try {
            const probe = "__pl_storage_probe__";
            window.localStorage.setItem(probe, probe);
            window.localStorage.removeItem(probe);
            this._available = true;
        } catch {
            this._available = false;
            console.warn("localStorage unavailable, falling back to cookies");
        }

        return this._available;
    }

    /**
     * Stores a value with an expiry in days
     * @returns {boolean} True if the value was stored
     */
    static set(key, value, days = 1) {
        if (!this.isAvailable()) {
            return CookieManager.setCookie(key, value, days);
        }

        try {
            const envelope = {
                v: this.ENVELOPE_VERSION,
                expiresAt: Date.now() + days * 864e5,
                data: value,
            };
            window.localStorage.setItem(key, JSON.stringify(envelope));
            return true;
        } catch (error) {
            console.error(`Error storing ${key} in localStorage:`, error);
            return false;
        }
    }

    /**
     * Reads a value; expired or corrupt entries are removed and return null
     */
    static get(key) {
        if (!this.isAvailable()) {
            return CookieManager.getCookie(key);
        }

        const raw = window.localStorage.getItem(key);
        if (raw === null) return null;

        try {
            const envelope = JSON.parse(raw);
            if (!envelope || typeof envelope !== "object" || !("data" in envelope)) {
                throw new Error("Invalid storage envelope");
            }
            if (envelope.expiresAt && envelope.expiresAt < Date.now()) {
                this.remove(key);
                return null;
            }
            return envelope.data;
        } catch (error) {
            console.warn(`Discarding corrupt ${key} from localStorage:`, error);
            this.remove(key);
            return null;
        }
    }

    /**
     * Removes a value
     */
    static remove(key) {
        if (!this.isAvailable()) {
            return CookieManager.deleteCookie(key);
        }

        try {
            window.localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error(`Error removing ${key} from localStorage:`, error);
            return false;
        }
    }

    /**
     * Whether a (non-expired) value exists
     */
    static has(key) {
        return this.get(key) !== null;
    }

    /**
     * Size in characters of the stored value (for debugging)
     */
    static size(key) {
        if (!this.isAvailable()) {
            return CookieManager.getCookieSize();
        }

        return window.localStorage.getItem(key)?.length || 0;
    }
}

export { StorageManager };
