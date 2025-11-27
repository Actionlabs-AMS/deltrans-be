<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CypaDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cypaDetails = [
            [
                'name' => 'Manila International Container Terminal',
                'address' => 'North Harbor, Manila, Philippines',
                'contact_name' => 'John Santos',
                'contact_mobile' => '+63 917 123 4567',
                'landlines' => json_encode(['+63 2 1234 5678', '+63 2 1234 5679']),
                'location_type' => 'Port Area',
            ],
            [
                'name' => 'South Harbor Container Terminal',
                'address' => 'South Harbor, Manila, Philippines',
                'contact_name' => 'Maria Garcia',
                'contact_mobile' => '+63 918 234 5678',
                'landlines' => json_encode(['+63 2 2345 6789']),
                'location_type' => 'Port Area',
            ],
            [
                'name' => 'Manila Container Yard A',
                'address' => 'Tondo, Manila, Philippines',
                'contact_name' => 'Carlos Reyes',
                'contact_mobile' => '+63 919 345 6789',
                'landlines' => json_encode(['+63 2 3456 7890']),
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'Manila Container Yard B',
                'address' => 'Caloocan, Metro Manila, Philippines',
                'contact_name' => 'Ana Cruz',
                'contact_mobile' => '+63 920 456 7890',
                'landlines' => json_encode(['+63 2 4567 8901']),
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'Batangas Port Container Terminal',
                'address' => 'Batangas City, Batangas, Philippines',
                'contact_name' => 'Roberto Mendoza',
                'contact_mobile' => '+63 921 567 8901',
                'landlines' => json_encode(['+63 43 5678 9012']),
                'location_type' => 'Port Area',
            ],
            [
                'name' => 'Subic Bay Container Terminal',
                'address' => 'Subic Bay Freeport Zone, Zambales, Philippines',
                'contact_name' => 'Elena Bautista',
                'contact_mobile' => '+63 922 678 9012',
                'landlines' => json_encode(['+63 47 6789 0123']),
                'location_type' => 'Port Area',
            ],
        ];

        foreach ($cypaDetails as $cypa) {
            DB::table('cypa_details')->updateOrInsert(
                ['name' => $cypa['name']],
                $cypa
            );
        }
    }
}

