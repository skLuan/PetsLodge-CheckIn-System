class Pill {
    constructor(name, type, index) {
        this.name = name;
        this.type = type;
        this.index = index;
        this.pillElement = document.createElement("div");
        this.pillElement.classList.add("pill");
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
        const pill = this.pillElement;
        return this.pillElement;
    }

    select() {
        if (this.pillElement) {
            this.pillElement.classList.add("selected");
        }
    }

    /**
     * Removes a pet from cookies and updates pills.
     * @param {number} index - The pet index to remove.
     */
    removePet(index) {
        const cookieKey = "formStep-2";
        let existingData = {};
        try {
            const cookie = document.cookie
                .split("; ")
                .find((row) => row.startsWith(`${cookieKey}=`));
            if (cookie) {
                existingData = JSON.parse(
                    decodeURIComponent(cookie.split("=")[1])
                );
            }
        } catch (e) {
            console.error(`Error parsing ${cookieKey} cookie JSON:`, e);
            return;
        }

        // Remove the pet at the specified index
        delete existingData[`pet${index}`];

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

        // Save updated data to cookies
        document.cookie = `${cookieKey}=${encodeURIComponent(
            JSON.stringify(reindexedData)
        )}; path=/; max-age=3600`;
        console.log(`Removed pet${index} and reindexed cookies.`);
    }

    getCloseIcon() {
        return this.pillElement.querySelector(".close-icon");
    }
}
export default Pill;
