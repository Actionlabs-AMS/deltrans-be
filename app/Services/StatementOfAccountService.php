<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Models\SoaDataOption;
use App\Http\Resources\StatementOfAccountResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class StatementOfAccountService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new StatementOfAccountResource(new StatementOfAccount), new StatementOfAccount());
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

            // Apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('dli_sa_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($query) {
                            $query->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return StatementOfAccountResource::collection(
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
     * @return StatementOfAccountResource
     */
    public function generate(array $data)
    {
        try {
            // Validate shipping line exists
            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);

            // Validate booking exists and has waybills
            $booking = \App\Models\Booking::findOrFail($data['booking_id']);
            $waybillCount = \App\Models\WaybillDetail::where('booking_id', $booking->id)->count();

            if ($waybillCount === 0) {
                throw new \Exception('The selected booking must have at least one waybill.');
            }

            // Validate that booking belongs to the shipping line
            if ($booking->shipping_line_id != $data['shipping_line_id']) {
                throw new \Exception('The booking does not belong to the selected shipping line.');
            }

            // Create the statement of account
            $soa = StatementOfAccount::create($data);

            // Load the relationship
            $soa->load('shippingLine');

            return StatementOfAccountResource::make($soa);
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
     * @return StatementOfAccountResource
     * @throws ModelNotFoundException
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

            return StatementOfAccountResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch statement of account: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for Statement of Account.
     * 
     * This function generates a PDF file for a Statement of Account based on:
     * - shipping_lines_template: Defines which shipping line information columns to display
     * - transaction_information_template: Defines which transaction columns to display in the table
     * 
     * The PDF includes:
     * - Company header (DELTRANS LOGISTICS INC.)
     * - SOA number and issue date
     * - Client information (Shipping Line details)
     * - Dynamic transaction table based on template columns
     * - Totals (subtotal, VAT, grand total)
     * - Signature sections
     *
     * @param int $id SOA ID
     * @return string Path to the generated PDF file (relative to public storage)
     * @throws \Exception
     */
    public function generatePdf($id)
    {
        try {
            // Load SOA with all necessary relationships
            $soa = StatementOfAccount::with([
                'shippingLine',
                'booking' => function ($query) {
                    $query->with([
                        'cypaFrom',
                        'cypaTo',
                        'containers'
                    ]);
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

            // Get shipping line template columns
            $shippingLineTemplateIds = $soa->shippingLine->shipping_lines_template ?? [];
            $shippingLineColumns = collect();
            if (!empty($shippingLineTemplateIds)) {
                $shippingLineColumns = SoaDataOption::whereIn('id', $shippingLineTemplateIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $shippingLineTemplateIds) . ')')
                    ->get(['id', 'name', 'description']);
            }

            // Get transaction information template columns
            $transactionTemplateIds = $soa->shippingLine->transaction_information_template ?? [];
            $transactionColumns = collect();
            if (!empty($transactionTemplateIds)) {
                $transactionColumns = SoaDataOption::whereIn('id', $transactionTemplateIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $transactionTemplateIds) . ')')
                    ->get(['id', 'name', 'description']);
            }

            // Prepare waybill data mapped to template columns
            $waybills = $soa->booking->waybills ?? collect();
            $transactionData = [];

            foreach ($waybills as $waybill) {
                $row = [];
                foreach ($transactionColumns as $column) {
                    $row[$column->name] = $this->mapTransactionField($column->name, $waybill, $soa);
                }
                $transactionData[] = $row;
            }

            // Calculate totals - use actual amounts (from total_rate_per_client or rate_per_client)
            $totalAmount = 0;
            foreach ($waybills as $waybill) {
                $amount = $waybill->total_rate_per_client ?? 0;
                // If amount is 0, try to get from rate_per_client relationship
                if ($amount == 0 && $waybill->ratePerClient) {
                    $amount = $waybill->ratePerClient->rate ?? 0;
                }
                // If still 0, try to find matching rate_per_client
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(function ($query) use ($waybill) {
                            $query->where('cypa_id', $waybill->booking->cypa_id_from)
                                ->orWhere('cypa_id', 0); // 0 means "all"
                        })
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = $matchingRate->rate ?? 0;
                    }
                }
                $totalAmount += $amount;
            }
            $vatRate = 0.12; // 12% VAT
            $totalVat = $totalAmount * $vatRate;
            $grandTotal = $totalAmount + $totalVat;

            // Company information (DELTRANS LOGISTICS INC.)
            $companyInfo = [
                'name' => 'DELTRANS LOGISTICS INC.',
                'address' => 'BLK 18 LOT 11, MANILA HARBOUR CENTRE, VITAS TONDO MANILA',
                'phone' => 'Tel# 02-8291-4477',
            ];

            // Prepare data for view
            $data = [
                'soa' => $soa,
                'companyInfo' => $companyInfo,
                'shippingLineColumns' => $shippingLineColumns,
                'transactionColumns' => $transactionColumns,
                'transactionData' => $transactionData,
                'totalAmount' => $totalAmount,
                'vatRate' => $vatRate,
                'totalVat' => $totalVat,
                'grandTotal' => $grandTotal,
                'issueDate' => now()->format('F d, Y'),
            ];

            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('soa.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            // Create directory if it doesn't exist
            $directory = 'soa-pdfs/' . date('Y/m');
            Storage::disk('public')->makeDirectory($directory);

            // Generate filename
            $filename = $soa->dli_sa_number . '_' . time() . '.pdf';
            $filePath = $directory . '/' . $filename;

            // Save PDF to storage
            Storage::disk('public')->put($filePath, $pdf->output());

            return $filePath;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Statement of account not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Map waybill data to template field names.
     *
     * @param string $fieldName
     * @param \App\Models\WaybillDetail $waybill
     * @param \App\Models\StatementOfAccount $soa
     * @return mixed
     */
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
                // helper_id is a JSON array of helper IDs
                $helperIds = $waybill->helper_id ?? [];
                if (!empty($helperIds) && is_array($helperIds)) {
                    $helpers = \App\Models\Helper::whereIn('id', $helperIds)
                        ->get(['first_name', 'last_name']);
                    if ($helpers->isNotEmpty()) {
                        $helperNames = $helpers->map(function ($helper) {
                            return trim($helper->first_name . ' ' . $helper->last_name);
                        })->implode(', ');
                        return $helperNames;
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
                // Try to get container from waybill's booking
                $containers = $waybill->booking->containers ?? collect();
                $container = $containers->where('waybill_id', $waybill->id)->first();
                // If not found, try SOA's booking
                if (!$container && $soa->booking) {
                    $containers = $soa->booking->containers ?? collect();
                    $container = $containers->where('waybill_id', $waybill->id)->first();
                }
                return $container ? $container->container_number : '-';

            case 'origin':
            case 'from':
                $originCypa = $waybill->booking->cypaFrom;
                return $originCypa ? $originCypa->name : '-';

            case 'destination':
            case 'to':
                $destCypa = $waybill->booking->cypaTo;
                return $destCypa ? $destCypa->name : '-';

            case 'remarks':
                return $waybill->ratePerClient ? ($waybill->ratePerClient->remarks ?? '-') : '-';

            case 'size':
                // Use container_size from waybill_details table
                $size = $waybill->container_size ?? '';
                if ($size) {
                    // Handle formats like '20ft', '40ft', '20', '40'
                    $sizeValue = preg_replace('/[^0-9]/', '', $size);
                    // Format: 1X40HC or 1X20FR
                    if ($sizeValue == '40') {
                        return '1X40HC';
                    } elseif ($sizeValue == '20') {
                        return '1X20FR';
                    } else {
                        return '1X' . $sizeValue;
                    }
                }
                return '-';

            case 'amount':
                // Try to get amount from total_rate_per_client, or from rate_per_client relationship
                $amount = $waybill->total_rate_per_client ?? 0;
                if ($amount == 0 && $waybill->ratePerClient) {
                    $amount = $waybill->ratePerClient->rate ?? 0;
                }
                // If still 0, try to find matching rate_per_client
                if ($amount == 0 && $waybill->booking) {
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(function ($query) use ($waybill) {
                            $query->where('cypa_id', $waybill->booking->cypa_id_from)
                                ->orWhere('cypa_id', 0); // 0 means "all"
                        })
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $amount = $matchingRate->rate ?? 0;
                    }
                }
                return number_format($amount, 2, '.', ',');

            case 'vessel':
                // Vessel information might be in booking or elsewhere
                return '-'; // Placeholder - update based on actual data structure

            case 'vat':
            case '12% vat':
            case '12%vat':
                // Get amount using same logic as 'amount' case
                $amount = $waybill->total_rate_per_client ?? 0;
                if ($amount == 0 && $waybill->ratePerClient) {
                    $amount = $waybill->ratePerClient->rate ?? 0;
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
                        $amount = $matchingRate->rate ?? 0;
                    }
                }
                $vat = $amount * 0.12;
                return number_format($vat, 2, '.', ',');

            case 'total amount':
                // Get amount using same logic as 'amount' case
                $amount = $waybill->total_rate_per_client ?? 0;
                if ($amount == 0 && $waybill->ratePerClient) {
                    $amount = $waybill->ratePerClient->rate ?? 0;
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
                        $amount = $matchingRate->rate ?? 0;
                    }
                }
                $vat = $amount * 0.12;
                return number_format($amount + $vat, 2, '.', ',');

            case 'booking number':
            case 'booking no':
                return $waybill->booking->reference_number ?? '-';

            case 'work order':
                // Work order might be in booking or elsewhere
                return '-'; // Placeholder - update based on actual data structure

            case 'stack run':
                $stackRun = 0;
                if ($waybill->ratePerClient) {
                    $stackRun = $waybill->ratePerClient->stack_run ?? 0;
                } else if ($waybill->booking) {
                    // Try to find matching rate_per_client for stack_run
                    $matchingRate = \App\Models\RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                        ->where('container_size', $waybill->container_size)
                        ->where(function ($query) use ($waybill) {
                            $query->where('cypa_id', $waybill->booking->cypa_id_from)
                                ->orWhere('cypa_id', 0);
                        })
                        ->where('is_active', 1)
                        ->first();
                    if ($matchingRate) {
                        $stackRun = $matchingRate->stack_run ?? 0;
                    }
                }
                return $stackRun > 0 ? number_format($stackRun, 2, '.', ',') : '-';

            default:
                return '-';
        }
    }
}




//todo: create api for email sender