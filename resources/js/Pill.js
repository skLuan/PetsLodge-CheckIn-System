import CookieHandler from "./cookies-and-form/CookieHandler.js";
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
        closeIcon.setAttribute("icon", "material-symbols:close");
        closeIcon.setAttribute("aria-label", `Remove ${this.name}`);

        closeIcon.addEventListener("click", () => {
            this.removePet(this.index);
            this.pillElement.remove();
        });

        const petTypeIcons = {
            dog: "mdi:dog",
            cat: "mdi:cat",
            pig: "mdi:pig-variant",
            rabbit: "mdi:rabbit",
            bird: "mdi:bird",
            fish: "mdi:fish",
            turtle: "mdi:turtle",
            hamster: "mdi:rodent",
            horse: "mdi:horse",
            other: "material-symbols:pet",
        };
        const iconName = petTypeIcons[this.type] || "material-symbols:pet";
        const petIcon = document.createElement("iconify-icon");
        petIcon.classList.add("pet-icon");
        petIcon.setAttribute("icon", iconName);
        petIcon.setAttribute("aria-label", `${this.name} (${this.type})`);

        const textSpan = document.createElement("span");
        textSpan.textContent = `${this.name}`;
        
        this.pillElement.appendChild(textSpan);
        this.pillElement.appendChild(petIcon);
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
        const isSelected = this.pillElement.classList.contains("selected");
        if (isSelected) {
            this.pillElement.classList.remove("selected");
            // Optionally clear the form fields here if needed
            const form = document.querySelector("#petInfoForm");
            form.reset();
            return;
        }
        document.querySelectorAll(".pill.selected").forEach((pill) => {
            pill.classList.remove("selected");
        });
        this.pillElement.classList.add("selected");
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
