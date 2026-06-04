<?php

namespace App\Services;

use App\Models\IssuedBudget;
use App\Models\TruckTripExpense;
use App\Models\PartsExpense;
use App\Models\DriverCashAdvance;
use App\Models\HelperCashAdvance;
use App\Http\Resources\ReportsResource;
use App\Http\Resources\IssuedBudgetResource;
use App\Http\Resources\TruckTripExpenseResource;
use App\Http\Resources\PartsExpenseResource;
use App\Http\Resources\CashAdvanceResource;
use App\Http\Resources\TransportDetailedResource;
use App\Http\Resources\TransportSummaryResource;
use App\Helpers\CsvExportHelper;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;


class ReportsService 
{
    
    //public function getSummaryReport($startDate, $endDate, $type = 'weekly', $search = null) 
    public function getSummaryReport($startDate, $endDate, $type = 'weekly', $search = null, $order = null, $sort = 'asc')
    {
        try {
            $perPage = ($type === 'monthly') ? 10 : 7;

            // 1. Accounting Totals Map
            $accountingData = DB::table('issued_budget')
                ->select('transaction_date')
                ->selectRaw("SUM(CASE WHEN shift = 'day' THEN amount ELSE 0 END) as day_total")
                ->selectRaw("SUM(CASE WHEN shift = 'night' THEN amount ELSE 0 END) as night_total")
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->groupBy('transaction_date')
                ->get();

            $accountingMap = [];
            foreach ($accountingData as $row) {
                $accountingMap[trim($row->transaction_date)] = [
                    'day' => (float)$row->day_total,
                    'night' => (float)$row->night_total
                ];
            }

            // 2. Truck Expenses Map
            $truckData = DB::table('truck_trip_expense')
                ->select('transaction_date')
                ->selectRaw("SUM(issued_cash_amount) as total")
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->groupBy('transaction_date')
                ->get();

            $truckMap = [];
            foreach ($truckData as $row) {
                $truckMap[trim($row->transaction_date)] = (float)$row->total;
            }

            // 3. Parts Expenses Map (Quantity * Amount)
            $partsData = DB::table('parts_expense')
                ->select('transaction_date')
                ->selectRaw("SUM(quantity * amount_per_item) as total")
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->groupBy('transaction_date')
                ->get();

            $partsMap = [];
            foreach ($partsData as $row) {
                $partsMap[trim($row->transaction_date)] = (float)$row->total;
            }

            // 4. Bale / Cash Advances Map
            // We merge Helper and Driver history into one shift-based map
            $baleMap = [];
            $caTables = ['driver_cash_advancement_history', 'helper_cash_advancement_history'];

            foreach ($caTables as $table) {
                $caData = DB::table($table)
                    ->select('transaction_date', 'shift')
                    ->selectRaw("SUM(amount) as total")
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->groupBy('transaction_date', 'shift')
                    ->get();

                foreach ($caData as $row) {
                    $dateKey = trim($row->transaction_date);
                    $shiftKey = strtolower(trim($row->shift)); // Ensures 'day' matches 'day'
                    
                    $baleMap[$dateKey][$shiftKey] = ($baleMap[$dateKey][$shiftKey] ?? 0) + (float)$row->total;
                }
            }

            $period = CarbonPeriod::create($startDate, $endDate);
            $report = [];
            $i = 0;
            
            foreach ($period as $date) {
                $currentDate = $date->format('Y-m-d');
                
                // 🌟 LOGIC: If search exists, only include if the date matches the search string
                // This allows searching for "2026-03" or "04" or "Friday" depending on format
                if ($search && !str_contains($currentDate, $search)) {
                    continue;
                }

                $report[] = (object) [
                    'id'                => $i++,
                    'date'              => $currentDate,
                    'accounting_day'    => $accountingMap[$currentDate]['day'] ?? 0,
                    'accounting_night'  => $accountingMap[$currentDate]['night'] ?? 0,
                    'truck_expense'     => $truckMap[$currentDate] ?? 0,
                    'parts_expense'     => $partsMap[$currentDate] ?? 0,
                    'bale_day'          => $baleMap[$currentDate]['day'] ?? 0,
                    'bale_night'        => $baleMap[$currentDate]['night'] ?? 0,
                    'created_at'        => null,
                    'updated_at'        => null,
                    'deleted_at'        => null,
                ];
            }

            $items = collect($report);

            if ($order) {
                if (strtolower($sort) === 'desc') {
                    $items = $items->sortByDesc($order);
                } else {
                    $items = $items->sortBy($order);
                }
            } else {
                $items = $items->sortByDesc('date');
            }
            
            $totalCount = $items->count();

            // Determine perPage based on type
            // $perPage = ($type === 'monthly') ? $totalCount : 7;
            // $perPage = max($perPage, 1);

            // $currentPage = LengthAwarePaginator::resolveCurrentPage();
            // $currentPageItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();
            
            // $paginatedResults = new LengthAwarePaginator(
            //     $currentPageItems, 
            //     $totalCount, 
            //     $perPage, 
            //     $currentPage, 
            //     [
            //         'path' => Request::url(), 
            //         'query' => Request::query()
            //     ]
            // );

            // return ReportsResource::collection($paginatedResults);
            $period = CarbonPeriod::create($startDate, $endDate);
            $report = [];
            $i = 0;
            
            foreach ($period as $date) {
                $currentDate = $date->format('Y-m-d');
                
                if ($search && !str_contains($currentDate, $search)) {
                    continue;
                }

                $report[] = (object) [
                    'id'                => $i++,
                    'date'              => $currentDate,
                    'accounting_day'    => $accountingMap[$currentDate]['day'] ?? 0,
                    'accounting_night'  => $accountingMap[$currentDate]['night'] ?? 0,
                    'truck_expense'     => $truckMap[$currentDate] ?? 0,
                    'parts_expense'     => $partsMap[$currentDate] ?? 0,
                    'bale_day'          => $baleMap[$currentDate]['day'] ?? 0,
                    'bale_night'        => $baleMap[$currentDate]['night'] ?? 0,
                    'created_at'        => null,
                    'updated_at'        => null,
                    'deleted_at'        => null,
                ];
            }

            $items = collect($report);

            // 🌟 APPLY SORTING TO COLLECTION
            if ($order) {
                if (strtolower($sort) === 'desc') {
                    $items = $items->sortByDesc($order);
                } else {
                    $items = $items->sortBy($order);
                }
            } else {
                // Default sort by date descending if no order is provided
                $items = $items->sortByDesc('date');
            }

            $totalCount = $items->count();
            $perPage = ($type === 'monthly') ? $totalCount : 7;
            $perPage = max($perPage, 1);

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            
            // 🌟 Use .values() to reset keys after sorting/slicing
            $currentPageItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();
            
            $paginatedResults = new LengthAwarePaginator(
                $currentPageItems, 
                $totalCount, 
                $perPage, 
                $currentPage, 
                [
                    'path' => Request::url(), 
                    'query' => Request::query()
                ]
            );

            return ReportsResource::collection($paginatedResults);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch report summary: ' . $e->getMessage());
        }
    }

