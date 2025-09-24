<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Models\User;
use App\Services\RoleService;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class UserRoleController extends Controller
{
    use ApiResponseTrait;

    protected RoleService $roleService;
    protected PermissionService $permissionService;

    public function __construct(
        RoleService $roleService,
        PermissionService $permissionService
    ) {
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
    }

    /**
     * Get user's roles and permissions
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['roles.permissions', 'permissions']);

            return $this->successResponse('User roles retrieved successfully.', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'direct_permissions' => $user->permissions,
                    'all_permissions' => $user->getAllPermissions()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user roles: ' . $e->getMessage());

            return $this->errorResponse('Failed to retrieve user roles.');
        }
    }

    /**
     * Sync user's roles
     */
    public function syncRoles(AssignRoleRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->roleService->syncRoles($user, $request->roles);

            return $this->successResponse('User roles updated successfully.', [
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'roles' => $updatedUser->roles,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update user roles: ' . $e->getMessage());

            return $this->errorResponse('Failed to update user roles.');
        }
    }

    /**
     * Add role to user
     */
    public function addRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role_id' => ['required', 'exists:roles,id']
        ]);

        try {
            $updatedUser = $this->roleService->assignRole($user, $request->role_id);

            return $this->successResponse('Role assigned successfully.', [
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'roles' => $updatedUser->roles,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to assign role: ' . $e->getMessage());

            return $this->errorResponse('Failed to assign role.');
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role_id' => ['required', 'exists:roles,id']
        ]);

        try {
            $updatedUser = $this->roleService->removeRole($user, $request->role_id);

            return $this->successResponse('Role removed successfully.', [
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'roles' => $updatedUser->roles,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to remove role: ' . $e->getMessage());

            return $this->errorResponse('Failed to remove role.');
        }
    }

    /**
     * Sync user's direct permissions
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        try {
            $updatedUser = $this->permissionService->syncPermissions($user, $request->permissions);

            return $this->successResponse('User permissions updated successfully.', [
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'permissions' => $updatedUser->permissions,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update user permissions: ' . $e->getMessage());

            return $this->errorResponse('Failed to update user permissions.');
        }
    }
}
