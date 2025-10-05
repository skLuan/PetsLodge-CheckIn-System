import {
    CoreDataManager,
    ValidationManager,
    InventoryManager,
    PetManager,
    UtilitiesManager,
    CookieReactivityManager,
    UIManager
} from "./managers/index.js";

/**
 * Main FormDataManager - Provides unified interface using modular managers
 * Maintains backward compatibility with existing code
 */
class FormDataManager {
    static CHECKIN_COOKIE_NAME = CoreDataManager.CHECKIN_COOKIE_NAME;
    static AUTO_SAVE_INTERVAL = 30000; // 30 segundos
    static autoSaveTimer = null;

    /**
     * Inicializa el sistema de manejo de datos
     * Debe llamarse al inicio de la aplicación
     */
    static initialize() {
        // Crear cookie checkin si no existe
        if (!CoreDataManager.getCheckinData()) {
            CoreDataManager.createInitialCheckin();
        }

        // Iniciar sistema de reactividad de cookies
        CookieReactivityManager.startListening();

        // Registrar listener para actualizar UI automáticamente
        UIManager.registerUIUpdateListener();

        // Trigger initial UI update with existing data
        UIManager.updateUIFromCookieData(CoreDataManager.getCheckinData());

        console.log(
            "FormDataManager initialized with checkin data:",
            CoreDataManager.getCheckinData()
        );
    }

    // Delegate to managers - maintain backward compatibility
    static registerUIUpdateListener() {
        return UIManager.registerUIUpdateListener();
    }

    static updateUIFromCookieData(cookieData) {
        return UIManager.updateUIFromCookieData(cookieData);
    }

    // Delegate UI update methods to UIManager
    static updateOwnerInfoForm(userInfo) {
        return UIManager.updateOwnerInfoForm(userInfo);
    }

    static updatePetPillsAndForms(pets) {
        return UIManager.updatePetPillsAndForms(pets);
    }

    static updatePetForm(petData) {
        return UIManager.updatePetForm(petData);
    }

    static updateFeedingMedicationUI(pets) {
        return UIManager.updateFeedingMedicationUI(pets);
    }

    static updateHealthInfoUI(pets, grooming, groomingDetails) {
        return UIManager.updateHealthInfoUI(pets, grooming, groomingDetails);
    }

    static updateGroomingAndInventoryUI(grooming, inventory, details) {
        return UIManager.updateGroomingAndInventoryUI(grooming, inventory, details);
    }

    static getCurrentSelectedPetIndex() {
        return UtilitiesManager.getCurrentSelectedPetIndex();
    }

    // Delegate data management methods to CoreDataManager
    static createInitialCheckin() {
        return CoreDataManager.createInitialCheckin();
    }

    static getCheckinData() {
        return CoreDataManager.getCheckinData();
    }

    static updateCheckinData(updates) {
        return CoreDataManager.updateCheckinData(updates);
    }

    static clearCheckinData() {
        return CoreDataManager.clearCheckinData();
    }

    // Delegate validation methods to ValidationManager
    static handleFormStep(step, formData, selectedPetIndex = null) {
        return ValidationManager.handleFormStep(step, formData, selectedPetIndex);
    }

    static updateUserInfo(userData) {
        return ValidationManager.updateUserInfo(userData);
    }

    static showTermsPopup() {
        return ValidationManager.showTermsPopup();
    }

    static hideTermsPopup() {
        return ValidationManager.hideTermsPopup();
    }

    static setTermsAccepted(accepted) {
        return ValidationManager.setTermsAccepted(accepted);
    }

    // Delegate pet management methods to PetManager
    static addPetToCheckin(petData) {
        return PetManager.addPetToCheckin(petData);
    }

    static updatePetInCheckin(petIndex, petData) {
        return PetManager.updatePetInCheckin(petIndex, petData);
    }

    static removePetFromCheckin(petIndex) {
        return PetManager.removePetFromCheckin(petIndex);
    }

    static addPetFeedingOrMedication(petIndex, type, data) {
        return PetManager.addPetFeedingOrMedication(petIndex, type, data);
    }

    static updatePetHealth(petIndex, healthData) {
        return PetManager.updatePetHealth(petIndex, healthData);
    }

    static updatePetHealthInfo(petIndex, healthData) {
        return PetManager.updatePetHealthInfo(petIndex, healthData);
    }

    static getAllPetsFromCheckin() {
        return PetManager.getAllPetsFromCheckin();
    }

    // Delegate inventory management methods to InventoryManager
    static addInventoryItem(itemText) {
        return InventoryManager.addInventoryItem(itemText);
    }

    static removeInventoryItem(itemIndex) {
        return InventoryManager.removeInventoryItem(itemIndex);
    }

    static updateInventoryItem(itemIndex, newText) {
        return InventoryManager.updateInventoryItem(itemIndex, newText);
    }

    static setInventoryComplete(complete) {
        return InventoryManager.setInventoryComplete(complete);
    }

    static updateInventoryUI(inventory, inventoryComplete) {
        return InventoryManager.updateInventoryUI(inventory, inventoryComplete);
    }

    static updateGroomingAndInventory(groomingData, inventoryData, groomingDetails = "") {
        return ValidationManager.updateGroomingAndInventory(groomingData, inventoryData, groomingDetails);
    }

    // Delegate utility methods to UtilitiesManager
    static debugCheckinData() {
        return UtilitiesManager.debugCheckinData();
    }

    // Legacy compatibility methods
    static getFormDataFromStep(step) {
        console.warn("getFormDataFromStep is deprecated, use getCheckinData instead");
        return CoreDataManager.getCheckinData();
    }

    static saveFormDataToStep(data, step = null) {
        console.warn("saveFormDataToStep is deprecated, use handleFormStep instead");
        return ValidationManager.saveFormDataToStep(data, step);
    }

    static getPetsFromCookies() {
        console.warn("getPetsFromCookies is deprecated, use getAllPetsFromCheckin instead");
        return PetManager.getAllPetsFromCheckin();
    }

    static updatePetInCookies(petIndex, data) {
        console.warn("updatePetInCookies is deprecated, use updatePetInCheckin instead");
        return PetManager.updatePetInCheckin(petIndex, data);
    }

    // Utility methods
    static generateCheckinId() {
        return CoreDataManager.generateCheckinId();
    }

    static generatePetId() {
        return PetManager.generatePetId();
    }

    static deepMerge(target, source) {
        return CoreDataManager.deepMerge(target, source);
    }

    // Cookie utilities
    static getCookieSize() {
        return UtilitiesManager.getCookieSize();
    }

    static canSetCookie(name, value) {
        return UtilitiesManager.canSetCookie(name, value);
    }

    static getCookiesByPattern(pattern) {
        return UtilitiesManager.getCookiesByPattern(pattern);
    }

    static clearCookiesByPattern(pattern) {
        return UtilitiesManager.clearCookiesByPattern(pattern);
    }

    static hasCookie(name) {
        return UtilitiesManager.hasCookie(name);
    }
}

// Initialize automatically when module loads
document.addEventListener("DOMContentLoaded", () => {
    FormDataManager.initialize();
});

// Expose debug method globally
window.debugCheckin = () => FormDataManager.debugCheckinData();

export { FormDataManager };
