<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Helper;

class HelperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $helpers = [
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'contact_number' => '+63 912 345 6789',
                'active_status' => true,
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'contact_number' => '+63 917 234 5678',
                'active_status' => true,
            ],
            [
                'first_name' => 'Jose',
                'last_name' => 'Reyes',
                'contact_number' => '+63 918 345 6789',
                'active_status' => true,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Garcia',
                'contact_number' => '+63 919 456 7890',
                'active_status' => true,
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Villanueva',
                'contact_number' => '+63 920 567 8901',
                'active_status' => true,
            ],
            [
                'first_name' => 'Rosa',
                'last_name' => 'Fernandez',
                'contact_number' => '+63 921 678 9012',
                'active_status' => true,
            ],
            [
                'first_name' => 'Miguel',
                'last_name' => 'Torres',
                'contact_number' => '+63 922 789 0123',
                'active_status' => true,
            ],
            [
                'first_name' => 'Luz',
                'last_name' => 'Ramirez',
                'contact_number' => '+63 923 890 1234',
                'active_status' => true,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Cruz',
                'contact_number' => '+63 924 901 2345',
                'active_status' => false,
            ],
            [
                'first_name' => 'Carmen',
                'last_name' => 'Lopez',
                'contact_number' => '+63 925 012 3456',
                'active_status' => true,
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Mendoza',
                'contact_number' => '+63 926 123 4567',
                'active_status' => true,
            ],
            [
                'first_name' => 'Elena',
                'last_name' => 'Bautista',
                'contact_number' => '+63 927 234 5678',
                'active_status' => true,
            ],
        ];

        foreach ($helpers as $helper) {
            Helper::updateOrCreate(
                ['contact_number' => $helper['contact_number']],
                $helper
            );
        }
    }
}









