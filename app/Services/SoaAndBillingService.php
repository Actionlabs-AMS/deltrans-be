<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Models\BillingStatement;
use App\Models\SoaDataOption;
use App\Http\Resources\SoaAndBillingResource;
use App\Http\Resources\BillingStatementResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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

            $query = StatementOfAccount::query();

            if ($trash) {
                $query->onlyTrashed();
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
                'is_paid' => $data['is_paid'] ?? false,
            ];
            $billingStatement = BillingStatement::create($billingData);
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser']);

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

                // Reorder columns: put AMOUNT before VAT
                $columnNames = $transactionColumns->pluck('name')->map('strtolower')->toArray();
                $amountIndex = array_search('amount', $columnNames);

                // Find VAT column (try different variations)
                $vatIndex = array_search('vat', $columnNames);
                if ($vatIndex === false) {
                    $vatIndex = array_search('12% vat', $columnNames);
                }
                if ($vatIndex === false) {
                    $vatIndex = array_search('12%vat', $columnNames);
                }

                if ($amountIndex !== false && $vatIndex !== false && $amountIndex > $vatIndex) {
                    // Swap positions: move AMOUNT before VAT (preserve model objects)
                    $columnsArray = $transactionColumns->all();
                    $amountColumn = $columnsArray[$amountIndex];
                    $vatColumn = $columnsArray[$vatIndex];
                    $columnsArray[$vatIndex] = $amountColumn;
                    $columnsArray[$amountIndex] = $vatColumn;
                    $transactionColumns = collect($columnsArray);
                }
            }

            $transactionData = [];
            foreach ($waybills as $waybill) {
                $row = [];
                foreach ($transactionColumns as $column) {
                    $row[$column->name] = $this->mapTransactionField($column->name, $waybill, $soa);
                }
                $transactionData[] = $row;
            }

            $totalAmount = 0;
            $totalVat = 0; // Sum of 12% VAT only for waybills where has_vat = true (stored on waybill)

            $vatPercent = 12.00;

            foreach ($waybills as $waybill) {
                $amount = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $waybillHasVat = (bool) ($waybill->has_vat ?? false);
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(function ($query) use ($waybill) {
                            $query->where('cypa_id', $waybill->booking->cypa_id_from)
                                ->orWhere('cypa_id', 0);
                        })
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = (float) ($matchingRate->rate ?? 0);
                        $waybillHasVat = (bool) ($matchingRate->has_vat ?? false);
                    }
                }
                $totalAmount += $amount;
                if ($waybillHasVat) {
                    $totalVat += $amount * ($vatPercent / 100);
                }
            }

            $vatRate = $vatPercent / 100;
            $grandTotal = $totalAmount + $totalVat;

            $companyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'BLK 18 LOT 11, MANILA HARBOUR CENTRE, VITAS TONDO MANILA',
                'phone' => 'Tel# 02-8291-4477',
            ];

            $logoPath = public_path('images/deltrans-logo.png');

            $attachmentPaths = [];
            if ($includeAttachments && $attachmentUserId !== null) {
                $attachmentPaths = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $data = [
                'soa' => $soa,
                'companyInfo' => $companyInfo,
                'shippingLineColumns' => $shippingLineColumns,
                'transactionColumns' => $transactionColumns,
                'transactionData' => $transactionData,
                'totalAmount' => $totalAmount,
                'vatRate' => $vatRate,
                'taxPercent' => $vatPercent,
                'totalVat' => $totalVat,
                'grandTotal' => $grandTotal,
                'issueDate' => now()->format('F d, Y'),
                'logoPath' => $logoPath,
                'attachment_paths' => $attachmentPaths,
            ];

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

    private function mapTransactionField($fieldName, $waybill, $soa)
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
                $containers = $waybill->booking && $waybill->relationLoaded('booking')
                    ? ($waybill->booking->containers ?? collect())
                    : collect();
                $container = $containers->where('waybill_id', $waybill->id)->first();
                return $container ? $container->container_number : '-';
            case 'origin':
            case 'from':
                return $waybill->booking->cypaFrom ? $waybill->booking->cypaFrom->name : '-';
            case 'destination':
            case 'to':
                return $waybill->booking->cypaTo ? $waybill->booking->cypaTo->name : '-';
            case 'remarks':
                return $waybill->remarks ?? '-';
            case 'size':
                $size = $waybill->container_size ?? '';
                if ($size) {
                    $sizeValue = preg_replace('/[^0-9]/', '', $size);
                    return $sizeValue == '40' ? '1X40HC' : ($sizeValue == '20' ? '1X20FR' : '1X' . $sizeValue);
                }
                return '-';
            case 'amount':
                $amount = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)->first();
                    if ($matchingRate) {
                        $amount = (float) ($matchingRate->rate ?? 0);
                    }
                }
                return number_format($amount, 2, '.', ',');
            case 'vessel':
                return $waybill->booking->vessel ?? '-';
            case 'vat':
            case '12% vat':
            case '12%vat':
                $amount = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = (float) ($matchingRate->rate ?? 0);
                        $hasVat = (bool) ($matchingRate->has_vat ?? false);
                    }
                }
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount * ($vatPercent / 100), 2, '.', ',');
            case 'total amount':
                $amount = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = (float) ($matchingRate->rate ?? 0);
                        $hasVat = (bool) ($matchingRate->has_vat ?? false);
                    }
                }
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount + ($amount * ($vatPercent / 100)), 2, '.', ',');
            case 'booking number':
            case 'booking no':
                return $waybill->booking->reference_number ?? '-';
            case 'work order':
                return $soa->work_order ?? '-';
            case 'stack run':
                $stackRun = (float) ($waybill->stack_run ?? 0);
                if ($stackRun == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)->first();
                    if ($matchingRate) {
                        $stackRun = (float) ($matchingRate->stack_run ?? 0);
                    }
                }
                return $stackRun > 0 ? number_format($stackRun, 2, '.', ',') : '-';
            default:
                return '-';
        }
    }

    /**
     * Retrieve all billing statements with paginate.
     */
    public function listBillingStatements($perPage = 10, $trash = false)
    {
        try {
            $allBillingStatements = BillingStatement::count();
            $trashedBillingStatements = BillingStatement::onlyTrashed()->count();

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

            return BillingStatementResource::collection(
                $query->with(['statementOfAccount', 'shippingLine', 'preparedByUser'])->paginate($perPage)->withQueryString()
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

            $billingStatement = BillingStatement::create($data);
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser']);

            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line, booking, or user not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
            $billingStatement = BillingStatement::with([
                'statementOfAccount',
                'shippingLine',
                'preparedByUser'
            ])->findOrFail($id);

            $bookingIds = $billingStatement->statementOfAccount->booking_ids ?? [];
            $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
                ->orderBy('booking_id')
                ->orderBy('id')
                ->get();

            $detailsData = [];
            $grandTotal = 0;
            $hasDetails = (bool) $billingStatement->has_details;

            // Same computation for grand total: sum over waybills of (base * 1.12 if has_vat else base)
            foreach ($waybills as $waybill) {
                $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                $grandTotal += $hasVat ? $base * 1.12 : $base;
            }

            if ($hasDetails && $waybills->isNotEmpty()) {
                // Template with itemized rows: group waybills by size + type, one row per group
                $grouped = [];
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

                    $key = $sizeNumeric . '|' . $typeCode;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'size_numeric' => $sizeNumeric,
                            'type_code' => $typeCode,
                            'quantity' => 0,
                            'waybills' => [],
                        ];
                    }
                    $grouped[$key]['quantity']++;
                    $grouped[$key]['waybills'][] = $waybill;
                }

                foreach ($grouped as $group) {
                    $totalAmount = 0;
                    $waybillsInGroup = $group['waybills'] ?? [];
                    foreach ($waybillsInGroup as $waybill) {
                        $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                        $hasVat = (bool) ($waybill->has_vat ?? false);
                        $totalAmount += $hasVat ? $base * 1.12 : $base;
                    }
                    $quantity = $group['quantity'];
                    $rateWithTax = $quantity > 0 ? $totalAmount / $quantity : 0;
                    $description = $quantity . 'X' . $group['size_numeric'] . $group['type_code'] . ' UNIT';

                    $detailsData[] = [
                        'date' => null,
                        'description' => $description,
                        'size' => $group['size_numeric'],
                        'rate_per_trip' => $rateWithTax,
                        'total_amount' => $totalAmount,
                    ];
                }
            } else {
                // Template has_details = false: single row — Reference, Billing #, Booking (from SOA work_order when present); Size and Rate of Trip blank; total = grandTotal
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
                    'date' => '',
                    'description' => implode("\n", $descriptionLines),
                    'description_lines' => $descriptionLines,
                    'size' => '',
                    'rate_per_trip' => null,
                    'total_amount' => $grandTotal,
                ];
            }

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

            $data = [
                'billingStatement' => $billingStatement,
                'companyInfo' => $companyInfo,
                'detailsData' => $detailsData,
                'grandTotal' => $grandTotal,
                'hasDetails' => $hasDetails,
                'issueDate' => $billingStatement->ci_date ? $billingStatement->ci_date->format('F d, Y') : now()->format('F d, Y'),
                'logoPath' => $logoPath,
                'attachment_paths' => $attachmentPaths,
            ];

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
            $billingStatement = BillingStatement::with([
                'statementOfAccount',
                'shippingLine',
                'preparedByUser'
            ])->findOrFail($billingStatementId);

            $soa = $billingStatement->statementOfAccount;
            if (!$soa) {
                throw new \Exception('Billing statement has no linked statement of account.');
            }

            // Build billing page data (same logic as generateBillingStatementPdf)
            $bookingIds = $soa->booking_ids ?? [];
            $waybills = \App\Models\WaybillDetail::whereIn('booking_id', $bookingIds)
                ->orderBy('booking_id')
                ->orderBy('id')
                ->get();
            $detailsData = [];
            $billingGrandTotal = 0;
            $hasDetails = (bool) $billingStatement->has_details;

            foreach ($waybills as $waybill) {
                $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $hasVat = (bool) ($waybill->has_vat ?? false);
                $billingGrandTotal += $hasVat ? $base * 1.12 : $base;
            }

            if ($hasDetails && $waybills->isNotEmpty()) {
                $grouped = [];
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
                        $typeCode = ($typeUpper === 'REEFER') ? 'R' : (($typeUpper === 'DRY' || $typeUpper === '') ? 'HC' : $typeUpper);
                    }
                    $key = $sizeNumeric . '|' . $typeCode;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = ['size_numeric' => $sizeNumeric, 'type_code' => $typeCode, 'quantity' => 0, 'waybills' => []];
                    }
                    $grouped[$key]['quantity']++;
                    $grouped[$key]['waybills'][] = $waybill;
                }
                foreach ($grouped as $group) {
                    $totalAmount = 0;
                    foreach ($group['waybills'] ?? [] as $waybill) {
                        $base = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                        $totalAmount += (bool) ($waybill->has_vat ?? false) ? $base * 1.12 : $base;
                    }
                    $quantity = $group['quantity'];
                    $detailsData[] = [
                        'date' => null,
                        'description' => $quantity . 'X' . $group['size_numeric'] . $group['type_code'] . ' UNIT',
                        'size' => $group['size_numeric'],
                        'rate_per_trip' => $quantity > 0 ? $totalAmount / $quantity : 0,
                        'total_amount' => $totalAmount,
                    ];
                }
            } else {
                $firstBooking = !empty($bookingIds) ? \App\Models\Booking::find($bookingIds[0]) : null;
                $reference = $firstBooking ? $firstBooking->reference_number : '';
                $billingNo = $billingStatement->billing_statement_no ?? '';
                $descriptionLines = ['Reference ' . ($reference ?: '-'), 'Billing # ' . ($billingNo ?: '-')];
                $workOrder = $soa->work_order;
                if ($workOrder !== null && $workOrder !== '') {
                    $descriptionLines[] = 'Booking ' . $workOrder;
                }
                $detailsData[] = [
                    'date' => '',
                    'description' => implode("\n", $descriptionLines),
                    'description_lines' => $descriptionLines,
                    'size' => '',
                    'rate_per_trip' => null,
                    'total_amount' => $billingGrandTotal,
                ];
            }

            $companyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'Blk 8 Lot 11 North Harbor Center Vitas St Barangay 101 Zone 08, 1013 Tondo I/II NCR, City of Manila, First District Philippines',
                'phone' => 'Tel. No. (02) 8291-4477',
                'tin' => 'VAT Reg. TIN.: 010-392-323-00000',
            ];
            $soaCompanyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'BLK 18 LOT 11, MANILA HARBOUR CENTRE, VITAS TONDO MANILA',
                'phone' => 'Tel# 02-8291-4477',
            ];
            $logoPath = public_path('images/deltrans-logo.png');
            $billingIssueDate = $billingStatement->ci_date ? $billingStatement->ci_date->format('F d, Y') : now()->format('F d, Y');

            // Load SOA with full relations and build SOA page data (same logic as generatePdf)
            $soaFull = StatementOfAccount::with('shippingLine')->findOrFail($soa->id);
            $soaBookingIds = $soaFull->booking_ids ?? [];
            $waybillsSoa = \App\Models\WaybillDetail::whereIn('booking_id', $soaBookingIds)
                ->with([
                    'shippingLine',
                    'driver',
                    'fleetTruck',
                    'fixedExpense',
                    'booking' => function ($qb) {
                        $qb->with(['cypaFrom', 'cypaTo', 'containers']);
                    }
                ])
                ->orderBy('booking_id')
                ->orderBy('id')
                ->get();

            $transactionTemplateIds = $soaFull->shippingLine->transaction_information_template ?? [];
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
                    $arr = $transactionColumns->all();
                    [$arr[$vatIndex], $arr[$amountIndex]] = [$arr[$amountIndex], $arr[$vatIndex]];
                    $transactionColumns = collect($arr);
                }
            }

            $transactionData = [];
            foreach ($waybillsSoa as $waybill) {
                $row = [];
                foreach ($transactionColumns as $column) {
                    $row[$column->name] = $this->mapTransactionField($column->name, $waybill, $soaFull);
                }
                $transactionData[] = $row;
            }

            $soaTotalAmount = 0;
            $soaTotalVat = 0;
            $vatPercent = 12.00;
            foreach ($waybillsSoa as $waybill) {
                $amount = (float) ($waybill->total_rate_per_client ?? $waybill->rate ?? 0);
                $waybillHasVat = (bool) ($waybill->has_vat ?? false);
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(function ($query) use ($waybill) {
                            $query->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0);
                        })
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = (float) ($matchingRate->rate ?? 0);
                        $waybillHasVat = (bool) ($matchingRate->has_vat ?? false);
                    }
                }
                $soaTotalAmount += $amount;
                if ($waybillHasVat) {
                    $soaTotalVat += $amount * ($vatPercent / 100);
                }
            }
            $soaGrandTotal = $soaTotalAmount + $soaTotalVat;
            $soaIssueDate = now()->format('F d, Y');

            $attachmentPaths = [];
            if ($includeAttachments && $attachmentUserId !== null) {
                $attachmentPaths = $this->getTempAttachmentPathsByUser($attachmentUserId);
            }

            $data = [
                'billingStatement' => $billingStatement,
                'companyInfo' => $companyInfo,
                'logoPath' => $logoPath,
                'billingIssueDate' => $billingIssueDate,
                'detailsData' => $detailsData,
                'billingGrandTotal' => $billingGrandTotal,
                'hasDetails' => $hasDetails,
                'soa' => $soaFull,
                'soaCompanyInfo' => $soaCompanyInfo,
                'soaIssueDate' => $soaIssueDate,
                'transactionColumns' => $transactionColumns,
                'transactionData' => $transactionData,
                'soaTotalAmount' => $soaTotalAmount,
                'soaTotalVat' => $soaTotalVat,
                'soaGrandTotal' => $soaGrandTotal,
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
                'preparedByUser'
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
            $billingStatement->update(array_intersect_key($data, array_flip($billingStatement->getFillable())));
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'preparedByUser']);
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
     * @return bool Success status
     */
    public function sendSoaEmail($id, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [])
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
            
            // Prepare email
            $subject = 'Statement of Account - ' . $soa->dli_sa_number;
            $body = '<h2>Statement of Account</h2>'
                . '<p>Dear ' . ($soa->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Statement of Account for ' . $soa->dli_sa_number . '.</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            
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
     * @return bool Success status
     */
    public function sendBillingStatementEmail($id, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [])
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
            
            // Prepare email
            $subject = 'Billing Statement - ' . $billingStatement->billing_statement_no;
            $body = '<h2>Billing Statement</h2>'
                . '<p>Dear ' . ($billingStatement->statementOfAccount->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Billing Statement for ' . $billingStatement->billing_statement_no . '.</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            
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
     * @return bool Success status
     */
    public function sendBillingAndSoaEmail($billingStatementId, ?int $attachmentUserId = null, bool $includeAttachments = false, $customEmail = null, $cc = [])
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
            
            // Prepare email
            $subject = 'Billing Statement & Statement of Account - ' . $billingStatement->billing_statement_no;
            $body = '<h2>Billing Statement & Statement of Account</h2>'
                . '<p>Dear ' . ($billingStatement->statementOfAccount->shippingLine->name ?? 'Valued Customer') . ',</p>'
                . '<p>Please find attached the Billing Statement (' . $billingStatement->billing_statement_no . ') and Statement of Account (' . ($soa->dli_sa_number ?? 'N/A') . ').</p>'
                . '<p>If you have any questions, please do not hesitate to contact us.</p>'
                . '<p>Best regards,<br>Deltrans Logistics Inc.</p>';
            
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


//todo: soa and billing - no tax, only vat (12%)
//! invoice - add tax base on rate per client
//! 