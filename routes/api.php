<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DropInController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Check-in API routes
Route::post('/checkin/submit', [App\Http\Controllers\CheckInApiController::class, 'submitCheckIn']);
Route::post('/checkin/autosave', [App\Http\Controllers\CheckInApiController::class, 'autoSaveCheckIn']);

Route::post('/check-user', [App\Http\Controllers\CheckInApiController::class, 'checkUser'])->name('check-user');
Route::post('/readyToPrint', [DropInController::class, 'readyToPrint']);

// Sequential submission routes
Route::post('/checkin/step1/user-info', [App\Http\Controllers\CheckInApiController::class, 'submitUserInfo']);
Route::post('/checkin/step2/pet-info', [App\Http\Controllers\CheckInApiController::class, 'submitPetInfo']);
Route::post('/checkin/step3/pet-health', [App\Http\Controllers\CheckInApiController::class, 'submitPetHealth']);
Route::post('/checkin/step4/checkin-data', [App\Http\Controllers\CheckInApiController::class, 'submitCheckInData']);
Route::post('/checkin/step5/extra-info', [App\Http\Controllers\CheckInApiController::class, 'submitExtraInfo']);
