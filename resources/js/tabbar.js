const progressBar = document.getElementById("stepProgress");
progressBar.addEventListener("click", function (event) {
    if (event.target.classList.contains("circle")) {
        const stepIndex = Array.from(event.target.parentNode.children).indexOf(
            event.target
        );
        showStep(stepIndex);
    }
});
const circles = progressBar.querySelectorAll(".circle");


document.getElementById("prevStep").addEventListener("click", function (e) {
   e.preventDefault();
    // Logic to go to the previous step
    let currentStep = getCurrentStep();
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
});

document.getElementById("nextStep").addEventListener("click", function (e) {
   e.preventDefault();
    // Logic to go to the next step
    let currentStep = getCurrentStep();
    if (currentStep < 6) {
        showStep(currentStep + 1);
    }
});

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
            }
        }, 0);
    }
}
deactivateAllSteps();
const urlStep = new URL(window.location).searchParams.get("step");
const initialStep = urlStep && !isNaN(Number(urlStep)) ? Number(urlStep) : 1;
showStep(initialStep); // Start with the step from URL or default to 1
