<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates fixed_expenses for every (shipping_line, cypa_from, cypa_to) route
     * so all bookings have a matching fixed expense for waybills (no fallbacks).
     */
    public function run(): void
    {
        $shippingLineIds = DB::table('shipping_lines')->pluck('id')->toArray();
        $cypaIds = DB::table('cypa_details')->where('is_active', 1)->pluck('id')->toArray();

        if (empty($shippingLineIds) || empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Not enough related records found. Please seed shipping_lines and cypa_details first.');
            return;
        }

        $fixedExpenses = [];
        $baseFee20 = 500.00;
        $baseFee40 = 750.00;
        $baseOnlineBooking = 100.00;
        $baseStack20 = 1500.00;
        $baseStack40 = 2000.00;
        $baseExp20 = 2000.00;
        $baseExp40 = 3000.00;

        foreach ($shippingLineIds as $lineIdx => $shippingLineId) {
            foreach ($cypaIds as $fromIdx => $cypaFrom) {
                foreach ($cypaIds as $toIdx => $cypaTo) {
                    if ($cypaFrom === $cypaTo) {
                        continue;
                    }
                    $variation = ($lineIdx + $fromIdx + $toIdx) % 3;
                    $ob20 = $baseOnlineBooking + ($variation * 10);
                    $ob40 = $baseOnlineBooking + ($variation * 15);
                    $fixedExpenses[] = [
                        'shipping_line_id' => $shippingLineId,
                        'cypa_id_from' => $cypaFrom,
                        'cypa_id_to' => $cypaTo,
                        'container_size' => '20ft',
                        'docs_fee' => $baseFee20 + ($variation * 50),
                        'online_booking_fee' => $ob20,
                        'stack_run' => $baseStack20 + ($variation * 100),
                        'expenses' => $baseExp20 + ($variation * 200),
                        'total_expenses' => $baseFee20 + $ob20 + $baseStack20 + $baseExp20 + ($variation * 350),
                    ];
                    $fixedExpenses[] = [
                        'shipping_line_id' => $shippingLineId,
                        'cypa_id_from' => $cypaFrom,
                        'cypa_id_to' => $cypaTo,
                        'container_size' => '40ft',
                        'docs_fee' => $baseFee40 + ($variation * 75),
                        'online_booking_fee' => $ob40,
                        'stack_run' => $baseStack40 + ($variation * 150),
                        'expenses' => $baseExp40 + ($variation * 300),
                        'total_expenses' => $baseFee40 + $ob40 + $baseStack40 + $baseExp40 + ($variation * 525),
                    ];
                }
            }
        }

        foreach ($fixedExpenses as $expense) {
            DB::table('fixed_expenses')->updateOrInsert(
                [
                    'shipping_line_id' => $expense['shipping_line_id'],
                    'cypa_id_from' => $expense['cypa_id_from'],
                    'cypa_id_to' => $expense['cypa_id_to'],
                    'container_size' => $expense['container_size'],
                ],
                $expense
            );
        }
    }
}

