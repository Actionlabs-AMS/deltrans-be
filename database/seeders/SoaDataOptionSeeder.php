<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoaDataOption;

class SoaDataOptionSeeder extends Seeder
{
    /**
     * Seeds `soa_data_options` (SOA field definitions). Other tables store **arrays of these row IDs**.
     *
     * `shipping_lines` JSON columns reference child option IDs only (not parent rows):
     * - `shipping_lines_template` — IDs of options whose parent is "Shipping Line"
     * - `transaction_information_template` — IDs of options whose parent is "Transaction Information"
     *
     * When this seeder runs against an empty `soa_data_options` table, auto-increment IDs are typically:
     * - 1 — Shipping Line (parent, `parent_id` null)
     * - 2 — Transaction Information (parent, `parent_id` null)
     * - 3–10 — Name, Email Address, Address, Contact Name, Contact Mobile, Landlines, Fax No, TIN
     * - 11–25 — Date, Booking Number, Origin, Destination, Waybill, Remarks, Plate Number, Container Number,
     *           Size, Vessel, Work Order, Stack Run, 12% VAT, Amount, Total Amount
     *
     * @see \Database\Seeders\ShippingLineSeeder
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
        // Order: Date, Booking Number, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Vessel, Work Order, Stack Run, VAT, Amount, Total Amount
        $transactionInfoChildren = [
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Date',
                'description' => 'Transaction date',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Booking Number',
                'description' => 'Booking number',
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
                'name' => 'Waybill',
                'description' => 'Waybill number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Remarks',
                'description' => 'Transaction remarks',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Plate Number',
                'description' => 'Truck plate number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Container Number',
                'description' => 'Container number',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Size',
                'description' => 'Container size',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Vessel',
                'description' => 'Vessel name',
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
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => '12% VAT',
                'description' => 'Value Added Tax (12%)',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Amount',
                'description' => 'Price of container',
            ],
            [
                'parent_id' => $transactionInfoParent->id,
                'name' => 'Total Amount',
                'description' => 'Amount + VAT',
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

