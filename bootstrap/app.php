<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\BlockedIpCheck::class,
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'permissions' => \App\Http\Middleware\CheckMultiplePermissions::class,
            'resource.permission' => \App\Http\Middleware\CheckResourcePermission::class,
            '2fa' => \App\Http\Middleware\RequireTwoFactor::class,
            'throttle.advanced' => \App\Http\Middleware\AdvancedThrottle::class,
        ]);

        // Configure rate limiting for routes
        $middleware->throttleApi('api', 'throttle:api');
    })->withSchedule(function (Schedule $schedule) {
        // Schedule token cleanup
        $schedule->command('auth:clean-tokens')->dailyAt('02:00');
    })->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
