<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Helper;

class HelperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Helpers assist drivers on container hauling. Linked to drivers via drivers.helper_id.
     */
    public function run(): void
    {
        $helpers = [
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'contact_number' => '+63 912 345 6789', 'is_active' => 1],
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '+63 917 234 5678', 'is_active' => 1],
            ['first_name' => 'Jose', 'last_name' => 'Reyes', 'contact_number' => '+63 918 345 6789', 'is_active' => 1],
            ['first_name' => 'Ana', 'last_name' => 'Garcia', 'contact_number' => '+63 919 456 7890', 'is_active' => 1],
            ['first_name' => 'Carlos', 'last_name' => 'Villanueva', 'contact_number' => '+63 920 567 8901', 'is_active' => 1],
            ['first_name' => 'Rosa', 'last_name' => 'Fernandez', 'contact_number' => '+63 921 678 9012', 'is_active' => 1],
            ['first_name' => 'Miguel', 'last_name' => 'Torres', 'contact_number' => '+63 922 789 0123', 'is_active' => 1],
            ['first_name' => 'Luz', 'last_name' => 'Ramirez', 'contact_number' => '+63 923 890 1234', 'is_active' => 1],
            ['first_name' => 'Pedro', 'last_name' => 'Cruz', 'contact_number' => '+63 924 901 2345', 'is_active' => 0],
            ['first_name' => 'Carmen', 'last_name' => 'Lopez', 'contact_number' => '+63 925 012 3456', 'is_active' => 1],
            ['first_name' => 'Roberto', 'last_name' => 'Mendoza', 'contact_number' => '+63 926 123 4567', 'is_active' => 1],
            ['first_name' => 'Elena', 'last_name' => 'Bautista', 'contact_number' => '+63 927 234 5678', 'is_active' => 1],
            ['first_name' => 'Antonio', 'last_name' => 'Torres', 'contact_number' => '+63 928 345 6789', 'is_active' => 0],
            ['first_name' => 'Teresa', 'last_name' => 'Dela Cruz', 'contact_number' => '+63 929 456 7890', 'is_active' => 0],
        ];

        foreach ($helpers as $helper) {
            Helper::updateOrCreate(
                ['contact_number' => $helper['contact_number']],
                $helper
            );
        }
    }
}









