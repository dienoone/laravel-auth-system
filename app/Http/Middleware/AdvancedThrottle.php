<?php

namespace App\Http\Middleware;

use App\Services\RateLimitService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvancedThrottle
{
    use ApiResponseTrait;

    protected RateLimitService $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'general', ?int $maxAttempts = null, ?int $decayMinutes = null): Response
    {
        $user = $request->user();
        $ip = $request->ip();

        // Default values from config
        $maxAttempts = $maxAttempts ?? config("auth.rate_limiting.{$action}.max_attempts", 60);
        $decayMinutes = $decayMinutes ?? config("auth.rate_limiting.{$action}.decay_minutes", 1);

        // Create composite key based on user and IP
        if ($user) {
            $key = $this->rateLimitService->getUserKey($user->id, $action);
        } else {
            $key = $this->rateLimitService->getIpKey($ip, $action);
        }

        // Check if rate limited
        if ($this->rateLimitService->isActionLimited($key, $maxAttempts, $decayMinutes)) {
            $seconds = $this->rateLimitService->limiter->availableIn($key);

            return $this->errorResponse(
                'Too many attempts. Please try again later.',
                [
                    'retry_after' => $seconds,
                    'retry_after_human' => $this->secondsToHuman($seconds)
                ],
                429
            );
        }

        // Hit the rate limiter
        $this->rateLimitService->hitAction($key, $decayMinutes);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $maxAttempts - $this->rateLimitService->limiter->attempts($key));

        return $response;
    }

    /**
     * Convert seconds to human readable format
     */
    protected function secondsToHuman(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = round($seconds / 60);
        return "{$minutes} minute" . ($minutes > 1 ? 's' : '');
    }
}
