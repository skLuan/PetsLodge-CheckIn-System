// Main index file for all managers - provides backward compatibility

// Core functionality
export { CoreDataManager } from './CoreDataManager.js';

// Validation and form handling
export { ValidationManager } from './ValidationManager.js';
export { FormHandler } from './FormHandler.js';

// Domain-specific managers
export { InventoryManager } from './InventoryManager.js';
export { PetManager } from './PetManager.js';

// UI and interaction managers
export { PetPillManager } from './PetPillManager.js';
export { PopupManager } from './PopupManager.js';
export { InventoryFormManager } from './InventoryFormManager.js';
export { HealthFormManager } from './HealthFormManager.js';
export { NavigationManager } from './NavigationManager.js';
export { SubmissionManager } from './SubmissionManager.js';

// Utilities and helpers
export { UtilitiesManager } from './UtilitiesManager.js';

// Reactivity and UI updates are now in the reactivitySystem folder
// Import from there if needed: import { CookieReactivityManager, UIManager } from '../reactivitySystem/index.js';

// Legacy compatibility - re-export main FormDataManager interface
import { FormDataManager } from '../FormDataManager.js';
export { FormDataManager };