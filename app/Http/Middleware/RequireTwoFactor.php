<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_enabled) {
            // Check if this token has 2FA verified
            $token = $user->currentAccessToken();

            if (!$token->can('2fa-verified')) {
                return $this->forbiddenResponse('Two-factor authentication required.');
            }
        }

        return $next($request);
    }
}
