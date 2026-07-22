<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use App\Models\Container;
use App\Helpers\CsvExportHelper;
use App\Helpers\FinancialDocumentCsvHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceService
{
    /**
     * Calculate invoice data from one or more SOAs (aggregated).
     *
     * @param int|array<int> $soaIds
     */
    public function calculateInvoiceFromSoa($soaIds)
    {
        $soaIds = $this->normalizeSoaIds($soaIds);
        $soas = StatementOfAccount::with('shippingLine')->whereIn('id', $soaIds)->get();

        if ($soas->isEmpty()) {
            throw (new ModelNotFoundException())->setModel(StatementOfAccount::class, $soaIds);
        }

        $bookingIds = $this->collectBookingIds($soas);

        $waybills = WaybillDetail::whereIn('booking_id', $bookingIds)
            ->with([
                'booking' => function ($q) {
                    $q->with('containers');
                }
            ])
            ->get();

        // 1. Calculate invoice items: Group by container size + type
        // waybill.rate is per container, so use it directly as unit_price
        $invoiceItems = [];
        $grouped = [];

        foreach ($waybills as $waybill) {
            $size = trim(str_ireplace('ft', '', $waybill->container_size ?? ''));
            $type = trim(str_ireplace('ft', '', $waybill->container_type ?? ''));
            $key = $size . ($type ? ' ' . $type : '');

            // Count containers for this waybill
            $containerCount = Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();

            if ($containerCount == 0) {
                // Fallback: count by booking_id only
                $containerCount = Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0)
                    $containerCount = 1; // Default to 1
            }

            // waybill.rate is per container, so use it directly as unit_price
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'size' => $size,
                    'type' => $type,
                    'quantity' => 0,
                    'unit_price' => (float) $waybill->rate, // waybill.rate is per container
                ];
            }
            $grouped[$key]['quantity'] += $containerCount;
        }

        // Build invoice items array (separate row per container size/type)
        foreach ($grouped as $group) {
            $sizeType = trim($group['size'] . ($group['type'] ? ' ' . $group['type'] : ''));
            $invoiceItems[] = [
                'description' => $sizeType,
                'quantity' => $group['quantity'],
                'unit_price' => (float) $group['unit_price'],
                'amount' => $group['quantity'] * (float) $group['unit_price'], // quantity × unit_price
            ];
        }

        // 2. Calculate totals: Sum of (waybill.rate × container_count) since waybill.rate is per container
        $sumRate = 0;
        $sumRateVat = 0;

        foreach ($waybills as $waybill) {
            $containerCount = Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();

            if ($containerCount == 0) {
                $containerCount = Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0)
                    $containerCount = 1;
            }

            // waybill.rate is per container, so multiply by container count
            $waybillTotalRate = (float) $waybill->rate * $containerCount;
            $sumRate += $waybillTotalRate;

            if ($waybill->has_vat) {
                $sumRateVat += $waybillTotalRate * 0.12;
            }
        }

        // 3. Calculate QUANTITY: Total container count
        $quantity = empty($bookingIds) ? 0 : Container::whereIn('booking_id', $bookingIds)->count();

        // 4. Calculate ITEM_DESCRIPTION: Combined text for backward compatibility
        $descriptionParts = [];
        foreach ($grouped as $group) {
            $sizeType = $group['size'] . ($group['type'] ? ' ' . $group['type'] : '');
            $descriptionParts[] = $group['quantity'] . ' x ' . $sizeType;
        }
        $itemDescription = implode(', ', $descriptionParts);

        // 5. Financial totals (formula: VATable Sales / Net of VAT = Total Sales (VAT Inclusive) − VAT)
        $vatableSales = $sumRate;
        $netOfVat = $sumRate;
        $vat = $sumRateVat;
        $lessVat = $sumRateVat;
        $totalSalesInclusive = $sumRate + $sumRateVat;
        $lessWithdrawingTax = $netOfVat * 0.02;
        $totalAmount = $totalSalesInclusive - $lessWithdrawingTax;

        return [
            'statement_of_account_ids' => $soaIds,
            'quantity' => $quantity,
            'unit_price' => 0,
            'item_description' => $itemDescription,
            'vatable_sales' => $vatableSales,
            'zero_rated_sales' => 0,
            'vat_exempt_sales' => 0,
            'vat' => $vat,
            'total_sales' => $sumRate,
            'less_vat' => $lessVat,
            'net_of_vat' => $netOfVat,
            'discount' => 0,
            'discount_id' => null,
            'less_withdrawing_tax' => $lessWithdrawingTax,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Get computed financial totals for one or more SOAs (used for PDF and API).
     * Formula: VATable Sales / Net of VAT = Total Sales (VAT Inclusive) − VAT.
     * Withholding Tax = 2% of Net of VAT. TOTAL AMOUNT DUE = Total Sales (VAT Inclusive) − Withholding − Discount.
     *
     * @param int|array<int> $soaIds
     * @param float $discount
     * @return array{vatable_sales: float, vat: float, total_sales: float, less_vat: float, net_of_vat: float, total_sales_inclusive: float, less_withdrawing_tax: float, total_amount: float}
     */
    public function getComputedTotals(int|array $soaIds, float $discount = 0): array
    {
        $data = $this->calculateInvoiceFromSoa($soaIds);
        $totalSalesInclusive = $data['total_sales'] + $data['less_vat'];
        $totalAmount = $totalSalesInclusive - $data['less_withdrawing_tax'] - (float) $discount;
        $totalAmount = max(0, $totalAmount);

        return [
            'vatable_sales' => (float) $data['vatable_sales'],
            'vat' => (float) $data['vat'],
            'total_sales' => (float) $data['total_sales'],
            'less_vat' => (float) $data['less_vat'],
            'net_of_vat' => (float) $data['net_of_vat'],
            'total_sales_inclusive' => (float) $totalSalesInclusive,
            'less_withdrawing_tax' => (float) $data['less_withdrawing_tax'],
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Computed totals for an invoice from all linked SOAs.
     */
    public function getComputedTotalsForInvoice(Invoice $invoice): array
    {
        $soaIds = $invoice->statement_of_account_ids;
        if (empty($soaIds)) {
            return [
                'vatable_sales' => 0.0,
                'vat' => 0.0,
                'total_sales' => 0.0,
                'less_vat' => 0.0,
                'net_of_vat' => 0.0,
                'total_sales_inclusive' => 0.0,
                'less_withdrawing_tax' => 0.0,
                'total_amount' => 0.0,
            ];
        }

        return $this->getComputedTotals($soaIds, (float) ($invoice->discount ?? 0));
    }

    /**
     * Calculate invoice items from SOA/Booking/Waybill data across one or more SOAs.
     * Returns array of items grouped by container size/type.
     * Unit price and amount are VAT-inclusive (same as Billing Statement) when waybill.has_vat is true.
     *
     * @param int|array<int> $soaIds
     */
    private function calculateInvoiceItems($soaIds)
    {
        $soaIds = $this->normalizeSoaIds($soaIds);
        $soas = StatementOfAccount::whereIn('id', $soaIds)->get();
        $bookingIds = $this->collectBookingIds($soas);

        $waybills = WaybillDetail::whereIn('booking_id', $bookingIds)
            ->with([
                'booking' => function ($q) {
                    $q->with('containers');
                }
            ])
            ->get();

        $invoiceItems = [];
        $grouped = [];

        foreach ($waybills as $waybill) {
            $size = trim(str_ireplace('ft', '', $waybill->container_size ?? ''));
            $type = trim(str_ireplace('ft', '', $waybill->container_type ?? ''));
            $key = $size . ($type ? ' ' . $type : '');

            $containerCount = Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();

            if ($containerCount == 0) {
                $containerCount = Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0)
                    $containerCount = 1;
            }

            // total_rate_per_client is already the full amount for this waybill (all containers);
            // do not multiply by container count to avoid double multiplication on the invoice.
            $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
            $hasVat = (bool) ($waybill->has_vat ?? false);
            $waybillTotal = $base;
            $lineTotal = $hasVat ? $waybillTotal * 1.12 : $waybillTotal;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'size' => $size,
                    'type' => $type,
                    'quantity' => 0,
                    'total_amount' => 0,
                ];
            }
            $grouped[$key]['quantity'] += $containerCount;
            $grouped[$key]['total_amount'] += $lineTotal;
        }

        foreach ($grouped as $group) {
            $sizeType = trim($group['size'] . ($group['type'] ? ' ' . $group['type'] : ''));
            $quantity = (int) $group['quantity'];
            $totalAmount = (float) $group['total_amount'];
            $unitPrice = $quantity > 0 ? $totalAmount / $quantity : 0;
            $invoiceItems[] = [
                'description' => $sizeType,
                'quantity' => $quantity,
                'unit_price' => $unitPrice, // VAT-inclusive, matches Billing "RATE OF TRIP"
                'amount' => $totalAmount,
            ];
        }

        return $invoiceItems;
    }

    /**
     * Generate one invoice from one or more SOAs.
     */
    public function generateInvoice($data)
    {
        $soaIds = $this->normalizeSoaIds($data['statement_of_account_ids'] ?? []);
        $soas = StatementOfAccount::whereIn('id', $soaIds)->get();

        if ($soas->count() !== count($soaIds)) {
            throw new \Exception('One or more selected statements of account do not exist.');
        }

        $shippingLineIds = $soas->pluck('shipping_line_id')->unique()->filter()->values();
        if ($shippingLineIds->count() > 1) {
            throw new \Exception('All selected statements of account must belong to the same shipping line.');
        }

        $alreadyLinked = DB::table('invoice_statement_of_account')
            ->whereIn('statement_of_account_id', $soaIds)
            ->exists();
        if ($alreadyLinked) {
            throw new \Exception('One or more selected statements of account are already linked to an invoice.');
        }

        $invoice = DB::transaction(function () use ($data, $soas, $soaIds) {
            $payload = [
                'invoice_number' => $data['invoice_number'] ?? null,
                'date' => $data['date'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'discount_id' => $data['discount_id'] ?? null,
            ];
            if (empty($payload['invoice_number'])) {
                $payload['invoice_number'] = $this->generateInvoiceNumber();
            }
            if (empty($payload['date'])) {
                $payload['date'] = now();
            }

            $invoice = Invoice::create($payload);
            $invoice->statementOfAccounts()->attach($soaIds);

            $bookingIds = $this->collectBookingIds($soas);
            if (!empty($bookingIds)) {
                Booking::whereIn('id', $bookingIds)->update(['is_complete' => true]);
            }

            BillingStatement::whereIn('statement_of_account_id', $soaIds)
                ->update(['is_paid' => true]);

            return $invoice;
        });

        $invoice->load(['statementOfAccounts.shippingLine']);

        return $invoice;
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber()
    {
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? ((int) preg_replace('/[^0-9]/', '', $lastInvoice->invoice_number)) + 1 : 1;
        return 'INV-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update invoice
     */
    public function updateInvoice($id, $data)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        $invoice->load(['statementOfAccounts.shippingLine']);
        return $invoice;
    }

    /**
     * Build Invoice PDF/CSV document data (same content as invoice PDF).
     */
    public function prepareInvoicePdfData(int $id): array
    {
        $invoice = Invoice::with(['statementOfAccounts.shippingLine'])->findOrFail($id);
        $soaIds = $invoice->statement_of_account_ids;
        $invoiceItems = $this->calculateInvoiceItems($soaIds);
        $totals = $this->getComputedTotalsForInvoice($invoice);

        return [
            'invoice' => $invoice,
            'totals' => $totals,
            'invoiceItems' => $invoiceItems,
            'companyInfo' => [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'Blk 8 Lot 11 North Harbor Center Vitas St Barangay 101 Zone 08, 1013 Tondo I/II NCR, City of Manila, First District Philippines',
                'phone' => 'Tel. No. (02) 8291-4477',
                'tin' => 'VAT Reg. TIN.: 010-392-323-00000',
            ],
            'logoPath' => public_path('images/deltrans-logo.png'),
            'issueDate' => $invoice->date ? $invoice->date->format('F d, Y') : now()->format('F d, Y'),
            'attachment_paths' => [],
        ];
    }

    /**
     * Export Invoice document as CSV (same content as invoice PDF).
     */
    public function exportInvoiceDocumentCsv(int $id): StreamedResponse
    {
        $data = $this->prepareInvoicePdfData($id);
        $invoice = $data['invoice'];
        $filename = FinancialDocumentCsvHelper::sanitizeFilename($invoice->invoice_number ?? '', 'invoice-' . $id) . '.csv';

        return FinancialDocumentCsvHelper::streamDownload($filename, function ($handle) use ($data) {
            $this->writeInvoiceDocumentCsv($handle, $data);
        });
    }

    /**
     * Export Invoice document as CSV by SOA ID.
     */
    public function exportInvoiceDocumentCsvBySoaId(int $soaId): StreamedResponse
    {
        $invoice = Invoice::whereHas('statementOfAccounts', function ($q) use ($soaId) {
            $q->where('statement_of_accounts.id', $soaId);
        })->firstOrFail();

        return $this->exportInvoiceDocumentCsv($invoice->id);
    }

    /**
     * @param resource $handle
     */
    private function writeInvoiceDocumentCsv($handle, array $data): void
    {
        $invoice = $data['invoice'];
        $shippingLine = $invoice->primaryStatementOfAccount()?->shippingLine;
        $totals = $data['totals'];

        FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Service Invoice');
        FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
            ['Date', $data['issueDate']],
            ['Invoice No.', $invoice->invoice_number],
            ['Service To', $shippingLine->name ?? ''],
            ['Registered Name', $shippingLine->name ?? ''],
            ['Business Address', $shippingLine->address ?? ''],
            ['TIN', $shippingLine->tin ?? ''],
        ]);

        FinancialDocumentCsvHelper::writeBlankRow($handle);
        FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Item Description / Nature of Service');

        $itemRows = [];
        foreach ($data['invoiceItems'] as $index => $item) {
            $desc = ($index === 0 ? 'Trucking Charges - ' : '') . ($item['description'] ?? '');
            $itemRows[] = [
                $desc,
                $item['quantity'] ?? 0,
                number_format((float) ($item['unit_price'] ?? 0), 2, '.', ','),
                number_format((float) ($item['amount'] ?? 0), 2, '.', ','),
            ];
        }
        FinancialDocumentCsvHelper::writeTable(
            $handle,
            ['Item Description / Nature of Service', 'Quantity', 'Unit Price', 'Amount'],
            $itemRows
        );

        FinancialDocumentCsvHelper::writeBlankRow($handle);
        FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Financial Summary');
        $summaryRows = [
            ['VATable Sales', number_format($totals['vatable_sales'], 2, '.', ',')],
            ['VAT', number_format($totals['vat'], 2, '.', ',')],
            ['Total Sales (VAT Inclusive)', number_format($totals['total_sales_inclusive'], 2, '.', ',')],
            ['Less: VAT', number_format($totals['less_vat'], 2, '.', ',')],
            ['Amount: Net of VAT', number_format($totals['net_of_vat'], 2, '.', ',')],
        ];
        if (($invoice->discount ?? 0) > 0) {
            $summaryRows[] = ['Less: Discount (SC/PWD/NAAC/MOV/SP)', number_format((float) $invoice->discount, 2, '.', ',')];
        }
        $summaryRows[] = ['Add: VAT', number_format($totals['vat'], 2, '.', ',')];
        $summaryRows[] = ['Withholding Tax (2% of Net of VAT)', number_format($totals['less_withdrawing_tax'], 2, '.', ',')];
        $summaryRows[] = ['TOTAL AMOUNT DUE', number_format($totals['total_amount'], 2, '.', ',')];
        FinancialDocumentCsvHelper::writeKeyValueRows($handle, $summaryRows);
    }

    /**
     * Generate PDF for Invoice. Returns PDF binary (not saved to disk).
     *
     * @param int $id Invoice ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @return string PDF binary content
     */
    public function generatePdf($id, ?int $attachmentUserId = null, bool $includeAttachments = false)
    {
        try {
            $data = $this->prepareInvoicePdfData((int) $id);

            if ($includeAttachments && $attachmentUserId !== null) {
                $data['attachment_paths'] = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoice.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $output = $pdf->output();
            if ($attachmentUserId !== null) {
                $this->deleteTempAttachmentsByUser($attachmentUserId);
            }
            return $output;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Invoice not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Send Invoice PDF via email to shipping line
     *
     * @param int $id Invoice ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @param string|null $customEmail Custom email address (overrides shipping line email)
     * @param array $cc CC recipients (optional)
     * @param string|null $subject Custom email subject (optional; if empty, standard subject is used)
     * @param string|null $body Custom email body HTML (optional; if empty, standard body is used)
     * @return bool Success status
     */
    public function sendInvoiceEmail($id, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [], ?string $subject = null, ?string $body = null)
    {
        try {
            $invoice = Invoice::with(['statementOfAccounts.shippingLine'])->findOrFail($id);
            $emailService = app(EmailService::class);
            $shippingLine = $invoice->primaryStatementOfAccount()?->shippingLine;

            // Use custom email if provided, otherwise use shipping line email
            $recipientEmail = $customEmail ?? $shippingLine?->email_address;

            if (empty($recipientEmail)) {
                throw new \Exception('No email address found for shipping line.');
            }

            // Generate PDF
            $pdfContent = $this->generatePdf($id, $attachmentUserId, $includeAttachments);
            $pdfFilename = $invoice->invoice_number . '.pdf';

            // Prepare email: use custom subject/body if provided and non-empty, otherwise standard
            $subject = trim((string) $subject) !== '' ? trim($subject) : 'Invoice - ' . $invoice->invoice_number;
            $defaultBody = '<h2>Invoice</h2>'
                . '<p>Dear ' . ($shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Invoice for ' . $invoice->invoice_number . '.</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            $body = trim((string) $body) !== '' ? trim($body) : $defaultBody;

            // Send email
            $emailService->sendEmailWithAttachment($recipientEmail, $subject, $body, $pdfContent, $pdfFilename, $cc);

            Log::info('[InvoiceService] Invoice email sent', [
                'invoice_id' => $id,
                'to' => $recipientEmail,
                'invoice_number' => $invoice->invoice_number
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[InvoiceService] Failed to send Invoice email', [
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * List invoices with pagination.
     * Returns paginator and counts for meta (all, trashed) to match SOA/billing API shape.
     */
    public function listInvoices($perPage = 10)
    {
        try {
            $allInvoices = Invoice::count();
            $trashedInvoices = Invoice::onlyTrashed()->count();

            $query = $this->buildInvoicesQuery()->with(['statementOfAccounts.shippingLine']);

            $paginator = $query->paginate($perPage)->withQueryString();

            return [
                'paginator' => $paginator,
                'meta_extra' => [
                    'all' => $allInvoices,
                    'trashed' => $trashedInvoices,
                ],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to list invoices: ' . $e->getMessage());
        }
    }

    /**
     * Export filtered invoices as CSV (no pagination).
     */
    public function exportInvoicesCsv(): StreamedResponse
    {
        $headers = [
            'id',
            'statement_of_account_ids',
            'invoice_number',
            'date',
            'discount',
            'discount_id',
            'dli_sa_numbers',
            'shipping_line_id',
            'shipping_line_name',
            'vatable_sales',
            'vat',
            'total_sales',
            'less_vat',
            'net_of_vat',
            'total_sales_inclusive',
            'less_withholding_tax',
            'total_amount',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $rows = function () {
            $query = $this->buildInvoicesQuery()->with(['statementOfAccounts.shippingLine']);

            foreach ($query->cursor() as $invoice) {
                $data = array_merge(
                    $invoice->toArray(),
                    $this->getComputedTotalsForInvoice($invoice)
                );

                $soas = $invoice->statementOfAccounts;
                $shippingLine = $invoice->primaryStatementOfAccount()?->shippingLine;

                yield [
                    $data['id'],
                    implode(',', $invoice->statement_of_account_ids),
                    $data['invoice_number'],
                    $this->formatExportDate($data['date'] ?? null),
                    $data['discount'],
                    $data['discount_id'],
                    $soas->pluck('dli_sa_number')->filter()->implode(', '),
                    $soas->first()?->shipping_line_id ?? '',
                    $shippingLine->name ?? '',
                    $data['vatable_sales'],
                    $data['vat'],
                    $data['total_sales'],
                    $data['less_vat'],
                    $data['net_of_vat'],
                    $data['total_sales_inclusive'],
                    $data['less_withdrawing_tax'],
                    $data['total_amount'],
                    $this->formatExportDateTime($data['created_at'] ?? null),
                    $this->formatExportDateTime($data['updated_at'] ?? null),
                    $this->formatExportDateTime($data['deleted_at'] ?? null),
                ];
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('invoices-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build invoice list/export query with shared filters.
     */
    private function buildInvoicesQuery(): Builder
    {
        $query = Invoice::query();

        if (request('search')) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'LIKE', '%' . request('search') . '%')
                    ->orWhereHas('statementOfAccounts.shippingLine', function ($query) {
                        $query->where('name', 'LIKE', '%' . request('search') . '%');
                    });
            });
        }

        if (request('shipping_line_id')) {
            $query->whereHas('statementOfAccounts', function ($q) {
                $q->where('shipping_line_id', request('shipping_line_id'));
            });
        }

        if (request('statement_of_account_id')) {
            $query->whereHas('statementOfAccounts', function ($q) {
                $q->where('statement_of_accounts.id', request('statement_of_account_id'));
            });
        }

        if (request('date_from')) {
            $query->where('date', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->where('date', '<=', request('date_to'));
        }

        return $query->orderBy('id', 'desc');
    }

    /**
     * Format a date value for CSV export.
     */
    private function formatExportDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }

    /**
     * Format a datetime value for CSV export.
     */
    private function formatExportDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    /**
     * Show invoice
     */
    public function showInvoice($id)
    {
        try {
            return Invoice::with(['statementOfAccounts.shippingLine'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Invoice not found.');
        }
    }

    /**
     * @param int|array<int> $soaIds
     * @return array<int>
     */
    private function normalizeSoaIds(int|array $soaIds): array
    {
        if (!is_array($soaIds)) {
            $soaIds = [$soaIds];
        }

        return array_values(array_unique(array_map('intval', $soaIds)));
    }

    /**
     * @param \Illuminate\Support\Collection<int, StatementOfAccount> $soas
     * @return array<int>
     */
    private function collectBookingIds($soas): array
    {
        return $soas
            ->flatMap(fn (StatementOfAccount $soa) => $soa->booking_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get temp attachments base path
     */
    private function getTempAttachmentsBasePath(): string
    {
        return 'temp-attachments';
    }

    /**
     * Get temp attachment file paths for a user (one folder per user).
     *
     * @param int $userId
     * @return array<string> Absolute file paths
     */
    public function getTempAttachmentPathsByUser(int $userId): array
    {
        $base = $this->getTempAttachmentsBasePath();
        $dir = $base . '/' . (string) $userId;
        if (!Storage::disk('local')->exists($dir)) {
            return [];
        }
        $paths = [];
        foreach (Storage::disk('local')->files($dir) as $relativePath) {
            $paths[] = Storage::disk('local')->path($relativePath);
        }
        return $paths;
    }

    /**
     * Delete the temp attachment directory for the given user (one folder per user).
     *
     * @param int $userId
     * @return void
     */
    public function deleteTempAttachmentsByUser(int $userId): void
    {
        $base = $this->getTempAttachmentsBasePath();
        $dir = $base . '/' . (string) $userId;
        Storage::disk('local')->deleteDirectory($dir);
    }

    /**
     * Store uploaded files under temp-attachments/{userId}. Replaces any existing
     * attachments for that user (one folder per user). Returns count stored.
     *
     * @param int $userId
     * @param array<\Illuminate\Http\UploadedFile> $files
     * @return int
     */
    public function storeTempAttachmentsForUser(int $userId, array $files): int
    {
        $base = $this->getTempAttachmentsBasePath();
        $dir = $base . '/' . (string) $userId;
        Storage::disk('local')->deleteDirectory($dir);
        Storage::disk('local')->makeDirectory($dir);
        $count = 0;
        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $safeName = 'image_' . $index . '_' . time() . '.' . $ext;
            Storage::disk('local')->putFileAs($dir, $file, $safeName);
            $count++;
        }
        return $count;
    }
}
