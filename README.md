# PetsLodge

PetsLodge is a comprehensive pet lodging management system designed to streamline check-in processes, pet profile management, and service tracking. The application features a modern cookie-based data flow architecture that ensures data consistency and provides an excellent user experience.

## Features

### Core Functionality
- **Pet Check-in Management** - Complete check-in process with multi-step forms
- **Pet Profile Management** - Detailed pet information including health, feeding, and medication
- **Service Management** - Grooming, feeding, medication, and inventory tracking
- **Owner Information** - Contact details, emergency contacts, and address management
- **User Authentication** - Secure login and role-based access control

### Advanced Features
- **Editing Mode** - Edit existing check-ins with change detection and original data snapshots
- **Data Persistence** - Automatic data saving across page reloads with cookie-based storage
- **Reactivity System** - Real-time UI updates when data changes via MutationObserver
- **Change Detection** - Track what changed when editing check-ins with detailed summaries
- **Form Validation** - Comprehensive validation at each step with error handling
- **Multiple Feeding/Medication Times** - Select multiple times of day for feeding and medication in a single action
- **PDF Generation** - Generate and print check-in summaries as PDF documents

## Architecture

### Data Flow Architecture

The system uses a **unified cookie-based data flow** that serves as the single source of truth:

```
Database → Laravel Session → DOM Attributes → Cookies (Single Source) → Form UI
```

**Key Principles:**
1. **Cookies as Single Source of Truth** - All form state stored in `pl_checkin_data` cookie
2. **One-Time Session Merge** - Session data merged once at initialization with proper ordering
3. **Explicit Editing Mode** - Dedicated flag tracks edit vs. create operations with original data snapshots
4. **Lossless Data Transformation** - All fields preserved during conversions with null-safe operators
5. **Automatic Reactivity** - Cookie changes trigger automatic UI updates via MutationObserver
6. **Race Condition Prevention** - Proper async/await ordering ensures editing mode preservation

### System Layers

1. **Database Layer** - Laravel database with check-in, pet, and service records
2. **Backend Layer** - Laravel controllers and services for data transformation with comprehensive validation
3. **Frontend Layer** - JavaScript managers for data handling and UI updates with reactivity
4. **Cookie Layer** - Browser cookies for persistent form state with size optimization
5. **Monitoring Layer** - Health check endpoints and data flow monitoring for production

### Recent Improvements (Phase 3-5)

**Phase 3: Critical Data Flow Fixes**
- Fixed editing mode flag initialization order
- Resolved cookie initialization race conditions
- Added null-safe operators for emergency contact handling
- Preserved editing mode during session data merge

**Phase 4: Comprehensive Testing**
- Verified all critical data flows working correctly
- Confirmed editing mode flag preservation
- Validated original data snapshot storage
- Tested data persistence across page reloads

**Phase 5: Deployment & Monitoring**
- Implemented health check endpoint (`/health`)
- Added monitoring dashboard for admins
- Created deployment verification scripts
- Established rollback procedures

For detailed architecture information, see [`docs/DATA_FLOW.md`](docs/DATA_FLOW.md).

## Getting Started

### Prerequisites

- PHP 8.1+
- Node.js 16+
- MySQL 8.0+
- Composer
- npm

### Installation

1. **Clone the repository:**
    ```bash
    git clone https://github.com/yourusername/PetsLodge.git
    cd PetsLodge
    ```

2. **Install PHP dependencies:**
    ```bash
    composer install
    ```

3. **Install Node.js dependencies:**
    ```bash
    npm install
    ```

4. **Set up environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Set up database:**
    ```bash
    php artisan migrate
    php artisan db:seed
    ```

6. **Build frontend assets:**
    ```bash
    npm run build
    ```

7. **Run the application:**
    ```bash
    php artisan serve
    npm run dev
    ```

The application will be available at `http://localhost:8000`

## Technologies Used

### Backend
- **Laravel 10** (PHP 8.1+)
- **MySQL 8.0** (Database)
- **Laravel Breeze** (Authentication)
- **Laravel Sanctum** (API Authentication)

### Frontend
- **Vite** (Build tool)
- **Tailwind CSS** (Styling)
- **Alpine.js** (Interactivity)
- **Vanilla JavaScript** (Form management)

### Development Tools
- **PHPUnit** (Testing)
- **Faker** (Test data)
- **Laravel Pint** (Code formatting)
- **Mockery** (Mocking)

## Documentation

### User Documentation
- [Check-in Process Guide](docs/USER_GUIDE.md) - Step-by-step check-in instructions
- [FAQ](docs/FAQ.md) - Frequently asked questions

