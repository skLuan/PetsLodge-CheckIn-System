class Utils {
    static sanitizePhoneNumber(phone) {
        return phone.replace(/\D/g, "");
    }

    static formatPhoneNumber(phone) {
        const cleaned = this.sanitizePhoneNumber(phone);
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return `(${match[1]}) ${match[2]}-${match[3]}`;
        }
        return phone;
    }

    static actualStep() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("step")) {
            const step = urlParams.get("step");
            return parseInt(step) - 1;
        } else return 0;
    }

    static safeOperation(operation, fallback = null) {
        try {
            return operation();
        } catch (error) {
            console.error("Cookie operation failed:", error);
            return fallback;
        }
    }
}

export default Utils;
