<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Statement &amp; SOA - {{ $billingStatement->billing_statement_no }} / {{ $soa->dli_sa_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', 'Helvetica', sans-serif; font-size: 11px; color: #000; line-height: 1.5; padding: 20px; }

        /* Page 1: Billing Statement */
        .page-billing .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .page-billing .header-left { display: flex; align-items: flex-start; gap: 12px; flex: 1; min-width: 0; }
        .page-billing .header-logo { flex-shrink: 0; width: 70px; height: auto; }
        .page-billing .header-company-text { flex: 1; min-width: 0; }
        .page-billing .header-right { text-align: right; min-width: 220px; }
        .page-billing .header-right .document-title,
        .page-billing .header-right .statement-no-label,
        .page-billing .header-right .statement-no-value { text-align: right; }
        .page-billing .company-name { font-size: 16px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
        .page-billing .company-address, .page-billing .company-phone, .page-billing .company-tin { font-size: 9px; margin-bottom: 2px; line-height: 1.3; }
        .page-billing .company-tin { margin-bottom: 0; }
        .page-billing .document-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline; margin-bottom: 6px; }
        .page-billing .statement-no-label { font-size: 10px; margin-bottom: 2px; }
        .page-billing .statement-no-value { font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .page-billing .client-info-wrap { width: 100%; margin-bottom: 20px; border: none; font-size: 9px; table-layout: fixed; }
        .page-billing .client-info-wrap td { vertical-align: middle; padding: 6px 12px 6px 0; border: none; }
        .page-billing .client-info-wrap td:first-child { width: 55%; }
        .page-billing .client-info-wrap td:last-child { width: 45%; padding: 6px 0 6px 12px; }
        .page-billing .field-row-left, .page-billing .field-row-right { display: flex; align-items: stretch; gap: 8px; width: 100%; font-size: 9px; }
        .page-billing .field-row-left .field-label { flex-shrink: 0; width: 72px; font-weight: bold; }
        .page-billing .field-row-right .field-label { flex-shrink: 0; width: 68px; font-weight: bold; }
        .page-billing .field-value-wrap { flex: 1; min-width: 0; border-bottom: 1px solid #333; min-height: 18px; display: flex; align-items: flex-end; padding-bottom: 2px; }
        .page-billing .field-value { display: block; width: 100%; }
        .page-billing table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9px; }
        .page-billing table thead { background-color: #e0e0e0; }
        .page-billing table th { border: 1px solid #000; padding: 8px 5px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 8px; background-color: #e0e0e0; }
        .page-billing table td { border: 1px solid #000; padding: 6px 5px; text-align: left; font-size: 8px; vertical-align: middle; }
        .page-billing table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .page-billing .text-right { text-align: right; }
        .page-billing .footer { margin-top: 30px; page-break-inside: avoid; }
        .page-billing .terms-section { margin-bottom: 20px; font-size: 8px; line-height: 1.4; }
        .page-billing .terms-section p { margin-bottom: 4px; }
        .page-billing .signature-section { display: flex; justify-content: space-between; margin-top: 30px; }
        .page-billing .signature-box { width: 45%; }
        .page-billing .signature-label { font-weight: bold; font-size: 9px; margin-bottom: 8px; text-transform: uppercase; }
        .page-billing .signature-line { border-bottom: 1px solid #000; margin-top: 36px; min-height: 24px; }
        .page-billing .signature-over-name { font-size: 8px; margin-top: 4px; font-style: italic; }
        .page-billing .prepared-name { font-weight: bold; font-size: 9px; margin-top: 4px; }
        .page-billing .received-note { font-size: 8px; margin-top: 4px; text-align: center; line-height: 1.3; }
        .page-billing .footer-info { margin-top: 30px; font-size: 7px; text-align: center; line-height: 1.3; border-top: 1px solid #ccc; padding-top: 10px; }

        /* Page 2: SOA */
        .page-soa { page-break-before: always; }
        .page-soa .header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .page-soa .header-logo { flex-shrink: 0; width: 80px; height: auto; }
        .page-soa .header-company { flex: 1; min-width: 0; }
        .page-soa .company-name { font-size: 18px; font-weight: bold; margin-bottom: 6px; letter-spacing: 0.5px; }
        .page-soa .company-address { font-size: 10px; margin-bottom: 4px; line-height: 1.4; }
        .page-soa .company-phone { font-size: 10px; margin-bottom: 0; }
        .page-soa .document-title { font-size: 20px; font-weight: bold; text-align: center; margin: 25px 0; text-transform: uppercase; letter-spacing: 1px; text-decoration: underline; }
        .page-soa .soa-info { display: flex; justify-content: space-between; margin-bottom: 25px; padding: 0 5px; }
        .page-soa .soa-number, .page-soa .soa-date { font-weight: bold; font-size: 12px; }
        .page-soa .client-info { margin-bottom: 25px; padding: 10px; background-color: #fafafa; border-left: 3px solid #000; }
        .page-soa .client-label { font-weight: bold; margin-bottom: 8px; font-size: 11px; text-transform: uppercase; }
        .page-soa .client-details { margin-left: 0; line-height: 1.6; }
        .page-soa .client-details div { margin-bottom: 3px; }
        .page-soa table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 9px; page-break-inside: auto; }
        .page-soa table thead { background-color: #e0e0e0; }
        .page-soa table th { border: none; padding: 9px 6px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 8px; background-color: #e0e0e0; letter-spacing: 0.3px; }
        .page-soa table td { border: none; padding: 7px 6px; text-align: left; font-size: 8px; vertical-align: middle; word-wrap: break-word; }
        .page-soa table tbody tr { page-break-inside: avoid; }
        .page-soa table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .page-soa .text-right { text-align: right; }
        .page-soa .text-center { text-align: center; }
        .page-soa .footer { margin-top: 40px; page-break-inside: avoid; }
        .page-soa .footer-right { width: 100%; text-align: right; float: right; }
        .page-soa .total-section { margin-top: 0; border-top: 2px solid #000; padding-top: 12px; padding-right: 0; text-align: right; margin-left: auto; width: 300px; }
        .page-soa .total-row { text-align: right; margin-bottom: 8px; font-size: 11px; }
        .page-soa .total-label { font-weight: bold; text-transform: uppercase; display: inline-block; margin-right: 15px; }
        .page-soa .total-value { font-weight: bold; font-size: 12px; text-align: right; display: inline-block; min-width: 120px; }
        .page-soa .signature-section { margin-top: 50px; text-align: right; }
        .page-soa .signature-line { border-top: 1px solid #000; margin-top: 10px; padding-top: 8px; width: 220px; margin-left: auto; margin-right: 0; }
        .page-soa .signature-label { font-size: 9px; margin-top: 2px; text-align: center; width: 220px; margin-left: auto; margin-right: 0; }
        .page-soa .respectfully-yours { font-weight: bold; margin-bottom: 50px; text-align: right; }
    </style>
</head>
<body>
    {{-- Page 1: Billing Statement --}}
    <div class="page-billing">
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
        <table class="client-info-wrap" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="field-row-left"><span class="field-label">Billed To :</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->shippingLine->name }}</span></span></div>
                </td>
                <td>
                    <div class="field-row-right"><span class="field-label">CI Date:</span><span class="field-value-wrap"><span class="field-value">{{ $billingIssueDate }}</span></span></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-row-left"><span class="field-label">Address:</span><span class="field-value-wrap"><span class="field-value">{{ $addressLine1 }}</span></span></div>
                </td>
                <td>
                    <div class="field-row-right"><span class="field-label">Pay Term:</span><span class="field-value-wrap"><span class="field-value">{{ $billingStatement->payment_term ?? '' }}</span></span></div>
                </td>
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
                        <td>
                            @if(!empty($detail['description_lines']))
                                @foreach($detail['description_lines'] as $line){{ $line }}<br>@endforeach
                            @else
                                {{ $detail['description'] ?? '' }}
                            @endif
                        </td>
                        <td>{{ $detail['size'] ?? '' }}</td>
                        <td class="text-right">{{ isset($detail['rate_per_trip']) && $detail['rate_per_trip'] !== null ? number_format($detail['rate_per_trip'], 2, '.', ',') : '' }}</td>
                        <td class="text-right">{{ number_format($detail['total_amount'], 2, '.', ',') }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td></td><td></td><td></td>
                    <td class="text-right">TOTAL AMOUNT</td>
                    <td class="text-right">PHP {{ number_format($billingGrandTotal, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div class="terms-section">
                <p><strong>TERMS &amp; CONDITIONS:</strong></p>
                <p>1. Interest at the rate of twelve percent (12%) per annum shall be charged on overdue accounts after thirty (30) days from due date.</p>
                <p>2. In case of suit, twenty-five percent (25%) of the amount due as attorney's fees shall be charged in addition to the amount due.</p>
            </div>
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
            <div class="footer-info">
                <p><strong>BIR Authority to Print &amp; Printer's Accreditation</strong></p>
                <p>This document is printed in accordance with BIR regulations.</p>
                <p style="font-weight: bold; margin-top: 5px;">THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAXES</p>
            </div>
        </div>
    </div>

    {{-- Page 2: Statement of Account --}}
    <div class="page-soa">
        <div class="header">
            @if(!empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" class="header-logo" />
            @endif
            <div class="header-company">
                <div class="company-name">{{ $soaCompanyInfo['name'] }}</div>
                <div class="company-address">{{ $soaCompanyInfo['address'] }}</div>
                <div class="company-phone">{{ $soaCompanyInfo['phone'] }}</div>
            </div>
        </div>
        <div class="document-title">Statement of Account</div>
        <div class="soa-info">
            <div class="soa-date">Date: {{ $soaIssueDate }}</div>
            <div class="soa-number">DLI-SA# {{ str_replace('SA-', '', $soa->dli_sa_number) }}</div>
        </div>
        <div class="client-info">
            <div class="client-label">BILLED TO:</div>
            <div class="client-details">
                <div><strong>{{ $soa->shippingLine->name }}</strong></div>
                @if($soa->shippingLine->address)<div>{{ $soa->shippingLine->address }}</div>@endif
                @if($soa->shippingLine->contact_name)<div>Attn: {{ $soa->shippingLine->contact_name }}</div>@endif
            </div>
        </div>
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
        <div class="footer">
            <div class="footer-right">
                <div class="total-section">
                    @if(in_array('amount', array_map('strtolower', $transactionColumns->pluck('name')->toArray())))
                        <div class="total-row">
                            <span class="total-label">SUBTOTAL:</span>
                            <span class="total-value">PHP {{ number_format($soaTotalAmount, 2, '.', ',') }}</span>
                        </div>
                        @if(isset($soaTotalVat) && $soaTotalVat > 0)
                            <div class="total-row">
                                <span class="total-label">VAT (12%):</span>
                                <span class="total-value">PHP {{ number_format($soaTotalVat, 2, '.', ',') }}</span>
                            </div>
                        @endif
                        <div class="total-row" style="border-top: 1px solid #000; padding-top: 8px; margin-top: 8px;">
                            <span class="total-label" style="font-size: 12px;">TOTAL:</span>
                            <span class="total-value" style="font-size: 13px;">PHP {{ number_format($soaGrandTotal, 2, '.', ',') }}</span>
                        </div>
                    @endif
                </div>
                <div class="signature-section">
                    <div class="respectfully-yours">RESPECTFULLY YOURS,</div>
                    <div class="signature-line"></div>
                    <div class="signature-label">Print Name &amp; Signature</div>
                    <div class="signature-line" style="margin-top: 50px;"></div>
                    <div class="signature-label" style="font-weight: bold; margin-top: 2px;">Received By</div>
                </div>
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
