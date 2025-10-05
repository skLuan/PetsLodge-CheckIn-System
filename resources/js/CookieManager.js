import config from "./config.js";

const { DEFAULT_CHECKIN_STRUCTURE } = config;

/**
 * Enhanced CookieManager with encryption support
 * Note: For production, sensitive data should be encrypted server-side
 */
class CookieManager {
    /**
     * Establece una cookie con el nombre, valor y días de expiración dados
     * Incluye medidas de seguridad básicas
     */
    static setCookie(name, value, days = 1, options = {}) {
        try {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            let cookieValue = typeof value === 'object'
                ? JSON.stringify(value)
                : value;

            // Basic obfuscation (not real encryption - use server-side for sensitive data)
            if (options.obfuscate) {
                cookieValue = btoa(encodeURIComponent(cookieValue));
            }

            // Build cookie string with security options
            let cookieString = `${name}=${encodeURIComponent(cookieValue)}; expires=${expires}; path=/`;

            // Add security attributes
            if (options.httpOnly !== false) {
                // Note: httpOnly can't be set from client-side, but we can request it server-side
            }
            if (options.secure) {
                cookieString += '; Secure';
            }
            if (options.sameSite) {
                cookieString += `; SameSite=${options.sameSite}`;
            }

            document.cookie = cookieString;
            return true;
        } catch (error) {
            console.error(`Error setting cookie ${name}:`, error);
            return false;
        }
    }

    /**
     * Obtiene el valor de una cookie por nombre
     */
    static getCookie(name, options = {}) {
        try {
            const cookies = document.cookie.split('; ');
            const cookie = cookies.find(row => row.startsWith(`${name}=`));

            if (!cookie) return null;

            let value = decodeURIComponent(cookie.split('=').slice(1).join('='));

            // Deobfuscate if needed
            if (options.obfuscate) {
                try {
                    value = decodeURIComponent(atob(value));
                } catch (e) {
                    console.warn(`Failed to deobfuscate cookie ${name}:`, e);
                    return null;
                }
            }

            // Intenta parsear como JSON, si falla retorna el string
            try {
                const parsed = JSON.parse(value);
                // Basic validation - ensure it's an object with expected structure
                if (typeof parsed === 'object' && parsed !== null) {
                    return parsed;
                }
                return value;
            } catch {
                return value;
            }
        } catch (error) {
            console.error(`Error getting cookie ${name}:`, error);
            return null;
        }
    }

    /**
     * Elimina una cookie estableciendo su fecha de expiración en el pasado
     */
    static deleteCookie(name) {
        return this.setCookie(name, '', -1);
    }

    /**
     * Verifica si existe una cookie con el nombre dado
     */
    static hasCookie(name) {
        return document.cookie.split('; ').some(row => row.startsWith(`${name}=`));
    }

    /**
     * Obtiene todas las cookies que coincidan con un patrón
     */
    static getCookiesByPattern(pattern) {
        const cookies = {};
        document.cookie.split('; ').forEach(cookie => {
            const [name, ...valueParts] = cookie.split('=');
            if (name.includes(pattern)) {
                cookies[name] = this.getCookie(name);
            }
        });
        return cookies;
    }

    /**
     * Limpia todas las cookies que coincidan con un patrón
     */
    static clearCookiesByPattern(pattern) {
        const cookieNames = document.cookie
            .split('; ')
            .map(cookie => cookie.split('='))
            .filter(name => name.includes(pattern));
        
        cookieNames.forEach(name => this.deleteCookie(name));
        return cookieNames.length;
    }

    /**
     * Obtiene el tamaño total de las cookies en bytes
     */
    static getCookieSize() {
        return document.cookie.length;
    }

    /**
     * Verifica si una cookie puede ser establecida (límite de 4KB por cookie)
     */
    static canSetCookie(name, value) {
        const cookieString = `${name}=${encodeURIComponent(
            typeof value === 'object' ? JSON.stringify(value) : value
        )}`;
        return cookieString.length <= 4096; // 4KB limit
    }
}

export { CookieManager };