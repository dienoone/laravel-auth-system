<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleService
{
  /**
   * Get all roles with permissions
   */
  public function getAllRoles(array $filters = [])
  {
    $query = Role::with('permissions');

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
   * Create a new role
   */
  public function createRole(array $data): Role
  {
    return DB::transaction(function () use ($data) {
      $role = Role::create([
        'name' => $data['name'],
        'slug' => $data['slug'] ?? Str::slug($data['name']),
        'description' => $data['description'] ?? null,
      ]);

      if (isset($data['permissions'])) {
        $role->permissions()->sync($data['permissions']);
      }

      return $role->load('permissions');
    });
  }

  /**
   * Update a role
   */
  public function updateRole(Role $role, array $data): Role
  {
    return DB::transaction(function () use ($role, $data) {
      $role->update([
        'name' => $data['name'] ?? $role->name,
        'slug' => $data['slug'] ?? $role->slug,
        'description' => $data['description'] ?? $role->description,
      ]);

      if (isset($data['permissions'])) {
        $role->permissions()->sync($data['permissions']);
      }

      return $role->load('permissions');
    });
  }

  /**
   * Delete a role
   */
  public function deleteRole(Role $role): bool
  {
    // Prevent deletion of system roles
    $systemRoles = ['admin', 'user', 'moderator'];
    if (in_array($role->slug, $systemRoles)) {
      throw new \Exception('Cannot delete system roles.');
    }

    return $role->delete();
  }

  /**
   * Assign role to user
   */
  public function assignRole(User $user, $roleId): User
  {
    $role = Role::findOrFail($roleId);

    if (!$user->roles()->where('role_id', $roleId)->exists()) {
      $user->roles()->attach($roleId);
    }

    return $user->load('roles.permissions');
  }

  /**
   * Remove role from user
   */
  public function removeRole(User $user, $roleId): User
  {
    $user->roles()->detach($roleId);

    // Ensure user has at least the default role
    if ($user->roles()->count() === 0) {
      $defaultRole = Role::where('slug', 'user')->first();
      if ($defaultRole) {
        $user->roles()->attach($defaultRole);
      }
    }

    return $user->load('roles.permissions');
  }

  /**
   * Sync user roles
   */
  public function syncRoles(User $user, array $roleIds): User
  {
    // Ensure at least one role
    if (empty($roleIds)) {
      $defaultRole = Role::where('slug', 'user')->first();
      if ($defaultRole) {
        $roleIds = [$defaultRole->id];
      }
    }

    $user->roles()->sync($roleIds);

    return $user->load('roles.permissions');
  }
}
