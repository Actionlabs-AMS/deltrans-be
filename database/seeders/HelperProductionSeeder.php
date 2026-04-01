<?php

namespace Database\Seeders;

use App\Models\Helper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HelperProductionSeeder extends Seeder
{
    public function run(): void
    {
        $helperNames = [
            'Edrian Balingit',
            'John Elecio Toraja',
            'Isidro Cojo',
            'Renold Toraja',
            'Rogelio Loiterrez',
            'Richard Escobillo',
            'Mark Anthony Rubia',
            'Renanté Del Rosario',
            'Ronald Rosario',
            'Michael Caling',
            'Ernesto Ginambayan',
            'Melgar Delos Angeles',
            'Marlon Valenzuela',
            'Remark Albasin',
            'Jerome Carolino',
            'Jemark Hanio',
            'Patrick Hanio',
            'Marvin Seno',
            'Jovel Serbelita',
            'Romel Tulib',
            'Chester Espina',
            'Redzkan B. Dalmasa',
            'Aldrin Husana',
            'Bayani Castillano',
            'Rollen Jade Mepranum',
            'Adrian Lalaguna',
        ];

        foreach ($helperNames as $fullName) {
            [$firstName, $lastName] = $this->splitName($fullName);

            Helper::updateOrCreate(
                ['contact_number' => $this->contactNumberFor($fullName)],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'contact_number' => $this->contactNumberFor($fullName),
                    'is_active' => 1,
                ]
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
        $n = 900000000 + ($hash % 100000000); // 9xxxxxxxx
        $digits = str_pad((string) $n, 9, '0', STR_PAD_LEFT);

        return '+63 '.$digits;
    }
}

