<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            ['name' => 'View Users', 'slug' => 'view-users'],
            ['name' => 'Create Users', 'slug' => 'create-users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users'],
            ['name' => 'View Posts', 'slug' => 'view-posts'],
            ['name' => 'Create Posts', 'slug' => 'create-posts'],
            ['name' => 'Edit Posts', 'slug' => 'edit-posts'],
            ['name' => 'Delete Posts', 'slug' => 'delete-posts'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles'],
            ['name' => 'Manage Permissions', 'slug' => 'manage-permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system access'
        ]);

        $moderatorRole = Role::create([
            'name' => 'Moderator',
            'slug' => 'moderator',
            'description' => 'Can moderate content'
        ]);

        $userRole = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Regular user access'
        ]);

        // Assign permissions to roles
        $adminRole->permissions()->sync(Permission::all());

        $moderatorRole->permissions()->sync(
            Permission::whereIn('slug', [
                'view-users',
                'view-posts',
                'edit-posts',
                'delete-posts'
            ])->get()
        );

        $userRole->permissions()->sync(
            Permission::whereIn('slug', [
                'view-posts',
                'create-posts',
                'edit-posts'
            ])->get()
        );
    }
}
