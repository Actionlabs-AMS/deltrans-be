<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CypaDetailsProductionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Container Yards (from attachment)
            [
                'name' => 'NCT TRANSNATIONAL CORP',
                'short_name' => 'NCT',
                'address' => 'TAWILIS ST. COR. DAGAT-DAGATAN AVE. BRGY 28, DISTRICT 28, DISTRICT 2, 1400 CALOOCAN CITY, NCR, THIRD DISTRICT, PHILS',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'TRANS-OCEAN CONTAINER SERVICES (PHILS) INN.',
                'short_name' => 'TOCSI',
                'address' => 'UNIT-1409 14TH FLR,. FEDERAL TOWER, DASMARINAS ST., BRGY 282, ZONE 26, 1010 SAN NICOLAS, NCR CITY MANILA, FIRST DISTRICT, PHILS',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'BRIGHTPOINT LOGISTICS CORP',
                'short_name' => 'BRIGHTPOINT',
                'address' => 'MANILA HARBOUR CENTER',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'ESAFE',
                'short_name' => 'ESAFE',
                'address' => 'MANILA HARBOUR CENTER',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'OCEANBOX',
                'short_name' => 'OCEANBOX',
                'address' => 'MANILA HARBOUR CENTER',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'SEACONTAINER DEPOT CORPORATION',
                'short_name' => 'SEACON',
                'address' => '1090TAS HONORIO LOPEZ BLVD, NAVOTAS CITY',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'IRS',
                'short_name' => 'IRS',
                'address' => 'MANILA HARBOUR CENTER',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'IRIS',
                'short_name' => 'IRIS',
                'address' => 'RADIAL RD #10 BRGY 128 TONDO II MANILA',
                'location_type' => 'Container Yard',
            ],
            [
                'name' => 'MEDLOG',
                'short_name' => 'MEDLOG',
                'address' => 'RADIAL RD #10 BRGY 128 TONDO II MANILA',
                'location_type' => 'Container Yard',
            ],

            // Ports / Port Areas (from attachment)
            [
                'name' => 'MANILA INTERNATIONAL CONTAINER TERMINAL',
                'short_name' => 'MIP',
                'address' => 'MICT S Access Rd, Tondo, Manila, 1012 Metro Manila',
                'location_type' => 'Port Area',
            ],
            [
                'name' => 'MANILA NORTH HABOUR PORT INC.',
                'short_name' => 'MNHPI',
                'address' => 'Manila Harbour Center Vitas St, Bgy 128, Zone 010, R-10, Tondo, Manila',
                'location_type' => 'Port Area',
            ],
            [
                'name' => 'ASIAN TERMINAL INC.',
                'short_name' => 'SOUTH',
                'address' => 'Bldg A, Bonifacio Dr, Port Area, Manila, 1018 Metro Manila',
                'location_type' => 'Port Area',
            ],

            // Additional CY/Port codes referenced by fixed expenses (no address provided in attachments)
            ['name' => 'ECD', 'short_name' => 'ECD', 'address' => null, 'location_type' => null],
            ['name' => 'PIER16PSACC', 'short_name' => 'PIER16PSACC', 'address' => null, 'location_type' => null],
            ['name' => 'PIER16LORENZO', 'short_name' => 'PIER16LORENZO', 'address' => null, 'location_type' => null],
            ['name' => 'SMY', 'short_name' => 'SMY', 'address' => null, 'location_type' => null],
            ['name' => 'PIER16TRANSASIA', 'short_name' => 'PIER16TRANSASIA', 'address' => null, 'location_type' => null],
            ['name' => 'CAVITE', 'short_name' => 'CAVITE', 'address' => null, 'location_type' => null],
            ['name' => 'MARINA', 'short_name' => 'MARINA', 'address' => null, 'location_type' => null],
            ['name' => 'MILT', 'short_name' => 'MILT', 'address' => null, 'location_type' => null],
        ];

        foreach ($rows as $row) {
            DB::table('cypa_details')->updateOrInsert(
                ['name' => $row['name']],
                array_merge($row, [
                    'address' => $row['address'] ?? null,
                    'contact_name' => null,
                    'contact_mobile' => null,
                    'landlines' => null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

