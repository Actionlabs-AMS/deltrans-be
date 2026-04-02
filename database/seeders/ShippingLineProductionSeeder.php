<?php

namespace Database\Seeders;

use App\Models\ShippingLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShippingLineProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Template option IDs from `soa_data_options`:
        // Shipping Line children (parent_id=1): 3..10
        // Transaction Information children (parent_id=2): 11..25
        // These are used to populate:
        // - shipping_lines.shipping_lines_template
        // - shipping_lines.transaction_information_template
        //
        // Note: we only fill templates when missing/empty so users can adjust existing templates later.
        $shippingLineTemplateIds = [3, 4, 5, 6, 7, 8, 9, 10];
        $transactionInformationTemplateIds = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];

        $shippingLines = [
            [
                'short_name' => 'ONE',
                'name' => 'OCEAN NETWORK EXPRESS PTE LTD',
                'address' => 'Unit No. 907A-910, 9th Floor West Tower 8912 Asean Ave. Bldg, corner Asean st. Asean City Paranaque City',
            ],
            [
                'short_name' => 'KMTC',
                'name' => 'KOREA MARINE TRANSPORT CO LTD',
                'address' => 'Unit N-1B & N-1C & N7 9F TIMES PLAZA BLDG UN AVE COR TAFT AVE ZONE 072 BARANGAY 666 1000 ERMITA NCR CITY OF MANILA',
            ],
            [
                'short_name' => 'OOCL',
                'name' => 'ORIENT OVERSEAS CONTAINER LINE, LTD',
                'address' => '11TH FLOOR TWO ECOM CENTER TOWER B BAY SHORE AVE MALL OF ASIA COMPLEX PASAY CITY',
            ],
            [
                'short_name' => 'INTERASIA',
                'name' => 'FREIGHT CONNECTION PHIL INC',
                'address' => 'UNIT EL-3RD FLOOR MALATE BAYVIEW MANSION MALATE MANILA',
            ],
            [
                'short_name' => 'MSC',
                'name' => 'MEDITERRANEAN SHIPPING COMPANY PHILIPPINES',
                'address' => '15F AIA TOWER 8767 PASEO DE ROXAS MAKATI CITY 1226 METRO MANILA PHILIPPINES',
            ],
            [
                'short_name' => 'MEDLOG',
                'name' => 'MEDLOG PHILIPPINES INC',
                'address' => '15F AIA TOWER 8767 PASEO DE ROXAS MAKATI CITY 1226 METRO MANILA PHILIPPINES',
            ],
            [
                'short_name' => 'NCT',
                'name' => 'NCT TRANS NATIONAL CORP',
                'address' => 'UNIT707 17TH FLR EACH BLDG INC BIDSDAO MACAPAGAL BLVD MOA COMPLEX PASAY CITY',
            ],
            [
                'short_name' => 'TS LINE',
                'name' => 'TS LINES LTD, C/O TSL CONTAINER LINES PHIL INC',
                'address' => 'UNIT4103 11TH FLR, TOWER B, TWO E-COM CENTER BLDG BAYSHORE AVENUE MOA COMPLEX PASAY CITY',
            ],
            [
                'short_name' => 'SEA LEAD',
                'name' => 'SEALEAD SHIPPING PTE. LTD.',
                'address' => '10E TIMES PLAZA BLDG, UNITED NATION AVE., ERMITA MANILA',
            ],
            [
                'short_name' => 'HYUNDAI',
                'name' => 'HMM (Philippines), Inc.',
                'address' => 'UNITS 703B and 704B, 7th Floor, East Tower of 8912 Asean Ave., BLDG Asean City, Parañaque City',
            ],
            [
                'short_name' => 'CMA CGM',
                'name' => 'CMA CGM',
                'address' => null,
            ],
            // Additional shipping lines referenced by fixed expenses (no extra details in attachments)
            [
                'short_name' => 'IAL',
                'name' => 'IAL',
                'address' => null,
            ],
            [
                'short_name' => 'AMC',
                'name' => 'AMC',
                'address' => null,
            ],
            [
                'short_name' => 'SINO TRANS',
                'name' => 'SINO TRANS',
                'address' => null,
            ],
        ];

        foreach ($shippingLines as $shippingLine) {
            $name = $shippingLine['name'];
            $shortName = $shippingLine['short_name'];

            $record = ShippingLine::where('short_name', $shortName)->first();
            $isNew = $record === null;

            if ($isNew) {
                $record = new ShippingLine();
                $record->short_name = $shortName;
                // email_address is required (non-nullable); use deterministic placeholder.
                $record->email_address = 'shipping-line+' . Str::slug($name) . '+placeholder@deltrans.local';
            }

            $record->name = $name;
            $record->address = $shippingLine['address'];

            // Only set templates if missing/empty OR if it doesn't contain the full set of IDs.
            $needsShippingTemplates = $isNew
                || empty($record->shipping_lines_template)
                || (is_array($record->shipping_lines_template) && count($record->shipping_lines_template) !== count($shippingLineTemplateIds));

            $needsTransactionTemplates = $isNew
                || empty($record->transaction_information_template)
                || (is_array($record->transaction_information_template) && count($record->transaction_information_template) !== count($transactionInformationTemplateIds));

            if ($needsShippingTemplates) {
                $record->shipping_lines_template = $shippingLineTemplateIds;
            }

            if ($needsTransactionTemplates) {
                $record->transaction_information_template = $transactionInformationTemplateIds;
            }

            $record->save();
        }

        // Also make sure any existing shipping lines in the DB (beyond the ones listed above)
        // get templates populated if missing.
        $shippingLineQuery = ShippingLine::query();
        foreach ($shippingLineQuery->get() as $record) {
            $needsShippingTemplates = empty($record->shipping_lines_template)
                || (is_array($record->shipping_lines_template) && count($record->shipping_lines_template) !== count($shippingLineTemplateIds));

            $needsTransactionTemplates = empty($record->transaction_information_template)
                || (is_array($record->transaction_information_template) && count($record->transaction_information_template) !== count($transactionInformationTemplateIds));

            if ($needsShippingTemplates) {
                $record->shipping_lines_template = $shippingLineTemplateIds;
            }
            if ($needsTransactionTemplates) {
                $record->transaction_information_template = $transactionInformationTemplateIds;
            }

            $record->save();
        }
    }
}

