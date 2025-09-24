<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionCheckController extends Controller
{
    use ApiResponseTrait;

    /**
     * Check if user has specific permissions
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
            'check_type' => ['sometimes', 'in:all,any'] // default: any
        ]);

        $user = $request->user();
        $permissions = $request->permissions;
        $checkType = $request->input('check_type', 'any');

        $results = [];
        foreach ($permissions as $permission) {
            $results[$permission] = $user->hasPermission($permission);
        }

        $hasPermission = $checkType === 'all'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);

        return $this->successResponse('Permission check completed.', [
            'has_permission' => $hasPermission,
            'check_type' => $checkType,
            'results' => $results,
            'user_permissions' => $user->getAllPermissions()->pluck('slug')
        ]);
    }

    /**
     * Get user's permissions grouped by category
     */
    public function userPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions();

        $grouped = $permissions->groupBy('category')->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug
                ];
            });
        });

        return $this->successResponse('User permissions retrieved.', [
            'permissions' => $permissions->pluck('slug'),
            'grouped_permissions' => $grouped,
            'total_permissions' => $permissions->count()
        ]);
    }
}
