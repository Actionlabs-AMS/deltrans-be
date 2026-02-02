<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available IDs from related tables
        $shippingLineIds = DB::table('shipping_lines')
            ->pluck('id')
            ->toArray();

        $cypaIds = DB::table('cypa_details')
            ->pluck('id')
            ->toArray();

        if (empty($shippingLineIds) || empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Required related records not found. Please seed shipping_lines and cypa_details first.');
            return;
        }

        $bookings = [
            [
                'reference_number' => 'RF-483624',
                'vessel' => 'MSC OSCAR',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'expected_date' => now()->addDays(10)->toDateString(),
                'is_complete' => true,
            ],
            [
                'reference_number' => 'RF-483625',
                'vessel' => 'EVER GIVEN',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'expected_date' => now()->addDays(15)->toDateString(),
                'is_complete' => true,
            ],
            [
                'reference_number' => 'RF-483626',
                'vessel' => 'MAERSK EINDHOVEN',
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'expected_date' => now()->addDays(20)->toDateString(),
                'is_complete' => false,
            ],
            [
                'reference_number' => 'RF-483627',
                'vessel' => 'COSCO SHIPPING',
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'expected_date' => now()->addDays(12)->toDateString(),
                'is_complete' => true,
            ],
            [
                'reference_number' => 'RF-483628',
                'vessel' => 'HMM OSLO',
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'expected_date' => now()->addDays(25)->toDateString(),
                'is_complete' => false,
            ],
        ];

        foreach ($bookings as $booking) {
            DB::table('bookings')->updateOrInsert(
                [
                    'reference_number' => $booking['reference_number'],
                ],
                array_merge($booking, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
