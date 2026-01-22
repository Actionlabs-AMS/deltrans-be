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
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .company-phone {
            font-size: 10px;
            margin-bottom: 15px;
        }
        
        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .soa-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .soa-number {
            font-weight: bold;
        }
        
        .soa-date {
            font-weight: bold;
        }
        
        .client-info {
            margin-bottom: 20px;
        }
        
        .client-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .client-details {
            margin-left: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        table thead {
            background-color: #f0f0f0;
        }
        
        table th {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }
        
        table td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: left;
            font-size: 8px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .footer-left {
            width: 50%;
        }
        
        .footer-right {
            width: 45%;
            text-align: right;
        }
        
        .total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 0 10px;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-value {
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            width: 200px;
        }
        
        .signature-label {
            font-size: 9px;
            margin-top: 5px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $companyInfo['name'] }}</div>
        <div class="company-address">{{ $companyInfo['address'] }}</div>
        <div class="company-phone">{{ $companyInfo['phone'] }}</div>
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
    <div class="footer">
        <div class="footer-left">
            <div class="signature-section">
                <div>RESPECTFULLY YOURS,</div>
                <div class="signature-line"></div>
                <div class="signature-label">Print Name & Signature</div>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="total-section">
                @if(in_array('amount', array_map('strtolower', $transactionColumns->pluck('name')->toArray())))
                    <div class="total-row">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">{{ number_format($grandTotal, 2, '.', ',') }}</span>
                    </div>
                @endif
            </div>
            
            <div class="signature-section" style="margin-top: 20px;">
                <div class="signature-line" style="margin-left: auto;"></div>
                <div class="signature-label">Received By</div>
            </div>
        </div>
    </div>
</body>
</html>
