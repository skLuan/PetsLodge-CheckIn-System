import { CookieManager } from "../CookieManager.js";
import config from "../config.js";

const { FORM_CONFIG, DEFAULT_CHECKIN_STRUCTURE } = config;

/**
 * Core Data Manager - Handles basic checkin data storage and retrieval
 */
class CoreDataManager {
    static CHECKIN_COOKIE_NAME = "pl_checkin_data";

    /**
     * Crea el cookie checkin inicial con la estructura completa
     */
    static createInitialCheckin() {
        const initialCheckin = {
            ...DEFAULT_CHECKIN_STRUCTURE,
            date: new Date().toISOString(),
            id: this.generateCheckinId(),
        };

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
    static updateCheckinData(updates) {
        const currentData = this.getCheckinData();
        if (!currentData) {
            console.warn("No checkin data found, creating initial data");
            this.createInitialCheckin();
            return this.updateCheckinData(updates);
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