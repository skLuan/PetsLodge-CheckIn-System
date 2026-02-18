/**
 * Reactivity System - Unified interface for reactive UI updates
 *
 * This module provides a comprehensive reactivity system for managing automatic
 * UI updates based on cookie data changes. It includes cookie monitoring,
 * form field updates, summary rendering, and centralized UI orchestration.
 *
 * The system is designed to be modular, maintainable, and performant, with
 * clear separation of concerns between different aspects of reactivity.
 *
 * Key Components:
 * - CookieReactivityManager: Monitors cookie changes and triggers updates
 * - FormUpdater: Handles form field population and updates
 * - SummaryRenderer: Generates detailed check-in summaries/receipts
 * - UIManager: Orchestrates all UI update operations
 *
 * @module reactivitySystem
 */

export { CookieReactivityManager } from "./CookieReactivityManager.js";
export { FormUpdater } from "./FormUpdater.js";
export { SummaryRenderer } from "./SummaryRenderer.js";
export { UIManager } from "./UIManager.js";