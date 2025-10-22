<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'active' => true,
                'is_super_admin' => true,
            ],
            [
                'name' => 'Admin',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Editor',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Viewer',
                'active' => true,
                'is_super_admin' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
