<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=0, initial-scale=1.0">
    <title>Service Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .header-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
        }

        .header-logo {
            flex-shrink: 0;
            width: 70px;
            height: auto;
        }

        .header-company-text {
            flex: 1;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .company-address,
        .company-phone,
        .company-tin {
            font-size: 9px;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .company-tin {
            margin-bottom: 0;
        }

        .header-right {
            text-align: right;
            min-width: 200px;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .invoice-no-box {
            border: 2px solid #000;
            padding: 8px 12px;
            margin-bottom: 8px;
            display: inline-block;
        }

        .invoice-no-label {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .invoice-no-value {
            font-size: 16px;
            font-weight: bold;
        }

        .date-box {
            border: 1px solid #000;
            padding: 6px 10px;
            display: inline-block;
            margin-top: 4px;
        }

        .date-label {
            font-size: 9px;
            margin-bottom: 2px;
        }

        .date-value {
            font-size: 11px;
            font-weight: bold;
        }

        .payment-type {
            margin-top: 8px;
            font-size: 10px;
        }

        .payment-type label {
            margin-right: 15px;
            cursor: pointer;
        }

        .client-info-box {
            border: 2px solid #000;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 9px;
        }

        .field-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 8px;
        }

        .field-row:last-child {
            margin-bottom: 0;
        }

        .field-label {
            flex-shrink: 0;
            width: 110px;
            font-weight: bold;
        }

        .field-value {
            flex: 1;
            border-bottom: 1px solid #333;
            min-height: 16px;
            padding-bottom: 2px;
        }

        .field-value-inline {
            display: inline-block;
            border-bottom: 1px solid #333;
            min-width: 150px;
            padding-bottom: 2px;
        }

        .service-section {
            margin-bottom: 20px;
        }

        .service-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 8px;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }

        .service-table thead {
            background-color: #d0d0d0;
        }

        .service-table th {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        .service-table td {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            vertical-align: top;
        }

        .service-table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .financial-summary-box {
            border: 2px solid #000;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 9px;
        }

        .financial-two-column {
            display: flex;
            gap: 20px;
        }

        .financial-left-col {
            flex: 1;
        }

        .financial-right-col {
            flex: 1;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            align-items: baseline;
        }

        .financial-row:last-child {
            margin-bottom: 0;
        }

        .financial-label {
            font-weight: bold;
        }

        .financial-value {
            font-weight: bold;
            text-align: right;
            min-width: 100px;
        }

        .financial-row.highlight {
            background-color: #e8e8e8;
            padding: 4px;
            margin: 4px -4px;
        }

        .financial-row.total {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 11px;
            font-weight: bold;
        }

        .financial-divider {
            border-top: 1px solid #ccc;
            margin: 8px 0;
        }

        .payment-acknowledgment {
            margin-bottom: 20px;
            font-size: 9px;
            line-height: 1.6;
        }

        .payment-checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 6px;
            vertical-align: middle;
        }

        .amount-in-words {
            margin-top: 4px;
            font-style: italic;
        }

        .signature-section {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 8px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .signature-box {
            border: 1px solid #000;
            padding: 8px;
            min-height: 50px;
            width: 48%;
        }

        .signature-label {
            font-size: 8px;
            margin-bottom: 4px;
        }

        .footer {
            margin-top: 30px;
            page-break-inside: avoid;
            font-size: 7px;
            line-height: 1.4;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .footer-row {
            margin-bottom: 3px;
        }

        .footer-label {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            @if(!empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" class="header-logo" />
            @endif
            <div class="header-company-text">
                <div class="company-name">{{ $companyInfo['name'] }}</div>
                <div class="company-tin">{{ $companyInfo['tin'] }}</div>
                <div class="company-address">{{ $companyInfo['address'] }}</div>
                <div class="company-phone">{{ $companyInfo['phone'] }}</div>
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">SERVICE INVOICE</div>
            <div class="invoice-no-box">
                <div class="invoice-no-label">№</div>
                <div class="invoice-no-value">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="date-box">
                <div class="date-label">DATE</div>
                <div class="date-value">{{ $issueDate }}</div>
            </div>
            <div class="payment-type">
                <label>☐ CASH</label>
                <label>☐ CHARGE</label>
            </div>
        </div>
    </div>

    @php
        $shippingLine = $invoice->statementOfAccount->shippingLine;
        $addr = $shippingLine->address ?? '';
        $addrLines = preg_split('/\r\n|\n/', trim($addr), 2);
        if (count($addrLines) < 2 && $addr !== '' && strpos($addr, ',') !== false) {
            $parts = explode(',', $addr, 2);
            $addrLines = [trim($parts[0]), trim($parts[1] ?? '')];
        } elseif (count($addrLines) < 2) {
            $addrLines = array_pad($addrLines, 2, '');
        }
        $addressLine1 = $addrLines[0] ?? '';
        $addressLine2 = $addrLines[1] ?? '';
        $tin = $shippingLine->tin ?? '';
    @endphp

    <div class="client-info-box">
        <div class="field-row">
            <span class="field-label">Service To:</span>
            <span class="field-value">{{ $shippingLine->name ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Registered Name:</span>
            <span class="field-value">{{ $shippingLine->name ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Business Address:</span>
            <span class="field-value">{{ $addressLine1 }}</span>
        </div>
        @if(!empty($addressLine2))
            <div class="field-row">
                <span class="field-label"></span>
                <span class="field-value">{{ $addressLine2 }}</span>
            </div>
        @endif
        <div class="field-row">
            <span class="field-label">TIN:</span>
            <span class="field-value-inline">{{ $tin }}</span>
        </div>
    </div>

    <div class="service-section">
        <div class="service-label">Item Description / Nature of Service:</div>
        <table class="service-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description / Nature of Service</th>
                    <th style="width: 15%;" class="text-center">Quantity</th>
                    <th style="width: 17.5%;" class="text-right">Unit Price</th>
                    <th style="width: 17.5%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $invoiceItems = $invoiceItems ?? [];
                    if (empty($invoiceItems) && !empty($invoice->item_description)) {
                        $invoiceItems = [
                            [
                                'description' => $invoice->item_description ?? 'Trucking Charges',
                                'quantity' => $invoice->quantity ?? 0,
                                'unit_price' => $invoice->unit_price ?? 0,
                                'amount' => $invoice->total_sales ?? 0,
                            ]
                        ];
                    }
                @endphp
                @if(!empty($invoiceItems))
                    @foreach($invoiceItems as $index => $item)
                        <tr>
                            <td>
                                @if($index === 0)
                                    <strong>Trucking Charges</strong><br>
                                @endif
                                {{ $item['description'] ?? '' }}
                            </td>
                            <td class="text-center">{{ $item['quantity'] ?? 0 }}</td>
                            <td class="text-right">{{ number_format($item['unit_price'] ?? 0, 2, '.', ',') }}</td>
                            <td class="text-right">{{ number_format($item['amount'] ?? 0, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            <strong>Trucking Charges</strong><br>
                            {{ $invoice->item_description ?? '' }}
                        </td>
                        <td class="text-center">{{ $invoice->quantity ?? 0 }}</td>
                        <td class="text-right">{{ number_format($invoice->unit_price ?? 0, 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($invoice->total_sales ?? 0, 2, '.', ',') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="financial-summary-box">
        <div class="financial-two-column">
            <div class="financial-left-col">
                <div class="financial-row">
                    <span class="financial-label">VATable Sales:</span>
                    <span class="financial-value">{{ number_format($invoice->vatable_sales, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">VAT:</span>
                    <span class="financial-value">{{ number_format($invoice->vat, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">Zero-Rated Sales:</span>
                    <span class="financial-value">{{ number_format($invoice->zero_rated_sales, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">VAT-Exempt Sales:</span>
                    <span class="financial-value">{{ number_format($invoice->vat_exempt_sales, 2, '.', ',') }}</span>
                </div>
            </div>
            <div class="financial-right-col">
                <div class="financial-row highlight">
                    <span class="financial-label">Total Sales (VAT Inclusive):</span>
                    <span class="financial-value">{{ number_format($invoice->total_sales, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">Less: VAT:</span>
                    <span class="financial-value">{{ number_format($invoice->less_vat, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">Amount: Net of VAT</span>
                    <span class="financial-value">{{ number_format($invoice->net_of_vat, 2, '.', ',') }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="financial-row">
                        <span class="financial-label">Less: Discount (SC/PWD/NAAC/MOV/SP):</span>
                        <span class="financial-value">{{ number_format($invoice->discount, 2, '.', ',') }}</span>
                    </div>
                @endif
                <div class="financial-row">
                    <span class="financial-label">Add: VAT:</span>
                    <span class="financial-value">{{ number_format($invoice->vat, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row">
                    <span class="financial-label">Withholding Tax:</span>
                    <span
                        class="financial-value">{{ number_format($invoice->less_withdrawing_tax, 2, '.', ',') }}</span>
                </div>
                <div class="financial-row total">
                    <span class="financial-label">TOTAL AMOUNT DUE:</span>
                    <span class="financial-value">{{ number_format($invoice->total_amount, 2, '.', ',') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-acknowledgment">
        <div>
            <span class="payment-checkbox"></span>
            <span>Received the amount of</span>
        </div>
        <div class="amount-in-words">
            PHP {{ number_format($invoice->total_amount, 2, '.', ',') }} pesos only.
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-box">
                <div class="signature-label">Withholding TSC/PWD/NAAC/MOV/ Solo Parent ID No.:</div>
                <div style="margin-top: 20px;"></div>
                <div class="signature-label">SC/PWD/NAAC/MOV/SP Signature:</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Cashier/Authorized Representative</div>
                <div style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-row">
            <span class="footer-label">Printer's Accreditation No.:</span> [Accreditation Number]
        </div>
        <div class="footer-row">
            <span class="footer-label">Accreditation Date:</span> [Date] • <span class="footer-label">Expiry
                Date:</span> [Date]
        </div>
        <div class="footer-row">
            <span class="footer-label">BIR AUTHORITY TO PRINT NO.:</span> [Authority Number]
        </div>
        <div class="footer-row">
            <span class="footer-label">Date Issued:</span> [Date] <span class="footer-label">Approved Series:</span>
            [Series]
        </div>
    </div>

    @if(!empty($attachment_paths))
        @foreach($attachment_paths as $path)
            <div style="page-break-before: always;"><img src="{{ $path }}" style="max-width: 100%; height: auto;" /></div>
        @endforeach
    @endif
</body>

</html>