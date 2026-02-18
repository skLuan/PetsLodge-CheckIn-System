export class CheckInHandler {
    constructor(options) {
        this.formSelector = options.formSelector || '#checkInForm';
        this.apiEndpoint = options.apiEndpoint;
        this.csrfToken = options.csrfToken;
        this.redirectRoutes = {
            newForm: options.newFormRoute,
            newFormPreFilled: options.newFormPreFilledRoute,
            confirmation: options.confirmationRoute
        };
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector(this.formSelector);
            if (form) {
                form.addEventListener('submit', (e) => this.handleSubmit(e));
            }
        });
    }

    handleSubmit(e) {
        e.preventDefault();
        const phoneInput = document.querySelector(`${this.formSelector} #phone`);
        const phone = phoneInput ? phoneInput.value : '';

        fetch(this.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({ phone: phone })
        })
        .then(response => response.json())
        .then(data => this.handleSuccess(data, phone))
        .catch(() => this.handleError());
    }

    handleSuccess(response, phone) {
        if (response.userExists === false) {
            window.location.href = `${this.redirectRoutes.newForm}?phone=${phone}`;
        } else if (response.userExists && !response.hasCheckIn) {
            window.location.href = `${this.redirectRoutes.newFormPreFilled}?phone=${phone}`;
        } else if (response.userExists && response.hasCheckIn) {
            window.location.href = `${this.redirectRoutes.confirmation}?phone=${phone}`;
        }
    }

    handleError() {
        alert('An error occurred. Please try again.');
    }
}

// Expose to global scope for inline script usage
window.CheckInHandler = CheckInHandler;