<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class PermissionService
{
  /**
   * Get all permissions
   */
  public function getAllPermissions(array $filters = [])
  {
    $query = Permission::query();

    if (isset($filters['search'])) {
      $query->where(function ($q) use ($filters) {
        $q->where('name', 'like', '%' . $filters['search'] . '%')
          ->orWhere('slug', 'like', '%' . $filters['search'] . '%')
          ->orWhere('description', 'like', '%' . $filters['search'] . '%');
      });
    }

    return $query->orderBy('name')->get();
  }

  /**
   * Create a new permission
   */
  public function createPermission(array $data): Permission
  {
    return Permission::create([
      'name' => $data['name'],
      'slug' => $data['slug'] ?? Str::slug($data['name']),
      'description' => $data['description'] ?? null,
    ]);
  }

  /**
   * Update a permission
   */
  public function updatePermission(Permission $permission, array $data): Permission
  {
    $permission->update([
      'name' => $data['name'] ?? $permission->name,
      'slug' => $data['slug'] ?? $permission->slug,
      'description' => $data['description'] ?? $permission->description,
    ]);

    return $permission;
  }

  /**
   * Delete a permission
   */
  public function deletePermission(Permission $permission): bool
  {
    return $permission->delete();
  }

  /**
   * Assign direct permission to user
   */
  public function assignPermission(User $user, $permissionId): User
  {
    if (!$user->permissions()->where('permission_id', $permissionId)->exists()) {
      $user->permissions()->attach($permissionId);
    }

    return $user->load('permissions');
  }

  /**
   * Remove direct permission from user
   */
  public function removePermission(User $user, $permissionId): User
  {
    $user->permissions()->detach($permissionId);

    return $user->load('permissions');
  }

  /**
   * Sync user direct permissions
   */
  public function syncPermissions(User $user, array $permissionIds): User
  {
    $user->permissions()->sync($permissionIds);

    return $user->load('permissions');
  }
}
