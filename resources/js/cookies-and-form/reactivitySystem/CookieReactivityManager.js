import { CoreDataManager } from "../managers/CoreDataManager.js";

/**
 * Cookie Reactivity Manager - Handles cookie change detection and listener management
 *
 * This manager provides a reactive system for monitoring cookie changes using
 * MutationObserver. It allows other components to register listeners that are
 * automatically notified when check-in data changes.
 *
 * Key Features:
 * - MutationObserver-based change detection
 * - Listener registration/removal
 * - Deep comparison of cookie data
 * - Automatic cleanup on stop
 *
 * @class
 * @static
 *
 * @example
 * // Start listening for changes
 * CookieReactivityManager.startListening();
 *
 * // Register a listener
 * CookieReactivityManager.addListener((cookieData) => {
 *     console.log('Cookie changed:', cookieData);
 * });
 *
 * // Stop listening
 * CookieReactivityManager.stopListening();
 */
class CookieReactivityManager {
    /**
     * Set of registered listeners
     * @static
     * @type {Set<Function>}
     * @private
     */
    static listeners = new Set();

    /**
     * Whether the reactivity system is currently listening
     * @static
     * @type {boolean}
     * @private
     */
    static isListening = false;

    /**
     * Last known cookie value for comparison
     * @static
     * @type {Object|null}
     * @private
     */
    static lastCookieValue = null;

    /**
     * MutationObserver instance
     * @static
     * @type {MutationObserver|null}
     * @private
     */
    static observer = null;

    /**
     * Dummy element used for MutationObserver
     * @static
     * @type {HTMLElement|null}
     * @private
     */
    static cookieObserver = null;

    /**
     * Start listening for cookie changes
     *
     * Initializes the MutationObserver system and begins monitoring
     * for cookie data changes. Only starts if not already listening.
     *
     * @static
     * @returns {void}
     *
     * @sideEffects
     * - Creates DOM element for observation
     * - Registers MutationObserver
     * - Logs initialization status
     */
    static startListening() {
        if (this.isListening) return;

        this.isListening = true;
        this.lastCookieValue = CoreDataManager.getCheckinData();

        // Use MutationObserver to detect programmatic cookie changes
        this.setupMutationObserver();

        console.log("🍪 Cookie reactivity listening started (MutationObserver only)");
    }

    /**
     * Setup MutationObserver for cookie changes
     *
     * Creates a hidden DOM element and MutationObserver to detect
     * when cookie data changes programmatically.
     *
     * @static
     * @private
     * @returns {void}
     *
     * @sideEffects
     * - Creates and appends DOM element
     * - Registers MutationObserver
     */
    static setupMutationObserver() {
        // Create a dummy element to observe cookie changes
        const cookieObserver = document.createElement('div');
        cookieObserver.id = 'cookie-observer';
        cookieObserver.style.display = 'none';
        document.body.appendChild(cookieObserver);

        const observer = new MutationObserver(() => {
            this.checkForCookieChanges();
        });

        observer.observe(cookieObserver, {
            attributes: true,
            attributeFilter: ['data-cookie-check']
        });

        // Store observer reference for cleanup
        this.observer = observer;
        this.cookieObserver = cookieObserver;
    }

    /**
     * Check if cookie has changed and notify listeners
     *
     * Compares current cookie data with last known value and notifies
     * all registered listeners if changes are detected.
     *
     * @static
     * @private
     * @returns {void}
     *
     * @sideEffects
     * - Updates lastCookieValue
     * - Calls all registered listeners
     * - Logs change detection
     */
    static checkForCookieChanges() {
        const currentValue = CoreDataManager.getCheckinData();

        if (this.hasCookieChanged(currentValue)) {
            console.log("🍪 Cookie change detected, notifying listeners");
            console.log("Previous cookie size:", this.lastCookieValue ? JSON.stringify(this.lastCookieValue).length : 0);
            console.log("New cookie size:", currentValue ? JSON.stringify(currentValue).length : 0);
            this.lastCookieValue = currentValue;
            this.notifyListeners(currentValue);
        }
    }

    /**
     * Check if cookie value has actually changed
     *
     * Performs deep comparison of JSON objects to detect meaningful changes.
     * Handles null values and JSON parsing errors gracefully.
     *
     * @static
     * @private
     * @param {Object|null} newValue - New cookie data to compare
     * @returns {boolean} True if cookie data has changed
     */
    static hasCookieChanged(newValue) {
        if (this.lastCookieValue === null && newValue === null) return false;
        if (this.lastCookieValue === null || newValue === null) return true;

        // Deep compare JSON objects
        try {
            const lastParsed = JSON.parse(JSON.stringify(this.lastCookieValue));
            const newParsed = JSON.parse(JSON.stringify(newValue));
            return JSON.stringify(lastParsed) !== JSON.stringify(newParsed);
        } catch {
            return JSON.stringify(this.lastCookieValue) !== JSON.stringify(newValue);
        }
    }

    /**
     * Add a listener for cookie changes
     *
     * Registers a callback function that will be called whenever
     * cookie data changes are detected.
     *
     * @static
     * @param {Function} callback - Function to call on cookie changes
     * @param {Object} callback.cookieData - Updated cookie data
     * @returns {void}
     *
     * @example
     * CookieReactivityManager.addListener((data) => {
     *     console.log('Cookie updated:', data);
     * });
     */
    static addListener(callback) {
        this.listeners.add(callback);
    }

    /**
     * Remove a listener for cookie changes
     *
     * Unregisters a previously added callback function.
     *
     * @static
     * @param {Function} callback - Previously registered callback function
     * @returns {void}
     */
    static removeListener(callback) {
        this.listeners.delete(callback);
    }

    /**
     * Notify all listeners of cookie change
     *
     * Calls all registered listeners with the updated cookie data.
     * Handles errors in individual listeners gracefully.
     *
     * @static
     * @private
     * @param {Object} cookieData - Updated cookie data
     * @returns {void}
     *
     * @sideEffects
     * - Calls all registered listener functions
     * - Logs notification process
     */
    static notifyListeners(cookieData) {
        console.log("🍪 Cookie changed, notifying listeners:", cookieData);
        this.listeners.forEach(callback => {
            try {
                callback(cookieData);
            } catch (error) {
                console.error("Error in cookie change listener:", error);
            }
        });
    }

    /**
     * Stop listening for changes
     *
     * Cleans up the MutationObserver system and clears all listeners.
     * Safe to call multiple times.
     *
     * @static
     * @returns {void}
     *
     * @sideEffects
     * - Disconnects MutationObserver
     * - Removes DOM element
     * - Clears listeners set
     * - Logs cleanup status
     */
    static stopListening() {
        if (!this.isListening) return;

        this.isListening = false;

        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.cookieObserver) {
            this.cookieObserver.remove();
        }

        this.listeners.clear();
        console.log("🍪 Cookie reactivity listening stopped");
    }

    /**
     * Manually trigger cookie check
     *
     * Forces an immediate check for cookie changes, useful after
     * programmatic updates that might not trigger MutationObserver.
     *
     * @static
     * @returns {void}
     *
     * @sideEffects
     * - May trigger listener notifications
     * - Updates DOM element attribute
     */
    static triggerCheck() {
        // Update the observer element to trigger MutationObserver
        if (this.cookieObserver) {
            this.cookieObserver.setAttribute('data-cookie-check', Date.now());
        }
        // Also do immediate check
        this.checkForCookieChanges();
    }
}

export { CookieReactivityManager };