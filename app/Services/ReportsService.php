<?php

namespace App\Services;

use App\Models\IssuedBudget;
use App\Models\TruckTripExpense;
use App\Models\PartsExpense;
use App\Models\DriverCashAdvance;
use App\Models\HelperCashAdvance;
use App\Http\Resources\ReportsResource;
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
            // 1. Initialize Query with necessary relationships
            // Assuming your IssuedBudget model has these relationships
            $query = IssuedBudget::query(); 

            // 2. --- Exact Date Filter (Drill-down Logic) ---
            // We filter specifically by the date passed from the summary row
            if ($formattedDate) {
                $query->whereDate('transaction_date', $formattedDate);
            }

            // 3. --- Optional Search (If user searches within the detailed tab) ---
            $searchTerm = request('search');
            if ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('source', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('amount', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // 4. --- Ordering: same date → newest created first; optional order/sort override
            if (request()->filled('order')) {
                $query->orderBy(request('order'), request('sort', 'desc'));
            } else {
                $query->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc');
            }

            // 5. Return paginated results
            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed issued budget: ' . $e->getMessage());
        }
    }

    public function get_truck_expense($perPage = 10, $formattedDate, $searchTerm = null)
    {
        try {
           
            $query = TruckTripExpense::with('helper');

  
            if ($formattedDate) {
                $query->whereDate('transaction_date', $formattedDate);
            }

            // 3. --- Optional Search (If user searches within the detailed tab) ---
            $searchTerm = request('search');
            if ($searchTerm) {
                // $query->where(function($q) use ($searchTerm) {
                //     $q->where('helper_id', 'LIKE', '%' . $searchTerm . '%')
                //     ->orWhere('issued_cash_amount', 'LIKE', '%' . $searchTerm . '%')
                //     ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%');
                // });
                $query->where(function($q) use ($searchTerm) {
                    $q->where('issued_cash_amount', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%')
                    // 🌟 Search in the related helper table
                    ->orWhereHas('helper', function($helperQuery) use ($searchTerm) {
                        $helperQuery->where('first_name', 'LIKE', '%' . $searchTerm . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%');
                    });
                });
            }

            // 4. --- Ordering: same date → newest created first; optional order/sort override
            if (request()->filled('order')) {
                $query->orderBy(request('order'), request('sort', 'desc'));
            } else {
                $query->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc');
            }

            // 5. Return paginated results
            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed truck trip expense: ' . $e->getMessage());
        }
    }

    public function get_parts_expense($perPage = 10, $formattedDate, $searchTerm = null)
    {
        try {
           
            $query = PartsExpense::query(); 

  
            if ($formattedDate) {
                $query->whereDate('transaction_date', $formattedDate);
            }

            // 3. --- Optional Search (If user searches within the detailed tab) ---
            $searchTerm = request('search');
            if ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('plate_number', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('receipt_no', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('article', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // 4. --- Ordering: same date → newest created first; optional order/sort override
            if (request()->filled('order')) {
                $query->orderBy(request('order'), request('sort', 'desc'));
            } else {
                $query->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc');
            }

            // 5. Return paginated results
            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch detailed parts expense: ' . $e->getMessage());
        }
    }

    public function get_cash_advances($perPage, $date, $search = null)
    {
        // 1. Capture sorting parameters from the request
        $sortColumn = request('order', 'created_at'); // Default to created_at
        $sortDirection = request('sort', 'desc');     // Default to desc

        // 2. Driver Query
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
                'dca.created_at'
            )
            ->whereDate('dca.transaction_date', $date)
            ->whereNull('dca.deleted_at');

        // 3. Helper Query
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
                'hca.created_at'
            )
            ->whereDate('hca.transaction_date', $date)
            ->whereNull('hca.deleted_at');

        // 4. Combine
        $combined = $drivers->union($helpers);

        // 5. Wrap and Apply Dynamic Sorting
        $query = DB::table(DB::raw("({$combined->toSql()}) as combined"))
            ->mergeBindings($combined);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('person_name', 'LIKE', "%{$search}%")
                ->orWhere('shift', 'LIKE', "%{$search}%")
                ->orWhere('amount', 'LIKE', "%{$search}%");
            });
        }

        // 🌟 DYNAMIC SORTING APPLIED HERE
        // Map frontend column names to subquery column names if they differ
        $map = [
            'driver_name' => 'person_name',
            'helper_name' => 'person_name',
        ];

        $finalSortColumn = $map[$sortColumn] ?? $sortColumn;

        $paginated = $query->orderBy($finalSortColumn, $sortDirection)->paginate($perPage);

       $paginated->getCollection()->transform(function ($item) {
            // 1. Check if it's a Driver record
            if (!empty($item->driver_id)) {
                $model = new \App\Models\DriverCAHistory((array) $item);
                $model->exists = true;
                
                // Find the driver, but handle case where driver might be deleted/missing
                $driver = \App\Models\Driver::find($item->driver_id);
                if ($driver) {
                    $model->setRelation('driver', $driver);
                }
                return $model;
            } 
            
            // 2. Check if it's a Helper record
            if (!empty($item->helper_id)) {
                $model = new \App\Models\HelperCAHistory((array) $item);
                $model->exists = true;

                $helper = \App\Models\Helper::find($item->helper_id);
                if ($helper) {
                    $model->setRelation('helper', $helper);
                }
                return $model;
            }

            return $item; // Fallback to avoid breaking the collection
        });

        return $paginated;
    }

    public function getTruckSummary($startDate, $endDate, $filterType = 'weekly', $search = null, $perPage = 15)
    {
        try {
            $query = DB::table('waybill_details')
                // Join with fixed_expenses to get the cost data
                ->leftJoin('fixed_expenses', 'waybill_details.fixed_expense_id', '=', 'fixed_expenses.id')
                ->select(
                    'waybill_details.truck_plate_number', 
                    DB::raw('count(waybill_details.id) as total_trips'),
                    DB::raw('SUM(IFNULL(fixed_expenses.total_expenses, 0)) as total_expenses')
                );

            // 1. --- Date Filtering Logic ---
            if ($filterType === 'monthly' && $startDate) {
                $date = \Carbon\Carbon::parse($startDate);
                $query->whereMonth('waybill_details.transaction_date', $date->month)
                    ->whereYear('waybill_details.transaction_date', $date->year);
            } else {
                if ($startDate && $endDate) {
                    $query->whereBetween('waybill_details.transaction_date', [$startDate, $endDate]);
                }
            }

            // 2. --- Optional Search ---
            $search = request('search');
            if ($search) {
                $query->where('waybill_details.truck_plate_number', 'LIKE', "%{$search}%");
            }

            // 3. --- Grouping and Ordering ---
            // Note: We use the raw column name or the alias depending on your DB driver
            $sortColumn = request('order', 'total_trips'); 
            $sortDirection = request('sort', 'desc');
            
            $query->groupBy('waybill_details.truck_plate_number')
                ->orderBy($sortColumn, $sortDirection);

            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck summary: ' . $e->getMessage());
        }
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
                ->leftJoin('user_meta as um1', function($join) {
                    $join->on('um1.user_id', '=', 'a.prepared_by')->where('um1.meta_key', '=', 'first_name');
                })
                ->leftJoin('user_meta as um2', function($join) {
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
                    DB::raw("CONCAT(IFNULL(um1.meta_value, ''), ' ', IFNULL(um2.meta_value, '')) as encoded_by")
                ]);

            // 1. --- Date Filtering ---
            if ($filterType === 'monthly' && $startDate) {
                $date = \Carbon\Carbon::parse($startDate);
                $query->whereMonth('a.transaction_date', $date->month)->whereYear('a.transaction_date', $date->year);
            } else if ($startDate && $endDate) {
                $query->whereBetween('a.transaction_date', [$startDate, $endDate]);
            }

            // 2. --- Specific Truck Filter ---
            if ($plateNumber) {
                $query->where('a.truck_plate_number', $plateNumber);
            }

            // 3. --- Optional Search ---
            $search = request('search');
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('a.waybill_number', 'LIKE', "%{$search}%")
                    ->orWhere('c.container_number', 'LIKE', "%{$search}%")
                    ->orWhere('d.name', 'LIKE', "%{$search}%");
                });
            }

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
}