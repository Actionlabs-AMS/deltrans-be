<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FleetTruck;

class FleetTruckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fleetTrucks = [
            [
                'plate_number' => 'ABC-1234',
                'condition' => 'Excellent',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'XYZ-5678',
                'condition' => 'Good',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'DEF-9012',
                'condition' => 'Good',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'GHI-3456',
                'condition' => 'Fair',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'JKL-7890',
                'condition' => 'Excellent',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'MNO-2345',
                'condition' => 'Good',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'PQR-6789',
                'condition' => 'Needs Repair',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'STU-0123',
                'condition' => 'Fair',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'VWX-4567',
                'condition' => 'Excellent',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'YZA-8901',
                'condition' => 'Good',
                'is_active' => 1,
            ],
            [
                'plate_number' => 'BCD-2345',
                'condition' => 'Needs Repair',
                'is_active' => 0,
            ],
            [
                'plate_number' => 'EFG-5678',
                'condition' => 'Fair',
                'is_active' => 0,
            ],
            [
                'plate_number' => 'HIJ-9012',
                'condition' => 'Needs Repair',
                'is_active' => 0,
            ],
        ];

        foreach ($fleetTrucks as $truck) {
            FleetTruck::updateOrCreate(
                ['plate_number' => $truck['plate_number']],
                $truck
            );
        }
    }
}









