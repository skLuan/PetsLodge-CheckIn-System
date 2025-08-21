document.addEventListener("DOMContentLoaded", function () {
    let forms = document.querySelectorAll(".step");
    forms = Array.from(forms).map((form) => form.querySelector("form"));
    console.log(forms);

    function takeStep() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("step")) {
            const step = urlParams.get("step");
            return parseInt(step) - 1;
        } else return 0;
    }
    // Function to get form data from cookies
    function getFormDataFromCookies() {
        const tempCookie = document.cookie
            .split("; ");
        const cookie = tempCookie.find((row) => row.includes(`formStep-${takeStep()+1}=`));
        console.log("Getting form data from cookies...");
        console.log(cookie);
        if (!cookie) return null;
        try {
            return JSON.parse(cookie.split("=")[1]);
        } catch (e) {
            console.error("Error parsing cookie JSON:", e);
            return null;
        }
    }

    let tempData = getFormDataFromCookies();
    if (tempData) {
        const form = forms[takeStep()]; // Get the current form
        Object.keys(tempData).forEach((key) => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === "checkbox") {
                    input.checked = tempData[key];
                } else {
                    input.value = tempData[key];
                }
            }
        });
    }

    // Function to extract input values for a given form
    function extractFormInputValues(formElementOrSelector) {
        console.log("Extracting form input values...");
        let formElement = formElementOrSelector;
        if (typeof formElementOrSelector === "string") {
            formElement = document.querySelector(formElementOrSelector);
        }
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
                        // If no radio in the group is checked yet, set as empty string
                        data[input.name] = "";
                    }
                } else {
                    data[input.name] = input.value;
                }
            }
        });
        console.log("Extracted form input values:");
        console.log(data);
        return data;
    }

    // Function to save form data to cookies
    function saveFormDataToCookies(data) {
        console.log("Saving form data to cookies...");
        document.cookie = `formStep-${takeStep()}=${JSON.stringify(
            data
        )}; path=/; max-age=3600`;
        console.log("Form data saved to cookies.");
    }

    //------------------------------------------------
    //------------------------------------------------
    // Save form data on submit
    const form = document.querySelector("#petInfoForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            const data = extractFormInputValues(form);
            saveFormDataToCookies(data);
        });
    }

    const nextButton = document.querySelector("#nextStep");
    let step = null;
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            const step = takeStep();
            console.log(step);
            const data = extractFormInputValues(forms[step - 1]);
            saveFormDataToCookies(data);
        });
    }
});
