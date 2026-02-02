<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedExpenseSeeder extends Seeder
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
            $this->command->warn('Not enough related records found. Please seed shipping_lines and cypa_details first.');
            return;
        }

        $fixedExpenses = [];
        
        // Create fixed expenses for all shipping lines
        // For each shipping line, create combinations for common CYPA routes and container sizes
        foreach ($shippingLineIds as $shippingLineId) {
            // Common route: cypa[0] to cypa[1] - 20ft
            $fixedExpenses[] = [
                'shipping_line_id' => $shippingLineId,
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'container_size' => '20ft',
                'docs_fee' => 500.00,
                'stack_run' => 1500.00,
                'expenses' => 2000.00,
                'total_expenses' => 4000.00,
            ];
            
            // Common route: cypa[0] to cypa[1] - 40ft
            $fixedExpenses[] = [
                'shipping_line_id' => $shippingLineId,
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'container_size' => '40ft',
                'docs_fee' => 750.00,
                'stack_run' => 2000.00,
                'expenses' => 3000.00,
                'total_expenses' => 5750.00,
            ];
            
            // Reverse route: cypa[1] to cypa[0] - 20ft
            $fixedExpenses[] = [
                'shipping_line_id' => $shippingLineId,
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'container_size' => '20ft',
                'docs_fee' => 500.00,
                'stack_run' => 1500.00,
                'expenses' => 2000.00,
                'total_expenses' => 4000.00,
            ];
            
            // Reverse route: cypa[1] to cypa[0] - 40ft
            $fixedExpenses[] = [
                'shipping_line_id' => $shippingLineId,
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'container_size' => '40ft',
                'docs_fee' => 750.00,
                'stack_run' => 2000.00,
                'expenses' => 3000.00,
                'total_expenses' => 5750.00,
            ];
            
            // Additional routes if more CYPAs exist
            if (count($cypaIds) > 2) {
                $fixedExpenses[] = [
                    'shipping_line_id' => $shippingLineId,
                    'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                    'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                    'container_size' => '20ft',
                    'docs_fee' => 600.00,
                    'stack_run' => 1600.00,
                    'expenses' => 2200.00,
                    'total_expenses' => 4400.00,
                ];
                
                $fixedExpenses[] = [
                    'shipping_line_id' => $shippingLineId,
                    'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                    'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                    'container_size' => '40ft',
                    'docs_fee' => 850.00,
                    'stack_run' => 2100.00,
                    'expenses' => 3100.00,
                    'total_expenses' => 6050.00,
                ];
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

