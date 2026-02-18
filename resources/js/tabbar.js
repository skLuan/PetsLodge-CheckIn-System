const progressBar = document.getElementById("stepProgress");
if(progressBar){
    progressBar.addEventListener("click", function (event) {
        if (event.target.classList.contains("circle")) {
            const stepIndex = Array.from(event.target.parentNode.children).indexOf(
                event.target
            );
            showStep(stepIndex);
        }
    });
}
const circles = progressBar.querySelectorAll(".circle");


document.getElementById("prevStep").addEventListener("click", function (e) {
   e.preventDefault();
    // Logic to go to the previous step
    let currentStep = getCurrentStep();
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
});

document.getElementById("nextStep").addEventListener("click", async function (e) {
    e.preventDefault();

    // First, save form data to cookies if the form saving logic is available
    if (window.SubmissionManager && window.SubmissionManager.handleNextStep) {
        const currentStep = getCurrentStep();
        const forms = document.querySelectorAll(".step form");
        const currentForm = forms[currentStep];
        const data = currentForm ? extractFormInputValues(currentForm) : {};

        // Get selected pet index if applicable
        const selectedPetIndex = window.PetPillManager ? window.PetPillManager.getSelectedPetIndex() : null;

        // Save form data to cookies
        const success = window.SubmissionManager.handleNextStep(currentStep, data, selectedPetIndex);

        if (!success) {
            // If saving failed, don't proceed to next step
            return;
        }
    }

    // Logic to go to the next step
    let currentStep = getCurrentStep();
    if (currentStep < 6) {
        showStep(currentStep + 1);
    }
});

// Helper function to extract form input values (copied from FormHandler for compatibility)
function extractFormInputValues(formElement) {
    if (!formElement) return {};

    const inputs = formElement.querySelectorAll("input, select, textarea");
    const data = {};

    inputs.forEach((input) => {
        if (input.name) {
            if (input.type === "checkbox") {
                data[input.name] = input.checked;
            } else if (input.type === "radio") {
                if (input.checked) {
                    data[input.name] = input.value;
                } else if (data[input.name] === undefined) {
                    data[input.name] = "";
                }
            } else {
                data[input.name] = input.value;
            }
        }
    });

    return data;
}

function getCurrentStep() {
    for (let i = 1; i <= 6; i++) {
        if (document.getElementById(`step${i}`).classList.contains("active")) {
            return i;
        }
    }
    return 1;
}

function deactivateAllSteps() {
    for (let i = 1; i <= 6; i++) {
        document.getElementById(`step${i}`).classList.remove("active");
        document.getElementById(`step${i}`).classList.add("inactive-right");
    }
    circles.forEach((circle) => {
        circle.classList.remove("active");
        // circle.classList.add('inactive');
    });
}

function showStep(step) {
    deactivateAllSteps();
    for (let i = 1; i <= 6; i++) {
        document
            .getElementById(`step${i}`)
            .classList.toggle("inactive-left", i < step);
        document
            .getElementById(`step${i}`)
            .classList.toggle("inactive-right", i > step);
        document
            .getElementById(`step${i}`)
            .classList.toggle("active", i === step);
        circles[i - 1].classList.toggle("active", i - 1 < step);

        setTimeout(() => {
            if (i === step) {
                const url = new URL(window.location);
                url.searchParams.set("step", step);
                window.history.replaceState({}, "", url);

                // Update navigation button text after step change
                if (typeof window.updateTabbarForStep === 'function') {
                    window.updateTabbarForStep();
                }
            }
        }, 0);
    }
}
deactivateAllSteps();
const urlStep = new URL(window.location).searchParams.get("step");
const initialStep = urlStep && !isNaN(Number(urlStep)) ? Number(urlStep) : 1;
showStep(initialStep); // Start with the step from URL or default to 1
