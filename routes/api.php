<?php

use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Auth\PermissionCheckController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\UserRoleController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
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
    Route::post('/2fa/verify', [LoginController::class, 'verifyTwoFactor']);

    // Email verification
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);

    // Password reset
    Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/password/validate-token', [PasswordResetController::class, 'validateToken']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

    // Social authentication
    Route::get('/{provider}', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google|github|facebook');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|github|facebook');
    Route::post('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|github|facebook');
    Route::post('/{provider}/mobile', [SocialAuthController::class, 'mobile'])
        ->where('provider', 'google|github|facebook');
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

    // Permission check
    Route::post('/permissions/check', [PermissionCheckController::class, 'check']);
    Route::get('/permissions', [PermissionCheckController::class, 'userPermissions']);


    // Social account management
    Route::post('/social/link', [SocialAuthController::class, 'link']);
    Route::get('/social/providers', [SocialAuthController::class, 'linkedProviders']);
    Route::delete('/social/{provider}', [SocialAuthController::class, 'unlink'])
        ->where('provider', 'google|github|facebook');

    // 2FA management
    Route::prefix('2fa')->group(function () {
        Route::get('/status', [TwoFactorController::class, 'status']);
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/confirm', [TwoFactorController::class, 'confirmEnable']);
        Route::delete('/disable', [TwoFactorController::class, 'disable']);
        Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    });
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

// Admin routes (require authentication and permissions)
Route::middleware(['auth:sanctum', 'permission:manage-roles,manage-permissions'])->prefix('admin')->group(function () {
    // Role management
    Route::apiResource('roles', RoleController::class);

    // Permission management
    Route::apiResource('permissions', PermissionController::class);

    // User role management
    Route::prefix('users/{user}')->group(function () {
        Route::get('/roles', [UserRoleController::class, 'show']);
        Route::put('/roles', [UserRoleController::class, 'syncRoles']);
        Route::post('/roles', [UserRoleController::class, 'addRole']);
        Route::delete('/roles', [UserRoleController::class, 'removeRole']);
        Route::put('/permissions', [UserRoleController::class, 'syncPermissions']);
    });
});

// Example of role-based routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin-only')->group(function () {
    // Admin only routes
});

Route::middleware(['auth:sanctum', 'role:admin,moderator'])->prefix('moderator')->group(function () {
    // Admin or moderator routes
});

// Routes that require 2FA verification
Route::middleware(['auth:sanctum', '2fa'])->group(function () {
    // Sensitive routes that require 2FA
});

/*
|--------------------------------------------------------------------------
| Example Usage in Routes
| Here's how to use the enhanced PBAC in your routes:
|--------------------------------------------------------------------------
*/

// Single permission check
Route::middleware(['auth:sanctum', 'permission:posts.edit'])->group(function () {
    // Routes for users who can edit posts
});

// Multiple permissions (all required)
Route::middleware(['auth:sanctum', 'permissions:all,posts.edit,posts.publish'])->group(function () {
    // Routes for users who can both edit AND publish posts
});

// Multiple permissions (any)
Route::middleware(['auth:sanctum', 'permissions:any,posts.edit,posts.delete'])->group(function () {
    // Routes for users who can either edit OR delete posts
});

// Resource-based permission
Route::middleware(['auth:sanctum', 'resource.permission:posts,edit'])->group(function () {
    // Routes that check posts.edit permission
});

// Wildcard permission example
Route::middleware(['auth:sanctum', 'permission:posts.*'])->group(function () {
    // Routes for users who have any posts permission
});

// Combined role and permission
Route::middleware(['auth:sanctum', 'role:admin,moderator', 'permission:system.logs.view'])->group(function () {
    // Routes for admins or moderators who can view logs
});
