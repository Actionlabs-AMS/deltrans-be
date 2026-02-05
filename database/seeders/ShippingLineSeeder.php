<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingLine;
use App\Models\SoaDataOption;

class ShippingLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get full shipping line template (parent_id = 1) - all option IDs
        $shippingLinesTemplate = SoaDataOption::where('parent_id', 1)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        // Get full transaction information template (parent_id = 2)
        $transactionInformationTemplate = SoaDataOption::where('parent_id', 2)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        // Custom SOA template for first shipping line (Maersk) – subset of options.
        // Reflects in SOAs whose booking has shipping_line_id = 1 (e.g. SOA 1 and 2 after full seed).
        $shippingLineParentId = SoaDataOption::whereNull('parent_id')->where('name', 'Shipping Line')->value('id');
        $transactionParentId = SoaDataOption::whereNull('parent_id')->where('name', 'Transaction Information')->value('id');
        $customShippingLinesTemplate = $shippingLineParentId
            ? SoaDataOption::where('parent_id', $shippingLineParentId)
                ->whereIn('name', ['Name', 'Email Address', 'Address', 'Contact Name', 'TIN'])
                ->orderBy('id')
                ->pluck('id')
                ->toArray()
            : $shippingLinesTemplate;
        $customTransactionTemplate = $transactionParentId
            ? SoaDataOption::where('parent_id', $transactionParentId)
                ->whereIn('name', ['Date', 'Booking Number', 'Waybill', 'Amount', 'VAT', 'Total Amount'])
                ->orderBy('id')
                ->pluck('id')
                ->toArray()
            : $transactionInformationTemplate;

        $shippingLines = [
            [
                'name' => 'Maersk Line',
                'email_address' => 'contact@maersk.com',
                'address' => 'Esplanaden 50, 1098 Copenhagen K, Denmark',
                'contact_name' => 'John Anderson',
                'contact_mobile' => '+45 33 63 33 63',
                'landlines' => ['+45 33 63 33 64', '+45 33 63 33 65'],
                'shipping_lines_template' => $customShippingLinesTemplate,
                'transaction_information_template' => $customTransactionTemplate,
                'fax_no' => '+45 33 63 33 66',
                'tin' => 'DK-12345678',
            ],
            [
                'name' => 'MSC Mediterranean Shipping Company',
                'email_address' => 'info@msc.com',
                'address' => 'Rue de la Loi 12, 1040 Brussels, Belgium',
                'contact_name' => 'Maria Garcia',
                'contact_mobile' => '+32 2 739 90 00',
                'landlines' => ['+32 2 739 90 01', '+32 2 739 90 02'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+32 2 739 90 03',
                'tin' => 'BE-98765432',
            ],
            [
                'name' => 'CMA CGM',
                'email_address' => 'contact@cmacgm.com',
                'address' => '4 Quai d\'Arenc, 13002 Marseille, France',
                'contact_name' => 'Pierre Dubois',
                'contact_mobile' => '+33 4 88 91 90 00',
                'landlines' => ['+33 4 88 91 90 01'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+33 4 88 91 90 02',
                'tin' => 'FR-1122334455',
            ],
            [
                'name' => 'COSCO Shipping Lines',
                'email_address' => 'info@coscoshipping.com',
                'address' => 'No. 1000 Pudong Avenue, Shanghai, China',
                'contact_name' => 'Li Wei',
                'contact_mobile' => '+86 21 3518 8888',
                'landlines' => ['+86 21 3518 8889', '+86 21 3518 8890', '+86 21 3518 8891'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+86 21 3518 8892',
                'tin' => 'CN-9988776655',
            ],
            [
                'name' => 'Evergreen Line',
                'email_address' => 'service@evergreen-line.com',
                'address' => 'No. 166, Minsheng E. Rd., Taipei, Taiwan',
                'contact_name' => 'Chen Ming',
                'contact_mobile' => '+886 2 2505 1188',
                'landlines' => ['+886 2 2505 1189'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+886 2 2505 1190',
                'tin' => 'TW-5566778899',
            ],
            [
                'name' => 'Hapag-Lloyd',
                'email_address' => 'info@hapag-lloyd.com',
                'address' => 'Ballindamm 25, 20095 Hamburg, Germany',
                'contact_name' => 'Hans Mueller',
                'contact_mobile' => '+49 40 3001 0',
                'landlines' => ['+49 40 3001 1', '+49 40 3001 2'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+49 40 3001 3',
                'tin' => 'DE-4455667788',
            ],
            [
                'name' => 'ONE (Ocean Network Express)',
                'email_address' => 'contact@one-line.com',
                'address' => '1-1, Uchisaiwaicho 1-chome, Chiyoda-ku, Tokyo, Japan',
                'contact_name' => 'Yuki Tanaka',
                'contact_mobile' => '+81 3 3578 7000',
                'landlines' => ['+81 3 3578 7001'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+81 3 3578 7002',
                'tin' => 'JP-1122334455',
            ],
            [
                'name' => 'Yang Ming Marine Transport',
                'email_address' => 'info@yangming.com',
                'address' => 'No. 271, Ming De 1st Road, Keelung, Taiwan',
                'contact_name' => 'Wang Li',
                'contact_mobile' => '+886 2 2455 6000',
                'landlines' => ['+886 2 2455 6001'],
                'shipping_lines_template' => $shippingLinesTemplate,
                'transaction_information_template' => $transactionInformationTemplate,
                'fax_no' => '+886 2 2455 6002',
                'tin' => 'TW-2233445566',
            ],
        ];

        foreach ($shippingLines as $shippingLine) {
            ShippingLine::updateOrCreate(
                ['email_address' => $shippingLine['email_address']],
                $shippingLine
            );
        }
    }
}

