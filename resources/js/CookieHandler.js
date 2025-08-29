import Utils from "./Utils";
class CookieHandler {
    static setCookie(name, value, days = 1) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value)) + '; expires=' + expires + '; path=/';
    }

    static getCookie(name) {
        return document.cookie.split('; ').reduce((r, v) => {
            const parts = v.split('=');
            return parts[0] === name ? decodeURIComponent(parts[1]) : r
        }, '');
    }

    static deleteCookie(name) {
        this.setCookie(name, '', -1);
    }

    // Function to get form data from cookies
    static getFormDataFromCookies(index) {
        const tempCookie = document.cookie.split("; ");
        const cookie = tempCookie.find((row) =>
            row.startsWith(`formStep-${index}=`)
        );
        console.log("Getting form data from cookies...");
        if (!cookie) return null;
        try {
            return JSON.parse(decodeURIComponent(cookie.split("=").slice(1).join("=")));
        } catch (e) {
            console.error("Error parsing cookie JSON:", e);
            return null;
        }
    }

    static getPetsFromCookies() {
        const tempCookie = this.getFormDataFromCookies(2);
        if (!tempCookie) return [];
        const pets = Object.keys(tempCookie)
            .filter((key) => key.startsWith("pet"))
            .map((key) => tempCookie[key]);
        return pets;
    }

    static updatePetInCookies(petIndex, data) {
        const step = 2;
        const cookieKey = `formStep-${step}`;
        let existingData = this.getFormDataFromCookies(step);

        if (existingData[`pet${petIndex}`]) {
            existingData[`pet${petIndex}`] = { ...existingData[`pet${petIndex}`], ...data };
            
            CookieHandler.setCookie(cookieKey, existingData);
            console.log(`Pet at index ${petIndex} updated in cookies.`);
        } else {
            console.warn(`No pet found at index ${petIndex} in cookies.`);
        }
    }

    // Function to save form data to cookies
    static saveFormDataToCookies(data) {
        console.log("Saving form data to cookies...");
        console.log(data);
        if (!data) {
            console.log("No data provided, skipping save.");
            return;
        }

        // Parse existing cookies for the current form step
        const step = Utils.actualStep() + 1;
        const cookieKey = `formStep-${step}`;
        let existingData = {};
        try {
            const tempCookie = document.cookie.split("; ");
            const cookie = tempCookie.find((row) =>
                row.startsWith(`${cookieKey}=`)
            );
            if (cookie) {
                existingData = JSON.parse(
                    decodeURIComponent(cookie.split("=")[1])
                );
            }
        } catch (e) {
            console.error(`Error parsing ${cookieKey} cookie JSON:`, e);
            existingData = {};
        }

        // Initialize newData with existing data
        let newData = { ...existingData };

        if (step === 2) {
            // Calculate petCounter based on existing pet keys
            let petCounter = 0;
            while (existingData[`pet${petCounter}`]) {
                petCounter++;
            }
            // Always add new pet with the next available key
            newData[`pet${petCounter}`] = data;
            petCounter++;
        } else {
            // For other steps, merge data without pet key logic
            newData = { ...existingData, ...data };
        }

        // Save merged data to cookies
        document.cookie = `${cookieKey}=${encodeURIComponent(
            JSON.stringify(newData)
        )}; path=/; max-age=3600`;
        console.log("Form data saved to cookies:", newData);
    }
}
export default CookieHandler;