### Developer Documentation
- [Data Flow Architecture](docs/DATA_FLOW.md) - Complete system architecture and data flow
- [Developer Guide](docs/DEVELOPER_GUIDE.md) - How to use FormDataManager API
- [API Reference](docs/API_REFERENCE.md) - Complete method documentation
- [Migration Guide](docs/MIGRATION_GUIDE.md) - Upgrading to new system
- [Deployment Guide](docs/DEPLOYMENT_GUIDE.md) - Deployment procedures

### Implementation & Testing Documentation
- [Phase 3 Fixes Summary](plans/PHASE_3_FIXES_SUMMARY.md) - Critical data flow fixes and improvements
- [Phase 4 Test Results](plans/PHASE_4_TEST_RESULTS_SUMMARY.md) - Comprehensive testing results
- [Implementation Summary](plans/IMPLEMENTATION_SUMMARY.md) - Feature implementation details
- [Deployment Log](DEPLOYMENT_LOG.md) - Deployment history and monitoring setup
- [Check-in Cookie Refactor](CHECKIN_COOKIE_REFACTOR_README.md) - Cookie-based state management details

## Key Components

### FormDataManager
The main API for managing check-in form data. Provides methods for:
- Creating and updating check-in data
- Managing pets, feeding, and medication with multiple time support
- Handling inventory items
- Detecting changes in editing mode with original data snapshots
- Automatic UI updates via reactivity
- Resetting to original data in edit mode

**Example:**
```javascript
// Get check-in data
const data = FormDataManager.getCheckinData();

// Add a pet
FormDataManager.addPetToCheckin({
    petName: "Max",
    petType: "dog",
    petBreed: "Golden Retriever"
});

// Check if data changed
if (FormDataManager.hasDataChanged()) {
    console.log("User made changes");
}

// Get change summary
const changes = FormDataManager.getChangeSummary();

// Reset to original data
FormDataManager.resetToOriginal();
```

See [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md) for more examples.

### Data Transformation
The system includes comprehensive data transformation between database and cookie formats:
- **CheckInTransformer** - Transforms between database and cookie formats with null-safe operators
- **CheckInDataValidator** - Validates data structure and required fields with comprehensive error handling

### Reactivity System
Automatic UI updates when data changes:
- **CookieReactivityManager** - Detects cookie changes via MutationObserver (no aggressive polling)
- **UIManager** - Updates DOM elements based on data changes without clearing user input
- **FormUpdater** - Handles form field updates with proper event handling

## Project Structure

```
PetsLodge/
├── app/
│   ├── Http/Controllers/          # Laravel controllers
│   │   ├── CheckInApiController.php # API endpoints
│   │   ├── CheckInFormController.php # Form handling
│   │   ├── HealthCheckController.php # Health monitoring
│   │   └── DropInController.php    # Drop-in management
│   ├── Services/                  # Business logic services
│   │   ├── CheckInTransformer.php # Data transformation
│   │   ├── CheckInDataValidator.php # Data validation
│   │   ├── CheckInService.php     # Check-in operations
│   │   ├── CheckInPetService.php  # Pet operations
│   │   ├── PdfService.php         # PDF generation
│   │   └── PrintNodeService.php   # Print integration
│   └── Models/                    # Eloquent models
├── resources/
│   ├── js/
│   │   └── cookies-and-form/      # Form data management
│   │       ├── FormDataManager.js # Main API
│   │       ├── managers/          # Specialized managers
│   │       │   ├── EditingModeManager.js # Editing mode handling
│   │       │   ├── CoreDataManager.js # Core data operations
│   │       │   ├── CheckInSummaryUpdater.js # Summary updates
│   │       │   └── FormHandler.js # Form extraction
│   │       └── reactivitySystem/  # Reactivity system
│   │           ├── UIManager.js   # DOM updates
│   │           └── FormUpdater.js # Form updates
│   └── views/                     # Blade templates
├── docs/                          # Documentation
│   ├── DATA_FLOW.md              # Architecture documentation
│   ├── DEVELOPER_GUIDE.md        # Developer guide
│   ├── API_REFERENCE.md          # API documentation
│   ├── DEPLOYMENT_GUIDE.md       # Deployment procedures
│   └── MIGRATION_GUIDE.md        # Migration guide
├── plans/                         # Implementation plans and reports
│   ├── PHASE_3_FIXES_SUMMARY.md  # Critical fixes documentation
│   ├── PHASE_4_TEST_RESULTS_SUMMARY.md # Testing results
│   └── IMPLEMENTATION_SUMMARY.md # Feature implementation details
└── database/
    ├── migrations/                # Database migrations
    └── seeders/                   # Database seeders
```

## Development Workflow

### Running Tests

