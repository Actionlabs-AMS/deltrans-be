<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use App\Models\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceService
{
    /**
     * Calculate invoice data from SOA
     */
    public function calculateInvoiceFromSoa($soaId)
    {
        $soa = StatementOfAccount::with('shippingLine')->findOrFail($soaId);
        $bookingIds = $soa->booking_ids ?? [];

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
        $quantity = Container::whereIn('booking_id', $bookingIds)->count();

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
            'statement_of_account_id' => $soa->id,
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
     * Get computed financial totals for an SOA (used for PDF and API).
     * Formula: VATable Sales / Net of VAT = Total Sales (VAT Inclusive) − VAT.
     * Withholding Tax = 2% of Net of VAT. TOTAL AMOUNT DUE = Total Sales (VAT Inclusive) − Withholding − Discount.
     *
     * @param int $soaId
     * @param float $discount
     * @return array{vatable_sales: float, vat: float, total_sales: float, less_vat: float, net_of_vat: float, total_sales_inclusive: float, less_withdrawing_tax: float, total_amount: float}
     */
    public function getComputedTotals(int $soaId, float $discount = 0): array
    {
        $data = $this->calculateInvoiceFromSoa($soaId);
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
     * Calculate invoice items from SOA/Booking/Waybill data
     * Returns array of items grouped by container size/type.
     * Unit price and amount are VAT-inclusive (same as Billing Statement) when waybill.has_vat is true.
     */
    private function calculateInvoiceItems($soaId)
    {
        $soa = StatementOfAccount::findOrFail($soaId);
        $bookingIds = $soa->booking_ids ?? [];

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

            $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
            $hasVat = (bool) ($waybill->has_vat ?? false);
            $waybillTotal = $base * $containerCount;
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
     * Generate invoice from SOA
     */
    public function generateInvoice($data)
    {
        $soa = StatementOfAccount::findOrFail($data['statement_of_account_id']);

        // Only store minimal fields; totals are computed on download/send email
        $payload = [
            'statement_of_account_id' => $soa->id,
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
        $invoice->load(['statementOfAccount.shippingLine']);

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
        $invoice->load(['statementOfAccount.shippingLine']);
        return $invoice;
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
            $invoice = Invoice::with(['statementOfAccount.shippingLine'])->findOrFail($id);

            // Calculate invoice items dynamically from SOA/Booking/Waybill data
            $invoiceItems = $this->calculateInvoiceItems($invoice->statement_of_account_id);

            $companyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'Blk 8 Lot 11 North Harbor Center Vitas St Barangay 101 Zone 08, 1013 Tondo I/II NCR, City of Manila, First District Philippines',
                'phone' => 'Tel. No. (02) 8291-4477',
                'tin' => 'VAT Reg. TIN.: 010-392-323-00000',
            ];

            $logoPath = public_path('images/deltrans-logo.png');

            $attachmentPaths = [];
            if ($includeAttachments && $attachmentUserId !== null) {
                $attachmentPaths = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $totals = $this->getComputedTotals(
                (int) $invoice->statement_of_account_id,
                (float) ($invoice->discount ?? 0)
            );

            $data = [
                'invoice' => $invoice,
                'totals' => $totals,
                'invoiceItems' => $invoiceItems,
                'companyInfo' => $companyInfo,
                'logoPath' => $logoPath,
                'issueDate' => $invoice->date ? $invoice->date->format('F d, Y') : now()->format('F d, Y'),
                'attachment_paths' => $attachmentPaths,
            ];

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
            $invoice = Invoice::with(['statementOfAccount.shippingLine'])->findOrFail($id);
            $emailService = app(EmailService::class);

            // Use custom email if provided, otherwise use shipping line email
            $recipientEmail = $customEmail ?? $invoice->statementOfAccount->shippingLine->email_address;

            if (empty($recipientEmail)) {
                throw new \Exception('No email address found for shipping line.');
            }

            // Generate PDF
            $pdfContent = $this->generatePdf($id, $attachmentUserId, $includeAttachments);
            $pdfFilename = $invoice->invoice_number . '.pdf';

            // Prepare email: use custom subject/body if provided and non-empty, otherwise standard
            $subject = trim((string) $subject) !== '' ? trim($subject) : 'Invoice - ' . $invoice->invoice_number;
            $defaultBody = '<h2>Invoice</h2>'
                . '<p>Dear ' . ($invoice->statementOfAccount->shippingLine->name ?? 'Valued Customer') . ',</p>'
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
     * List invoices with pagination
     */
    public function listInvoices($perPage = 10)
    {
        try {
            $query = Invoice::with(['statementOfAccount.shippingLine']);

            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('statementOfAccount.shippingLine', function ($query) {
                            $query->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            if (request('statement_of_account_id')) {
                $query->where('statement_of_account_id', request('statement_of_account_id'));
            }

            if (request('date_from')) {
                $query->where('date', '>=', request('date_from'));
            }

            if (request('date_to')) {
                $query->where('date', '<=', request('date_to'));
            }

            $query->orderBy('id', 'desc');

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception('Failed to list invoices: ' . $e->getMessage());
        }
    }

    /**
     * Show invoice
     */
    public function showInvoice($id)
    {
        try {
            return Invoice::with(['statementOfAccount.shippingLine'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Invoice not found.');
        }
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
