// CookieHandler.js - Manteniendo compatibilidad con la API existente
import { FormDataManager } from './FormDataManager.js';
import { CookieManager } from './CookieManager.js';

// Update CookieManager to use the new cookie name for backward compatibility
CookieManager.PL_CHECKIN_COOKIE = "pl_checkin_data";

/**
 * Wrapper para mantener compatibilidad con la API existente
 * @deprecated Usar FormDataManager y CookieManager directamente
 */
class CookieHandler {
    // Métodos de cookies básicos - delegados a CookieManager
    static setCookie(name, value, days = 1) {
        return CookieManager.setCookie(name, value, days);
    }

    static getCookie(name) {
        return CookieManager.getCookie(name);
    }

    static deleteCookie(name) {
        return CookieManager.deleteCookie(name);
    }

    // Métodos específicos del formulario - delegados a FormDataManager
    static getFormDataFromCookies(step) {
        return FormDataManager.getFormDataFromStep(step);
    }

    static getPetsFromCookies() {
        return FormDataManager.getAllPetsFromCookies();
    }

    static updatePetInCookies(petIndex, data) {
        return FormDataManager.updatePetInCookies(petIndex, data);
    }

    static saveFormDataToCookies(data) {
        return FormDataManager.saveFormDataToStep(data);
    }

    // Nuevos métodos expuestos
    static compileCheckInData() {
        return FormDataManager.compileCheckInData();
    }

    static clearAllFormData() {
        return FormDataManager.clearAllFormData();
    }

    static getFormProgress() {
        return FormDataManager.getFormProgress();
    }

    static validateFormCompletion() {
        return FormDataManager.validateFormCompletion();
    }
}

export default CookieHandler;