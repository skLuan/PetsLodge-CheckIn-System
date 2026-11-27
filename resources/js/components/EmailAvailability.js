// Step 1: warn when the email already belongs to another client.
// users.email is unique, so letting it through ends in a SQL 1062 at submission.

const form = () => document.getElementById("ownerInfoForm");

async function isEmailTaken(email, phone) {
    if (!email) return false;
    try {
        const response = await fetch("/api/check-email", {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({ email, phone }),
        });
        if (!response.ok) return false; // never block the wizard on a failed check
        return (await response.json()).emailTaken === true;
    } catch {
        return false;
    }
}

function showError(taken) {
    const error = document.getElementById("emailTakenError");
    const input = form()?.querySelector("[name='email']");
    error?.classList.toggle("hidden", !taken);
    input?.classList.toggle("border-red-500", taken);
    input?.setCustomValidity(taken ? "This email already exist" : "");
}

/** Checks the current step-1 email; returns true when it is OK to continue. */
export async function validateOwnerEmail() {
    const f = form();
    if (!f) return true;
    const email = f.querySelector("[name='email']")?.value.trim();
    const phone = f.querySelector("[name='phone']")?.value.trim();
    const taken = await isEmailTaken(email, phone);
    showError(taken);
    return !taken;
}

const f = form();
if (f) {
    const input = f.querySelector("[name='email']");
    input?.addEventListener("blur", validateOwnerEmail);
    input?.addEventListener("input", () => showError(false));
}
