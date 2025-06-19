<?php

use App\Http\Controllers\DriverController;
use App\Http\Controllers\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


// Driver routes
Route::prefix('drivers')->group(function () {
    // Get available drivers near a location
    Route::get('/available', [DriverController::class, 'availableDriversNear']);

    // Driver details and updates
    Route::get('/{driver}', [DriverController::class, 'show']);
    Route::patch('/{driver}/location', [DriverController::class, 'updateLocation']);
    Route::patch('/{driver}/availability', [DriverController::class, 'updateAvailability']);
});

// Trip routes
Route::prefix('trips')->group(function () {
    // Book a new trip
    Route::post('/', [TripController::class, 'book']);

    // Trip details and updates
    Route::get('/{trip}', [TripController::class, 'show']);
    Route::patch('/{trip}/status', [TripController::class, 'updateStatus']);

    // Trip history for a user
    // Route::get('/user/history', [TripController::class, 'userHistory']);

    // // Active trips for a driver
    // Route::get('/driver/active', [TripController::class, 'driverActiveTrips']);
});
