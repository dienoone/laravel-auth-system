<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class RoleController extends Controller
{
    use ApiResponseTrait;

    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search']);
            $roles = $this->roleService->getAllRoles($filters);

            return $this->successResponse('Roles retrieved successfully.', [
                'roles' => $roles
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve roles: ' . $e->getMessage());

            return $this->errorResponse('Failed to retrieve roles.');
        }
    }

    /**
     * Store a newly created role
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->createRole($request->validated());

            return $this->successResponse(
                'Role created successfully.',
                ['role' => $role],
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create role: ' . $e->getMessage());

            return $this->errorResponse('Failed to create role.');
        }
    }

    /**
     * Display the specified role
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->load(['permissions', 'users']);

            return $this->successResponse('Role retrieved successfully.', [
                'role' => $role
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve role: ' . $e->getMessage());

            return $this->errorResponse('Failed to retrieve role.');
        }
    }

    /**
     * Update the specified role
     */
    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        try {
            $updatedRole = $this->roleService->updateRole($role, $request->validated());

            return $this->successResponse('Role updated successfully.', [
                'role' => $updatedRole
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update role: ' . $e->getMessage());

            return $this->errorResponse('Failed to update role.');
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $this->roleService->deleteRole($role);

            return $this->successResponse('Role deleted successfully.');
        } catch (Exception $e) {
            Log::error('Failed to delete role: ' . $e->getMessage());

            return $this->errorResponse($e->getMessage());
        }
    }
}
