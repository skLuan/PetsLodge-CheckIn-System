import { CookieManager } from "../CookieManager.js";
import config from "../config.js";
import { EditingModeManager } from "./EditingModeManager.js";

const { FORM_CONFIG, DEFAULT_CHECKIN_STRUCTURE } = config;

/**
 * Core Data Manager - Handles basic checkin data storage and retrieval
 */
class CoreDataManager {
    static CHECKIN_COOKIE_NAME = "pl_checkin_data";
    static sessionData = null;

    /**
      * Creates the initial checkin cookie with complete structure
      * Returns a Promise that resolves when the cookie is successfully created
      */
    static async createInitialCheckin() {
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

    /**
     * Merges session data into the cookie one time only
     * 
     * This method is called during initialization when editing an existing check-in.
     * It merges the pre-populated session data into the cookie structure, but only
     * once. Subsequent updates should use updateCheckinData() directly.
     * 
     * @static
     * @param {Object} sessionData - The session data to merge into the cookie
     * @returns {boolean} True if merge was successful
     * 
     * @example
     * const sessionData = { user: {...}, pets: [...] };
     * CoreDataManager.mergeSessionDataIntoCookie(sessionData);
     * 
     * @sideEffects
     * - Updates cookie with merged data
     * - Triggers cookie reactivity
     */
     static mergeSessionDataIntoCookie(sessionData) {
         try {
             if (!sessionData || Object.keys(sessionData).length === 0) {
                 console.warn("No session data to merge");
                 return false;
             }

             const currentData = this.getCheckinData();
             if (!currentData) {
                 console.error("No current check-in data found to merge into");
                 return false;
             }

             // CRITICAL FIX #4: Preserve editing mode flag during merge
             // Store the editing mode before merge so it's not lost
             const existingEditingMode = currentData.editingMode ? 
                 JSON.parse(JSON.stringify(currentData.editingMode)) : null;

             // Merge session data into current data
             const mergedData = this.deepMerge(currentData, sessionData);
             
             // CRITICAL FIX #4: Restore the editing mode flag after merge
             // This ensures editing mode is not overwritten by session data
             if (existingEditingMode) {
                 mergedData.editingMode = existingEditingMode;
             }

             // Update the cookie with merged data
             const success = this.updateCheckinData(mergedData);
             if (success) {
                 console.log("✅ Session data merged into cookie");
             }
             return success;
         } catch (error) {
             console.error("Error merging session data into cookie:", error);
             return false;
         }
     }

    /**
     * Sets editing mode for the current check-in
     * 
     * Delegates to EditingModeManager to enable editing mode with the given
     * check-in ID and original data snapshot.
     * 
     * @static
     * @param {number} checkInId - The ID of the check-in being edited
     * @param {Object} originalData - Snapshot of the original check-in data
     * @returns {boolean} True if editing mode was successfully enabled
     * 
     * @example
     * CoreDataManager.setEditingMode(123, originalCheckInData);
     * 
     * @see EditingModeManager.enableEditingMode
     */
    static setEditingMode(checkInId, originalData) {
        return EditingModeManager.enableEditingMode(checkInId, originalData);
    }

    /**
     * Gets the current editing mode state
     * 
     * Delegates to EditingModeManager to retrieve the editing mode object.
     * 
     * @static
     * @returns {Object|null} The editing mode object if editing, null otherwise
     * 
     * @example
     * const editingMode = CoreDataManager.getEditingMode();
     * if (editingMode && editingMode.enabled) {
     *     console.log('Editing check-in:', editingMode.checkInId);
     * }
     * 
     * @see EditingModeManager.isEditingMode
     */
    static getEditingMode() {
        try {
            const checkinData = this.getCheckinData();
            return checkinData?.editingMode || null;
        } catch (error) {
            console.error("Error getting editing mode:", error);
            return null;
        }
    }

    /**
     * Clears editing mode
     * 
     * Delegates to EditingModeManager to disable editing mode and clear
     * the original data snapshot.
     * 
     * @static
     * @returns {boolean} True if editing mode was successfully cleared
     * 
     * @example
     * CoreDataManager.clearEditingMode();
     * 
     * @see EditingModeManager.disableEditingMode
     */
    static clearEditingMode() {
        return EditingModeManager.disableEditingMode();
    }
}

export { CoreDataManager };