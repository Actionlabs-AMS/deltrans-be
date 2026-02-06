<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Models\BillingStatement;
use App\Models\SoaDataOption;
use App\Http\Resources\SoaAndBillingResource;
use App\Http\Resources\BillingStatementResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

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
     * Generate a new statement of account.
     *
     * @param array $data
     * @return SoaAndBillingResource
     */
    public function generate(array $data)
    {
        try {
            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);
            $booking = \App\Models\Booking::findOrFail($data['booking_id']);
            $waybillCount = \App\Models\WaybillDetail::where('booking_id', $booking->id)->count();

            if ($waybillCount === 0) {
                throw new \Exception('The selected booking must have at least one waybill.');
            }

            if ($booking->shipping_line_id != $data['shipping_line_id']) {
                throw new \Exception('The booking does not belong to the selected shipping line.');
            }

            $soa = StatementOfAccount::create($data);
            $soa->load(['shippingLine', 'booking']);

            return SoaAndBillingResource::make($soa);
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
            $soa = StatementOfAccount::with([
                'shippingLine',
                'booking.waybills' => function ($query) {
                    $query->with([
                        'shippingLine',
                        'driver',
                        'fleetTruck',
                        'fixedExpense',
                    ]);
                }
            ])->findOrFail($id);

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
     * @param array $data Only provided keys are updated.
     * @return SoaAndBillingResource
     */
    public function updateSoa($id, array $data)
    {
        try {
            $soa = StatementOfAccount::findOrFail($id);
            $soa->update(array_intersect_key($data, array_flip($soa->getFillable())));
            $soa->load(['shippingLine', 'booking']);
            return SoaAndBillingResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Generate PDF for Statement of Account.
     *
     * @param int $id SOA ID
     * @return string Path to the generated PDF file
     */
    public function generatePdf($id)
    {
        try {
            $soa = StatementOfAccount::with([
                'shippingLine',
                'booking' => function ($query) {
                    $query->with(['cypaFrom', 'cypaTo', 'containers']);
                },
                'booking.waybills' => function ($query) {
                    $query->with([
                        'shippingLine',
                        'driver',
                        'fleetTruck',
                        'fixedExpense',
                        'booking' => function ($q) {
                            $q->with(['cypaFrom', 'cypaTo', 'containers']);
                        }
                    ]);
                }
            ])->findOrFail($id);

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

            $waybills = $soa->booking->waybills ?? collect();
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
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('soa.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $directory = 'soa-pdfs/' . date('Y/m');
            Storage::disk('public')->makeDirectory($directory);
            $filename = $soa->dli_sa_number . '_' . time() . '.pdf';
            $filePath = $directory . '/' . $filename;
            Storage::disk('public')->put($filePath, $pdf->output());

            return $filePath;
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
                $containers = $waybill->booking->containers ?? collect();
                $container = $containers->where('waybill_id', $waybill->id)->first();
                if (!$container && $soa->booking) {
                    $containers = $soa->booking->containers ?? collect();
                    $container = $containers->where('waybill_id', $waybill->id)->first();
                }
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
                $query->whereHas('statementOfAccount', fn ($q) => $q->where('shipping_line_id', request('shipping_line_id')));
            }

            if (request('booking_id')) {
                $query->whereHas('statementOfAccount', fn ($q) => $q->where('booking_id', request('booking_id')));
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
                $query->with(['statementOfAccount', 'shippingLine', 'booking', 'preparedByUser'])->paginate($perPage)->withQueryString()
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
            $soa = \App\Models\StatementOfAccount::with(['booking'])->find($data['statement_of_account_id'] ?? null);
            if (!$soa) {
                throw new \Exception('The selected statement of account does not exist.');
            }

            $waybillCount = \App\Models\WaybillDetail::where('booking_id', $soa->booking_id)->count();
            if ($waybillCount === 0) {
                throw new \Exception('The selected booking must have at least one waybill.');
            }

            // Set prepared_by to current authenticated user if not provided
            if (!isset($data['prepared_by']) && auth()->check()) {
                $data['prepared_by'] = auth()->id();
            }

            $billingStatement = BillingStatement::create($data);
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'booking', 'preparedByUser']);

            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line, booking, or user not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Generate PDF for Billing Statement.
     *
     * @param int $id Billing Statement ID
     * @return string Path to the generated PDF file
     */
    public function generateBillingStatementPdf($id)
    {
        try {
            $billingStatement = BillingStatement::with([
                'statementOfAccount',
                'shippingLine',
                'booking',
                'preparedByUser'
            ])->findOrFail($id);

            $waybills = \App\Models\WaybillDetail::where('booking_id', $billingStatement->booking_id)
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
                $reference = $billingStatement->booking ? $billingStatement->booking->reference_number : '';
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

            $data = [
                'billingStatement' => $billingStatement,
                'companyInfo' => $companyInfo,
                'detailsData' => $detailsData,
                'grandTotal' => $grandTotal,
                'hasDetails' => $hasDetails,
                'issueDate' => $billingStatement->ci_date ? $billingStatement->ci_date->format('F d, Y') : now()->format('F d, Y'),
                'logoPath' => $logoPath,
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing-statement.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $directory = 'billing-statement-pdfs/' . date('Y/m');
            Storage::disk('public')->makeDirectory($directory);
            $filename = $billingStatement->billing_statement_no . '_' . time() . '.pdf';
            $filePath = $directory . '/' . $filename;
            Storage::disk('public')->put($filePath, $pdf->output());

            return $filePath;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
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
                'booking',
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
            $billingStatement->load(['statementOfAccount', 'shippingLine', 'booking', 'preparedByUser']);
            return BillingStatementResource::make($billingStatement);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Billing statement not found.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}


//todo: soa and billing - no tax, only vat (12%)
//! invoice - add tax base on rate per client
//! 