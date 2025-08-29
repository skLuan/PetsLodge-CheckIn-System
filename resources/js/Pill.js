import CookieHandler from "./CookieHandler.js";
class Pill {
    constructor(name, type, index) {
        this.name = name;
        this.type = type;
        this.index = index;
        this.pillElement = document.createElement("div");
        this.pillElement.classList.add("pill");
        this.pillElement.dataset.index = index;

        const closeIcon = document.createElement("iconify-icon");
        closeIcon.classList.add("close-icon");
        closeIcon.setAttribute("icon", "lucide:delete");
        closeIcon.setAttribute("aria-label", `Remove ${this.name}`);

        closeIcon.addEventListener("click", () => {
            this.removePet(this.index);
            this.pillElement.remove();
        });
        const textSpan = document.createElement("span");
        textSpan.textContent = `${this.name} (${this.type})`;

        this.pillElement.appendChild(textSpan);
        setTimeout(() => {
            this.pillElement.appendChild(closeIcon);
        }, 0);

        this.pillElement.addEventListener("click", () => {
            this.select();
        });
    }

    render() {
        return this.pillElement;
    }

    select() {
        if (!this.pillElement) return;
        document.querySelectorAll(".pill.selected").forEach((pill) => {
            pill.classList.remove("selected");
        });
        // Populate form with this pet's data
        const petData =
            CookieHandler.getFormDataFromCookies(2)[`pet${this.index}`];
        if (petData) {
            Object.entries(petData).forEach(([key, value]) => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === "radio") {
                        input.checked = value;
                    } else {
                        input.value = value;
                    }
                }
            });
        }
    }
    deselect() {
        if (this.pillElement) {
            this.pillElement.classList.remove("selected");
        }
    }

    /**
     * Removes a pet from cookies and updates pills.
     * @param {number} index - The pet index to remove.
     */
    removePet(index) {
        let existingData = CookieHandler.getFormDataFromCookies(2);
        const cookieKey = `formStep-2`;
        // Remove the pet at the specified index
        delete existingData[`pet${index}`];
        console.log(existingData);

        // Reindex remaining pets to maintain sequential keys
        const reindexedData = {};
        let newIndex = 0;
        Object.keys(existingData)
            .sort() // Ensure consistent order
            .forEach((key) => {
                if (key.startsWith("pet")) {
                    reindexedData[`pet${newIndex}`] = existingData[key];
                    newIndex++;
                }
            });

        CookieHandler.setCookie(cookieKey, reindexedData);
        console.log(reindexedData);
        console.log(`Removed pet${index} and reindexed cookies.`);
    }

    getCloseIcon() {
        return this.pillElement.querySelector(".close-icon");
    }
}
export default Pill;
