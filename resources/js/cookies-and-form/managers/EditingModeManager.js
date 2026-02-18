/**
 * @fileoverview EditingModeManager - Manages editing mode state for check-in forms
 *
 * This manager handles the editing mode flag that indicates whether a check-in
 * is being edited (updating existing record) or created new. It tracks the
 * original data snapshot and provides utilities for comparing changes.
 *
 * @module EditingModeManager
 * @requires CoreDataManager
 *
 * @example
 * // Enable editing mode for a check-in
 * EditingModeManager.enableEditingMode(123, originalData);
 *
 * // Check if currently editing
 * if (EditingModeManager.isEditingMode()) {
 *     console.log('Editing check-in:', EditingModeManager.getEditingCheckInId());
 * }
 *
 * // Check if data has changed
 * if (EditingModeManager.hasDataChanged()) {
 *     console.log('Data has been modified');
 * }
 *
 * // Disable editing mode
 * EditingModeManager.disableEditingMode();
 */

import { CoreDataManager } from "./CoreDataManager.js";

/**
 * EditingModeManager - Manages editing mode state and original data tracking
 *
 * @class
 * @static
 */
class EditingModeManager {
    /**
     * Enables editing mode for a specific check-in
     *
     * Sets the editing mode flag in the cookie and stores a snapshot of the
     * original data for comparison purposes. This should be called once when
     * entering edit mode.
     *
     * @static
     * @param {number} checkInId - The ID of the check-in being edited
     * @param {Object} originalData - Snapshot of the original check-in data
     * @returns {boolean} True if editing mode was successfully enabled
     *
     * @example
     * const checkInData = { user: {...}, pets: [...] };
     * EditingModeManager.enableEditingMode(123, checkInData);
     *
     * @sideEffects
     * - Updates cookie with editing mode flag
     * - Stores original data snapshot
     * - Triggers cookie reactivity
     */
    static enableEditingMode(checkInId, originalData) {
        try {
            const checkinData = CoreDataManager.getCheckinData();
            if (!checkinData) {
                console.error("No check-in data found to enable editing mode");
                return false;
            }

            const updates = {
                editingMode: {
                    enabled: true,
                    checkInId: checkInId,
                    originalData: JSON.parse(JSON.stringify(originalData)), // Deep copy
                },
            };

            const success = CoreDataManager.updateCheckinData(updates);
            if (success) {
                console.log(`✏️ Editing mode enabled for check-in ${checkInId}`);
            }
            return success;
        } catch (error) {
            console.error("Error enabling editing mode:", error);
            return false;
        }
    }

    /**
     * Disables editing mode
     *
     * Clears the editing mode flag and removes the original data snapshot.
     * This should be called after form submission or when canceling edit mode.
     *
     * @static
     * @returns {boolean} True if editing mode was successfully disabled
     *
     * @example
     * EditingModeManager.disableEditingMode();
     *
     * @sideEffects
     * - Updates cookie to clear editing mode flag
     * - Removes original data snapshot
     * - Triggers cookie reactivity
     */
    static disableEditingMode() {
        try {
            const checkinData = CoreDataManager.getCheckinData();
            if (!checkinData) {
                console.warn("No check-in data found to disable editing mode");
                return false;
            }

            const updates = {
                editingMode: {
                    enabled: false,
                    checkInId: null,
                    originalData: null,
                },
            };

            const success = CoreDataManager.updateCheckinData(updates);
            if (success) {
                console.log("✏️ Editing mode disabled");
            }
            return success;
        } catch (error) {
            console.error("Error disabling editing mode:", error);
            return false;
        }
    }

    /**
     * Checks if currently in editing mode
     *
     * @static
     * @returns {boolean} True if editing mode is enabled, false otherwise
     *
     * @example
     * if (EditingModeManager.isEditingMode()) {
     *     console.log('Currently editing a check-in');
     * }
     */
    static isEditingMode() {
        try {
            const checkinData = CoreDataManager.getCheckinData();
            return checkinData?.editingMode?.enabled === true;
        } catch (error) {
            console.error("Error checking editing mode:", error);
            return false;
        }
    }

    /**
     * Gets the ID of the check-in being edited
     *
     * @static
     * @returns {number|null} The check-in ID if editing, null otherwise
     *
     * @example
     * const checkInId = EditingModeManager.getEditingCheckInId();
     * if (checkInId) {
     *     console.log(`Editing check-in ${checkInId}`);
     * }
     */
    static getEditingCheckInId() {
        try {
            const checkinData = CoreDataManager.getCheckinData();
            return checkinData?.editingMode?.checkInId || null;
        } catch (error) {
            console.error("Error getting editing check-in ID:", error);
            return null;
        }
    }

