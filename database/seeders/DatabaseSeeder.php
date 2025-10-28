<?php

namespace Database\Seeders;

use Database\Seeders\Organization\OrganizationWithOUsSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\Auth\SysadmiRolesAndPermissionsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SysadmiRolesAndPermissionsSeeder::class,
            OrganizationWithOUsSeeder::class,
        ]);
    }
}
