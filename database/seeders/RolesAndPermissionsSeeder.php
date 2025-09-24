<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions with categories
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'category' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'category' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'category' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'category' => 'users'],
            ['name' => 'Manage All Users', 'slug' => 'users.*', 'category' => 'users'],

            // Post Management
            ['name' => 'View Posts', 'slug' => 'posts.view', 'category' => 'posts'],
            ['name' => 'Create Posts', 'slug' => 'posts.create', 'category' => 'posts'],
            ['name' => 'Edit Posts', 'slug' => 'posts.edit', 'category' => 'posts'],
            ['name' => 'Delete Posts', 'slug' => 'posts.delete', 'category' => 'posts'],
            ['name' => 'Publish Posts', 'slug' => 'posts.publish', 'category' => 'posts'],
            ['name' => 'Manage All Posts', 'slug' => 'posts.*', 'category' => 'posts'],

            // System Management
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'category' => 'system'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'category' => 'system'],
            ['name' => 'View System Settings', 'slug' => 'system.settings.view', 'category' => 'system'],
            ['name' => 'Edit System Settings', 'slug' => 'system.settings.edit', 'category' => 'system'],
            ['name' => 'View Logs', 'slug' => 'system.logs.view', 'category' => 'system'],
            ['name' => 'Manage Everything', 'slug' => '*', 'category' => 'system'],
        ];

        // Clear existing permissions
        Permission::truncate();

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create or update roles
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'description' => 'Full system access']
        );

        $moderatorRole = Role::updateOrCreate(
            ['slug' => 'moderator'],
            ['name' => 'Moderator', 'description' => 'Can moderate content']
        );

        $editorRole = Role::updateOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editor', 'description' => 'Can manage posts']
        );

        $userRole = Role::updateOrCreate(
            ['slug' => 'user'],
            ['name' => 'User', 'description' => 'Regular user access']
        );

        // Assign permissions to roles
        $adminRole->permissions()->sync(
            Permission::where('slug', '*')->pluck('id')
        );

        $moderatorRole->permissions()->sync(
            Permission::whereIn('slug', [
                'users.view',
                'posts.*',
                'system.logs.view'
            ])->pluck('id')
        );

        $editorRole->permissions()->sync(
            Permission::whereIn('slug', [
                'posts.view',
                'posts.create',
                'posts.edit',
                'posts.publish'
            ])->pluck('id')
        );

        $userRole->permissions()->sync(
            Permission::whereIn('slug', [
                'posts.view',
                'posts.create'
            ])->pluck('id')
        );
    }
}
