<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\FleetTruck;
use App\Models\Helper;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available truck plate numbers from fleet_trucks table
        $truckPlateNumbers = FleetTruck::where('is_active', 1)
            ->pluck('plate_number')
            ->toArray();

        // Get available helper IDs from helpers table
        $helperIds = Helper::where('is_active', 1)
            ->pluck('id')
            ->toArray();

        $drivers = [
            [
                'first_name' => 'Ramon',
                'last_name' => 'Gutierrez',
                'contact_number' => '+63 928 345 6789',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 0, 1),
                'stack_run' => null,
                'helpers_id' => array_slice($helperIds, 0, 2),
            ],
            [
                'first_name' => 'Fernando',
                'last_name' => 'Alvarez',
                'contact_number' => '+63 929 456 7890',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 1, 1),
                'stack_run' => null,
                'helpers_id' => array_slice($helperIds, 2, 2),
            ],
            [
                'first_name' => 'Ricardo',
                'last_name' => 'Morales',
                'contact_number' => '+63 930 567 8901',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 2, 1),
                'stack_run' => ['Route A', 'Route B'],
                'helpers_id' => array_slice($helperIds, 4, 2),
            ],
            [
                'first_name' => 'Antonio',
                'last_name' => 'Castillo',
                'contact_number' => '+63 931 678 9012',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 3, 2),
                'stack_run' => ['Route C'],
                'helpers_id' => array_slice($helperIds, 6, 1),
            ],
            [
                'first_name' => 'Manuel',
                'last_name' => 'Rivera',
                'contact_number' => '+63 932 789 0123',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 5, 1),
                'stack_run' => null,
                'helpers_id' => array_slice($helperIds, 7, 2),
            ],
            [
                'first_name' => 'Francisco',
                'last_name' => 'Ortiz',
                'contact_number' => '+63 933 890 1234',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 6, 1),
                'stack_run' => ['Route D', 'Route E'],
                'helpers_id' => array_slice($helperIds, 9, 1),
            ],
            [
                'first_name' => 'Eduardo',
                'last_name' => 'Martinez',
                'contact_number' => '+63 934 901 2345',
                'is_active' => 0,
                'assigned_truck_plate_numbers' => null,
                'stack_run' => null,
                'helpers_id' => null,
            ],
            [
                'first_name' => 'Alberto',
                'last_name' => 'Sanchez',
                'contact_number' => '+63 935 012 3456',
                'is_active' => 1,
                'assigned_truck_plate_numbers' => array_slice($truckPlateNumbers, 7, 1),
                'stack_run' => null,
                'helpers_id' => array_slice($helperIds, 10, 1),
            ],
            [
                'first_name' => 'Jose',
                'last_name' => 'Torres',
                'contact_number' => '+63 936 123 4567',
                'is_active' => 0,
                'assigned_truck_plate_numbers' => null,
                'stack_run' => null,
                'helpers_id' => null,
            ],
            [
                'first_name' => 'Miguel',
                'last_name' => 'Ramos',
                'contact_number' => '+63 937 234 5678',
                'is_active' => 0,
                'assigned_truck_plate_numbers' => null,
                'stack_run' => null,
                'helpers_id' => null,
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::updateOrCreate(
                ['contact_number' => $driver['contact_number']],
                $driver
            );
        }
    }
}

