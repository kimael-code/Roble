<?php

namespace Database\Seeders;

use App\Models\Security\Permission;
use Illuminate\Database\Seeder;

class TestPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds for testing environment.
     * 
     * Creates all necessary permissions that Observers require.
     * Disables observers to avoid circular dependency issues.
     */
    public function run(): void
    {
        // Disable observers to avoid circular dependency
        Permission::withoutEvents(function ()
        {
            $permissions = [
                // User permissions
                ['name' => 'create users', 'description' => 'Create users', 'guard_name' => 'web'],
                ['name' => 'update users', 'description' => 'Update users', 'guard_name' => 'web'],
                ['name' => 'delete users', 'description' => 'Delete users', 'guard_name' => 'web'],
                ['name' => 'disable users', 'description' => 'Disable users', 'guard_name' => 'web'],
                ['name' => 'enable users', 'description' => 'Enable users', 'guard_name' => 'web'],
                ['name' => 'view users', 'description' => 'View users', 'guard_name' => 'web'],

                // Role permissions
                ['name' => 'create new roles', 'description' => 'Create new roles', 'guard_name' => 'web'],
                ['name' => 'update roles', 'description' => 'Update roles', 'guard_name' => 'web'],
                ['name' => 'delete roles', 'description' => 'Delete roles', 'guard_name' => 'web'],
                ['name' => 'view roles', 'description' => 'View roles', 'guard_name' => 'web'],

                // Permission permissions
                ['name' => 'create new permissions', 'description' => 'Create new permissions', 'guard_name' => 'web'],
                ['name' => 'update permissions', 'description' => 'Update permissions', 'guard_name' => 'web'],
                ['name' => 'delete permissions', 'description' => 'Delete permissions', 'guard_name' => 'web'],
                ['name' => 'view permissions', 'description' => 'View permissions', 'guard_name' => 'web'],

                // Organization permissions
                ['name' => 'create organizations', 'description' => 'Create organizations', 'guard_name' => 'web'],
                ['name' => 'update organizations', 'description' => 'Update organizations', 'guard_name' => 'web'],
                ['name' => 'delete organizations', 'description' => 'Delete organizations', 'guard_name' => 'web'],
                ['name' => 'view organizations', 'description' => 'View organizations', 'guard_name' => 'web'],
            ];

            foreach ($permissions as $permission)
            {
                Permission::firstOrCreate(
                    ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                    ['description' => $permission['description']]
                );
            }
        });
    }
}
