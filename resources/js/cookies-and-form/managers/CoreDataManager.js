import { CookieManager } from "../CookieManager.js";
import config from "../config.js";

const { FORM_CONFIG, DEFAULT_CHECKIN_STRUCTURE } = config;

/**
 * Core Data Manager - Handles basic checkin data storage and retrieval
 */
class CoreDataManager {
    static CHECKIN_COOKIE_NAME = "pl_checkin_data";
    static sessionData = null;
    static sessionDataPromise = Promise.resolve(); // Promise that resolves when session data is set

    /**
     * Sets session data to be merged during initialization
     * Called from form-processor.js with data from the DOM data attribute
     * Also stores in browser sessionStorage for persistence across page navigation
     * Resolves the sessionDataPromise to signal that data is ready
     */
    static setSessionData(data) {
        this.sessionData = data;
        // Store in browser sessionStorage for persistence
        if (data) {
            try {
                sessionStorage.setItem('pl_session_checkin_data', JSON.stringify(data));
                console.log('Session data set for pre-population and stored in sessionStorage:', data);
            } catch (e) {
                console.warn('Failed to store session data in sessionStorage:', e);
            }
        }
        // Signal that session data has been processed
        this.sessionDataPromise = Promise.resolve();
    }

    /**
      * Creates the initial checkin cookie with complete structure
      * Waits for session data to be available before creating the cookie
      * Returns a Promise that resolves when the cookie is successfully created
      */
    static async createInitialCheckin() {
        // Wait for session data to be set (if any)
        await this.sessionDataPromise;

        let initialCheckin = {
            ...DEFAULT_CHECKIN_STRUCTURE,
            date: new Date().toISOString(),
            id: this.generateCheckinId(),
        };

        // Check for session data to pre-populate (for editing existing check-ins)
        if (this.sessionData) {
            console.log('Pre-populating form with session data for editing:', this.sessionData);
            // Merge session data into the initial checkin structure
            initialCheckin = this.deepMerge(initialCheckin, this.sessionData);
        }

        const success = CookieManager.setCookie(
            this.CHECKIN_COOKIE_NAME,
            initialCheckin,
            FORM_CONFIG.DEFAULT_COOKIE_DAYS
        );

        if (success) {
            console.log("Initial checkin data created:", initialCheckin);
        } else {
            console.error("Failed to create initial checkin data");
        }

        return success;
    }

    /**
     * Obtiene los datos completos del checkin desde la cookie
     */
    static getCheckinData() {
        return CookieManager.getCookie(this.CHECKIN_COOKIE_NAME);
    }

    /**
     * Actualiza los datos del checkin en la cookie
     */
    static updateCheckinData(updates, _isRetry = false) {
        let currentData = this.getCheckinData();
        
        if (!currentData) {
            if (_isRetry) {
                // Prevent infinite loop - if we've already tried once, log error and return false
                console.error("Failed to create initial checkin data after retry. Cookie may not be persisting.");
                return false;
            }
            
            console.warn("No checkin data found, creating initial data");
            const created = this.createInitialCheckin();
            
            if (!created) {
                console.error("Failed to create initial checkin data");
                return false;
            }
            
            // Retry once with the flag set to prevent infinite recursion
            return this.updateCheckinData(updates, true);
        }

        const updatedData = this.deepMerge(currentData, updates);
        updatedData.lastUpdated = new Date().toISOString();

        // Check cookie size before setting to prevent data loss
        if (!CookieManager.canSetCookie(this.CHECKIN_COOKIE_NAME, updatedData)) {
            console.error("Cookie data too large, cannot save. Size limit exceeded.");
            return false;
        }

        const success = CookieManager.setCookie(
            this.CHECKIN_COOKIE_NAME,
            updatedData,
            FORM_CONFIG.DEFAULT_COOKIE_DAYS,
            {
                secure: window.location.protocol === 'https:',
                sameSite: 'Lax',
                obfuscate: false // Set to true for sensitive data in production
            }
        );

        if (success) {
            console.log("Checkin data updated successfully");
            console.log("Cookie size:", JSON.stringify(updatedData).length, "characters");
        } else {
            console.error("Failed to update checkin data - cookie could not be set");
            console.error("Data size attempted:", JSON.stringify(updatedData).length, "characters");
            console.error("Cookie limit: 4096 characters");
            // Don't show alert to user, just log for debugging
        }

        return success;
    }

    /**
     * Limpia todos los datos del checkin
     */
    static clearCheckinData() {
        CookieManager.deleteCookie(this.CHECKIN_COOKIE_NAME);
        console.log("🗑️ Checkin data cleared");
    }

    /**
     * Genera un ID único para el checkin
     */
    static generateCheckinId() {
        return (
            "checkin_" +
            Date.now() +
            "_" +
            Math.random().toString(36).substr(2, 9)
        );
    }

    /**
     * Deep merge utility
     */
    static deepMerge(target, source) {
        const result = { ...target };

        for (const key in source) {
            if (source.hasOwnProperty(key)) {
                if (
                    source[key] &&
                    typeof source[key] === "object" &&
                    !Array.isArray(source[key])
                ) {
                    result[key] = this.deepMerge(
                        result[key] || {},
                        source[key]
                    );
                } else {
                    result[key] = source[key];
                }
            }
        }

        return result;
    }
}

export { CoreDataManager };