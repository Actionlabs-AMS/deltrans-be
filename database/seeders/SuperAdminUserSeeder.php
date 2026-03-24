<?php

namespace Database\Seeders;

use App\Helpers\PasswordHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Create a single super admin user (same credentials as UserSeeder’s primary admin).
     * Run after RoleSeeder (and optionally RolePermissionSeeder).
     */
    public function run(): void
    {
        $superAdminRole = Role::where('is_super_admin', true)->first()
            ?? Role::where('name', 'Developer Account')->first()
            ?? Role::where('name', 'Super Admin')->first();

        if (! $superAdminRole) {
            $this->command?->error('Super Admin role not found. Run RoleSeeder first.');

            return;
        }

        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'admin123');

        User::updateOrCreate(
            ['user_email' => 'admin@deltrans.com'],
            [
                'user_login' => 'admin',
                'user_email' => 'admin@deltrans.com',
                'user_pass' => $password,
                'user_salt' => $salt,
                'user_status' => 1,
                'user_activation_key' => null,
                'role_id' => $superAdminRole->id,
            ]
        );
    }
}
