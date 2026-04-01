<?php

namespace Database\Seeders;

use App\Models\FleetTruck;
use Illuminate\Database\Seeder;

class FleetTruckProductionSeeder extends Seeder
{
    public function run(): void
    {
        $fleetTrucks = [
            ['plate_number' => 'NET2079'],
            ['plate_number' => 'MAP6422'],
            ['plate_number' => 'MAP6474'],
            ['plate_number' => 'NBL6716'],
            ['plate_number' => 'NBL6717'],
            ['plate_number' => 'MAF5152'],
            ['plate_number' => 'CAD4507'],
            ['plate_number' => 'NCK7371'],
            ['plate_number' => 'NIR847'],
            ['plate_number' => 'TCN314'],
            ['plate_number' => 'TYA960'],
            ['plate_number' => 'TXV269'],
            ['plate_number' => 'ULD956'],
            ['plate_number' => 'UVC378'],
            ['plate_number' => 'UVC615'],
            ['plate_number' => 'UVC689'],
            ['plate_number' => 'ABB5761'],
            ['plate_number' => 'ZFF956'],
            ['plate_number' => 'XTZ568'],
            ['plate_number' => 'WKZ814'],
            ['plate_number' => 'RLK766'],
            ['plate_number' => 'UVC388'],
            ['plate_number' => 'UVU760'],
            ['plate_number' => 'RGY688'],
            ['plate_number' => 'AAQ9158'],
            ['plate_number' => 'CCR9149'],
            ['plate_number' => 'NCK6498'],
            ['plate_number' => 'AMA2723'],
            ['plate_number' => 'UDG255', 'condition' => 'FOR DISPOSE', 'is_active' => 0],
        ];

        foreach ($fleetTrucks as $truck) {
            $plateNumber = trim((string) ($truck['plate_number'] ?? ''));

            if ($plateNumber === '' || mb_strlen($plateNumber) < 3) {
                continue;
            }

            FleetTruck::updateOrCreate(
                ['plate_number' => $plateNumber],
                [
                    'plate_number' => $plateNumber,
                    'condition' => $truck['condition'] ?? null,
                    'is_active' => $truck['is_active'] ?? 1,
                ]
            );
        }
    }
}

