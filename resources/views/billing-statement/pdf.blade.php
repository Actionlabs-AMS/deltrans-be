<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Statement - {{ $billingStatement->billing_statement_no }}</title>
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
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        
        .header-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        
        .header-logo {
            flex-shrink: 0;
            width: 70px;
            height: auto;
        }
        
        .header-company-text {
            flex: 1;
            min-width: 0;
        }
        
        .header-right {
            text-align: right;
            min-width: 220px;
        }
        
        .header-right .document-title,
        .header-right .statement-no-label,
        .header-right .statement-no-value {
            text-align: right;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .company-address {
            font-size: 9px;
            margin-bottom: 2px;
            line-height: 1.3;
        }
        
        .company-phone {
            font-size: 9px;
            margin-bottom: 2px;
        }
        
        .company-tin {
            font-size: 9px;
            margin-bottom: 0;
        }
        
        .document-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
            margin-bottom: 6px;
        }
        
        .statement-no-label {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .statement-no-value {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .client-info-wrap {
            width: 100%;
            margin-bottom: 20px;
            border: none;
            font-size: 9px;
            table-layout: fixed;
        }
        
        .client-info-wrap td {
            vertical-align: middle;
            padding: 6px 12px 6px 0;
            border: none;
        }
        
        .client-info-wrap td:first-child {
            width: 55%;
        }
        
        .client-info-wrap td:last-child {
            width: 45%;
            padding: 6px 0 6px 12px;
        }
        
        .field-row-left,
        .field-row-right {
            display: flex;
            align-items: stretch;
            gap: 8px;
            width: 100%;
            font-size: 9px;
        }
        
        .field-row-left .field-label {
            flex-shrink: 0;
            width: 72px;
            font-weight: bold;
        }
        
        .field-row-right .field-label {
            flex-shrink: 0;
            width: 68px;
            font-weight: bold;
        }
        
        .field-value-wrap {
            flex: 1;
            min-width: 0;
            border-bottom: 1px solid #333;
            min-height: 18px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 2px;
        }
        
        .field-value {
            display: block;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        table thead {
            background-color: #e0e0e0;
        }
        
        table th {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            background-color: #e0e0e0;
        }
        
        table td {
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: left;
            font-size: 8px;
            vertical-align: middle;
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
            page-break-inside: avoid;
        }
        
        .terms-section {
            margin-bottom: 20px;
            font-size: 8px;
            line-height: 1.4;
        }
        
        .terms-section p {
            margin-bottom: 4px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .signature-box {
            width: 45%;
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 36px;
            min-height: 24px;
        }
        
        .signature-over-name {
            font-size: 8px;
            margin-top: 4px;
            font-style: italic;
        }
        
        .prepared-name {
            font-weight: bold;
            font-size: 9px;
            margin-top: 4px;
        }
        
        .received-note {
            font-size: 8px;
            margin-top: 4px;
            text-align: center;
            line-height: 1.3;
        }
        
        .footer-info {
            margin-top: 30px;
            font-size: 7px;
            text-align: center;
            line-height: 1.3;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .total-section {
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
        }
        
        .total-row {
            margin-bottom: 5px;
        }
        
        .total-label {
            display: inline-block;
            min-width: 150px;
            text-align: right;
            margin-right: 10px;
        }
        
        .total-value {
            display: inline-block;
            min-width: 120px;
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header: company left, title & statement no right -->
    <div class="header">
        <div class="header-left">
            @if(!empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" class="header-logo" />
            @endif
            <div class="header-company-text">
                <div class="company-name">{{ $companyInfo['name'] }}</div>
                <div class="company-address">{{ $companyInfo['address'] }}</div>
                <div class="company-phone">{{ $companyInfo['phone'] }}</div>
                <div class="company-tin">{{ $companyInfo['tin'] }}</div>
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">Billing Statement</div>
            <div class="statement-no-label">Billing statement No</div>
            <div class="statement-no-value">{{ str_replace('BS-', '', $billingStatement->billing_statement_no) }}</div>
        </div>
    </div>
    
    @php
        $addr = $billingStatement->shippingLine->address ?? '';
        $addrLines = preg_split('/\r\n|\n/', trim($addr), 2);
        if (count($addrLines) < 2 && $addr !== '' && strpos($addr, ',') !== false) {
            $parts = explode(',', $addr, 2);
            $addrLines = [trim($parts[0]), trim($parts[1] ?? '')];
        } elseif (count($addrLines) < 2) {
            $addrLines = array_pad($addrLines, 2, '');
        }
        $addressLine1 = $addrLines[0] ?? '';
        $addressLine2 = $addrLines[1] ?? '';
    @endphp
    @php $hasAddressLine2 = !empty(trim($addressLine2)); @endphp
    <!-- Billed To: Second address line row only when address is long. Right column: CI Date / Pay Term / Due Date / Bus. Style -->
    <table class="client-info-wrap" cellpadding="0" cellspacing="0">
        <tr>
            <td><div class="field-row-left"><span class="field-label">Billed To :</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->shippingLine->name }}</span></span></div></td>
            <td><div class="field-row-right"><span class="field-label">CI Date:</span><span class="field-value-wrap"><span class="field-value">{{ $issueDate }}</span></span></div></td>
        </tr>
        <tr>
            <td><div class="field-row-left"><span class="field-label">Address:</span><span class="field-value-wrap"><span class="field-value">{{ $addressLine1 }}</span></span></div></td>
            <td><div class="field-row-right"><span class="field-label">Pay Term:</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->payment_term ?? '' }}</span></span></div></td>
        </tr>
        @if($hasAddressLine2)
        <tr>
            <td><div class="field-row-left"><span class="field-label"></span><span class="field-value-wrap"><span class="field-value">{{ $addressLine2 }}</span></span></div></td>
            <td><div class="field-row-right"><span class="field-label">Due Date:</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->due_date ? $billingStatement->due_date->format('F d, Y') : '' }}</span></span></div></td>
        </tr>
        @endif
        <tr>
            <td><div class="field-row-left"><span class="field-label">TIN:</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->shippingLine->tin ?? '' }}</span></span></div></td>
            <td><div class="field-row-right"><span class="field-label">{{ $hasAddressLine2 ? 'Bus. Style:' : 'Due Date:' }}</span><span class="field-value-wrap"><span class="field-value">{{ $hasAddressLine2 ? ($billingStatement->bus_style ?? '') : ($billingStatement->due_date ? $billingStatement->due_date->format('F d, Y') : '') }}</span></span></div></td>
        </tr>
        @if(!$hasAddressLine2)
        <tr>
            <td><div class="field-row-left"><span class="field-label"></span><span class="field-value-wrap"><span class="field-value"></span></span></div></td>
            <td><div class="field-row-right"><span class="field-label">Bus. Style:</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->bus_style ?? '' }}</span></span></div></td>
        </tr>
        @endif
    </table>
    
    @if(!empty($detailsData))
    <!-- Charges Table -->
    <div style="border-top: 1px solid #000; margin: 16px 0 12px 0;"></div>
    <table>
        <thead>
            <tr>
                <th>DATE</th>
                <th>DESCRIPTION OF CHARGES</th>
                <th>SIZE</th>
                <th>RATE OF TRIP</th>
                <th class="text-right">TOTAL AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detailsData as $detail)
            <tr>
                <td>{{ $detail['date'] ?? '' }}</td>
                <td>{{ $detail['description'] }}</td>
                <td>{{ $detail['size'] }}</td>
                <td class="text-right">{{ isset($detail['rate_per_trip']) && $detail['rate_per_trip'] !== null ? number_format($detail['rate_per_trip'], 2, '.', ',') : '-' }}</td>
                <td class="text-right">{{ number_format($detail['total_amount'], 2, '.', ',') }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td></td>
                <td>TOTAL AMOUNT</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($grandTotal, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <!-- Simple format (has_details: false) -->
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; text-align: center;">
        <p style="font-size: 12px; font-weight: bold;">Total Amount Due: PHP {{ number_format($grandTotal, 2, '.', ',') }}</p>
    </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <!-- Terms & Conditions -->
        <div class="terms-section">
            <p><strong>TERMS & CONDITIONS:</strong></p>
            <p>1. Interest at the rate of twelve percent (12%) per annum shall be charged on overdue accounts after thirty (30) days from due date.</p>
            <p>2. In case of suit, twenty-five percent (25%) of the amount due as attorney's fees shall be charged in addition to the amount due.</p>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Prepared by:</div>
                <div class="signature-line"></div>
                <div class="signature-over-name">Signature Over Printed Name</div>
                @if($billingStatement->preparedByUser)
                    <div class="prepared-name">{{ $billingStatement->preparedByUser->name ?? ($billingStatement->preparedByUser->first_name . ' ' . $billingStatement->preparedByUser->last_name ?? '') }}</div>
                @endif
            </div>
            <div class="signature-box">
                <div class="signature-label">Received by:</div>
                <div class="signature-line"></div>
                <div class="received-note">Received the above articles in good order and condition.</div>
            </div>
        </div>
        
        <!-- Footer Info -->
        <div class="footer-info">
            <p><strong>BIR Authority to Print & Printer's Accreditation</strong></p>
            <p>This document is printed in accordance with BIR regulations.</p>
            <p style="font-weight: bold; margin-top: 5px;">THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAXES</p>
        </div>
    </div>
</body>
</html>
