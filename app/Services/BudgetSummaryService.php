<?php

namespace App\Services;

use App\Models\DriverCAHistory;
use App\Models\FundsForStackRun;
use App\Models\HelperCAHistory;
use App\Models\IssuedBudget;
use App\Models\PartsExpense;
use App\Models\TruckTripExpense;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BudgetSummaryService
{
    public const TYPE_BUDGET = 'Issued Budget';

    public const TYPE_TRUCK_EXPENSE = 'Truck Expense';

    public const TYPE_PARTS_EXPENSE = 'Parts Expense';

    public const TYPE_OTHER_EXPENSE = 'Other Expense';

    public const TYPE_DRIVER_CASH_ADVANCE = 'Driver Cash Advance';

    public const TYPE_HELPER_CASH_ADVANCE = 'Helper Cash Advance';

    /**
     * @var array<int, string>
     */
    private const EXPENSE_TYPES = [
        self::TYPE_TRUCK_EXPENSE,
        self::TYPE_PARTS_EXPENSE,
        self::TYPE_OTHER_EXPENSE,
        self::TYPE_DRIVER_CASH_ADVANCE,
        self::TYPE_HELPER_CASH_ADVANCE,
    ];

    /**
     * Build base query with common filters (transaction_date, created_at, shift).
     */
    private function applyFilters($query, string $transactionDateColumn, ?string $shift): void
    {
        $this->applyDateRangeFilters($query, $transactionDateColumn, $shift,
            request('transaction_date_from'),
            request('transaction_date_to'));

        if (request('created_at_from')) {
            $from = strlen(request('created_at_from')) === 10 ? request('created_at_from') . ' 00:00:00' : request('created_at_from');
            $query->where('created_at', '>=', $from);
        }
        if (request('created_at_to')) {
            $to = strlen(request('created_at_to')) === 10 ? request('created_at_to') . ' 23:59:59' : request('created_at_to');
            $query->where('created_at', '<=', $to);
        }
    }

    private function applyDateRangeFilters($query, string $transactionDateColumn, ?string $shift, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $query->where($transactionDateColumn, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where($transactionDateColumn, '<=', $dateTo);
        }
        if ($shift && $shift !== 'All') {
            $query->where('shift', $shift);
        }
    }

    /**
     * Collect all rows for a fixed date range (no created_at filters). Used by warehouse dashboard.
     */
    public function collectUnifiedRowsForDateRange(string $dateFrom, string $dateTo, string $shift = 'All'): Collection
    {
        return $this->collectUnifiedRows($dateFrom, $dateTo, $shift, null, false);
    }

    /**
     * Category totals + daily income/expense series for the warehouse budget panel (Figma).
     *
     * @return array{category_totals: array{issued_budget: float, truck_trip_budget: float, parts: float, others: float, driver_cash_advance: float, helper_cash_advance: float}, daily_budget_chart: array<int, array{date: string, income: float, expense: float}>}
     */
    public function getWarehouseBudgetSnapshot(string $dateFrom, string $dateTo, string $shift = 'All'): array
    {
        $sorted = $this->collectUnifiedRowsForDateRange($dateFrom, $dateTo, $shift)
            ->sortByDesc(function ($item) {
                return $item['transaction_date'] . ' ' . str_pad((string) $item['id'], 10, '0', STR_PAD_LEFT);
            })->values();

        return [
            'category_totals' => $this->computeCategoryTotals($sorted),
            'daily_budget_chart' => $this->computeDailyBudgetChart($sorted, $dateFrom, $dateTo),
        ];
    }

    /**
     * @return array<string, float>
     */
    public function computeCategoryTotals(Collection $sorted): array
    {
        $issued = round((float) $sorted->where('type', self::TYPE_BUDGET)->sum('amount'), 2);

        return [
            'issued_budget' => $issued,
            'truck_trip_budget' => round((float) $sorted->where('type', self::TYPE_TRUCK_EXPENSE)->sum('amount'), 2),
            'parts' => round((float) $sorted->where('type', self::TYPE_PARTS_EXPENSE)->sum('amount'), 2),
            'others' => round((float) $sorted->where('type', self::TYPE_OTHER_EXPENSE)->sum('amount'), 2),
            'driver_cash_advance' => round((float) $sorted->where('type', self::TYPE_DRIVER_CASH_ADVANCE)->sum('amount'), 2),
            'helper_cash_advance' => round((float) $sorted->where('type', self::TYPE_HELPER_CASH_ADVANCE)->sum('amount'), 2),
        ];
    }

    /**
     * One object per calendar day in range: issued budget income vs budget expenses (by transaction_date).
     *
     * @return array<int, array{date: string, income: float, expense: float}>
     */
    private function computeDailyBudgetChart(Collection $sorted, string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $dates = [];
        $incomePerDay = [];
        $expensePerDay = [];

        $period = CarbonPeriod::create($start, '1 day', $end);
        foreach ($period as $day) {
            $d = $day->format('Y-m-d');
            $dates[] = $d;
            $dayRows = $sorted->where('transaction_date', $d);
            $incomePerDay[] = round((float) $dayRows
                ->where('type', self::TYPE_BUDGET)
                ->sum('amount'), 2);
            $expenseSum = (float) $dayRows->whereIn('type', self::EXPENSE_TYPES)->sum('amount');
            $expensePerDay[] = round($expenseSum, 2);
        }

        $series = [];
        foreach ($dates as $i => $d) {
            $series[] = [
                'date' => $d,
                'income' => $incomePerDay[$i],
                'expense' => $expensePerDay[$i],
            ];
        }

        return $series;
    }

    /**
     * @param  string|null  $typeFilter  When set and not "All", filter by budget row type
     */
    private function collectUnifiedRows(?string $dateFrom, ?string $dateTo, string $shift, ?string $typeFilter, bool $useCreatedAtFromRequest): Collection
    {
        $dateFrom = $dateFrom ?? request('transaction_date_from');
        $dateTo = $dateTo ?? request('transaction_date_to');

        $all = collect();

        $issuedQuery = IssuedBudget::query();
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($issuedQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($issuedQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($issuedQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_BUDGET,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => (float) $row->amount,
                'description' => $row->source,
                'source_table' => 'issued_budget',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $truckQuery = TruckTripExpense::query()->with('helper');
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($truckQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($truckQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($truckQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $desc = $row->helper ? trim($row->helper->first_name . ' ' . $row->helper->last_name) : null;
            $amount = (float) $row->issued_cash_amount;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_TRUCK_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'truck_trip_expense',
                'cash_on_hand' => (float) $row->cash_on_hand,
                'issued_cash_amount' => $amount,
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $partsQuery = PartsExpense::query();
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($partsQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($partsQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($partsQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount_per_item * (int) $row->quantity;
            $desc = $row->article ?: $row->receipt_no;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_PARTS_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'parts_expense',
                'plate_number' => $row->plate_number,
                'receipt_no' => $row->receipt_no,
                'quantity' => $row->quantity,
                'article' => $row->article,
                'amount_per_item' => (float) $row->amount_per_item,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $fundsQuery = FundsForStackRun::query();
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($fundsQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($fundsQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($fundsQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_OTHER_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $row->remarks,
                'source_table' => 'funds_for_stack_run',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $driverCAQuery = DriverCAHistory::query()->with('driver');
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($driverCAQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($driverCAQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($driverCAQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->driver ? trim($row->driver->first_name . ' ' . $row->driver->last_name) : null;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_DRIVER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'driver_cash_advancement_history',
                'driver_id' => $row->driver_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $helperCAQuery = HelperCAHistory::query()->with('helper');
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($helperCAQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($helperCAQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($helperCAQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->helper ? trim($row->helper->first_name . ' ' . $row->helper->last_name) : null;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_HELPER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'helper_cash_advancement_history',
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        if ($typeFilter && $typeFilter !== 'All') {
            $all = $all->filter(fn ($row) => $row['type'] === $typeFilter);
        }

        return $all;
    }

    /**
     * Collect all rows from the 6 tables, map to unified shape, sort, and compute totals.
     */
    public function list(int $perPage = 10, ?string $shift = 'All', ?string $type = null): array
    {
        $sorted = $this->collectUnifiedRows(null, null, $shift, $type, true)
            ->sortByDesc(function ($item) {
                return $item['transaction_date'] . ' ' . str_pad((string) $item['id'], 10, '0', STR_PAD_LEFT);
            })->values();

        $total = $sorted->count();
        $totalBudget = $sorted->where('type', self::TYPE_BUDGET)->sum('amount');
        $totalExpense = (float) $sorted->whereIn('type', self::EXPENSE_TYPES)->sum('amount');
        $cashOnHand = $totalBudget - $totalExpense;

        $categoryTotals = $this->computeCategoryTotals($sorted);

        $dailyChart = null;
        $from = request('transaction_date_from');
        $to = request('transaction_date_to');
        if ($from && $to) {
            $dailyChart = $this->computeDailyBudgetChart($sorted, $from, $to);
        }

        $page = (int) request('page', 1);
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $response = [
            'data' => $paginator->items(),
            'total_budget' => round($totalBudget, 2),
            'total_expense' => round($totalExpense, 2),
            'cash_on_hand' => round($cashOnHand, 2),
            'category_totals' => $categoryTotals,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        if ($dailyChart !== null) {
            $response['daily_budget_chart'] = $dailyChart;
        }

        return $response;
    }
}
