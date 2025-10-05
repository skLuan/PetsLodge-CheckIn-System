const FORM_CONFIG = {
    STEPS: {
        OWNER_INFO: 1,
        PET_INFO: 2,
        FEEDING_MEDICATION: 3,
        HEALTH_INFO: 4,
        INVENTORY: 5,
        THANKS: 6,
    },
    COOKIE_PREFIX: "formStep",
    DEFAULT_COOKIE_DAYS: 7, // Cambiar a 7 días para mayor persistencia
    COOKIE_MAX_AGE: 604800, // 7 días en segundos
    PET_KEY_PREFIX: "pet",

    // Nuevas configuraciones útiles
    MAX_PETS: 10,
    COOKIE_SIZE_LIMIT: 4096, // 4KB
    DEBUG_MODE: true, // Para activar logging adicional
};

// Agregar validaciones para los tipos de feeding
const FEEDING_TYPES = {
    FOOD: "food",
    MEDICATION: "medication",
};

const TIME_SLOTS = {
    MORNING: "morning",
    NOON: "noon",
    AFTERNOON: "afternoon",
    EVENING: "evening",
    NIGHT: "night",
};

const DEFAULT_CHECKIN_STRUCTURE = {
    date: "",
    user: {
        info: {
            phone: "",
            name: "",
            email: "",
            address: "",
            city: "",
            zip: "",
        },
        emergencyContact: {
            name: "",
            phone: "",
        },
    },
    pets: [],
    grooming: {
        bath: false,
        nails: false,
        grooming: false,
    },
    groomingDetails: "",
    inventory: [],
};

const DEFAULT_PET_STRUCTURE = {
    info: {
        petName: "",
        petColor: "",
        petType: "",
        petBreed: "",
        petAge: "",
        petWeight: "",
        petGender: "",
        petSpayed: "",
    },
    health: {
        unusualHealthBehavior: false,
        healthBehaviors: "",
        warnings: "",
    },
    feeding: [],
    medication: [],
};
export default {
    DEFAULT_CHECKIN_STRUCTURE,
    DEFAULT_PET_STRUCTURE,
    FORM_CONFIG,
    FEEDING_TYPES,
    TIME_SLOTS,
};
