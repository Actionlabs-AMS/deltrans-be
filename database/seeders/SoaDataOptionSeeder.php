<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoaDataOption;

class SoaDataOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent templates first
        $shippingLineParent = SoaDataOption::create([
            'parent_id' => null,
            'name' => 'Shipping Line',
            'description' => 'Shipping line information template',
        ]);

        $transactionInfoParent = SoaDataOption::create([
            'parent_id' => null,
            'name' => 'Transaction Information',
            'description' => 'Transaction information template',
        ]);

        // Create child templates for Shipping Line (parent_id = 1)
        $shippingLineChildren = [
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Name',
                'description' => 'Shipping line name',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Email Address',
                'description' => 'Shipping line email address',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Address',
                'description' => 'Shipping line address',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Contact Name',
                'description' => 'Contact person name',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Contact Mobile',
                'description' => 'Contact mobile number',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Landlines',
                'description' => 'Landline numbers',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'Fax No',
                'description' => 'Fax number',
            ],
            [
                'parent_id' => $shippingLineParent->id,
                'name' => 'TIN',
                'description' => 'Tax Identification Number',
            ],
        ];

        // Create child templates for Transaction Information (parent_id = 2)
        $transactionInfoChildren = [
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Date',
                'description' => 'Transaction date',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Plate Number',
                'description' => 'Truck plate number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Waybill',
                'description' => 'Waybill number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Container Number',
                'description' => 'Container number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Origin',
                'description' => 'Origin location',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Destination',
                'description' => 'Destination location',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Remarks',
                'description' => 'Transaction remarks',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Size',
                'description' => 'Container size',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Amount',
                'description' => 'Price of container',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Vessel',
                'description' => 'Vessel name',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'VAT',
                'description' => 'Value Added Tax',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Total Amount',
                'description' => 'Amount + VAT',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Booking Number',
                'description' => 'Booking number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Work Order',
                'description' => 'Work order number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Stack Run',
                'description' => 'Stack run information',
            ],
        ];

        // Insert all child templates
        foreach ($shippingLineChildren as $child) {
            SoaDataOption::create($child);
        }

        foreach ($transactionInfoChildren as $child) {
            SoaDataOption::create($child);
        }
    }
}

