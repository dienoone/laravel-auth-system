<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;

class LogSecurityEvent
{
    /**
     * Handle failed login attempts.
     */
    public function handleFailedLogin(Failed $event): void
    {
        Log::warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? $event->credentials['login'] ?? 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle account lockout.
     */
    public function handleLockout(Lockout $event): void
    {
        Log::error('Account locked due to too many failed attempts', [
            'email' => $event->request->input('email') ?? $event->request->input('login'),
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
