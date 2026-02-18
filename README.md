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
- **Editing Mode** - Edit existing check-ins with change detection
- **Data Persistence** - Automatic data saving across page reloads
- **Reactivity System** - Real-time UI updates when data changes
- **Change Detection** - Track what changed when editing check-ins
- **Form Validation** - Comprehensive validation at each step

## Architecture

### Data Flow Architecture

The system uses a **unified cookie-based data flow** that serves as the single source of truth:

```
Database → Laravel Session → DOM Attributes → Cookies (Single Source) → Form UI
```

**Key Principles:**
1. **Cookies as Single Source of Truth** - All form state stored in `pl_checkin_data` cookie
2. **One-Time Session Merge** - Session data merged once at initialization
3. **Explicit Editing Mode** - Dedicated flag tracks edit vs. create operations
4. **Lossless Data Transformation** - All fields preserved during conversions
5. **Automatic Reactivity** - Cookie changes trigger automatic UI updates

### System Layers

1. **Database Layer** - Laravel database with check-in, pet, and service records
2. **Backend Layer** - Laravel controllers and services for data transformation
3. **Frontend Layer** - JavaScript managers for data handling and UI updates
4. **Cookie Layer** - Browser cookies for persistent form state

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

## Key Components

### FormDataManager
The main API for managing check-in form data. Provides methods for:
- Creating and updating check-in data
- Managing pets, feeding, and medication
- Handling inventory items
- Detecting changes in editing mode
- Automatic UI updates via reactivity

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
```

See [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md) for more examples.

### Data Transformation
The system includes comprehensive data transformation between database and cookie formats:
- **CheckInTransformer** - Transforms between database and cookie formats
- **CheckInDataValidator** - Validates data structure and required fields

### Reactivity System
Automatic UI updates when data changes:
- **CookieReactivityManager** - Detects cookie changes
- **UIManager** - Updates DOM elements based on data changes

## Project Structure

```
PetsLodge/
├── app/
│   ├── Http/Controllers/          # Laravel controllers
│   ├── Services/                  # Business logic services
│   │   ├── CheckInTransformer.php # Data transformation
│   │   └── CheckInDataValidator.php # Data validation
│   └── Models/                    # Eloquent models
├── resources/
│   ├── js/
│   │   └── cookies-and-form/      # Form data management
│   │       ├── FormDataManager.js # Main API
│   │       ├── managers/          # Specialized managers
│   │       └── reactivitySystem/  # Reactivity system
│   └── views/                     # Blade templates
├── docs/                          # Documentation
│   ├── DATA_FLOW.md              # Architecture documentation
│   ├── DEVELOPER_GUIDE.md        # Developer guide
│   ├── API_REFERENCE.md          # API documentation
│   ├── DEPLOYMENT_GUIDE.md       # Deployment procedures
│   └── MIGRATION_GUIDE.md        # Migration guide
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
- Clear browser cookies and reload

**Changes not detected:**
- Verify editing mode is enabled
- Check original data snapshot is stored
- See [`docs/DATA_FLOW.md`](docs/DATA_FLOW.md) troubleshooting section

**Cookie size exceeded:**
- Reduce number of pets or inventory items
- Clear unnecessary data before submission
- See [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md) for more details

For more troubleshooting help, see:
- [`docs/DATA_FLOW.md`](docs/DATA_FLOW.md) - Data flow troubleshooting
- [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md) - Debugging tips
- [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md) - Deployment troubleshooting

## Contributing

Contributions are welcome! Please open issues or submit pull requests.

## License

This project is licensed under the MIT License.

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

For a full list of dependencies, see `composer.json` and `package.json`.