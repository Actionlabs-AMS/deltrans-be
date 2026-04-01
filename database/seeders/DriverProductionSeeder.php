<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Helper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DriverProductionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['plate_number' => 'NET2079', 'driver_name' => 'Antonio Toraja', 'helper_name' => 'Edrian Balingit'],
            ['plate_number' => 'MAP6422', 'driver_name' => 'Elmer Toraja', 'helper_name' => 'John Elecio Toraja'],
            ['plate_number' => 'MAP6474', 'driver_name' => 'Mike Acabal', 'helper_name' => 'Isidro Cojo'],
            ['plate_number' => 'NBL6716', 'driver_name' => 'Rewil Escobillo', 'helper_name' => 'Renold Toraja'],
            ['plate_number' => 'NBL6717', 'driver_name' => 'Romy Gallego', 'helper_name' => 'Rogelio Loiterrez'],
            ['plate_number' => 'MAF5152', 'driver_name' => 'Melvin Longyapon', 'helper_name' => 'Richard Escobillo'],
            ['plate_number' => 'CAD4507', 'driver_name' => 'Ronel Zamora', 'helper_name' => 'Mark Anthony Rubia'],
            ['plate_number' => 'NCK7371', 'driver_name' => 'Rey Cojo', 'helper_name' => 'Renanté Del Rosario'],
            ['plate_number' => 'NIR847', 'driver_name' => 'Dionisio Casabar', 'helper_name' => 'Ronald Rosario'],
            ['plate_number' => 'TCN314', 'driver_name' => 'Nobelito Gonato', 'helper_name' => 'Michael Caling'],
            ['plate_number' => 'TYA960', 'driver_name' => 'Jefferson Aguado', 'helper_name' => 'Ernesto Ginambayan'],
            ['plate_number' => 'TXV269', 'driver_name' => 'Sonny boy Poblete', 'helper_name' => 'Melgar Delos Angeles'],
            ['plate_number' => 'ULD956', 'driver_name' => 'Edgar Asis', 'helper_name' => 'Marlon Valenzuela'],
            ['plate_number' => 'UVC378', 'driver_name' => 'Regin Cadelina', 'helper_name' => 'Remark Albasin'],
            ['plate_number' => 'UVC615', 'driver_name' => 'Federico Gayares', 'helper_name' => 'Jerome Carolino'],
            ['plate_number' => 'UVC689', 'driver_name' => 'Rolando Padrones', 'helper_name' => 'Jemark Hanio'],
            ['plate_number' => 'ABB5761', 'driver_name' => 'Pablito Hanio', 'helper_name' => 'Patrick Hanio'],
            ['plate_number' => 'ZFF956', 'driver_name' => 'Jasredion Perater Macion', 'helper_name' => 'Marvin Seno'],
            ['plate_number' => 'XTZ568', 'driver_name' => 'Genoldren Salumag', 'helper_name' => 'Jovel Serbelita'],
            ['plate_number' => 'WKZ814', 'driver_name' => 'Ricky Opaño', 'helper_name' => 'Romel Tulib'],
            ['plate_number' => 'RLK766', 'driver_name' => 'Edryjon Espina', 'helper_name' => 'Chester Espina'],
            ['plate_number' => 'UVC388', 'driver_name' => 'Tony Valdez', 'helper_name' => 'Redzkan B. Dalmasa'],
            ['plate_number' => 'UVU760', 'driver_name' => 'Reynaldo Torreon', 'helper_name' => null],
            ['plate_number' => 'XTZ568', 'driver_name' => 'Armando Abuhan', 'helper_name' => 'Aldrin Husana'],
            ['plate_number' => 'RGY688', 'driver_name' => 'Arlo Opano', 'helper_name' => 'Bayani Castillano'],
            ['plate_number' => 'CCR9149', 'driver_name' => 'Jerome Lariosa Cariño', 'helper_name' => 'Rollen Jade Mepranum'],
            ['plate_number' => 'NCK6498', 'driver_name' => 'Efren Dayot', 'helper_name' => 'Adrian Lalaguna'],
        ];

        foreach ($rows as $row) {
            $driverName = trim((string) ($row['driver_name'] ?? ''));
            if ($driverName === '') {
                continue;
            }

            [$firstName, $lastName] = $this->splitName($driverName);

            $helperId = null;
            $helperName = $row['helper_name'] ?? null;
            if (is_string($helperName) && trim($helperName) !== '') {
                [$helperFirst, $helperLast] = $this->splitName($helperName);
                $helperId = Helper::where('first_name', $helperFirst)
                    ->where('last_name', $helperLast)
                    ->orderBy('id')
                    ->value('id');
            }

            $plate = trim((string) ($row['plate_number'] ?? ''));
            $assignedPlates = $plate !== '' ? [$plate] : null;

            Driver::updateOrCreate(
                ['contact_number' => $this->contactNumberFor($driverName)],
                Arr::whereNotNull([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'contact_number' => $this->contactNumberFor($driverName),
                    'assigned_truck_plate_numbers' => $assignedPlates,
                    'helper_id' => $helperId,
                    'is_active' => 1,
                ])
            );
        }
    }

    private function splitName(string $fullName): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);
        $parts = preg_split('/\s+/', $normalized) ?: [$normalized];

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function contactNumberFor(string $seed): string
    {
        $hash = abs(crc32(Str::lower(trim($seed))));
        $n = 910000000 + ($hash % 100000000); // 9xxxxxxxx (different range than helpers)
        $digits = str_pad((string) $n, 9, '0', STR_PAD_LEFT);

        return '+63 '.$digits;
    }
}

