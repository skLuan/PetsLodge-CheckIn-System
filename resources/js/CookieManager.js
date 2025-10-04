import DEFAULT_CHECKIN_STRUCTURE from "./config.js";
class CookieManager {
    /**
     * Establece una cookie con el nombre, valor y días de expiración dados
     */
    static setCookie(name, value, days = 1) {
        try {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            const cookieValue = typeof value === 'object' 
                ? JSON.stringify(value) 
                : value;
            
            document.cookie = `${name}=${encodeURIComponent(cookieValue)}; expires=${expires}; path=/`;
            return true;
        } catch (error) {
            console.error(`Error setting cookie ${name}:`, error);
            return false;
        }
    }

    /**
     * Obtiene el valor de una cookie por nombre
     */
    static getCookie(name) {
        try {
            const cookies = document.cookie.split('; ');
            const cookie = cookies.find(row => row.startsWith(`${name}=`));
            
            if (!cookie) return null;
            
            const value = decodeURIComponent(cookie.split('=').slice(1).join('='));
            
            // Intenta parsear como JSON, si falla retorna el string
            try {
                return JSON.parse(value);
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