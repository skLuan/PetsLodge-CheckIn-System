<?php

use App\Http\Controllers\DropInController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\PetStaffDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\TermsAndConditionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::redirect('/', '/check-in');
Route::redirect('/checkin', '/check-in');
Route::get('/check-in', function () {
    return view('checkIn');
})->name('CheckIn');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'admin.only'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'pet.staff.only'])->group(function () {
    Route::get('/drop-in', [DropInController::class, 'show'])->name('drop-in.show');
    Route::get('/drop-in/confirmation', [DropInController::class, 'showDropConfirmation'])->name('drop-in.confirmation');
    Route::post('/drop-in/check-user', [DropInController::class, 'checkUser'])->name('drop-in.check-user');

    // Pet Staff Dashboard
    Route::get('/petstaff/dashboard', [PetStaffDashboardController::class, 'index'])->name('pet-staff.dashboard');
    Route::post('/petstaff/checkout/{id}', [PetStaffDashboardController::class, 'checkout'])->name('pet-staff.checkout');
    Route::post('/petstaff/dropped-in/{id}', [PetStaffDashboardController::class, 'dropped_in'])->name('pet-staff.dropped-in');
    Route::post('/petstaff/cancel/{id}', [PetStaffDashboardController::class, 'cancel'])->name('pet-staff.cancel');
    Route::post('/petstaff/reprint/{id}', [PetStaffDashboardController::class, 'reprint'])->name('pet-staff.reprint');

    // Terms & Conditions editor (entered from the pet-staff dashboard)
    Route::get('/petstaff/terms', [TermsAndConditionsController::class, 'edit'])->name('pet-staff.terms.edit');
    Route::put('/petstaff/terms', [TermsAndConditionsController::class, 'update'])->name('pet-staff.terms.update');

    // Signatures. Deliberately web routes, not api.php: the `api` middleware
    // group is stateless (Sanctum's stateful middleware is commented out in
    // Kernel.php), so `pet.staff.only` could never see the staff session there.
    // Signature images are personal data — `show` is the ONLY way to read one.
    Route::post('/signatures', [SignatureController::class, 'store'])->name('signatures.store');
    Route::get('/signatures/{signature}', [SignatureController::class, 'show'])->name('signatures.show');
});

Route::redirect('/dropin', '/drop-in');
// Convenience aliases. The canonical URL prefix is `/petstaff` (no hyphen) even
// though the route *names* are `pet-staff.*` — both spellings land on the dashboard.
Route::redirect('/petstaff', '/petstaff/dashboard');
Route::redirect('/pet-staff', '/petstaff/dashboard');
// -----------------------
// ---------------------
// Check-in routes
Route::get('/new-form', [App\Http\Controllers\CheckInFormController::class, 'newForm'])->name('new-form');
Route::get('/new-form-pre-filled', [App\Http\Controllers\CheckInFormController::class, 'newFormPreFilled'])->name('new-form-pre-filled');
Route::get('/view-check-in', [App\Http\Controllers\CheckInFormController::class, 'viewCheckIn'])->name('view-check-in');
Route::get('/edit-check-in/{checkInId}', [App\Http\Controllers\CheckInFormController::class, 'editCheckIn'])->name('edit-check-in');
Route::delete('/delete-check-in/{checkInId}', [App\Http\Controllers\CheckInFormController::class, 'deleteCheckIn'])->name('delete-check-in');

// Health check routes
Route::get('/health', [HealthCheckController::class, 'index'])->name('health.check');
Route::get('/health/report', [HealthCheckController::class, 'report'])->name('health.report');

// Monitoring dashboard
Route::get('/admin/monitoring-dashboard', function () {
    return view('admin.monitoring-dashboard');
})->middleware(['auth', 'verified', 'admin.only'])->name('monitoring-dashboard');

require __DIR__.'/auth.php';
