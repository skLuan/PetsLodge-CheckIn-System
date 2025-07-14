<?php

use App\Http\Controllers\ProfileController;
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
    return view('CheckIn');
})->name('CheckIn');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\CheckInController;

Route::post('/check-user', [CheckInController::class, 'checkUser'])->name('check-user');
Route::get('/new-form', [CheckInController::class, 'newForm'])->name('new-form');
Route::get('/new-form-pre-filled', [CheckInController::class, 'newFormPreFilled'])->name('new-form-pre-filled');
Route::get('/view-check-in', [CheckInController::class, 'viewCheckIn'])->name('view-check-in');
require __DIR__.'/auth.php';
