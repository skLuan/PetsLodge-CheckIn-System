// Main index file for all managers - provides backward compatibility

// Core functionality
export { CoreDataManager } from './CoreDataManager.js';

// Validation and form handling
export { ValidationManager } from './ValidationManager.js';

// Domain-specific managers
export { InventoryManager } from './InventoryManager.js';
export { PetManager } from './PetManager.js';

// Utilities and helpers
export { UtilitiesManager } from './UtilitiesManager.js';

// Reactivity and UI updates
export { CookieReactivityManager, UIManager } from './ReactivityManager.js';

// Legacy compatibility - re-export main FormDataManager interface
import { FormDataManager } from '../FormDataManager.js';
export { FormDataManager };