```bash
# Run PHP tests
php artisan test

# Run JavaScript tests
npm run test
```

### Building for Production

```bash
# Build frontend assets
npm run build

# Optimize Laravel
php artisan optimize
php artisan config:cache
php artisan route:cache
```

### Debugging

```javascript
// In browser console
FormDataManager.debugCheckinData();  // View all check-in data
FormDataManager.isEditingMode();     // Check editing mode
FormDataManager.hasDataChanged();    // Check for changes
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Create a feature branch (`git checkout -b feature/amazing-feature`)
2. Commit your changes (`git commit -m 'Add amazing feature'`)
3. Push to the branch (`git push origin feature/amazing-feature`)
4. Open a Pull Request

## Troubleshooting

### Common Issues

**Form fields not pre-populated when editing:**
- Check browser console for errors
- Verify session data in DOM attributes
- Verify editing mode flag is set: `FormDataManager.isEditingMode()`
- Clear browser cookies and reload
- Check that `data-session-checkin` attribute contains valid JSON

**Changes not detected:**
- Verify editing mode is enabled: `FormDataManager.isEditingMode()`
- Check original data snapshot is stored: `FormDataManager.getEditingMode()`
- Verify you're modifying data (not just viewing)
- See [`docs/DATA_FLOW.md`](docs/DATA_FLOW.md) troubleshooting section

**Cookie size exceeded:**
- Monitor cookie size with: `FormDataManager.debugCheckinData()`
- Reduce number of pets or inventory items
- Clear unnecessary data before submission
- See [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md) for more details

**UI not updating when data changes:**
- Verify reactivity listeners are registered
- Check browser console for MutationObserver errors
- Ensure cookie changes are being triggered
- Try manual trigger: `CookieReactivityManager.triggerCheck()`

**Multiple feeding/medication times not working:**
- Verify checkboxes are selected (not radio buttons)
- Check that at least one time is selected
- Verify form submission includes all selected times
- See [`plans/IMPLEMENTATION_SUMMARY.md`](plans/IMPLEMENTATION_SUMMARY.md) for details

For more troubleshooting help, see:
- [`docs/DATA_FLOW.md`](docs/DATA_FLOW.md) - Data flow troubleshooting
- [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md) - Debugging tips
- [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md) - Deployment troubleshooting
- [`plans/PHASE_3_FIXES_SUMMARY.md`](plans/PHASE_3_FIXES_SUMMARY.md) - Critical fixes documentation

## Recent Updates

### Latest Changes (March 2026)

**System Stability & Monitoring:**
- ✅ Health check endpoint for system monitoring
- ✅ Monitoring dashboard for administrators
- ✅ Deployment verification scripts
- ✅ Rollback procedures documented

**Data Flow Improvements:**
- ✅ Fixed editing mode flag initialization order
- ✅ Resolved cookie initialization race conditions
- ✅ Added null-safe operators for emergency contact handling
- ✅ Preserved editing mode during session data merge

**Feature Enhancements:**
- ✅ Multiple feeding/medication times in single action
- ✅ PDF generation and printing support
- ✅ Comprehensive change detection with summaries
- ✅ Original data snapshots for edit mode

**Testing & Validation:**
- ✅ Phase 4 comprehensive testing completed
- ✅ All critical data flows verified
- ✅ Data persistence validated
- ✅ Change detection mechanisms tested

For detailed information on recent changes, see:
- [`plans/PHASE_3_FIXES_SUMMARY.md`](plans/PHASE_3_FIXES_SUMMARY.md) - Critical fixes
- [`plans/PHASE_4_TEST_RESULTS_SUMMARY.md`](plans/PHASE_4_TEST_RESULTS_SUMMARY.md) - Testing results
- [`DEPLOYMENT_LOG.md`](DEPLOYMENT_LOG.md) - Deployment history

## License

This project is licensed under the MIT License. See [`LICENCE`](LICENCE) for details.

---
## Project Stack

PetsLodge is built with the following technologies:

- **Backend:** Laravel 10 (PHP 8.1+)
    - Key packages: dompdf/dompdf, guzzlehttp/guzzle, laravel/breeze, laravel/sanctum, laravel/tinker
- **Frontend:** Vite, Tailwind CSS, Alpine.js, Iconify
- **Testing & Dev Tools:** PHPUnit, Faker, Laravel Pint, Mockery, Collision, Spatie Laravel Ignition
- **Build Tools:** Vite, PostCSS, Autoprefixer
- **API & HTTP:** Axios
- **Containerization:** Docker
- **Monitoring:** Health check endpoints, data flow monitoring

For a full list of dependencies, see [`composer.json`](composer.json) and [`package.json`](package.json).