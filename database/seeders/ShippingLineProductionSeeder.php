<?php

namespace Database\Seeders;

use App\Models\ShippingLine;
use App\Models\SoaDataOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShippingLineProductionSeeder extends Seeder
{
    /** Default matrix row (TS LINE, SEA LEAD, HMM, CMA CGM, IAL, AMC, SINO TRANS, INTERASIA, RO ILAGAN, NCT, …). */
    private const TXN_STANDARD = ['Date', 'Origin', 'Destination', 'Waybill', 'Remarks', 'Plate Number', 'Container Number', 'Size', 'Amount'];

    /** KMTC — per Excel: includes Plate Number + Vessel */
    private const TXN_KMTC = ['Date', 'Origin', 'Destination', 'Waybill', 'Remarks', 'Plate Number', 'Container Number', 'Size', 'Vessel', 'Amount'];

    private const TXN_ONE = ['Booking Number', 'Origin', 'Destination', 'Container Number', 'Size', 'Work Order', 'Amount'];

    private const TXN_OOCL = ['Date', 'Booking Number', 'Origin', 'Destination', 'Waybill', 'Remarks', 'Plate Number', 'Container Number', 'Size', 'Stack Run', 'Amount'];

    /** MSC & MEDLOG — per Excel: includes 12% VAT + Amount + Total Amount. */
    private const TXN_MSC_MEDLOG = ['Date', 'Origin', 'Destination', 'Waybill', 'Remarks', 'Plate Number', 'Container Number', 'Size', '12% VAT', 'Amount', 'Total Amount'];

    public function run(): void
    {
        $shippingLineParentId = SoaDataOption::whereNull('parent_id')->where('name', 'Shipping Line')->value('id');
        $transactionParentId = SoaDataOption::whereNull('parent_id')->where('name', 'Transaction Information')->value('id');

        if (! $shippingLineParentId || ! $transactionParentId) {
            $this->command?->warn('SoaDataOption parents missing. Run SoaDataOptionSeeder first.');

            return;
        }

        $shippingLineTemplateIds = SoaDataOption::where('parent_id', $shippingLineParentId)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        // Full Transaction Information (all 15 columns) for any shipping line NOT present in the Excel matrix.
        $txnAll15Ids = SoaDataOption::where('parent_id', $transactionParentId)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $matrixByShortName = [
            'KMTC' => self::TXN_KMTC,
            'ONE' => self::TXN_ONE,
            'OOCL' => self::TXN_OOCL,
            'RO ILAGAN' => self::TXN_STANDARD,
            'INTERASIA' => self::TXN_STANDARD,
            'MSC' => self::TXN_MSC_MEDLOG,
            'MEDLOG' => self::TXN_MSC_MEDLOG,
            'TS LINE' => self::TXN_STANDARD,
            'SEA LEAD' => self::TXN_STANDARD,
            // Excel row is "HMM" but DB short_name is "HYUNDAI".
            'HYUNDAI' => self::TXN_STANDARD,
            'CMA CGM' => self::TXN_STANDARD,
            'IAL' => self::TXN_STANDARD,
            'AMC' => self::TXN_STANDARD,
            'SINO TRANS' => self::TXN_STANDARD,
        ];

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
                'short_name' => 'RO ILAGAN',
                'name' => 'RO ILAGAN',
                'address' => null,
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
                'address' => null,
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

            $needsShippingTemplates = $isNew
                || empty($record->shipping_lines_template)
                || (is_array($record->shipping_lines_template) && count($record->shipping_lines_template) !== count($shippingLineTemplateIds));

            if ($needsShippingTemplates) {
                $record->shipping_lines_template = $shippingLineTemplateIds;
            }

            $fieldNames = $matrixByShortName[$shortName] ?? self::TXN_STANDARD;
            $record->transaction_information_template = $this->transactionFieldIds($transactionParentId, $fieldNames);

            $record->save();
        }

        // All shipping lines in DB:
        // - ensure shipping template is present
        // - set transaction template to:
        //   - Excel matrix subset if short_name is in the matrix
        //   - otherwise ALL 15 columns
        foreach (ShippingLine::query()->cursor() as $record) {
            $needsShippingTemplates = empty($record->shipping_lines_template)
                || (is_array($record->shipping_lines_template) && count($record->shipping_lines_template) !== count($shippingLineTemplateIds));

            if ($needsShippingTemplates) {
                $record->shipping_lines_template = $shippingLineTemplateIds;
            }

            $shortName = (string) ($record->short_name ?? '');
            if (array_key_exists($shortName, $matrixByShortName)) {
                $record->transaction_information_template = $this->transactionFieldIds($transactionParentId, $matrixByShortName[$shortName]);
            } else {
                $record->transaction_information_template = $txnAll15Ids;
            }

            $record->save();
        }
    }

    /**
     * @param  array<int, string>  $namesInOrder
     * @return array<int, int>
     */
    private function transactionFieldIds(int $transactionParentId, array $namesInOrder): array
    {
        if ($namesInOrder === []) {
            return [];
        }

        $byName = SoaDataOption::where('parent_id', $transactionParentId)
            ->whereIn('name', $namesInOrder)
            ->get(['id', 'name'])
            ->keyBy('name');

        $ordered = [];
        foreach ($namesInOrder as $name) {
            if ($byName->has($name)) {
                $ordered[] = (int) $byName[$name]->id;
            }
        }

        return $ordered;
    }
}