    public function get_issued_budget($perPage = 10, $formattedDate, $searchTerm = null)
    {
        try {
            $query = $this->buildIssuedBudgetQuery($formattedDate, $searchTerm);

            return $query->paginate($perPage)->withQueryString();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed issued budget: ' . $e->getMessage());
        }
    }

    /**
     * Export issued budget report as CSV (no pagination).
     */
    public function exportIssuedBudgetCsv(string $formattedDate, ?string $searchTerm = null): StreamedResponse
    {
        $headers = [
            'id', 'shift', 'transaction_date', 'amount', 'source',
            'created_at', 'updated_at', 'deleted_at',
        ];

        $rows = function () use ($formattedDate, $searchTerm) {
            $query = $this->buildIssuedBudgetQuery($formattedDate, $searchTerm);

            foreach ($query->cursor() as $row) {
                $data = (new IssuedBudgetResource($row))->toArray(request());
                yield $this->resourceRowToCsv($data, $headers);
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('issued-budget-export'),
            $headers,
            $rows()
        );
    }

    private function buildIssuedBudgetQuery(?string $formattedDate, ?string $searchTerm = null): Builder
    {
        $query = IssuedBudget::query();

        if ($formattedDate) {
            $query->whereDate('transaction_date', $formattedDate);
        }

        $searchTerm = $searchTerm ?? request('search');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('source', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('amount', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        if (request()->filled('order')) {
            $query->orderBy(request('order'), request('sort', 'desc'));
        } else {
            $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query;
    }

    public function get_truck_expense($perPage = 10, $formattedDate, $searchTerm = null)
    {
        try {
            $query = $this->buildTruckExpenseQuery($formattedDate, $searchTerm);

            return $query->paginate($perPage)->withQueryString();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed truck trip expense: ' . $e->getMessage());
        }
    }

    /**
     * Export truck trip expense report as CSV (no pagination).
     */
    public function exportTruckExpenseCsv(string $formattedDate, ?string $searchTerm = null): StreamedResponse
    {
        $headers = [
            'id', 'shift', 'plate_number', 'helper_id', 'helper_name',
            'cash_on_hand', 'issued_cash_amount', 'remaining_amount',
            'transaction_date', 'created_at', 'updated_at', 'deleted_at',
        ];

        $rows = function () use ($formattedDate, $searchTerm) {
            $query = $this->buildTruckExpenseQuery($formattedDate, $searchTerm);

            foreach ($query->cursor() as $row) {
                $data = (new TruckTripExpenseResource($row))->toArray(request());
                yield $this->resourceRowToCsv($data, $headers);
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('truck-trip-expense-export'),
            $headers,
            $rows()
        );
    }

    private function buildTruckExpenseQuery(?string $formattedDate, ?string $searchTerm = null): Builder
    {
        $query = TruckTripExpense::with('helper');

        if ($formattedDate) {
            $query->whereDate('transaction_date', $formattedDate);
        }

        $searchTerm = $searchTerm ?? request('search');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('issued_cash_amount', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('helper', function ($helperQuery) use ($searchTerm) {
                        $helperQuery->where('first_name', 'LIKE', '%' . $searchTerm . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        if (request()->filled('order')) {
            $query->orderBy(request('order'), request('sort', 'desc'));
        } else {
            $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query;
    }

    public function get_parts_expense($perPage = 10, $formattedDate, $searchTerm = null)
    {
        try {
            $query = $this->buildPartsExpenseQuery($formattedDate, $searchTerm);

            return $query->paginate($perPage)->withQueryString();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed parts expense: ' . $e->getMessage());
        }
    }

    /**
     * Export parts expense report as CSV (no pagination).
     */
    public function exportPartsExpenseCsv(string $formattedDate, ?string $searchTerm = null): StreamedResponse
    {
        $headers = [
            'id', 'shift', 'plate_number', 'receipt_no', 'quantity', 'article',
            'amount_per_item', 'total_amount', 'transaction_date',
            'created_at', 'updated_at', 'deleted_at',
        ];

        $rows = function () use ($formattedDate, $searchTerm) {
            $query = $this->buildPartsExpenseQuery($formattedDate, $searchTerm);

            foreach ($query->cursor() as $row) {
                $data = (new PartsExpenseResource($row))->toArray(request());
                yield $this->resourceRowToCsv($data, $headers);
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('parts-expense-export'),
            $headers,
            $rows()
        );
    }

    private function buildPartsExpenseQuery(?string $formattedDate, ?string $searchTerm = null): Builder
    {
        $query = PartsExpense::query();

        if ($formattedDate) {
            $query->whereDate('transaction_date', $formattedDate);
        }

        $searchTerm = $searchTerm ?? request('search');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('plate_number', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('receipt_no', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('article', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        if (request()->filled('order')) {
            $query->orderBy(request('order'), request('sort', 'desc'));
        } else {
            $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query;
    }

    public function get_cash_advances($perPage, $date, $search = null)
    {
        $query = $this->buildCashAdvancesQuery($date, $search);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->transformCashAdvanceRow($item);
        });

        return $paginated;
    }

    /**
     * Export cash advances report as CSV (no pagination).
     */
    public function exportCashAdvancesCsv(string $date, ?string $search = null): StreamedResponse
    {
        $headers = [
            'type', 'id', 'amount', 'transaction_date', 'shift',
            'driver_id', 'driver_name', 'helper_id', 'helper_name',
            'created_at', 'updated_at', 'deleted_at',
        ];

        $rows = function () use ($date, $search) {
            $items = $this->buildCashAdvancesQuery($date, $search)->get();

            foreach ($items as $item) {
                $model = $this->transformCashAdvanceRow($item);
                $data = (new CashAdvanceResource($model))->toArray(request());

                yield [
                    $data['type'] ?? '',
                    $data['id'] ?? '',
                    $data['amount'] ?? 0,
                    $data['transaction_date'] ?? '',
                    $data['shift'] ?? '',
                    $data['driver_id'] ?? '',
                    $data['driver_name'] ?? '',
                    $data['helper_id'] ?? '',
                    $data['helper_name'] ?? '',
                    $data['created_at'] ?? '',
                    $data['updated_at'] ?? '',
                    $data['deleted_at'] ?? '',
                ];
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('cash-advances-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build unified cash advances query (drivers + helpers).
     */
    private function buildCashAdvancesQuery(string $date, ?string $search = null)
    {
        $sortColumn = request('order', 'created_at');
        $sortDirection = request('sort', 'desc');

        $drivers = DB::table('driver_cash_advancement_history as dca')
            ->join('drivers as d', 'd.id', '=', 'dca.driver_id')
            ->select(
                'dca.id',
                'dca.amount',
                'dca.transaction_date',
                'dca.shift',
                'dca.driver_id',
                DB::raw('NULL as helper_id'),
                DB::raw("CONCAT(d.first_name, ' ', d.last_name) as person_name"),
                'dca.created_at',
                'dca.updated_at',
                DB::raw('NULL as deleted_at')
            )
            ->whereDate('dca.transaction_date', $date)
            ->whereNull('dca.deleted_at');

        $helpers = DB::table('helper_cash_advancement_history as hca')
            ->join('helpers as h', 'h.id', '=', 'hca.helper_id')
            ->select(
                'hca.id',
                'hca.amount',
                'hca.transaction_date',
                'hca.shift',
                DB::raw('NULL as driver_id'),
                'hca.helper_id',
                DB::raw("CONCAT(h.first_name, ' ', h.last_name) as person_name"),
                'hca.created_at',
                'hca.updated_at',
                DB::raw('NULL as deleted_at')
            )
            ->whereDate('hca.transaction_date', $date)
            ->whereNull('hca.deleted_at');

        $combined = $drivers->union($helpers);

        $query = DB::table(DB::raw("({$combined->toSql()}) as combined"))
            ->mergeBindings($combined);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('person_name', 'LIKE', "%{$search}%")
                    ->orWhere('shift', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%");
            });
        }

        $map = [
            'driver_name' => 'person_name',
            'helper_name' => 'person_name',
        ];

        $finalSortColumn = $map[$sortColumn] ?? $sortColumn;

        return $query->orderBy($finalSortColumn, $sortDirection);
    }

    /**
     * Transform a unified cash advance row into a model for the resource.
     */
    private function transformCashAdvanceRow(object $item)
    {
        if (!empty($item->driver_id)) {
            $model = new \App\Models\DriverCAHistory((array) $item);
            $model->exists = true;

            $driver = \App\Models\Driver::find($item->driver_id);
            if ($driver) {
                $model->setRelation('driver', $driver);
            }

            return $model;
        }

        if (!empty($item->helper_id)) {
            $model = new \App\Models\HelperCAHistory((array) $item);
            $model->exists = true;

            $helper = \App\Models\Helper::find($item->helper_id);
            if ($helper) {
                $model->setRelation('helper', $helper);
            }

            return $model;
        }

        return $item;
    }

    /**
     * Map a resource array to CSV row values in header order.
     */
    private function resourceRowToCsv(array $data, array $headers): array
    {
        $row = [];
        foreach ($headers as $key) {
            $value = $data[$key] ?? '';
            if (is_array($value) || is_object($value)) {
                $value = '';
            }
            $row[] = $value;
        }

        return $row;
    }

    public function getTruckSummary($startDate, $endDate, $filterType = 'weekly', $search = null, $perPage = 15)
    {
        try {
            $query = $this->buildTruckSummaryQuery($startDate, $endDate, $filterType, $search);

            // Note: We use the raw column name or the alias depending on your DB driver
            $sortColumn = request('order', 'total_trips');
            $sortDirection = request('sort', 'desc');
            $query->orderBy($sortColumn, $sortDirection);

            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck summary: ' . $e->getMessage());
        }
    }

    /**
     * Export transport summary report (no pagination).
     */
    public function exportTruckSummaryCsv($startDate, $endDate, $filterType = 'weekly', $search = null): StreamedResponse
    {
        $headers = [
            'truck_plate_number',
            'total_trips',
            'total_expenses',
        ];

        $rows = function () use ($startDate, $endDate, $filterType, $search) {
            $query = $this->buildTruckSummaryQuery($startDate, $endDate, $filterType, $search);

            $sortColumn = request('order', 'total_trips');
            $sortDirection = request('sort', 'desc');
            $query->orderBy($sortColumn, $sortDirection);

            foreach ($query->cursor() as $row) {
                $data = (new TransportSummaryResource($row))->toArray(request());

                yield [
                    $data['truck_plate_number'] ?? '',
                    $data['total_trips'] ?? 0,
                    $data['total_expenses'] ?? 0,
                ];
            }
        };

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('transport-summary-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build transport summary query with shared filters.
     */
    private function buildTruckSummaryQuery($startDate, $endDate, $filterType = 'weekly', $search = null)
    {
        $query = DB::table('waybill_details')
            ->leftJoin('fixed_expenses', 'waybill_details.fixed_expense_id', '=', 'fixed_expenses.id')
            ->select(
                'waybill_details.truck_plate_number',
                DB::raw('count(waybill_details.id) as total_trips'),
                DB::raw('SUM(IFNULL(fixed_expenses.total_expenses, 0)) as total_expenses')
            );

        if ($filterType === 'monthly' && $startDate) {
            $date = \Carbon\Carbon::parse($startDate);
            $query->whereMonth('waybill_details.transaction_date', $date->month)
                ->whereYear('waybill_details.transaction_date', $date->year);
        } else {
            if ($startDate && $endDate) {
                $query->whereBetween('waybill_details.transaction_date', [$startDate, $endDate]);
            }
        }

        if ($search) {
            $query->where('waybill_details.truck_plate_number', 'LIKE', "%{$search}%");
        }

        return $query->groupBy('waybill_details.truck_plate_number');
    }

    // public function getTruckDetailedReport($startDate, $endDate, $filterType = 'weekly', $plateNumber = null, $perPage = 15)
    // {
    //     try {
    //         $query = DB::table('waybill_details as a')
    //             ->join('bookings as b', 'a.booking_id', '=', 'b.id')
    //             ->leftJoin('containers as c', 'c.waybill_id', '=', 'a.id')
    //             ->join('shipping_lines as d', 'd.id', '=', 'b.shipping_line_id')
    //             ->join('cypa_details as e', 'e.id', '=', 'b.cypa_id_from')
    //             ->join('cypa_details as f', 'f.id', '=', 'b.cypa_id_to')
    //             ->join('drivers as g', 'g.id', '=', 'a.driver_id')
    //             ->join('helpers as h', 'h.id', '=', 'a.helper_id')
                
    //             // 🌟 REMOVED: fixed_expenses join
    //             // 🌟 NEW JOIN: truck_trip_expenses
    //             ->leftJoin('truck_trip_expense as tte', 'tte.id', '=', 'a.truck_trip_expense_id')
                
    //             ->leftJoin('diesel_expenses as j', 'j.id', '=', 'a.diesel_expense_id')
                
    //             ->leftJoin('user_meta as um1', function($join) {
    //                 $join->on('um1.user_id', '=', 'a.prepared_by')
    //                     ->where('um1.meta_key', '=', 'first_name');
    //             })
    //             ->leftJoin('user_meta as um2', function($join) {
    //                 $join->on('um2.user_id', '=', 'a.prepared_by')
    //                     ->where('um2.meta_key', '=', 'last_name');
    //             })
                
    //             ->select([
    //                 'a.id',
    //                 'a.transaction_date',
    //                 'd.short_name as shipping_line_name',
    //                 'a.truck_plate_number',
    //                 'a.waybill_number',
    //                 'c.container_number',
    //                 'e.short_name as from',
    //                 'f.short_name as to',
    //                 DB::raw("CASE WHEN b.is_ship_in = 1 THEN 'SHIP IN' ELSE 'SHIP OUT' END as status"),
    //                 'a.container_size',
    //                 DB::raw('IFNULL(tte.cash_on_hand, 0) + IFNULL(tte.issued_cash_amount, 0) as truck_expense'),
    //                 'j.purchase_order',
    //                 DB::raw('IFNULL(j.amount, 0) as diesel_amount'),
    //                 'a.remarks',
    //                 'g.last_name as driver',
    //                 'h.last_name as helper',
    //                 DB::raw("CONCAT(IFNULL(um1.meta_value, ''), ' ', IFNULL(um2.meta_value, '')) as encoded_by")
    //             ]);

    //         // 1. --- Date Filtering Logic ---
    //         if ($filterType === 'monthly' && $startDate) {
    //             $date = \Carbon\Carbon::parse($startDate);
    //             $query->whereMonth('a.transaction_date', $date->month)
    //                 ->whereYear('a.transaction_date', $date->year);
    //         } else {
    //             if ($startDate && $endDate) {
    //                 $query->whereBetween('a.transaction_date', [$startDate, $endDate]);
    //             }
    //         }

    //         // 2. --- Specific Truck Filter ---
    //         if ($plateNumber) {
    //             $query->where('a.truck_plate_number', $plateNumber);
    //         }

    //         // 3. --- Optional Search ---
    //         $search = request('search');
    //         if ($search) {
    //             $query->where(function($q) use ($search) {
    //                 $q->where('a.waybill_number', 'LIKE', "%{$search}%")
    //                 ->orWhere('c.container_number', 'LIKE', "%{$search}%")
    //                 ->orWhere('d.name', 'LIKE', "%{$search}%")
    //                 ->orWhere('um1.meta_value', 'LIKE', "%{$search}%")
    //                 ->orWhere('um2.meta_value', 'LIKE', "%{$search}%");
    //             });
    //         }

    //         // 4. --- Ordering ---
    //         $sortColumn = request('order', 'a.transaction_date'); 
    //         $sortDirection = request('sort', 'asc');
    //         $query->orderBy($sortColumn, $sortDirection);

    //         return $query->paginate($perPage)->withQueryString();

    //     } catch (\Exception $e) {
    //         throw new \Exception('Failed to fetch detailed report: ' . $e->getMessage());
    //     }
    // }

    public function getTruckDetailedReport($startDate, $endDate, $filterType = 'weekly', $plateNumber = null, $perPage = 15)
    {
        try {
            $query = $this->buildTruckDetailedReportQuery($startDate, $endDate, $filterType, $plateNumber, request('search'));

            // 4. --- 🌟 FIXED ORDERING LOGIC ---
            $requestedOrder = request('order');
            $sortDirection = request('sort', 'asc');

            // Map frontend column names to backend/alias names
            $sortMap = [
                'transaction_date'   => 'a.transaction_date',
                'shipping_line'      => 'shipping_line_name', 
                'truck_plate_number' => 'a.truck_plate_number',
                'waybill_number'     => 'a.waybill_number',
                'status'             => 'status',             
                'truck_expenses'      => 'truck_expense',
                'container_number'   => 'c.container_number', 
                'size'               => 'a.container_size',
                'from'               => 'from',
                'to'                 => 'to',
                'driver'             => 'driver',
                'helper'             => 'helper',
                'diesel_consumption' => 'diesel_amount',
                'encoded_by' => 'encoded_by',
            ];

            $sortColumn = $sortMap[$requestedOrder] ?? 'a.transaction_date';

            $query->orderBy($sortColumn, $sortDirection);

            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed report: ' . $e->getMessage());
        }
    }

    /**
     * Export transport detailed report for a specific truck (no pagination).
     */
    public function exportTruckDetailedCsv($startDate, $endDate, $filterType = 'weekly', $plateNumber = null, $search = null): StreamedResponse
    {
        $headers = [
            'id',
            'transaction_date',
            'shipping_line',
            'plate_number',
            'waybill_number',
            'container_number',
            'from',
            'to',
            'status',
            'size',
            'truck_expenses',
            'remarks',
            'driver',
            'helper',
            'encoded_by',
            'diesel_consumption',
            'purchase_order',
        ];

        $rows = function () use ($startDate, $endDate, $filterType, $plateNumber, $search) {
            $query = $this->buildTruckDetailedReportQuery($startDate, $endDate, $filterType, $plateNumber, $search);

            $requestedOrder = request('order');
            $sortDirection = request('sort', 'asc');

            $sortMap = [
                'transaction_date'   => 'a.transaction_date',
                'shipping_line'      => 'd.short_name',
                'plate_number'       => 'a.truck_plate_number',
                'waybill_number'     => 'a.waybill_number',
                'container_number'   => 'c.container_number',
                'from'               => 'e.short_name',
                'to'                 => 'f.short_name',
                'size'               => 'a.container_size',
                'truck_expenses'     => 'truck_expense',
                'diesel_consumption' => 'diesel_amount',
            ];

            $sortColumn = $sortMap[$requestedOrder] ?? 'a.transaction_date';
            $query->orderBy($sortColumn, $sortDirection);

            foreach ($query->cursor() as $row) {
                $data = (new TransportDetailedResource($row))->toArray(request());

                yield [
                    $data['id'] ?? '',
                    $data['transaction_date'] ?? '',
                    $data['shipping_line'] ?? '',
                    $data['plate_number'] ?? '',
                    $data['waybill_number'] ?? '',
                    $data['container_number'] ?? '',
                    $data['from'] ?? '',
                    $data['to'] ?? '',
                    $data['status'] ?? '',
                    $data['size'] ?? '',
                    $data['truck_expenses'] ?? 0,
                    $data['remarks'] ?? '',
                    $data['driver'] ?? '',
                    $data['helper'] ?? '',
                    $data['encoded_by'] ?? '',
                    $data['diesel_consumption'] ?? 0,
                    $data['purchase_order'] ?? '',
                ];
            }
        };

        $safePlate = $plateNumber ? preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $plateNumber) : 'all';

        return CsvExportHelper::streamDownload(
            CsvExportHelper::datedFilename('transport-details-' . $safePlate . '-export'),
            $headers,
            $rows()
        );
    }

    /**
     * Build transport detailed report query with shared filters.
     */
    private function buildTruckDetailedReportQuery($startDate, $endDate, $filterType = 'weekly', $plateNumber = null, $search = null)
    {
        $query = DB::table('waybill_details as a')
            ->join('bookings as b', 'a.booking_id', '=', 'b.id')
            ->leftJoin('containers as c', 'c.waybill_id', '=', 'a.id')
            ->join('shipping_lines as d', 'd.id', '=', 'b.shipping_line_id')
            ->join('cypa_details as e', 'e.id', '=', 'b.cypa_id_from')
            ->join('cypa_details as f', 'f.id', '=', 'b.cypa_id_to')
            ->join('drivers as g', 'g.id', '=', 'a.driver_id')
            ->join('helpers as h', 'h.id', '=', 'a.helper_id')
            ->leftJoin('truck_trip_expense as tte', 'tte.id', '=', 'a.truck_trip_expense_id')
            ->leftJoin('diesel_expenses as j', 'j.id', '=', 'a.diesel_expense_id')
            ->leftJoin('user_meta as um1', function ($join) {
                $join->on('um1.user_id', '=', 'a.prepared_by')->where('um1.meta_key', '=', 'first_name');
            })
            ->leftJoin('user_meta as um2', function ($join) {
                $join->on('um2.user_id', '=', 'a.prepared_by')->where('um2.meta_key', '=', 'last_name');
            })
            ->select([
                'a.id',
                'a.transaction_date',
                'd.short_name as shipping_line_name',
                'a.truck_plate_number',
                'a.waybill_number',
                'c.container_number',
                'e.short_name as from',
                'f.short_name as to',
                DB::raw("CASE WHEN b.is_ship_in = 1 THEN 'SHIP IN' ELSE 'SHIP OUT' END as status"),
                'a.container_size',
                DB::raw('IFNULL(tte.cash_on_hand, 0) + IFNULL(tte.issued_cash_amount, 0) as truck_expense'),
                'j.purchase_order',
                DB::raw('IFNULL(j.amount, 0) as diesel_amount'),
                'a.remarks',
                'g.last_name as driver',
                'h.last_name as helper',
                DB::raw("CONCAT(IFNULL(um1.meta_value, ''), ' ', IFNULL(um2.meta_value, '')) as encoded_by"),
            ]);

        if ($filterType === 'monthly' && $startDate) {
            $date = \Carbon\Carbon::parse($startDate);
            $query->whereMonth('a.transaction_date', $date->month)->whereYear('a.transaction_date', $date->year);
        } elseif ($startDate && $endDate) {
            $query->whereBetween('a.transaction_date', [$startDate, $endDate]);
        }

        if ($plateNumber) {
            $query->where('a.truck_plate_number', $plateNumber);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('a.waybill_number', 'LIKE', "%{$search}%")
                    ->orWhere('c.container_number', 'LIKE', "%{$search}%")
                    ->orWhere('d.name', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }
}