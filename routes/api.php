<?php

use App\Http\Controllers\ApiBookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\NotificationController;
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

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/driver/create', [AuthController::class, 'register']);
Route::post('/login/users', [AuthController::class, 'auth'])->withoutMiddleware('auth:sanctum');
Route::post('/upload-image', [ImageUploadController::class, 'upload']);
Route::get('/bookings/date', [ApiBookingController::class, 'getBookingsByDate']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [DriverController::class, 'index']);
    Route::get('/users/{id}', [DriverController::class, 'show']);
    Route::put('/users/{id}', [DriverController::class, 'update']);
    Route::delete('/users/{id}', [DriverController::class, 'destroy']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/driver/data', [DriverController::class, 'userBookings']);
    Route::post('/bookings/{id}/status', [ApiBookingController::class, 'updateStatus']);
    Route::post('/user/{id}/status', [DriverController::class, 'updateStatus']);
    Route::post('/user/{id}/rating', [DriverController::class, 'updateRating']);
    Route::post('/active/{id}/status', [DriverController::class, 'isActive']);
    Route::apiResource('bookings', ApiBookingController::class);
    Route::put('bookings/restore/{id}', [ApiBookingController::class, 'restore']);
    Route::delete('bookings/permanent/{id}', [ApiBookingController::class, 'forceDelete']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/bookings/{booking}/assign-driver', [ApiBookingController::class, 'assignDriver']);
    Route::post('/bookings/{booking}/assign-customer', [ApiBookingController::class, 'assignCustomer']);
    Route::get('/user/bookings/{user_id}', [DriverController::class, 'userBookSngle']);
    Route::get('/customer/bookings/{user_id}', [DriverController::class, 'customerBookSngle']);
    Route::get('/dashboard/user', [ApiBookingController::class, 'getUserBookings']);
    Route::post('/notifications', [NotificationController::class, 'createNotification']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::get('/details/{userId}/users', [ApiBookingController::class, 'getDriverBookings']);
    Route::put('/bookings/{bookingId}/update-date', [ApiBookingController::class, 'updateBookingDate']);
    Route::get('/drivers/{id}/logs', [DriverController::class, 'getDriverOnlineStats']);

});
