<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMultiplePermissions
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     * Usage: 'permissions:all,permission1,permission2' or 'permissions:any,permission1,permission2'
     */
    public function handle(Request $request, Closure $next, string $type, ...$permissions): Response
    {
        if (!$request->user()) {
            return $this->unauthorizedResponse();
        }

        $hasPermission = match ($type) {
            'all' => $request->user()->hasAllPermissions($permissions),
            'any' => $request->user()->hasAnyPermission($permissions),
            default => false
        };

        if (!$hasPermission) {
            return $this->forbiddenResponse(
                $type === 'all'
                    ? 'You must have all required permissions to access this resource.'
                    : 'You must have at least one of the required permissions to access this resource.'
            );
        }

        return $next($request);
    }
}
