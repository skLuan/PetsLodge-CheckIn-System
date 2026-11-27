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

        closeIcon.addEventListener("click", (e) => {
            e.stopPropagation();
            document.dispatchEvent(new CustomEvent("pet:delete-request", {
                detail: { index: this.index, name: this.name }
            }));
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

        // Click the pill body to select this pet for editing (only one selected at a time).
        // The close icon stops propagation, so delete stays independent of select.
        this.pillElement.addEventListener("click", () => {
            const wasSelected = this.pillElement.classList.contains("selected");

            document.querySelectorAll("#petPillsContainer .pill.selected")
                .forEach((p) => p.classList.remove("selected"));

            if (!wasSelected) {
                this.pillElement.classList.add("selected");
            }

            document.dispatchEvent(new CustomEvent("pet:select-request", {
                detail: { index: this.index, name: this.name, selected: !wasSelected }
            }));
        });
    }

    render() {
        return this.pillElement;
    }
}
export default Pill;
