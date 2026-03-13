<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Account - {{ $soa->dli_sa_number }}</title>
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
            line-height: 1.5;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-logo {
            flex-shrink: 0;
            width: 80px;
            height: auto;
        }

        .header-company {
            flex: 1;
            min-width: 0;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .company-address {
            font-size: 10px;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .company-phone {
            font-size: 10px;
            margin-bottom: 0;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 25px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: underline;
        }

        .soa-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 0 5px;
        }

        .soa-number {
            font-weight: bold;
            font-size: 12px;
        }

        .soa-date {
            font-weight: bold;
            font-size: 12px;
        }

        .client-info {
            margin-bottom: 25px;
            padding: 10px;
            background-color: #fafafa;
            border-left: 3px solid #000;
        }

        .client-label {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .client-details {
            margin-left: 0;
            line-height: 1.6;
        }

        .client-details div {
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 9px;
            page-break-inside: auto;
        }

        table thead {
            background-color: #e0e0e0;
        }

        table th {
            border: none;
            padding: 9px 6px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            background-color: #e0e0e0;
            letter-spacing: 0.3px;
        }

        table td {
            border: none;
            padding: 7px 6px;
            text-align: left;
            font-size: 8px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table tbody tr {
            page-break-inside: avoid;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            position: relative;
            page-break-inside: avoid;
        }

        .footer-right {
            width: 100%;
            text-align: right;
            float: right;
        }

        .total-section {
            margin-top: 0;
            border-top: 2px solid #000;
            padding-top: 12px;
            padding-right: 0;
            text-align: right;
            margin-left: auto;
            width: 300px;
        }

        .total-row {
            text-align: right;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .total-label {
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-right: 15px;
        }

        .total-value {
            font-weight: bold;
            font-size: 12px;
            text-align: right;
            display: inline-block;
            min-width: 120px;
        }


        .signature-section {
            margin-top: 50px;
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 10px;
            padding-top: 8px;
            width: 220px;
            margin-left: auto;
            margin-right: 0;
        }

        .signature-label {
            font-size: 9px;
            margin-top: 2px;
            text-align: center;
            width: 220px;
            margin-left: auto;
            margin-right: 0;
        }

        .respectfully-yours {
            font-weight: bold;
            margin-bottom: 50px;
            text-align: right;
        }

        .signature-name {
            font-size: 10px;
            margin-top: 5px;
            font-weight: bold;
        }

        .signature-title {
            font-size: 9px;
            margin-top: 2px;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        @if(!empty($logoPath) && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="header-logo" />
        @endif
        <div class="header-company">
            <div class="company-name">{{ $companyInfo['name'] }}</div>
            <div class="company-address">{{ $companyInfo['address'] }}</div>
            <div class="company-phone">{{ $companyInfo['phone'] }}</div>
        </div>
    </div>

    <!-- Document Title -->
    <div class="document-title">Statement of Account</div>

    <!-- SOA Info -->
    <div class="soa-info">
        <div class="soa-date">Date: {{ $issueDate }}</div>
        <div class="soa-number">DLI-SA# {{ str_replace('SA-', '', $soa->dli_sa_number) }}</div>
    </div>

    <!-- Client Information -->
    <div class="client-info">
        <div class="client-label">BILLED TO:</div>
        <div class="client-details">
            <div><strong>{{ $soa->shippingLine->name }}</strong></div>
            @if($soa->shippingLine->address)
                <div>{{ $soa->shippingLine->address }}</div>
            @endif
            @if($soa->shippingLine->contact_name)
                <div>Attn: {{ $soa->shippingLine->contact_name }}</div>
            @endif
        </div>
    </div>

    <!-- Transaction Table -->
    <table>
        <thead>
            <tr>
                @foreach($transactionColumns as $column)
                    <th>{{ strtoupper($column->name) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($transactionData as $row)
                <tr>
                    @foreach($transactionColumns as $column)
                        @php
                            $value = $row[$column->name] ?? '-';
                            $columnNameLower = strtolower($column->name);
                            $isNumeric = in_array($columnNameLower, ['amount', 'vat', '12% vat', '12%vat', 'total amount', 'stack run']);
                        @endphp
                        <td class="{{ $isNumeric ? 'text-right' : '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($transactionColumns) }}" class="text-center">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer no-break">
        <div class="footer-right">
            <div class="total-section">
                @if(in_array('amount', array_map('strtolower', $transactionColumns->pluck('name')->toArray())))
                    <div class="total-row">
                        <span class="total-label">SUBTOTAL:</span>
                        <span class="total-value">PHP {{ number_format($totalAmount, 2, '.', ',') }}</span>
                    </div>
                    {{-- VAT (12%): only show when at least one waybill has rate_per_client.has_vat = true --}}
                    @if(isset($totalVat) && $totalVat > 0)
                        <div class="total-row">
                            <span class="total-label">VAT (12%):</span>
                            <span class="total-value">PHP {{ number_format($totalVat, 2, '.', ',') }}</span>
                        </div>
                    @endif
                    <div class="total-row" style="border-top: 1px solid #000; padding-top: 8px; margin-top: 8px;">
                        <span class="total-label" style="font-size: 12px;">TOTAL:</span>
                        <span class="total-value" style="font-size: 13px;">PHP
                            {{ number_format($grandTotal, 2, '.', ',') }}</span>
                    </div>
                @endif
            </div>

            <div class="signature-section">
                <div class="respectfully-yours">RESPECTFULLY YOURS,</div>
                <div class="signature-line"></div>
                <div class="signature-label">Print Name & Signature</div>
                <div class="signature-line" style="margin-top: 50px;"></div>
                <div class="signature-label" style="font-weight: bold; margin-top: 2px;">Received By</div>
            </div>
        </div>
    </div>
    @if(!empty($attachment_paths))
        @foreach($attachment_paths as $path)
            <div style="page-break-before: always;"><img src="{{ $path }}" style="max-width: 100%; height: auto;" /></div>
        @endforeach
    @endif
</body>

</html>