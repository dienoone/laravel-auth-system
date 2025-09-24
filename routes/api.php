<?php

use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);

    // Email verification
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);

    // Password reset
    Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/password/validate-token', [PasswordResetController::class, 'validateToken']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
});

// Protected auth routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/logout-all', [LogoutController::class, 'logoutAll']);

    // Email verification (authenticated)
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);
    Route::get('/email/status', [EmailVerificationController::class, 'status']);

    // Password update
    Route::put('/password', [PasswordController::class, 'update']);
});

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/', [ProfileController::class, 'update']); // Alternative for form-data
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });
});

// Routes that require verified email
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Add routes that require verified email here
});
