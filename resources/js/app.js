import './bootstrap';
import "iconify-icon";
import { CheckInHandler } from './components/CheckInHandler.js';
import { checkInConfig, isCheckInPage } from './config/checkInConfig.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Initialize CheckInHandler only on the /check-in page
if (isCheckInPage()) {
    const checkInHandler = new CheckInHandler({
        formSelector: checkInConfig.formSelector,
        apiEndpoint: checkInConfig.apiEndpoint,
        csrfToken: checkInConfig.csrfToken,
        newFormRoute: checkInConfig.routes.newForm,
        newFormPreFilledRoute: checkInConfig.routes.newFormPreFilled,
        confirmationRoute: checkInConfig.routes.viewCheckIn
    });
}

Alpine.start();
