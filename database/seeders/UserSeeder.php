<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Helpers\PasswordHelper;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        
        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
            return;
        }

        // Create Super Admin user
        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'admin123');
        
        $superAdmin = User::updateOrCreate(
            ['user_email' => 'admin@basecode.com'],
            [
                'user_login' => 'admin',
                'user_email' => 'admin@basecode.com',
                'user_pass' => $password,
                'user_salt' => $salt,
                'user_status' => 1,
                'user_activation_key' => null,
            ]
        );

        // Save user role in user_meta
        $superAdmin->saveUserMeta([
            'user_role' => json_encode([
                'id' => $superAdminRole->id,
                'name' => $superAdminRole->name
            ])
        ]);

        // Create additional test users
        $testUsers = [
            [
                'user_login' => 'editor',
                'user_email' => 'editor@basecode.com',
                'password' => 'editor123',
                'role' => 'Editor'
            ],
            [
                'user_login' => 'viewer',
                'user_email' => 'viewer@basecode.com',
                'password' => 'viewer123',
                'role' => 'Viewer'
            ]
        ];

        foreach ($testUsers as $userData) {
            $role = Role::where('name', $userData['role'])->first();
            if (!$role) continue;

            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, $userData['password']);
            
            $user = User::updateOrCreate(
                ['user_email' => $userData['user_email']],
                [
                    'user_login' => $userData['user_login'],
                    'user_email' => $userData['user_email'],
                    'user_pass' => $password,
                    'user_salt' => $salt,
                    'user_status' => 1,
                    'user_activation_key' => null,
                ]
            );

            // Save user role in user_meta
            $user->saveUserMeta([
                'user_role' => json_encode([
                    'id' => $role->id,
                    'name' => $role->name
                ])
            ]);
        }
    }
}
