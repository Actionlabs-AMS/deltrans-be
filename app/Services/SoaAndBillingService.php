<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Models\SoaDataOption;
use App\Http\Resources\SoaAndBillingResource;
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
            $soa->load('shippingLine');

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
            foreach ($waybills as $waybill) {
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
                $totalAmount += $amount;
            }
            $vatRate = 0.12;
            $totalVat = $totalAmount * $vatRate;
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
                $helperIds = $waybill->helper_id ?? [];
                if (!empty($helperIds) && is_array($helperIds)) {
                    $helpers = \App\Models\Helper::whereIn('id', $helperIds)->get(['first_name', 'last_name']);
                    if ($helpers->isNotEmpty()) {
                        return $helpers->map(fn($h) => trim($h->first_name . ' ' . $h->last_name))->implode(', ');
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
                return number_format($amount * 0.12, 2, '.', ',');
            case 'total amount':
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
                return number_format($amount + $amount * 0.12, 2, '.', ',');
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
}
