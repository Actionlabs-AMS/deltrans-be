<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs permissions, roles, navigations, role-permission links, languages, and options
 * in the order required by foreign keys and RolePermissionSeeder.
 */
class CoreAppSeeders extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            NavigationSeeder::class,
            RolePermissionSeeder::class,
            LanguageSeeder::class,
            OptionSeeder::class,
        ]);
    }
}
//php artisan db:seed --class=CoreAppSeeders
