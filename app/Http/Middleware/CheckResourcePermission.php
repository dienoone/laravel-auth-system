<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     * Check permission based on resource ownership or specific rules
     */
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        if (!$request->user()) {
            return $this->unauthorizedResponse();
        }

        $user = $request->user();
        $permission = "{$resource}.{$action}";

        // Check if user has the permission
        if (!$user->hasPermission($permission)) {
            // Check for wildcard permission
            if (!$user->hasPermission("{$resource}.*") && !$user->hasPermission("*")) {
                return $this->forbiddenResponse("You don't have permission to {$action} {$resource}.");
            }
        }

        // Check resource-specific rules
        if ($resourceId = $request->route($resource)) {
            // Here you can add model-specific checks
            // For example, check if user owns the resource
            $request->attributes->add(['resource_permission_checked' => true]);
        }

        return $next($request);
    }
}
