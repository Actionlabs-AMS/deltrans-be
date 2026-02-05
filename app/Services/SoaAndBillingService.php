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
                        'ratePerClient'
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
                        'ratePerClient',
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
            $totalVat = 0; // Sum of 12% VAT only for waybills where rate_per_client.has_vat = true

            // VAT (12%) is added only when rate_per_client.has_vat is true; otherwise no VAT
            $vatPercent = 12.00;

            foreach ($waybills as $waybill) {
                $amount = $waybill->total_rate_per_client ?? 0;
                $waybillHasVat = false;
                $rpc = null;

                if ($waybill->ratePerClient) {
                    $rpc = $waybill->ratePerClient;
                    if ($amount == 0) {
                        $amount = $rpc->rate ?? 0;
                    }
                    $waybillHasVat = $rpc->has_vat ?? false;
                }
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
                        if ($amount == 0) {
                            $amount = $matchingRate->rate ?? 0;
                        }
                        $waybillHasVat = $matchingRate->has_vat ?? false;
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
                return $waybill->ratePerClient ? ($waybill->ratePerClient->remarks ?? '-') : '-';
            case 'size':
                $size = $waybill->container_size ?? '';
                if ($size) {
                    $sizeValue = preg_replace('/[^0-9]/', '', $size);
                    return $sizeValue == '40' ? '1X40HC' : ($sizeValue == '20' ? '1X20FR' : '1X' . $sizeValue);
                }
                return '-';
            case 'amount':
                $amount = $waybill->total_rate_per_client ?? 0;
                if ($amount == 0 && $waybill->ratePerClient) {
                    $amount = $waybill->ratePerClient->rate ?? 0;
                }
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)->first();
                    if ($matchingRate) {
                        $amount = $matchingRate->rate ?? 0;
                    }
                }
                return number_format($amount, 2, '.', ',');
            case 'vessel':
                return $waybill->booking->vessel ?? '-';
            case 'vat':
            case '12% vat':
            case '12%vat':
                // Get amount (base rate)
                $amount = $waybill->total_rate_per_client ?? 0;
                $hasVat = false; // Default: no VAT

                // Check has_vat from rate_per_client
                if ($waybill->ratePerClient) {
                    $hasVat = $waybill->ratePerClient->has_vat ?? false;
                    if ($amount == 0) {
                        $amount = $waybill->ratePerClient->rate ?? 0;
                    }
                } elseif ($waybill->booking) {
                    // Try to find matching rate_per_client to get has_vat
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $hasVat = $matchingRate->has_vat ?? false;
                        if ($amount == 0) {
                            $amount = $matchingRate->rate ?? 0;
                        }
                    }
                }

                // Calculate VAT: 12% if has_vat is true, 0% if false
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount * ($vatPercent / 100), 2, '.', ',');
            case 'total amount':
                // Get amount (base rate)
                $amount = $waybill->total_rate_per_client ?? 0;
                $hasVat = false; // Default: no VAT

                // Check has_vat from rate_per_client
                if ($waybill->ratePerClient) {
                    $hasVat = $waybill->ratePerClient->has_vat ?? false;
                    if ($amount == 0) {
                        $amount = $waybill->ratePerClient->rate ?? 0;
                    }
                } elseif ($waybill->booking) {
                    // Try to find matching rate_per_client to get has_vat
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $hasVat = $matchingRate->has_vat ?? false;
                        if ($amount == 0) {
                            $amount = $matchingRate->rate ?? 0;
                        }
                    }
                }

                // Calculate total amount: add 12% VAT if has_vat is true, otherwise just the amount
                $vatPercent = $hasVat ? 12.00 : 0.00;
                return number_format($amount + ($amount * ($vatPercent / 100)), 2, '.', ',');
            case 'booking number':
            case 'booking no':
                return $waybill->booking->reference_number ?? '-';
            case 'work order':
                return $soa->work_order ?? '-';
            case 'stack run':
                $stackRun = 0;
                if ($waybill->ratePerClient) {
                    $stackRun = $waybill->ratePerClient->stack_run ?? 0;
                } elseif ($waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                        ->where('is_active', 1)->first();
                    if ($matchingRate) {
                        $stackRun = $matchingRate->stack_run ?? 0;
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
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            if (request('booking_id')) {
                $query->where('booking_id', request('booking_id'));
            }

            if (request()->has('is_paid')) {
                $query->where('is_paid', request('is_paid'));
            }

            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return BillingStatementResource::collection(
                $query->with(['shippingLine', 'booking', 'preparedByUser'])->paginate($perPage)->withQueryString()
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
            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);
            $booking = \App\Models\Booking::findOrFail($data['booking_id']);
            $waybillCount = \App\Models\WaybillDetail::where('booking_id', $booking->id)->count();

            if ($waybillCount === 0) {
                throw new \Exception('The selected booking must have at least one waybill.');
            }

            if ($booking->shipping_line_id != $data['shipping_line_id']) {
                throw new \Exception('The booking does not belong to the selected shipping line.');
            }

            // Set prepared_by to current authenticated user if not provided
            if (!isset($data['prepared_by']) && auth()->check()) {
                $data['prepared_by'] = auth()->id();
            }

            $billingStatement = BillingStatement::create($data);
            $billingStatement->load(['shippingLine', 'booking', 'preparedByUser']);

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
                'shippingLine',
                'booking',
                'preparedByUser'
            ])->findOrFail($id);

            $waybills = \App\Models\WaybillDetail::where('booking_id', $billingStatement->booking_id)
                ->with(['ratePerClient'])
                ->get();

            $detailsData = [];
            $grandTotal = 0;

            // Always build detailed breakdown from waybills when available (PDF shows full table regardless of has_details)
            if ($waybills->isNotEmpty()) {
                // Group waybills by normalized container size + container type
                // Normalize: remove "offhire" and "ft" from size; Offhire -> type AC
                $grouped = [];
                foreach ($waybills as $waybill) {
                    $rawSize = $waybill->container_size ?? '';
                    $rawType = $waybill->container_type ?? '';

                    // Normalize size: remove "offhire" and "ft", then take numeric part (e.g. "40ft Offhire" -> "40")
                    $sizeNormalized = trim(str_ireplace(['offhire', 'ft'], '', $rawSize));
                    $sizeNumeric = preg_replace('/[^0-9]/', '', $sizeNormalized) ?: preg_replace('/[^0-9]/', '', $rawSize);

                    // Type code: if size string contained "offhire" -> AC; else DRY/empty -> HC, REEFER -> R
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

                // Process each group: rate from rate_per_client (with tax), total = rate_with_tax * quantity
                foreach ($grouped as $group) {
                    $rateWithTax = 0;
                    if (!empty($group['waybills'])) {
                        $firstWaybill = $group['waybills'][0];
                        if ($firstWaybill->ratePerClient) {
                            $rpc = $firstWaybill->ratePerClient;
                            $baseRate = (float) ($rpc->rate ?? 0);
                            $hasVat = $rpc->has_vat ?? false;
                            // Add 12% VAT if has_vat is true, otherwise use base rate
                            $rateWithTax = $hasVat
                                ? $baseRate * 1.12
                                : $baseRate;
                        } elseif ($firstWaybill->total_rate_per_client > 0) {
                            $rateWithTax = (float) $firstWaybill->total_rate_per_client;
                        }
                    }

                    $quantity = $group['quantity'];
                    $totalAmount = $rateWithTax * $quantity;
                    $grandTotal += $totalAmount;

                    // Description: quantity X container size + container type "UNIT" (e.g. "56X20HC UNIT", "6X40AC UNIT")
                    $description = $quantity . 'X' . $group['size_numeric'] . $group['type_code'] . ' UNIT';

                    $detailsData[] = [
                        'date' => null,
                        'description' => $description,
                        'size' => $group['size_numeric'],
                        'rate_per_trip' => $rateWithTax,
                        'total_amount' => $totalAmount,
                    ];
                }
            }

            // Fallback: single summary row only when there are no waybills or no grouped details (e.g. no container data)
            if (empty($detailsData) && $waybills->isNotEmpty()) {
                foreach ($waybills as $waybill) {
                    $amount = $waybill->total_rate_per_client ?? 0;
                    if ($amount == 0 && $waybill->ratePerClient) {
                        $amount = $waybill->ratePerClient->rate ?? 0;
                    }
                    $grandTotal += $amount;
                }
                if ($grandTotal > 0) {
                    $detailsData[] = [
                        'date' => '',
                        'description' => 'Charges',
                        'size' => '-',
                        'rate_per_trip' => null,
                        'total_amount' => $grandTotal,
                    ];
                }
            }

            $companyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'Blk 8 Lot 11 North Harbor Center Vitas St Barangay 101 Zone 08, 1013 Tondo I/II NCR, City of Manila, First District Philippines',
                'phone' => 'Tel. No. (02) 8291-4477',
                'tin' => 'VAT Reg. TIN.: 010-392-323-00000',
            ];

            $data = [
                'billingStatement' => $billingStatement,
                'companyInfo' => $companyInfo,
                'detailsData' => $detailsData,
                'grandTotal' => $grandTotal,
                'issueDate' => $billingStatement->ci_date ? $billingStatement->ci_date->format('F d, Y') : now()->format('F d, Y'),
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
}


//todo: soa and billing - no tax, only vat (12%)
//! invoice - add tax base on rate per client
//! 