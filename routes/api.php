<?php

use App\Http\Controllers\ApiBookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ImageUploadController;
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

Route::post('/driver/create', [AuthController::class, 'register']);
Route::post('/login/users', [AuthController::class, 'auth'])->withoutMiddleware('auth:sanctum');
Route::post('/upload-image', [ImageUploadController::class, 'upload']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [DriverController::class, 'index']);
    Route::get('/users/{id}', [DriverController::class, 'show']);
    Route::put('/users/{id}', [DriverController::class, 'update']);
    Route::delete('/users/{id}', [DriverController::class, 'destroy']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/driver/data', [DriverController::class, 'userBookings']);
    Route::post('/bookings/{id}/status', [ApiBookingController::class, 'updateStatus']);
    Route::post('/user/{id}/status', [DriverController::class, 'updateStatus']);
    Route::post('/active/{id}/status', [DriverController::class, 'isActive']);
    Route::apiResource('bookings', ApiBookingController::class);
    Route::put('bookings/restore/{id}', [ApiBookingController::class, 'restore']);
    Route::delete('bookings/permanent/{id}', [ApiBookingController::class, 'forceDelete']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/bookings/{booking}/assign-driver', [ApiBookingController::class, 'assignDriver']);
    Route::get('/user/bookings/{user_id}', [DriverController::class, 'userBookSngle']);

});
