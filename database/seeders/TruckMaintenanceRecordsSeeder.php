<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\FleetTruck;
use Carbon\Carbon;

class TruckMaintenanceRecordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Fetch existing Plate Numbers
        // This is crucial for linking maintenance records to actual trucks
        $plateNumbers = FleetTruck::pluck('plate_number')->toArray();

        // Check if any trucks exist. If not, stop the seeder.
        if (empty($plateNumbers)) {
            $this->command->warn('No trucks found. Skipping Truck Maintenance Records seeding.');
            return;
        }

        $faker = \Faker\Factory::create();
        $date = Carbon::now();

        $articles = [
            'Engine Oil Change',
            'Tire Replacement',
            'Brake Pad Service',
            'Air Filter Replacement',
            'Fuel Filter Replacement',
            'Transmission Fluid Flush',
            'Battery Check',
            'Coolant Flush',
        ];

        for ($i = 1; $i <= 50; $i++) {
            $randomPlate = $faker->randomElement($plateNumbers);
            $maintenanceDate = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
            $createdAt = Carbon::parse($maintenanceDate)->addHours($faker->numberBetween(1, 24));
            $receiptNumber = 'RCP-' . now()->format('Ymd') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            DB::table('fleet_truck_maintenance_history')->updateOrInsert(
                ['receipt_number' => $receiptNumber],
                [
                    'receipt_number' => $receiptNumber,
                    'article' => $faker->randomElement($articles),
                    'quantity' => $faker->numberBetween(1, 4),
                    'price' => $faker->randomFloat(2, 50, 5000),
                    'maintenance_date' => $maintenanceDate,
                    'fleet_truck_plate_number' => $randomPlate,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                    'deleted_at' => null,
                ]
            );
        }
    }
}