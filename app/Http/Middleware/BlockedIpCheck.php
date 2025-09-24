<?php

namespace App\Http\Middleware;

use App\Services\RateLimitService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockedIpCheck
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
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($this->rateLimitService->isIpBlocked($ip)) {
            $blockInfo = $this->rateLimitService->getBlockedIpInfo($ip);

            return $this->errorResponse(
                'Your IP address has been temporarily blocked due to ' . ($blockInfo['reason'] ?? 'suspicious activity') . '.',
                [
                    'blocked_until' => $blockInfo['blocked_until'] ?? null,
                    'reason' => $blockInfo['reason'] ?? 'Unknown'
                ],
                429
            );
        }

        return $next($request);
    }
}
