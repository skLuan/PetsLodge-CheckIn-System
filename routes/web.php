<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DropInController;
use App\Http\Controllers\HealthCheckController;

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

Route::get('/drop-in', [DropInController::class, 'show'])->name('drop-in.show');
Route::get('/drop-in/confirmation', [DropInController::class, 'showDropConfirmation'])->name('drop-in.confirmation');
Route::redirect('/dropin', '/drop-in');
// -----------------------
Route::get('/drop-in/check', [DropInController::class, 'checkInfo'])->name('drop-in.check');
// ---------------------
// Check-in routes
Route::get('/new-form', [App\Http\Controllers\CheckInFormController::class, 'newForm'])->name('new-form');
Route::get('/new-form-pre-filled', [App\Http\Controllers\CheckInFormController::class, 'newFormPreFilled'])->name('new-form-pre-filled');
Route::get('/view-check-in', [App\Http\Controllers\CheckInFormController::class, 'viewCheckIn'])->name('view-check-in');
Route::get('/edit-check-in/{checkInId}', [App\Http\Controllers\CheckInFormController::class, 'editCheckIn'])->name('edit-check-in');

// Health check routes
Route::get('/health', [HealthCheckController::class, 'index'])->name('health.check');
Route::get('/health/report', [HealthCheckController::class, 'report'])->name('health.report');

// Monitoring dashboard
Route::get('/admin/monitoring-dashboard', function () {
    return view('admin.monitoring-dashboard');
})->middleware(['auth', 'verified', 'admin.only'])->name('monitoring-dashboard');

require __DIR__.'/auth.php';

Route::post('/drop-in/check-user', [DropInController::class, 'checkUser'])->name('drop-in.check-user');