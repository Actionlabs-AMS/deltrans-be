<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FleetTruck;

class FleetTruckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Philippine plate format: 3 letters + 4 digits (e.g. ABC 1234). Fleet trucks for container hauling.
     */
    public function run(): void
    {
        $fleetTrucks = [
            ['plate_number' => 'NAA 1123', 'condition' => 'Excellent', 'is_active' => 1],
            ['plate_number' => 'NAB 2456', 'condition' => 'Good', 'is_active' => 1],
            ['plate_number' => 'NAC 3789', 'condition' => 'Good', 'is_active' => 1],
            ['plate_number' => 'NAD 4012', 'condition' => 'Fair', 'is_active' => 1],
            ['plate_number' => 'NAE 5234', 'condition' => 'Excellent', 'is_active' => 1],
            ['plate_number' => 'NAF 6567', 'condition' => 'Good', 'is_active' => 1],
            ['plate_number' => 'NAG 7890', 'condition' => 'Needs Repair', 'is_active' => 1],
            ['plate_number' => 'NAH 8123', 'condition' => 'Fair', 'is_active' => 1],
            ['plate_number' => 'NAI 9345', 'condition' => 'Excellent', 'is_active' => 1],
            ['plate_number' => 'NAJ 0456', 'condition' => 'Good', 'is_active' => 1],
            ['plate_number' => 'NAK 1567', 'condition' => 'Good', 'is_active' => 1],
            ['plate_number' => 'NAL 2678', 'condition' => 'Needs Repair', 'is_active' => 0],
            ['plate_number' => 'NAM 3789', 'condition' => 'Fair', 'is_active' => 0],
            ['plate_number' => 'NAN 4890', 'condition' => 'Needs Repair', 'is_active' => 0],
        ];

        foreach ($fleetTrucks as $truck) {
            FleetTruck::updateOrCreate(
                ['plate_number' => $truck['plate_number']],
                $truck
            );
        }
    }
}









