<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\FleetTruck; // Assuming you have a Truck model
use Illuminate\Support\Str;
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

        $records = [];
        $faker = \Faker\Factory::create();
        $date = Carbon::now();

        for ($i = 1; $i <= 50; $i++) {
            // Pick a random plate number from the existing trucks
            $randomPlate = $faker->randomElement($plateNumbers);

            // Determine a random date for maintenance (in the past 365 days)
            $maintenanceDate = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
            
            // Randomly select a creation date close to the maintenance date
            $createdAt = Carbon::parse($maintenanceDate)->addHours($faker->numberBetween(1, 24));
            
            $records[] = [
                'receipt_number' => 'RECEIPT-' . Str::upper(Str::random(8)) . $i,
                'article' => $faker->randomElement([
                    'Engine Oil Change', 
                    'Tire Replacement', 
                    'Brake Pad Service', 
                    'Air Filter Replacement', 
                    'Fuel Filter Replacement',
                    'Transmission Fluid Flush'
                ]),
                'quantity' => $faker->numberBetween(1, 4),
                // Generate a random price between 50 and 5000
                'price' => $faker->randomFloat(2, 50, 5000), 
                'maintenance_date' => $maintenanceDate,
                'fleet_truck_plate_number' => $randomPlate,
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
                'deleted_at' => null,
            ];
        }

        // Insert the data into the database table
        DB::table('fleet_truck_maintenance_history')->insert($records);
    }
}