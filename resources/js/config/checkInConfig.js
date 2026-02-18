/**
 * Check-In Configuration
 * Stores all route mappings and API endpoints for the Check-In handler
 */

export const checkInConfig = {
    formSelector: '#checkInForm',
    apiEndpoint: '/api/check-user', // API endpoint for checking user (Laravel API route)
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    routes: {
        newForm: '/new-form',
        newFormPreFilled: '/new-form-pre-filled',
        viewCheckIn: '/view-check-in'
    }
};
/**
 * Drop In Configuration
 * Stores all route mappings and API endpoints for the Check-In handler
 */

export const dropInConfig = {
    formSelector: '#drop-in-form',
    apiEndpoint: '/api/check-user', // API endpoint for checking user (Laravel API route)
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    routes: {
        newForm: '/new-form',
        newFormPreFilled: '/new-form-pre-filled',
        viewDropIn: '/drop-in/confirmation'
    }
};

/**
 * Check if the current page is the check-in page
 * @returns {boolean} True if current URL is /check-in
 */
export function isCheckInPage() {
    return window.location.pathname === '/check-in';
}
export function isDropInPage() {
    return window.location.pathname === '/drop-in';
}
