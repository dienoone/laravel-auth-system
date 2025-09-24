<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasPermissions
{
  /**
   * Check if user has all of the given permissions
   */
  public function hasAllPermissions(array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if (!$this->hasPermission($permission)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Check if user has any of the given permissions
   */
  public function hasAnyPermission(array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if ($this->hasPermission($permission)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if user has permission through wildcard
   * e.g., 'posts.*' matches 'posts.create', 'posts.edit', etc.
   */
  public function hasWildcardPermission(string $permission): bool
  {
    $userPermissions = $this->getAllPermissions()->pluck('slug');

    // Direct permission check
    if ($userPermissions->contains($permission)) {
      return true;
    }

    // Wildcard check
    $parts = explode('.', $permission);

    while (count($parts) > 0) {
      array_pop($parts);
      $wildcard = implode('.', $parts) . '.*';

      if ($userPermissions->contains($wildcard)) {
        return true;
      }
    }

    // Check if user has any wildcard that matches this permission
    foreach ($userPermissions as $userPermission) {
      if (str_ends_with($userPermission, '*')) {
        $pattern = str_replace('*', '.*', $userPermission);
        if (preg_match('/^' . $pattern . '$/', $permission)) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Get permissions by category
   */
  public function getPermissionsByCategory(string $category): Collection
  {
    return $this->getAllPermissions()->filter(function ($permission) use ($category) {
      return $permission->category === $category;
    });
  }

  /**
   * Grant permissions
   */
  public function grantPermissions(array $permissions): void
  {
    $permissionIds = \App\Models\Permission::whereIn('slug', $permissions)->pluck('id');
    $this->permissions()->syncWithoutDetaching($permissionIds);
  }

  /**
   * Revoke permissions
   */
  public function revokePermissions(array $permissions): void
  {
    $permissionIds = \App\Models\Permission::whereIn('slug', $permissions)->pluck('id');
    $this->permissions()->detach($permissionIds);
  }

  /**
   * Check if user has permission in specific context
   */
  public function hasContextPermission(string $permission, string $context, $resourceId = null): bool
  {
    // Check basic permission
    if (!$this->hasPermission($permission)) {
      return false;
    }

    // Check context-specific permission
    $contextPermission = "{$context}.{$permission}";
    if ($this->hasPermission($contextPermission)) {
      return true;
    }

    // Check resource-specific permission if needed
    if ($resourceId !== null) {
      $resourcePermission = "{$context}.{$resourceId}.{$permission}";
      return $this->hasPermission($resourcePermission);
    }

    return false;
  }
}
