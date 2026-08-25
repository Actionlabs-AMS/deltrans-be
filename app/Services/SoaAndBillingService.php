<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Models\BillingStatement;
use App\Models\SoaDataOption;
use App\Http\Resources\SoaAndBillingResource;
use App\Http\Resources\BillingStatementResource;
use App\Helpers\CsvExportHelper;
use App\Helpers\FinancialDocumentCsvHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoaAndBillingService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new SoaAndBillingResource(new StatementOfAccount), new StatementOfAccount());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allSoa = $this->getTotalCount();
            $trashedSoa = $this->getTrashedCount();

            $query = $this->buildSoaQuery($trash);

            return SoaAndBillingResource::collection(
                $query->with('shippingLine')->paginate($perPage)->withQueryString()
            )->additional([
                        'meta' => [
                            'all' => $allSoa,
                            'trashed' => $trashedSoa
                        ]
                    ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch statement of accounts: ' . $e->getMessage());
        }
    }

    /**
     * Export filtered SOA records as CSV (no pagination).
     */
    public function exportSoaCsv(): StreamedResponse
    {
        $headers = [
            'id',
            'dli_sa_number',
            'work_order',
            'shipping_line_id',
            'shipping_line_name',
            'vessel',
            'booking_ids',
            'total_amount',
            'created_at',
        ];

        $rows = function () {
            $query = $this->buildSoaQuery(false)->with('shippingLine');

            foreach ($query->cursor() as $soa) {
                $data = (new SoaAndBillingResource($soa))->toArray(request());

                yield [
                    $data['id'],
                    $data['dli_sa_number'],
                    $data['work_order'],
                    $data['shipping_line_id'],
                    $data['shipping_line']['name'] ?? '',
                    $data['vessel'],
                    CsvExportHelper::formatIds($data['booking_ids'] ?? []),
                    $data['total_amount'],
                    $data['created_at'],
                ];
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('soa-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build SOA list/export query with shared filters.
     */
    private function buildSoaQuery(bool $trash = false): Builder
    {
        $query = StatementOfAccount::query();

        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('shipping_line_id')) {
            $query->where('shipping_line_id', request('shipping_line_id'));
        }

        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        if (request('search')) {
            $query->where(function ($q) {
                $q->where('dli_sa_number', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('work_order', 'LIKE', '%' . request('search') . '%')
                    ->orWhereHas('shippingLine', function ($query) {
                        $query->where('name', 'LIKE', '%' . request('search') . '%');
                    });
            });
        }

        if (request('order')) {
            $query->orderBy(request('order'), request('sort') ?? 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Normalize booking_id or booking_ids to array of integers.
     */
    private function normalizeBookingIds(array $data): array
    {
        if (isset($data['booking_ids']) && is_array($data['booking_ids'])) {
            return array_map('intval', array_values($data['booking_ids']));
        }
        if (isset($data['booking_id'])) {
            return [(int) $data['booking_id']];
        }
        return [];
    }

    /**
     * Generate a new statement of account.
     *
     * @param array $data Must contain booking_ids (array) or booking_id (single)
     * @return SoaAndBillingResource
     */
    public function generate(array $data)
    {
        try {
            $bookingIds = $this->normalizeBookingIds($data);
            if (empty($bookingIds)) {
                throw new \Exception('At least one booking is required.');
            }
            $data['booking_ids'] = $bookingIds;

            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);
            foreach ($bookingIds as $bid) {
                $booking = \App\Models\Booking::findOrFail($bid);
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $booking->id)->count();
                if ($waybillCount === 0) {
                    throw new \Exception('The selected booking (ID: ' . $bid . ') must have at least one waybill.');
                }
                if ($booking->shipping_line_id != $data['shipping_line_id']) {
                    throw new \Exception('The booking (ID: ' . $bid . ') does not belong to the selected shipping line.');
                }
            }

            $soa = StatementOfAccount::create(array_intersect_key($data, array_flip((new StatementOfAccount())->getFillable())));
            $soa->setRelation('bookings', \App\Models\Booking::whereIn('id', $soa->booking_ids)->get());
            $soa->load('shippingLine');

            return SoaAndBillingResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line or booking not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Generate SOA and Billing Statement in one request (combined payload).
     * Creates SOA first, then Billing Statement linked to it.
     *
     * @param array $data Combined SOA + Billing fields (booking_ids array or booking_id single)
     * @return array{soa: SoaAndBillingResource, billing: BillingStatementResource}
     */
    public function generateSoaAndBilling(array $data)
    {
        try {
            $bookingIds = $this->normalizeBookingIds($data);
            if (empty($bookingIds)) {
                throw new \Exception('At least one booking is required.');
            }
            $data['booking_ids'] = $bookingIds;

            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);
            foreach ($bookingIds as $bid) {
                $booking = \App\Models\Booking::findOrFail($bid);
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $booking->id)->count();
                if ($waybillCount === 0) {
                    throw new \Exception('The selected booking (ID: ' . $bid . ') must have at least one waybill.');
                }
                if ($booking->shipping_line_id != $data['shipping_line_id']) {
                    throw new \Exception('The booking (ID: ' . $bid . ') does not belong to the selected shipping line.');
                }
            }

            $soaData = array_intersect_key($data, array_flip((new StatementOfAccount())->getFillable()));
            $soa = StatementOfAccount::create($soaData);
            $soa->setRelation('bookings', \App\Models\Booking::whereIn('id', $soa->booking_ids)->get());
            $soa->load('shippingLine');

            $billingData = [
                'statement_of_account_id' => $soa->id,
                'billing_statement_no' => $data['billing_statement_no'],
                'prepared_by' => $data['prepared_by'] ?? (auth()->check() ? auth()->id() : null),
                'payment_term' => $data['payment_term'] ?? null,
                'ci_date' => $data['ci_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'bus_style' => $data['bus_style'] ?? null,
                'has_details' => $data['has_details'] ?? false,
            ];
            $billingStatement = BillingStatement::create($billingData);
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser.getUserMetas']);

            return [
                'soa' => SoaAndBillingResource::make($soa),
                'billing' => BillingStatementResource::make($billingStatement),
            ];
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line or booking not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Retrieve a single resource by ID with relationships.
     *
     * @param int $id
     * @return SoaAndBillingResource
     */
    public function show($id)
    {
        try {
            $soa = StatementOfAccount::with('shippingLine')->findOrFail($id);
            $bookingIds = $soa->booking_ids ?? [];
            $soa->setRelation('bookings', \App\Models\Booking::whereIn('id', $bookingIds)->get());
            $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
                ->with([
                    'shippingLine',
                    'driver',
                    'fleetTruck',
                    'fixedExpense',
                    'booking' => function ($q) {
                        $q->with(['cypaFrom', 'cypaTo', 'containers']);
                    }
                ])
                ->orderBy('booking_id')
                ->orderBy('id')
                ->get();
            $soa->setRelation('waybills', $waybills);

            return SoaAndBillingResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch statement of account: ' . $e->getMessage());
        }
    }

    /**
     * Update a statement of account by ID.
     *
     * @param int $id
     * @param array $data Only provided keys are updated (booking_ids normalized if booking_id present).
     * @return SoaAndBillingResource
     */
    public function updateSoa($id, array $data)
    {
        try {
            if (array_key_exists('booking_id', $data) || array_key_exists('booking_ids', $data)) {
                $data['booking_ids'] = $this->normalizeBookingIds($data);
                unset($data['booking_id']);
            }
            $soa = StatementOfAccount::findOrFail($id);
            $soa->update(array_intersect_key($data, array_flip($soa->getFillable())));
            $soa->setRelation('bookings', \App\Models\Booking::whereIn('id', $soa->booking_ids ?? [])->get());
            $soa->load('shippingLine');
            return SoaAndBillingResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Temp attachments path (relative to local disk root). Used for upload-before-download.
     */
    protected function getTempAttachmentsBasePath(): string
    {
        return config('filesystems.temp_attachments', 'temp-attachments');
    }

    /**
     * Get full filesystem paths for images in the given user's temp attachment folder.
     *
     * @param int $userId
     * @return array<int, string> Full paths to image files (empty if dir missing)
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

    /**
     * Get line items (transaction table rows) by booking ID(s) and shipping line. Same data shape as SOA PDF table. Paginated.
     * Columns come from shipping_line.transaction_information_template. All bookings must belong to the given shipping line.
     *
     * @param array<int> $bookingIds One or more booking IDs
     * @param int $shippingLineId Shipping line ID (required; columns and validation)
     * @param int $perPage Items per page (default 10)
     * @return array{paginator: LengthAwarePaginator, columns: array, total_amount: float, total_vat: float, grand_total: float}
     * @throws \Exception
     */
    public function getLineItemsByBookingIds(array $bookingIds, int $shippingLineId, $perPage = 10)
    {
        $bookingIds = array_values(array_unique(array_map('intval', $bookingIds)));
        if (empty($bookingIds)) {
            throw new \Exception('At least one booking_id is required.');
        }

        $shippingLine = \App\Models\ShippingLine::find($shippingLineId);
        if (!$shippingLine) {
            throw new \Exception('Shipping line not found.');
        }

        $bookings = \App\Models\Booking::whereIn('id', $bookingIds)->get(['id', 'shipping_line_id']);
        $mismatched = $bookings->filter(fn($b) => (int) $b->shipping_line_id !== $shippingLineId);
        if ($mismatched->isNotEmpty()) {
            $ids = $mismatched->pluck('id')->implode(', ');
            throw new \Exception("One or more bookings (ID(s): {$ids}) do not belong to the specified shipping line.");
        }

        $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
            ->with([
                'shippingLine',
                'driver',
                'fleetTruck',
                'fixedExpense',
                'booking' => function ($q) {
                    $q->with(['cypaFrom', 'cypaTo', 'containers', 'shippingLine']);
                }
            ])
            ->orderBy('booking_id')
            ->orderBy('id')
            ->get();

        if ($waybills->isEmpty()) {
            $transactionColumns = collect();
            $transactionData = [];
            $totalAmount = 0;
            $totalVat = 0;
            $grandTotal = 0;
        } else {
            $transactionTemplateIds = $shippingLine->transaction_information_template ?? [];
            $transactionColumns = collect();
            if (!empty($transactionTemplateIds)) {
                $transactionColumns = SoaDataOption::whereIn('id', $transactionTemplateIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $transactionTemplateIds) . ')')
                    ->get(['id', 'name', 'description']);

                $columnNames = $transactionColumns->pluck('name')->map('strtolower')->toArray();
                $amountIndex = array_search('amount', $columnNames);
                $vatIndex = array_search('vat', $columnNames);
                if ($vatIndex === false) {
                    $vatIndex = array_search('12% vat', $columnNames);
                }
                if ($vatIndex === false) {
                    $vatIndex = array_search('12%vat', $columnNames);
                }
                if ($amountIndex !== false && $vatIndex !== false && $amountIndex > $vatIndex) {
                    $columnsArray = $transactionColumns->all();
                    $amountColumn = $columnsArray[$amountIndex];
                    $vatColumn = $columnsArray[$vatIndex];
                    $columnsArray[$vatIndex] = $amountColumn;
                    $columnsArray[$amountIndex] = $vatColumn;
                    $transactionColumns = collect($columnsArray);
                }
            }

            $fallbackSoa = (object) ['work_order' => null];
            $soaPerWaybill = [];
            foreach ($waybills as $waybill) {
                $soa = $this->findSoaContainingBooking((int) $waybill->booking_id);
                if ($soa) {
                    $soaPerWaybill[$waybill->id] = $soa;
                }
            }

            $built = $this->buildSoaTransactionData($waybills, $fallbackSoa, $transactionColumns, $soaPerWaybill);
            $transactionData = $built['transactionData'];
            $totalAmount = $built['totalAmount'];
            $totalVat = $built['totalVat'];
            $grandTotal = $built['grandTotal'];
        }

        if (!isset($transactionData)) {
            $transactionData = [];
        }
        if (!isset($totalAmount)) {
            $totalAmount = 0;
        }
        if (!isset($totalVat)) {
            $totalVat = 0;
        }
        if (!isset($grandTotal)) {
            $grandTotal = 0;
        }
        $columns = isset($transactionColumns) ? $transactionColumns->map(fn($col) => ['id' => $col->id, 'name' => $col->name])->values()->all() : [];
        $total = count($transactionData);

        $currentPage = (int) request('page', 1);
        $currentPage = max(1, $currentPage);
        $paginator = new LengthAwarePaginator(
            array_slice($transactionData, ($currentPage - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'paginator' => $paginator,
            'columns' => $columns,
            'total_amount' => (float) $totalAmount,
            'total_vat' => (float) $totalVat,
            'grand_total' => (float) $grandTotal,
        ];
    }

    /**
     * Find one Statement of Account that contains the given booking ID.
     */
    private function findSoaContainingBooking(int $bookingId): ?StatementOfAccount
    {
        return StatementOfAccount::query()
            ->whereNull('deleted_at')
            // MariaDB doesn't support CAST(<int> AS JSON) reliably; pass JSON literal instead (e.g. "6")
            ->whereRaw("JSON_CONTAINS(booking_ids, ?, '$')", [json_encode($bookingId)])
            ->first();
    }

    /**
     * Get SOA line items (transaction table rows) for API by SOA ID. Same data as the SOA PDF table. Paginated.
     *
     * @param int $id SOA ID
     * @param int $perPage Items per page (default 10)
     * @return array{paginator: LengthAwarePaginator, columns: array, total_amount: float, total_vat: float, grand_total: float}
     * @throws \Exception
     */
    public function getSoaLineItems($id, $perPage = 10)
    {
        $soa = StatementOfAccount::with('shippingLine')->findOrFail($id);
        $bookingIds = $soa->booking_ids ?? [];
        $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
            ->with([
                'shippingLine',
                'driver',
                'fleetTruck',
                'fixedExpense',
                'booking' => function ($q) {
                    $q->with(['cypaFrom', 'cypaTo', 'containers']);
                }
            ])
            ->orderBy('booking_id')
            ->orderBy('id')
            ->get();

        $transactionTemplateIds = $soa->shippingLine->transaction_information_template ?? [];
        $transactionColumns = collect();
        if (!empty($transactionTemplateIds)) {
            $transactionColumns = SoaDataOption::whereIn('id', $transactionTemplateIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $transactionTemplateIds) . ')')
                ->get(['id', 'name', 'description']);

            $columnNames = $transactionColumns->pluck('name')->map('strtolower')->toArray();
            $amountIndex = array_search('amount', $columnNames);
            $vatIndex = array_search('vat', $columnNames);
            if ($vatIndex === false) {
                $vatIndex = array_search('12% vat', $columnNames);
            }
            if ($vatIndex === false) {
                $vatIndex = array_search('12%vat', $columnNames);
            }
            if ($amountIndex !== false && $vatIndex !== false && $amountIndex > $vatIndex) {
                $columnsArray = $transactionColumns->all();
                $amountColumn = $columnsArray[$amountIndex];
                $vatColumn = $columnsArray[$vatIndex];
                $columnsArray[$vatIndex] = $amountColumn;
                $columnsArray[$amountIndex] = $vatColumn;
                $transactionColumns = collect($columnsArray);
            }
        }

        $built = $this->buildSoaTransactionData($waybills, $soa, $transactionColumns);
        $transactionData = $built['transactionData'];
        $total = count($transactionData);

        $currentPage = (int) request('page', 1);
        $currentPage = max(1, $currentPage);
        $paginator = new LengthAwarePaginator(
            array_slice($transactionData, ($currentPage - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $columns = $transactionColumns->map(fn($col) => ['id' => $col->id, 'name' => $col->name])->values()->all();

        return [
            'paginator' => $paginator,
            'columns' => $columns,
            'total_amount' => (float) $built['totalAmount'],
            'total_vat' => (float) $built['totalVat'],
            'grand_total' => (float) $built['grandTotal'],
        ];
    }

    /**
     * Build SOA PDF/CSV document data (same content as SOA PDF).
     */
    public function prepareSoaPdfData(int $id): array
    {
        $soa = StatementOfAccount::with('shippingLine')->findOrFail($id);
        $bookingIds = $soa->booking_ids ?? [];
        $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
            ->with([
                'shippingLine',
                'driver',
                'fleetTruck',
                'fixedExpense',
                'booking' => function ($q) {
                    $q->with(['cypaFrom', 'cypaTo', 'containers']);
                },
            ])
            ->orderBy('booking_id')
            ->orderBy('id')
            ->get();

        $shippingLineTemplateIds = $soa->shippingLine->shipping_lines_template ?? [];
        $shippingLineColumns = collect();
        if (!empty($shippingLineTemplateIds)) {
            $shippingLineColumns = SoaDataOption::whereIn('id', $shippingLineTemplateIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $shippingLineTemplateIds) . ')')
                ->get(['id', 'name', 'description']);
        }

        $transactionTemplateIds = $soa->shippingLine->transaction_information_template ?? [];
        $transactionColumns = collect();
        if (!empty($transactionTemplateIds)) {
            $transactionColumns = SoaDataOption::whereIn('id', $transactionTemplateIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $transactionTemplateIds) . ')')
                ->get(['id', 'name', 'description']);

            $columnNames = $transactionColumns->pluck('name')->map('strtolower')->toArray();
            $amountIndex = array_search('amount', $columnNames);
            $vatIndex = array_search('vat', $columnNames);
            if ($vatIndex === false) {
                $vatIndex = array_search('12% vat', $columnNames);
            }
            if ($vatIndex === false) {
                $vatIndex = array_search('12%vat', $columnNames);
            }

            if ($amountIndex !== false && $vatIndex !== false && $amountIndex > $vatIndex) {
                $columnsArray = $transactionColumns->all();
                $amountColumn = $columnsArray[$amountIndex];
                $vatColumn = $columnsArray[$vatIndex];
                $columnsArray[$vatIndex] = $amountColumn;
                $columnsArray[$amountIndex] = $vatColumn;
                $transactionColumns = collect($columnsArray);
            }
        }

        $transactionColumns = $this->orderTransactionColumnsForPdf($transactionColumns);

        $vatPercent = 12.00;
        $built = $this->buildSoaTransactionData($waybills, $soa, $transactionColumns);

        return [
            'soa' => $soa,
            'companyInfo' => [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'BLK 18 LOT 11, MANILA HARBOUR CENTRE, VITAS TONDO MANILA',
                'phone' => 'Tel# 02-8291-4477',
            ],
            'shippingLineColumns' => $shippingLineColumns,
            'transactionColumns' => $transactionColumns,
            'transactionData' => $built['transactionData'],
            'totalAmount' => $built['totalAmount'],
            'vatRate' => $vatPercent / 100,
            'taxPercent' => $vatPercent,
            'totalVat' => $built['totalVat'],
            'grandTotal' => $built['grandTotal'],
            'issueDate' => now()->format('F d, Y'),
            'logoPath' => public_path('images/deltrans-logo.png'),
            'attachment_paths' => [],
        ];
    }

    /**
     * Export SOA document as CSV (same content as SOA PDF).
     */
    public function exportSoaDocumentCsv(int $id): StreamedResponse
    {
        $data = $this->prepareSoaPdfData($id);
        $soa = $data['soa'];
        $shippingLine = $soa->shippingLine;
        $filename = FinancialDocumentCsvHelper::sanitizeFilename($soa->dli_sa_number ?? '', 'soa-' . $id) . '.csv';

        return FinancialDocumentCsvHelper::streamDownload($filename, function ($handle) use ($data, $soa, $shippingLine) {
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Statement of Account');
            FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
                ['Date', $data['issueDate']],
                ['DLI-SA#', str_replace('SA-', '', (string) $soa->dli_sa_number)],
                ['Billed To', $shippingLine->name ?? ''],
                ['Address', $shippingLine->address ?? ''],
                ['Attn', $shippingLine->contact_name ?? ''],
            ]);

            FinancialDocumentCsvHelper::writeBlankRow($handle);
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Transactions');

            $headers = $data['transactionColumns']->pluck('name')->all();
            $rows = [];
            foreach ($data['transactionData'] as $row) {
                $line = [];
                foreach ($headers as $header) {
                    $line[] = $row[$header] ?? '';
                }
                $rows[] = $line;
            }
            FinancialDocumentCsvHelper::writeTable($handle, $headers, $rows);

            FinancialDocumentCsvHelper::writeBlankRow($handle);
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Totals');
            FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
                ['SUBTOTAL', 'PHP ' . number_format($data['totalAmount'], 2, '.', ',')],
                ['VAT (12%)', $data['totalVat'] > 0 ? 'PHP ' . number_format($data['totalVat'], 2, '.', ',') : ''],
                ['TOTAL', 'PHP ' . number_format($data['grandTotal'], 2, '.', ',')],
            ]);
        });
    }

    /**
     * Generate PDF for Statement of Account. Returns PDF binary (not saved to disk).
     *
     * @param int $id SOA ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use (one folder per user)
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @return string PDF binary content
     */
    public function generatePdf($id, ?int $attachmentUserId = null, bool $includeAttachments = false)
    {
        try {
            $data = $this->prepareSoaPdfData((int) $id);

            $attachmentPaths = [];
            if ($includeAttachments && $attachmentUserId !== null) {
                $attachmentPaths = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }
            $data['attachment_paths'] = $attachmentPaths;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('soa.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $output = $pdf->output();
            if ($attachmentUserId !== null) {
                $this->deleteTempAttachmentsByUser($attachmentUserId);
            }
            return $output;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Reorder transaction columns for SOA PDF and SOA + Billing PDF to canonical order:
     * Date, Plate No, Waybill No, Container No, Origin, Destination, Remarks, Size, Amount, 12% VAT, Total Amount, Stack Run, Work Order, Booking No.
     * Columns not in this list remain at the end in their current order.
     *
     * @param \Illuminate\Support\Collection $transactionColumns
     * @return \Illuminate\Support\Collection
     */
    private function orderTransactionColumnsForPdf(\Illuminate\Support\Collection $transactionColumns): \Illuminate\Support\Collection
    {
        $order = [
            'date' => 0, 'bate' => 0,
            'plate number' => 1, 'plate no' => 1, 'plt#' => 1, 'plt' => 1,
            'waybill' => 2, 'waybill number' => 2, 'way bill#' => 2, 'way bill' => 2,
            'container number' => 3, 'container no' => 3, 'container#' => 3,
            'origin' => 4, 'from' => 4,
            'destination' => 5, 'to' => 5,
            'remarks' => 6,
            'size' => 7,
            'amount' => 8,
            '12% vat' => 9, '12%vat' => 9, 'vat' => 9,
            'total amount' => 10,
            'stack run' => 11,
            'work order' => 12,
            'booking number' => 13, 'booking no' => 13,
        ];
        $endOrder = 999;
        $indexed = $transactionColumns->values()->map(function ($col, $i) use ($order, $endOrder) {
            $key = strtolower(trim($col->name));
            return ['col' => $col, 'order' => $order[$key] ?? $endOrder, 'i' => $i];
        });
        return $indexed->sortBy(['order', 'i'])->pluck('col')->values();
    }

    /**
     * Build transaction rows and totals for SOA PDF / API. One row per waybill (or per container when SOA has one row per container).
     * Totals are computed per waybill (not doubled for multiple containers).
     * Amounts and VAT flags come only from waybill_details (rate, then total_rate_per_client, has_vat); no lookup to rate_per_clients.
     *
     * @param \Illuminate\Support\Collection $waybills Waybills with booking.containers loaded
     * @param \App\Models\StatementOfAccount|object $soa Fallback SOA for work_order when soaPerWaybill not used
     * @param \Illuminate\Support\Collection $transactionColumns
     * @param array<int, \App\Models\StatementOfAccount|object> $soaPerWaybill Optional waybill_id => SOA (use this SOA for that waybill's row)
     * @return array{transactionData: array, totalAmount: float, totalVat: float, grandTotal: float}
     */
    private function buildSoaTransactionData($waybills, $soa, $transactionColumns, array $soaPerWaybill = [])
    {
        $vatPercent = 12.00;
        $transactionData = [];
        $totalAmount = 0;
        $totalVat = 0;

        foreach ($waybills as $waybill) {
            $effectiveSoa = $soaPerWaybill[$waybill->id] ?? $soa;
            $containersForWaybill = collect();
            if ($waybill->relationLoaded('booking') && $waybill->booking) {
                $containersForWaybill = ($waybill->booking->containers ?? collect())
                    ->where('waybill_id', $waybill->id)
                    ->values();
            }
            $containerList = $containersForWaybill->isEmpty() ? [null] : $containersForWaybill->all();

            foreach ($containerList as $container) {
                $row = [];
                foreach ($transactionColumns as $column) {
                    $row[$column->name] = $this->mapTransactionField($column->name, $waybill, $effectiveSoa, $container);
                }
                $transactionData[] = $row;
            }

            $amount = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
            $waybillHasVat = (bool) ($waybill->has_vat ?? false);
            // waybill.rate is per container, so multiply by container count for totals
            $containerCount = count($containerList);
            $waybillTotalAmount = $amount * $containerCount;
            $totalAmount += $waybillTotalAmount;
            if ($waybillHasVat) {
                $totalVat += $waybillTotalAmount * ($vatPercent / 100);
            }
        }

        return [
            'transactionData' => $transactionData,
            'totalAmount' => $totalAmount,
            'totalVat' => $totalVat,
            'grandTotal' => $totalAmount + $totalVat,
        ];
    }

    /**
     * @param object|null $container Optional container model for this row (when SOA has one row per container)
     */
    private function mapTransactionField($fieldName, $waybill, $soa, $container = null)
    {
        switch (strtolower($fieldName)) {
            case 'date':
            case 'bate':
                return $waybill->transaction_date ? $waybill->transaction_date->format('d-M') : '-';
            case 'plate number':
            case 'plt#':
            case 'plt':
                return $waybill->fleetTruck ? $waybill->fleetTruck->plate_number : '-';
            case 'driver':
            case 'driver name':
                if ($waybill->driver) {
                    return trim($waybill->driver->first_name . ' ' . $waybill->driver->last_name);
                }
                return '-';
            case 'helper':
            case 'helper name':
            case 'helpers':
                $helperId = $waybill->helper_id ?? null;
                if ($helperId) {
                    $helper = \App\Models\Helper::find($helperId);
                    if ($helper) {
                        return trim($helper->first_name . ' ' . $helper->last_name);
                    }
                }
                return '-';
            case 'waybill':
            case 'waybill number':
            case 'way bill#':
            case 'way bill':
                return $waybill->waybill_number ?? '-';
            case 'container number':
            case 'container no':
            case 'container#':
                if ($container !== null) {
                    return $container->container_number ?? '-';
                }
                $containers = $waybill->booking && $waybill->relationLoaded('booking')
                    ? ($waybill->booking->containers ?? collect())
                    : collect();
                $firstContainer = $containers->where('waybill_id', $waybill->id)->first();
                return $firstContainer ? $firstContainer->container_number : '-';
            case 'origin':
            case 'from':
                return $waybill->booking->cypaFrom
                    ? ($waybill->booking->cypaFrom->short_name ?? $waybill->booking->cypaFrom->name ?? '-')
                    : '-';
            case 'destination':
            case 'to':
                return $waybill->booking->cypaTo
                    ? ($waybill->booking->cypaTo->short_name ?? $waybill->booking->cypaTo->name ?? '-')
                    : '-';
            case 'remarks':
                return $waybill->booking
                    ? ($waybill->booking->is_ship_in ? 'SHIP IN' : 'SHIP OUT')
                    : '-';
            case 'size':
                $size = trim(str_ireplace('ft', '', $waybill->container_size ?? ''));
                $type = trim(str_ireplace('ft', '', $waybill->container_type ?? ''));
                $combined = trim($size . ($type !== '' ? ' ' . $type : ''));
                return $combined !== '' ? $combined : '-';
            case 'amount':
                $amount = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
                return number_format($amount, 2, '.', ',');
            case 'vessel':
                return $waybill->booking->vessel ?? '-';
            case 'vat':
            case '12% vat':
            case '12%vat':
                $amount = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount * ($vatPercent / 100), 2, '.', ',');
            case 'total amount':
                $amount = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount + ($amount * ($vatPercent / 100)), 2, '.', ',');
            case 'booking number':
            case 'booking no':
                return $waybill->booking->reference_number ?? '-';
            case 'work order':
                return $soa->work_order ?? '-';
            case 'stack run':
                $stackRun = (float) ($waybill->stack_run ?? 0);
                return $stackRun > 0 ? number_format($stackRun, 2, '.', ',') : '-';
            default:
                return '-';
        }
    }

    /**
     * Billing statement itemized rows (has_details): one row per waybill so description, rate, and total
     * match that waybill only (no aggregation across waybills with the same container size/type).
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\WaybillDetail>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\WaybillDetail> $waybills
     * @return array<int, array{date: string, description: string, size: string, rate_per_trip: float, total_amount: float}>
     */
    private function buildBillingStatementDetailsRows($waybills, BillingStatement $billingStatement): array
    {
        $detailsData = [];
        foreach ($waybills as $waybill) {
            $rawSize = $waybill->container_size ?? '';
            $rawType = $waybill->container_type ?? '';

            $sizeNormalized = trim(str_ireplace(['offhire', 'ft'], '', $rawSize));
            $sizeNumeric = preg_replace('/[^0-9]/', '', $sizeNormalized) ?: preg_replace('/[^0-9]/', '', $rawSize);

            $typeCode = 'HC';
            if (stripos($rawSize, 'offhire') !== false) {
                $typeCode = 'AC';
            } elseif ($rawType) {
                $typeUpper = strtoupper(trim($rawType));
                if ($typeUpper === 'REEFER') {
                    $typeCode = 'R';
                } elseif ($typeUpper === 'DRY' || $typeUpper === '') {
                    $typeCode = 'HC';
                } else {
                    $typeCode = $typeUpper;
                }
            }

            $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();
            if ($containerCount == 0) {
                $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0) {
                    $containerCount = 1;
                }
            }

            // Match buildSoaTransactionData: rate is per container (use first); total_rate_per_client when rate unset.
            // Using total_rate_per_client before rate caused double multiplication when both were set (e.g. offhire 102 = 51×2).
            $unitBeforeVat = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
            $hasVat = (bool) ($waybill->has_vat ?? false);
            $waybillTotal = $unitBeforeVat * $containerCount;
            $totalAmount = $hasVat ? $waybillTotal * 1.12 : $waybillTotal;
            $ratePerTrip = $containerCount > 0 ? $totalAmount / $containerCount : 0;
            $description = $containerCount . 'X' . $sizeNumeric . $typeCode . ' UNIT';

            $detailsData[] = [
                'date' => $this->billingPdfDetailLineDate([$waybill], $billingStatement),
                'description' => $description,
                'size' => $sizeNumeric,
                'rate_per_trip' => $ratePerTrip,
                'total_amount' => $totalAmount,
            ];
        }

        return $detailsData;
    }

    /**
     * Date for billing PDF "DATE" column: same source as SOA table (waybill transaction_date, d-M). Falls back to billing ci_date.
     *
     * @param iterable<int, \App\Models\WaybillDetail> $waybillsInGroup
     */
    private function billingPdfDetailLineDate(iterable $waybillsInGroup, BillingStatement $billingStatement): string
    {
        $minDate = null;
        foreach ($waybillsInGroup as $waybill) {
            $td = $waybill->transaction_date ?? null;
            if ($td && ($minDate === null || $td->lt($minDate))) {
                $minDate = $td;
            }
        }
        if ($minDate !== null) {
            return $minDate->format('d-M');
        }
        if ($billingStatement->ci_date) {
            return $billingStatement->ci_date->format('d-M');
        }

        return '';
    }

    /**
     * Retrieve all billing statements with paginate.
     */
    public function listBillingStatements($perPage = 10, $trash = false)
    {
        try {
            $allBillingStatements = BillingStatement::count();
            $trashedBillingStatements = BillingStatement::onlyTrashed()->count();

            $query = $this->buildBillingStatementsQuery($trash);

            return BillingStatementResource::collection(
                $query->with(['statementOfAccount', 'shippingLine', 'preparedByUser.getUserMetas'])->paginate($perPage)->withQueryString()
            )->additional([
                        'meta' => [
                            'all' => $allBillingStatements,
                            'trashed' => $trashedBillingStatements
                        ]
                    ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch billing statements: ' . $e->getMessage());
        }
    }

    /**
     * Export filtered billing statements as CSV (no pagination).
     */
    public function exportBillingStatementsCsv(): StreamedResponse
    {
        $headers = [
            'id',
            'billing_statement_no',
            'statement_of_account_id',
            'dli_sa_number',
            'work_order',
            'shipping_line_id',
            'shipping_line_name',
            'payment_term',
            'ci_date',
            'due_date',
            'bus_style',
            'has_details',
            'is_paid',
            'prepared_by',
            'booking_ids',
            'created_at',
        ];

        $rows = function () {
            $query = $this->buildBillingStatementsQuery(false)
                ->with(['statementOfAccount', 'shippingLine', 'preparedByUser.getUserMetas']);

            foreach ($query->cursor() as $billingStatement) {
                $data = (new BillingStatementResource($billingStatement))->toArray(request());
                $soa = $data['statement_of_account'] ?? [];

                yield [
                    $data['id'],
                    $data['billing_statement_no'],
                    $data['statement_of_account_id'],
                    $soa['dli_sa_number'] ?? '',
                    $soa['work_order'] ?? '',
                    $data['shipping_line_id'],
                    $data['shipping_line']['name'] ?? '',
                    $data['payment_term'],
                    $data['ci_date'],
                    $data['due_date'],
                    $data['bus_style'],
                    CsvExportHelper::formatBool($data['has_details']),
                    CsvExportHelper::formatBool($data['is_paid']),
                    $data['prepared_by'] ?? '',
                    CsvExportHelper::formatIds($data['booking_ids'] ?? []),
                    $data['created_at'],
                ];
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('billing-statements-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build billing statement list/export query with shared filters.
     */
    private function buildBillingStatementsQuery(bool $trash = false): Builder
    {
        $query = BillingStatement::query();

        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $query->where(function ($q) {
                $q->where('billing_statement_no', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('payment_term', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('bus_style', 'LIKE', '%' . request('search') . '%')
                    ->orWhereHas('shippingLine', function ($query) {
                        $query->where('name', 'LIKE', '%' . request('search') . '%');
                    });
            });
        }

        if (request('shipping_line_id')) {
            $query->whereHas('statementOfAccount', fn($q) => $q->where('shipping_line_id', request('shipping_line_id')));
        }

        if (request('booking_id')) {
            $query->whereHas('statementOfAccount', function ($q) {
                $q->whereJsonContains('booking_ids', (int) request('booking_id'));
            });
        }

        if (request()->has('is_paid')) {
            $query->where('is_paid', request('is_paid'));
        }

        if (request('order')) {
            $query->orderBy(request('order'), request('sort') ?? 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        if (request('statement_of_account_id')) {
            $query->where('statement_of_account_id', request('statement_of_account_id'));
        }

        if (request('date_from')) {
            $query->whereDate('billing_statements.created_at', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('billing_statements.created_at', '<=', request('date_to'));
        }

        return $query;
    }

    /**
     * Generate a new billing statement.
     *
     * @param array $data
     * @return BillingStatementResource
     */
    public function generateBillingStatement(array $data)
    {
        try {
            $soa = \App\Models\StatementOfAccount::find($data['statement_of_account_id'] ?? null);
            if (!$soa) {
                throw new \Exception('The selected statement of account does not exist.');
            }

            $bookingIds = $soa->booking_ids ?? [];
            $waybillCount = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)->count();
            if ($waybillCount === 0) {
                throw new \Exception('The selected statement of account must have at least one waybill (across its bookings).');
            }

            // Set prepared_by to current authenticated user if not provided
            if (!isset($data['prepared_by']) && auth()->check()) {
                $data['prepared_by'] = auth()->id();
            }

            unset($data['is_paid']);

            $billingStatement = BillingStatement::create($data);
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser.getUserMetas']);

            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line, booking, or user not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Build Billing Statement PDF/CSV document data (same content as billing PDF).
     */
    public function prepareBillingStatementPdfData(int $id): array
    {
        $billingStatement = BillingStatement::with([
            'statementOfAccount',
            'shippingLine',
            'preparedByUser.getUserMetas',
        ])->findOrFail($id);

        $bookingIds = $billingStatement->statementOfAccount->booking_ids ?? [];
        $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
            ->orderBy('booking_id')
            ->orderBy('id')
            ->get();

        $detailsData = [];
        $grandTotal = 0;
        $hasDetails = (bool) $billingStatement->has_details;

        foreach ($waybills as $waybill) {
            $unitBeforeVat = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
            $hasVat = (bool) ($waybill->has_vat ?? false);
            $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();
            if ($containerCount == 0) {
                $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0) {
                    $containerCount = 1;
                }
            }
            $waybillTotal = $unitBeforeVat * $containerCount;
            $grandTotal += $hasVat ? $waybillTotal * 1.12 : $waybillTotal;
        }

        if ($hasDetails && $waybills->isNotEmpty()) {
            $detailsData = $this->buildBillingStatementDetailsRows($waybills, $billingStatement);
        } else {
            $firstBooking = $billingStatement->statementOfAccount && !empty($billingStatement->statementOfAccount->booking_ids)
                ? \App\Models\Booking::find($billingStatement->statementOfAccount->booking_ids[0])
                : null;
            $reference = $firstBooking ? $firstBooking->reference_number : '';
            $billingNo = $billingStatement->billing_statement_no ?? '';
            $descriptionLines = [
                'Reference ' . ($reference ?: '-'),
                'Billing # ' . ($billingNo ?: '-'),
            ];
            $workOrder = $billingStatement->statementOfAccount?->work_order;
            if ($workOrder !== null && $workOrder !== '') {
                $descriptionLines[] = 'Booking ' . $workOrder;
            }
            $detailsData[] = [
                'date' => $this->billingPdfDetailLineDate($waybills, $billingStatement),
                'description' => implode("\n", $descriptionLines),
                'description_lines' => $descriptionLines,
                'size' => '',
                'rate_per_trip' => null,
                'total_amount' => $grandTotal,
            ];
        }

        return [
            'billingStatement' => $billingStatement,
            'companyInfo' => [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'Blk 8 Lot 11 North Harbor Center Vitas St Barangay 101 Zone 08, 1013 Tondo I/II NCR, City of Manila, First District Philippines',
                'phone' => 'Tel. No. (02) 8291-4477',
                'tin' => 'VAT Reg. TIN.: 010-392-323-00000',
            ],
            'detailsData' => $detailsData,
            'grandTotal' => $grandTotal,
            'hasDetails' => $hasDetails,
            'issueDate' => $billingStatement->ci_date ? $billingStatement->ci_date->format('F d, Y') : now()->format('F d, Y'),
            'logoPath' => public_path('images/deltrans-logo.png'),
            'attachment_paths' => [],
        ];
    }

    /**
     * Export Billing Statement document as CSV (same content as billing PDF).
     */
    public function exportBillingStatementDocumentCsv(int $id): StreamedResponse
    {
        $data = $this->prepareBillingStatementPdfData($id);
        $bs = $data['billingStatement'];
        $sl = $bs->shippingLine;
        $filename = FinancialDocumentCsvHelper::sanitizeFilename($bs->billing_statement_no ?? '', 'billing-' . $id) . '.csv';

        return FinancialDocumentCsvHelper::streamDownload($filename, function ($handle) use ($data, $bs, $sl) {
            $this->writeBillingStatementDocumentCsv($handle, $data, $bs, $sl);
        });
    }

    /**
     * Export combined Billing + SOA document as CSV by SOA ID (same content as combined PDF).
     */
    public function exportBillingAndSoaDocumentCsvBySoaId(int $soaId): StreamedResponse
    {
        $soa = StatementOfAccount::findOrFail($soaId);
        $billingStatement = BillingStatement::where('statement_of_account_id', $soa->id)->orderBy('id')->first();
        if (!$billingStatement) {
            throw new \Exception('No billing statement found for this statement of account.');
        }

        $billingData = $this->prepareBillingStatementPdfData($billingStatement->id);
        $soaData = $this->prepareSoaPdfData($soaId);
        $filename = FinancialDocumentCsvHelper::sanitizeFilename(
            ($billingStatement->billing_statement_no ?? 'billing') . '_' . ($soa->dli_sa_number ?? 'soa'),
            'billing-soa-' . $soaId
        ) . '.csv';

        return FinancialDocumentCsvHelper::streamDownload($filename, function ($handle) use ($billingData, $soaData) {
            $bs = $billingData['billingStatement'];
            $sl = $bs->shippingLine;
            $this->writeBillingStatementDocumentCsv($handle, $billingData, $bs, $sl);

            FinancialDocumentCsvHelper::writeBlankRow($handle);
            FinancialDocumentCsvHelper::writeBlankRow($handle);

            $soa = $soaData['soa'];
            $shippingLine = $soa->shippingLine;
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Statement of Account');
            FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
                ['Date', $soaData['issueDate']],
                ['DLI-SA#', str_replace('SA-', '', (string) $soa->dli_sa_number)],
                ['Billed To', $shippingLine->name ?? ''],
                ['Address', $shippingLine->address ?? ''],
                ['Attn', $shippingLine->contact_name ?? ''],
            ]);
            FinancialDocumentCsvHelper::writeBlankRow($handle);
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Transactions');
            $headers = $soaData['transactionColumns']->pluck('name')->all();
            $rows = [];
            foreach ($soaData['transactionData'] as $row) {
                $line = [];
                foreach ($headers as $header) {
                    $line[] = $row[$header] ?? '';
                }
                $rows[] = $line;
            }
            FinancialDocumentCsvHelper::writeTable($handle, $headers, $rows);
            FinancialDocumentCsvHelper::writeBlankRow($handle);
            FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Totals');
            FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
                ['SUBTOTAL', 'PHP ' . number_format($soaData['totalAmount'], 2, '.', ',')],
                ['VAT (12%)', $soaData['totalVat'] > 0 ? 'PHP ' . number_format($soaData['totalVat'], 2, '.', ',') : ''],
                ['TOTAL', 'PHP ' . number_format($soaData['grandTotal'], 2, '.', ',')],
            ]);
        });
    }

    /**
     * @param resource $handle
     */
    private function writeBillingStatementDocumentCsv($handle, array $data, BillingStatement $bs, $sl): void
    {
        FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Billing Statement');
        FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
            ['Billing statement No', str_replace('BS-', '', (string) $bs->billing_statement_no)],
            ['Billed To', $sl->name ?? ''],
            ['Address', $sl->address ?? ''],
            ['TIN', $sl->tin ?? ''],
            ['CI Date', $data['issueDate']],
            ['Pay Term', $bs->payment_term ?? ''],
            ['Due Date', $bs->due_date ? $bs->due_date->format('F d, Y') : ''],
            ['Bus. Style', $bs->bus_style ?? ''],
        ]);

        FinancialDocumentCsvHelper::writeBlankRow($handle);
        FinancialDocumentCsvHelper::writeSectionTitle($handle, 'Charges');

        $tableRows = [];
        foreach ($data['detailsData'] as $detail) {
            $description = !empty($detail['description_lines'])
                ? implode(' | ', $detail['description_lines'])
                : ($detail['description'] ?? '');
            $rate = isset($detail['rate_per_trip']) && $detail['rate_per_trip'] !== null
                ? number_format((float) $detail['rate_per_trip'], 2, '.', ',')
                : '';
            $tableRows[] = [
                $detail['date'] ?? '',
                $description,
                $detail['size'] ?? '',
                $rate,
                number_format((float) ($detail['total_amount'] ?? 0), 2, '.', ','),
            ];
        }
        FinancialDocumentCsvHelper::writeTable(
            $handle,
            ['DATE', 'DESCRIPTION OF CHARGES', 'SIZE', 'RATE OF TRIP', 'TOTAL AMOUNT'],
            $tableRows
        );

        FinancialDocumentCsvHelper::writeBlankRow($handle);
        FinancialDocumentCsvHelper::writeKeyValueRows($handle, [
            ['GRAND TOTAL', 'PHP ' . number_format($data['grandTotal'], 2, '.', ',')],
        ]);
    }

    /**
     * Generate PDF for Billing Statement. Returns PDF binary (not saved to disk).
     *
     * @param int $id Billing Statement ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @return string PDF binary content
     */
    public function generateBillingStatementPdf($id, ?int $attachmentUserId = null, bool $includeAttachments = false)
    {
        try {
            $data = $this->prepareBillingStatementPdfData((int) $id);

            if ($includeAttachments && $attachmentUserId !== null) {
                $data['attachment_paths'] = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing-statement.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $output = $pdf->output();
            if ($attachmentUserId !== null) {
                $this->deleteTempAttachmentsByUser($attachmentUserId);
            }
            return $output;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate a single 2-page PDF: Page 1 = Billing Statement, Page 2 = SOA.
     * One view, one PDF output (no separate generation or merge). Returns PDF binary (not saved to disk).
     *
     * @param int $billingStatementId
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @return string PDF binary content
     */
    public function generateBillingAndSoaCombinedPdf($billingStatementId, ?int $attachmentUserId = null, bool $includeAttachments = false)
    {
        try {
            $billingData = $this->prepareBillingStatementPdfData((int) $billingStatementId);
            $billingStatement = $billingData['billingStatement'];
            $soa = $billingStatement->statementOfAccount;
            if (!$soa) {
                throw new \Exception('Billing statement has no linked statement of account.');
            }

            $soaData = $this->prepareSoaPdfData((int) $soa->id);

            $attachmentPaths = [];
            if ($includeAttachments && $attachmentUserId !== null) {
                $attachmentPaths = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $data = [
                'billingStatement' => $billingStatement,
                'companyInfo' => $billingData['companyInfo'],
                'logoPath' => $billingData['logoPath'],
                'billingIssueDate' => $billingData['issueDate'],
                'detailsData' => $billingData['detailsData'],
                'billingGrandTotal' => $billingData['grandTotal'],
                'hasDetails' => $billingData['hasDetails'],
                'soa' => $soaData['soa'],
                'soaCompanyInfo' => $soaData['companyInfo'],
                'soaIssueDate' => $soaData['issueDate'],
                'transactionColumns' => $soaData['transactionColumns'],
                'transactionData' => $soaData['transactionData'],
                'soaTotalAmount' => $soaData['totalAmount'],
                'soaTotalVat' => $soaData['totalVat'],
                'soaGrandTotal' => $soaData['grandTotal'],
                'attachment_paths' => $attachmentPaths,
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing-and-soa.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $output = $pdf->output();
            if ($attachmentUserId !== null) {
                $this->deleteTempAttachmentsByUser($attachmentUserId);
            }
            return $output;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate combined PDF: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a single billing statement by ID with relationships.
     *
     * @param int $id
     * @return BillingStatementResource
     */
    public function showBillingStatement($id)
    {
        try {
            $billingStatement = BillingStatement::with([
                'statementOfAccount',
                'shippingLine',
                'preparedByUser.getUserMetas'
            ])->findOrFail($id);

            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch billing statement: ' . $e->getMessage());
        }
    }

    /**
     * Update a billing statement by ID.
     *
     * @param int $id
     * @param array $data Only provided keys are updated.
     * @return BillingStatementResource
     */
    public function updateBillingStatement($id, array $data)
    {
        try {
            $billingStatement = BillingStatement::findOrFail($id);
            unset($data['is_paid']);
            $billingStatement->update(array_intersect_key($data, array_flip($billingStatement->getFillable())));
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser.getUserMetas']);
            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Send SOA PDF via email to shipping line
     *
     * @param int $id SOA ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @param string|null $customEmail Custom email address (overrides shipping line email)
     * @param array $cc CC recipients (optional)
     * @param string|null $subject Custom email subject (optional; if empty, standard subject is used)
     * @param string|null $body Custom email body HTML (optional; if empty, standard body is used)
     * @return bool Success status
     */
    public function sendSoaEmail($id, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [], ?string $subject = null, ?string $body = null)
    {
        try {
            $soa = StatementOfAccount::with('shippingLine')->findOrFail($id);
            $emailService = app(EmailService::class);

            // Use custom email if provided, otherwise use shipping line email
            $recipientEmail = $customEmail ?? $soa->shippingLine->email_address;

            if (empty($recipientEmail)) {
                throw new \Exception('No email address found for shipping line.');
            }

            // Generate PDF
            $pdfContent = $this->generatePdf($id, $attachmentUserId, $includeAttachments);
            $pdfFilename = $soa->dli_sa_number . '.pdf';

            // Prepare email: use custom subject/body if provided and non-empty, otherwise standard
            $subject = trim((string) $subject) !== '' ? trim($subject) : 'Statement of Account - ' . $soa->dli_sa_number;
            $defaultBody = '<h2>Statement of Account</h2>'
                . '<p>Dear ' . ($soa->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Statement of Account for ' . $soa->dli_sa_number . '.</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            $body = trim((string) $body) !== '' ? trim($body) : $defaultBody;

            // Send email
            $emailService->sendEmailWithAttachment($recipientEmail, $subject, $body, $pdfContent, $pdfFilename, $cc);

            Log::info('[SoaAndBillingService] SOA email sent', [
                'soa_id' => $id,
                'to' => $recipientEmail,
                'soa_number' => $soa->dli_sa_number
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[SoaAndBillingService] Failed to send SOA email', [
                'soa_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send Billing Statement PDF via email to shipping line
     *
     * @param int $id Billing Statement ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @param string|null $customEmail Custom email address (overrides shipping line email)
     * @param array $cc CC recipients (optional)
     * @param string|null $subject Custom email subject (optional; if empty, standard subject is used)
     * @param string|null $body Custom email body HTML (optional; if empty, standard body is used)
     * @return bool Success status
     */
    public function sendBillingStatementEmail($id, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [], ?string $subject = null, ?string $body = null)
    {
        try {
            $billingStatement = BillingStatement::with(['statementOfAccount.shippingLine'])->findOrFail($id);
            $emailService = app(EmailService::class);

            // Use custom email if provided, otherwise use shipping line email
            $recipientEmail = $customEmail ?? $billingStatement->statementOfAccount->shippingLine->email_address;

            if (empty($recipientEmail)) {
                throw new \Exception('No email address found for shipping line.');
            }

            // Generate PDF
            $pdfContent = $this->generateBillingStatementPdf($id, $attachmentUserId, $includeAttachments);
            $pdfFilename = $billingStatement->billing_statement_no . '.pdf';

            // Prepare email: use custom subject/body if provided and non-empty, otherwise standard
            $subject = trim((string) $subject) !== '' ? trim($subject) : 'Billing Statement - ' . $billingStatement->billing_statement_no;
            $defaultBody = '<h2>Billing Statement</h2>'
                . '<p>Dear ' . ($billingStatement->statementOfAccount->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Billing Statement for ' . $billingStatement->billing_statement_no . '.</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            $body = trim((string) $body) !== '' ? trim($body) : $defaultBody;

            // Send email
            $emailService->sendEmailWithAttachment($recipientEmail, $subject, $body, $pdfContent, $pdfFilename, $cc);

            Log::info('[SoaAndBillingService] Billing Statement email sent', [
                'billing_statement_id' => $id,
                'to' => $recipientEmail,
                'billing_statement_no' => $billingStatement->billing_statement_no
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[SoaAndBillingService] Failed to send Billing Statement email', [
                'billing_statement_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send Combined Billing Statement + SOA PDF via email to shipping line
     *
     * @param int $billingStatementId Billing Statement ID
     * @param int|null $attachmentUserId User ID whose temp attachments folder to use
     * @param bool $includeAttachments Whether to append attachment pages to the PDF
     * @param string|null $customEmail Custom email address (overrides shipping line email)
     * @param array $cc CC recipients (optional)
     * @param string|null $subject Custom email subject (optional; if empty, standard subject is used)
     * @param string|null $body Custom email body HTML (optional; if empty, standard body is used)
     * @return bool Success status
     */
    public function sendBillingAndSoaEmail($billingStatementId, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [], ?string $subject = null, ?string $body = null)
    {
        try {
            $billingStatement = BillingStatement::with(['statementOfAccount.shippingLine'])->findOrFail($billingStatementId);
            $emailService = app(EmailService::class);

            // Use custom email if provided, otherwise use shipping line email
            $recipientEmail = $customEmail ?? $billingStatement->statementOfAccount->shippingLine->email_address;

            if (empty($recipientEmail)) {
                throw new \Exception('No email address found for shipping line.');
            }

            // Generate PDF
            $pdfContent = $this->generateBillingAndSoaCombinedPdf($billingStatementId, $attachmentUserId, $includeAttachments);
            $soa = $billingStatement->statementOfAccount;
            $pdfFilename = ($billingStatement->billing_statement_no ?? 'billing') . '_' . ($soa->dli_sa_number ?? 'soa') . '.pdf';

            // Prepare email: use custom subject/body if provided and non-empty, otherwise standard
            $subject = trim((string) $subject) !== '' ? trim($subject) : 'Billing Statement & Statement of Account - ' . $billingStatement->billing_statement_no;
            $defaultBody = '<h2>Billing Statement & Statement of Account</h2>'
                . '<p>Dear ' . ($billingStatement->statementOfAccount->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Billing Statement (' . $billingStatement->billing_statement_no . ') and Statement of Account (' . ($soa->dli_sa_number ?? 'N/A') . ').</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            $body = trim((string) $body) !== '' ? trim($body) : $defaultBody;

            // Send email
            $emailService->sendEmailWithAttachment($recipientEmail, $subject, $body, $pdfContent, $pdfFilename, $cc);

            Log::info('[SoaAndBillingService] Combined Billing & SOA email sent', [
                'billing_statement_id' => $billingStatementId,
                'to' => $recipientEmail,
                'billing_statement_no' => $billingStatement->billing_statement_no
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[SoaAndBillingService] Failed to send Combined Billing & SOA email', [
                'billing_statement_id' => $billingStatementId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}


//todo: SOA PDF and SOA + Billing PDF: remove grid in table
//todo: Rearrange columns in table of SOA of SOA PDF and SOA of SOA + Billing PDF
// Date
// Plate No
// Waybill No
// Container No
// Origin
// Destination
// Remarks
// Size
// Amount
// 12% Vat*
// Total Amount*
// Stack Run*
// Work Order*
// Booking No

//todo: in table of SOA of SOA PDF and SOA of SOA + Billing PDF, instead of cypa.name, use cypa.short_name
