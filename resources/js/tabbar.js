// Wizard tab bar: step navigation for Process.blade.php.
//
// Everything this module touches is treated as OPTIONAL. tabbar.js is a Vite
// entry point loaded via @vite, so an uncaught TypeError at module top level
// aborts the whole module and silently kills every listener below it. A page
// that does not render the wizard (or renders fewer steps) must degrade, not
// throw.
//
// Steps are DISCOVERED from the DOM rather than hard-coded. The wizard dropped
// from 7 steps to 6 when health-info moved into the pet step, and the four
// hard-coded `i <= 7` loops then dereferenced a null #step7 — producing
// "can't access property classList, document.getElementById(...) is null".
// Reading the count from the DOM means the next change to the step list
// cannot reintroduce that bug.

import { validateOwnerEmail } from "./components/EmailAvailability.js";

const progressBar = document.getElementById("stepProgress");
const prevButton = document.getElementById("prevStep");
const nextButton = document.getElementById("nextStep");

// Sorted numerically by the trailing digits of #stepN, so the array index is
// authoritative even if the markup order ever diverges from the id order.
const stepElements = Array.from(document.querySelectorAll(".step[id^='step']")).sort(
    (a, b) => Number(a.id.slice(4)) - Number(b.id.slice(4))
);
const stepCount = stepElements.length;
const circles = progressBar
    ? Array.from(progressBar.querySelectorAll(".circle"))
    : [];

// Step numbers are 1-based to match the #stepN ids and the ?step= query param.
function stepElement(stepNumber) {
    return stepElements[stepNumber - 1] || null;
}

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
    for (let i = 1; i <= stepCount; i++) {
        if (stepElement(i).classList.contains("active")) {
            return i;
        }
    }
    return 1;
}

function deactivateAllSteps() {
    stepElements.forEach((element) => {
        element.classList.remove("active");
        element.classList.add("inactive-right");
    });
    circles.forEach((circle) => {
        circle.classList.remove("active");
        // circle.classList.add('inactive');
    });
}

function showStep(step) {
    deactivateAllSteps();
    stepElements.forEach((element, index) => {
        const i = index + 1;
        element.classList.toggle("inactive-left", i < step);
        element.classList.toggle("inactive-right", i > step);
        element.classList.toggle("active", i === step);

        // The progress bar may carry fewer circles than there are steps; a
        // missing circle is a cosmetic gap, not a reason to abort navigation.
        if (circles[index]) {
            circles[index].classList.toggle("active", index < step);
        }

        setTimeout(() => {
            if (i === step) {
                const url = new URL(window.location);
                url.searchParams.set("step", step);
                window.history.replaceState({}, "", url);

                // Update navigation button text after step change
                if (typeof window.updateTabbarForStep === "function") {
                    window.updateTabbarForStep();
                }
            }
        }, 0);
    });
}

if (progressBar) {
    progressBar.addEventListener("click", function (event) {
        if (event.target.classList.contains("circle")) {
            // #stepProgress's first child is the rail, so the Nth circle sits
            // at child index N — already the 1-based step number.
            const stepIndex = Array.from(
                event.target.parentNode.children
            ).indexOf(event.target);
            showStep(stepIndex);
        }
    });
}

if (prevButton) {
    prevButton.addEventListener("click", function (e) {
        e.preventDefault();
        // Logic to go to the previous step
        let currentStep = getCurrentStep();

        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
}

if (nextButton) {
    nextButton.addEventListener("click", async function (e) {
        e.preventDefault();

        // Step 1: an email owned by another client would fail at submission.
        if (getCurrentStep() === 1 && !(await validateOwnerEmail())) {
            document.querySelector("#ownerInfoForm [name='email']")?.focus();
            return;
        }

        // First, save form data to cookies if the form saving logic is available
        if (window.SubmissionManager && window.SubmissionManager.handleNextStep) {
            const currentStep = getCurrentStep();
            const forms = document.querySelectorAll(".step form");
            const currentForm = forms[currentStep];
            const data = currentForm ? extractFormInputValues(currentForm) : {};

            // Save form data to cookies (bulk edit: apply to all pets)
            const success = window.SubmissionManager.handleNextStep(
                currentStep,
                data,
                null
            );

            if (!success) {
                // If saving failed, don't proceed to next step
                return;
            }
        }

        // Logic to go to the next step
        let currentStep = getCurrentStep();
        if (currentStep < stepCount) {
            showStep(currentStep + 1);
        }
    });
}

// Only drive the wizard on a page that actually has one.
if (stepCount > 0) {
    deactivateAllSteps();
    const urlStep = new URL(window.location).searchParams.get("step");
    const requestedStep =
        urlStep && !isNaN(Number(urlStep)) ? Number(urlStep) : 1;
    // Clamp: a hand-edited ?step=99 would otherwise leave every step inactive.
    const initialStep = Math.min(Math.max(requestedStep, 1), stepCount);
    showStep(initialStep); // Start with the step from URL or default to 1
}
