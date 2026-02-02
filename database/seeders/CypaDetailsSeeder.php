<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CypaDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * CYPA = Container Yard / Port Area. Data reflects real Philippine port and yard locations.
     */
    public function run(): void
    {
        $cypaDetails = [
            [
                'name' => 'Manila International Container Terminal (MICT)',
                'address' => 'North Harbor, Port Area, Manila 1018',
                'contact_name' => 'Engr. Ricardo Santos',
                'contact_mobile' => '+63 917 123 4567',
                'landlines' => json_encode(['+63 2 8527 1234', '+63 2 8527 1235']),
                'location_type' => 'Port Area',
                'is_active' => 1,
            ],
            [
                'name' => 'South Harbor Container Terminal',
                'address' => 'South Harbor, Port Area, Manila 1018',
                'contact_name' => 'Ms. Lorna Garcia',
                'contact_mobile' => '+63 918 234 5678',
                'landlines' => json_encode(['+63 2 8528 2345']),
                'location_type' => 'Port Area',
                'is_active' => 1,
            ],
            [
                'name' => 'Manila Container Yard – Tondo',
                'address' => 'Tondo, Manila 1012',
                'contact_name' => 'Carlos Reyes',
                'contact_mobile' => '+63 919 345 6789',
                'landlines' => json_encode(['+63 2 8256 3456']),
                'location_type' => 'Container Yard',
                'is_active' => 1,
            ],
            [
                'name' => 'Manila Container Yard – Caloocan',
                'address' => 'Camarin Road, Caloocan City, Metro Manila',
                'contact_name' => 'Ana Cruz',
                'contact_mobile' => '+63 920 456 7890',
                'landlines' => json_encode(['+63 2 8365 4567', '+63 2 8365 4568']),
                'location_type' => 'Container Yard',
                'is_active' => 1,
            ],
            [
                'name' => 'Batangas Port Container Terminal',
                'address' => 'Brgy. Alangilan, Batangas City 4200',
                'contact_name' => 'Roberto Mendoza',
                'contact_mobile' => '+63 921 567 8901',
                'landlines' => json_encode(['+63 43 723 9012']),
                'location_type' => 'Port Area',
                'is_active' => 1,
            ],
            [
                'name' => 'Subic Bay Container Terminal',
                'address' => 'Subic Bay Freeport Zone, Zambales 2222',
                'contact_name' => 'Elena Bautista',
                'contact_mobile' => '+63 922 678 9012',
                'landlines' => json_encode(['+63 47 252 0123']),
                'location_type' => 'Port Area',
                'is_active' => 1,
            ],
            [
                'name' => 'Manila Container Yard – Navotas',
                'address' => 'Navotas, Metro Manila 1480',
                'contact_name' => 'Roberto Dela Cruz',
                'contact_mobile' => '+63 923 789 0123',
                'landlines' => json_encode(['+63 2 8283 5678']),
                'location_type' => 'Container Yard',
                'is_active' => 0,
            ],
            [
                'name' => 'Cavite Port Terminal',
                'address' => 'Cavite City, Cavite 4100',
                'contact_name' => 'Luis Fernandez',
                'contact_mobile' => '+63 924 890 1234',
                'landlines' => json_encode(['+63 46 431 2345']),
                'location_type' => 'Port Area',
                'is_active' => 0,
            ],
        ];

        foreach ($cypaDetails as $cypa) {
            DB::table('cypa_details')->updateOrInsert(
                ['name' => $cypa['name']],
                array_merge($cypa, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