    /**
     * Gets the original data snapshot
     *
     * Returns a deep copy of the original data that was stored when
     * editing mode was enabled. Useful for comparing current data
     * with original values.
     *
     * @static
     * @returns {Object|null} Deep copy of original data if editing, null otherwise
     *
     * @example
     * const originalData = EditingModeManager.getOriginalData();
     * if (originalData) {
     *     console.log('Original pet name:', originalData.pets[0].info.petName);
     * }
     */
    static getOriginalData() {
        try {
            const checkinData = CoreDataManager.getCheckinData();
            const originalData = checkinData?.editingMode?.originalData;
            if (originalData) {
                // Return deep copy to prevent accidental modifications
                return JSON.parse(JSON.stringify(originalData));
            }
            return null;
        } catch (error) {
            console.error("Error getting original data:", error);
            return null;
        }
    }

    /**
     * Checks if data has changed since editing mode was enabled
     *
     * Performs a deep comparison between current check-in data and the
     * original data snapshot. Returns true if any differences are found.
     *
     * @static
     * @returns {boolean} True if data has changed, false if unchanged or not editing
     *
     * @example
     * if (EditingModeManager.hasDataChanged()) {
     *     console.log('User has made changes to the check-in');
     * }
     */
    static hasDataChanged() {
        try {
            if (!this.isEditingMode()) {
                return false;
            }

            const currentData = CoreDataManager.getCheckinData();
            const originalData = this.getOriginalData();

            if (!originalData) {
                console.warn("No original data available for comparison");
                return false;
            }

            // Deep comparison of JSON strings
            const currentJson = JSON.stringify(currentData);
            const originalJson = JSON.stringify(originalData);

            return currentJson !== originalJson;
        } catch (error) {
            console.error("Error checking if data has changed:", error);
            return false;
        }
    }

    /**
     * Gets a summary of what has changed
     *
     * Compares current data with original data and returns a summary
     * of which sections have been modified.
     *
     * @static
     * @returns {Object} Object with boolean flags for each section that changed
     *
     * @example
     * const changes = EditingModeManager.getChangeSummary();
     * if (changes.userInfo) {
     *     console.log('User information was modified');
     * }
     * if (changes.pets) {
     *     console.log('Pet information was modified');
     * }
     */
    static getChangeSummary() {
        try {
            if (!this.isEditingMode()) {
                return null;
            }

            const currentData = CoreDataManager.getCheckinData();
            const originalData = this.getOriginalData();

            if (!originalData) {
                return null;
            }

            return {
                userInfo:
                    JSON.stringify(currentData.user) !==
                    JSON.stringify(originalData.user),
                pets:
                    JSON.stringify(currentData.pets) !==
                    JSON.stringify(originalData.pets),
                grooming:
                    JSON.stringify(currentData.grooming) !==
                    JSON.stringify(originalData.grooming),
                inventory:
                    JSON.stringify(currentData.inventory) !==
                    JSON.stringify(originalData.inventory),
                groomingDetails:
                    currentData.groomingDetails !== originalData.groomingDetails,
            };
        } catch (error) {
            console.error("Error getting change summary:", error);
            return null;
        }
    }

    /**
     * Resets data to original values
     *
     * Reverts all changes and restores the original data snapshot.
     * Useful for "Cancel" or "Revert" functionality.
     *
     * @static
     * @returns {boolean} True if data was successfully reset
     *
     * @example
     * if (confirm('Discard all changes?')) {
     *     EditingModeManager.resetToOriginal();
     * }
     *
     * @sideEffects
     * - Updates cookie with original data
     * - Triggers cookie reactivity
     * - UI will update to show original values
     */
    static resetToOriginal() {
        try {
            if (!this.isEditingMode()) {
                console.warn("Not in editing mode, cannot reset");
                return false;
            }

            const originalData = this.getOriginalData();
            if (!originalData) {
                console.error("No original data available to reset");
                return false;
            }

            // Preserve editing mode flag while resetting data
            const resetData = JSON.parse(JSON.stringify(originalData));
            resetData.editingMode = {
                enabled: true,
                checkInId: this.getEditingCheckInId(),
                originalData: JSON.parse(JSON.stringify(originalData)),
            };

            const success = CoreDataManager.updateCheckinData(resetData);
            if (success) {
                console.log("✏️ Data reset to original values");
            }
            return success;
        } catch (error) {
            console.error("Error resetting to original data:", error);
            return false;
        }
    }
}

export { EditingModeManager };
