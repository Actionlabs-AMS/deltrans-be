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
                'status' => 1,
            ],
            [
                'plate_number' => 'XYZ-5678',
                'condition' => 'Good',
                'status' => 1,
            ],
            [
                'plate_number' => 'DEF-9012',
                'condition' => 'Good',
                'status' => 1,
            ],
            [
                'plate_number' => 'GHI-3456',
                'condition' => 'Fair',
                'status' => 1,
            ],
            [
                'plate_number' => 'JKL-7890',
                'condition' => 'Excellent',
                'status' => 1,
            ],
            [
                'plate_number' => 'MNO-2345',
                'condition' => 'Good',
                'status' => 1,
            ],
            [
                'plate_number' => 'PQR-6789',
                'condition' => 'Needs Repair',
                'status' => 1,
            ],
            [
                'plate_number' => 'STU-0123',
                'condition' => 'Fair',
                'status' => 1,
            ],
            [
                'plate_number' => 'VWX-4567',
                'condition' => 'Excellent',
                'status' => 1,
            ],
            [
                'plate_number' => 'YZA-8901',
                'condition' => 'Good',
                'status' => 1,
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









