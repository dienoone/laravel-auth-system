<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class PermissionController extends Controller
{
    use ApiResponseTrait;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of permissions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'category']);

            $query = Permission::query();

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('slug', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            $permissions = $query->orderBy('category')->orderBy('name')->get();
            $groupedPermissions = $permissions->groupBy('category');
            $categories = Permission::getCategories();

            return $this->successResponse('Permissions retrieved successfully.', [
                'permissions' => $permissions,
                'grouped_permissions' => $groupedPermissions,
                'categories' => $categories,
                'total' => $permissions->count()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve permissions: ' . $e->getMessage());

            return $this->errorResponse('Failed to retrieve permissions.');
        }
    }

    /**
     * Store a newly created permission
     */
    public function store(PermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());

            return $this->successResponse(
                'Permission created successfully.',
                ['permission' => $permission],
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create permission: ' . $e->getMessage());

            return $this->errorResponse('Failed to create permission.');
        }
    }

    /**
     * Display the specified permission
     */
    public function show(Permission $permission): JsonResponse
    {
        try {
            $permission->load(['roles', 'users']);

            return $this->successResponse('Permission retrieved successfully.', [
                'permission' => $permission
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve permission: ' . $e->getMessage());

            return $this->errorResponse('Failed to retrieve permission.');
        }
    }

    /**
     * Update the specified permission
     */
    public function update(PermissionRequest $request, Permission $permission): JsonResponse
    {
        try {
            $updatedPermission = $this->permissionService->updatePermission($permission, $request->validated());

            return $this->successResponse('Permission updated successfully.', [
                'permission' => $updatedPermission
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update permission: ' . $e->getMessage());

            return $this->errorResponse('Failed to update permission.');
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        try {
            $this->permissionService->deletePermission($permission);

            return $this->successResponse('Permission deleted successfully.');
        } catch (Exception $e) {
            Log::error('Failed to delete permission: ' . $e->getMessage());

            return $this->errorResponse('Failed to delete permission.');
        }
    }
}
