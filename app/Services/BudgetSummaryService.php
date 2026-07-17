<?php

namespace App\Services;

use App\Models\DriverCAHistory;
use App\Models\FundsForStackRun;
use App\Models\HelperCAHistory;
use App\Models\IssuedBudget;
use App\Models\PartsExpense;
use App\Models\ShiftBudgetBalance;
use App\Models\TruckTripExpense;
use App\Support\ShiftChronology;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BudgetSummaryService
{
    public function __construct(
        private readonly ShiftBudgetBalanceService $shiftBudgetBalanceService
    ) {
    }

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
        $this->applyDateRangeFilters(
            $query,
            $transactionDateColumn,
            $shift,
            request('transaction_date_from'),
            request('transaction_date_to')
        );

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
     * Newest first: latest transaction_date, then latest created_at, then highest id (cross-table safe).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortUnifiedRowsNewestFirst(Collection $rows): Collection
    {
        return $rows->sort(function ($a, $b) {
            $dCmp = strcmp($b['transaction_date'] ?? '', $a['transaction_date'] ?? '');
            if ($dCmp !== 0) {
                return $dCmp;
            }
            $cCmp = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($cCmp !== 0) {
                return $cCmp;
            }

            return ($b['source_id'] ?? 0) <=> ($a['source_id'] ?? 0);
        })->values();
    }

    /**
     * Replace cross-table-colliding PKs with a single 1-based id across the full filtered list (stable for pagination).
     * Preserves the original row id in source_id.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function assignUniqueUnifiedRowIds(Collection $rows): Collection
    {
        return $rows->values()->map(function (array $row, int $index) {
            $row['id'] = $index + 1;

            return $row;
        });
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
        $sorted = $this->sortUnifiedRowsNewestFirst(
            $this->collectUnifiedRowsForDateRange($dateFrom, $dateTo, $shift)
        );

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
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const SOURCE_MODELS = [
        'issued_budget' => IssuedBudget::class,
        'truck_trip_expense' => TruckTripExpense::class,
        'parts_expense' => PartsExpense::class,
        'funds_for_stack_run' => FundsForStackRun::class,
        'driver_cash_advancement_history' => DriverCAHistory::class,
        'helper_cash_advancement_history' => HelperCAHistory::class,
    ];

    public function getActiveCount(): int
    {
        return IssuedBudget::count()
            + TruckTripExpense::count()
            + PartsExpense::count()
            + FundsForStackRun::count()
            + DriverCAHistory::count()
            + HelperCAHistory::count();
    }

    public function getTrashedCount(): int
    {
        return IssuedBudget::onlyTrashed()->count()
            + TruckTripExpense::onlyTrashed()->count()
            + PartsExpense::onlyTrashed()->count()
            + FundsForStackRun::onlyTrashed()->count()
            + DriverCAHistory::onlyTrashed()->count()
            + HelperCAHistory::onlyTrashed()->count();
    }

    public function destroyBySource(string $sourceTable, int $sourceId): void
    {
        $model = $this->resolveSourceModel($sourceTable, $sourceId);
        $model->delete();
    }

    public function restoreBySource(string $sourceTable, int $sourceId): array
    {
        $modelClass = $this->resolveSourceModelClass($sourceTable);
        $model = $modelClass::withTrashed()->findOrFail($sourceId);
        $model->restore();

        if (in_array($sourceTable, ['truck_trip_expense', 'driver_cash_advancement_history', 'helper_cash_advancement_history'], true)) {
            $model->load($sourceTable === 'truck_trip_expense' ? 'helper' : ($sourceTable === 'driver_cash_advancement_history' ? 'driver' : 'helper'));
        }

        return $this->mapSourceModelToRow($model, $sourceTable);
    }

    public function forceDeleteBySource(string $sourceTable, int $sourceId): void
    {
        $modelClass = $this->resolveSourceModelClass($sourceTable);
        $model = $modelClass::withTrashed()->findOrFail($sourceId);
        $model->forceDelete();
    }

    private function resolveSourceModelClass(string $sourceTable): string
    {
        $modelClass = self::SOURCE_MODELS[$sourceTable] ?? null;
        if ($modelClass === null) {
            throw new \InvalidArgumentException('Unsupported budget source table.');
        }

        return $modelClass;
    }

    private function resolveSourceModel(string $sourceTable, int $sourceId)
    {
        $modelClass = $this->resolveSourceModelClass($sourceTable);

        return $modelClass::findOrFail($sourceId);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTruckExpenseDescription($model): ?string
    {
        $helperName = $model->helper
            ? trim($model->helper->first_name . ' ' . $model->helper->last_name)
            : null;
        $plateNumber = $model->plate_number ?: ($model->truck?->plate_number ?? null);

        if ($helperName && $plateNumber) {
            return trim($helperName . ' (' . $plateNumber . ')');
        }

        return $helperName ?: $plateNumber;
    }

    private function mapSourceModelToRow($model, string $sourceTable): array
    {
        return match ($sourceTable) {
            'issued_budget' => [
                'source_id' => $model->id,
                'row_key' => 'issued_budget:' . $model->id,
                'type' => self::TYPE_BUDGET,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->amount,
                'description' => $model->source,
                'source_table' => 'issued_budget',
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            'truck_trip_expense' => [
                'source_id' => $model->id,
                'row_key' => 'truck_trip_expense:' . $model->id,
                'type' => self::TYPE_TRUCK_EXPENSE,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->issued_cash_amount,
                'description' => $this->formatTruckExpenseDescription($model),
                'source_table' => 'truck_trip_expense',
                'cash_on_hand' => (float) $model->cash_on_hand,
                'issued_cash_amount' => (float) $model->issued_cash_amount, 
                'helper_id' => $model->helper_id,
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            'parts_expense' => [
                'source_id' => $model->id,
                'row_key' => 'parts_expense:' . $model->id,
                'type' => self::TYPE_PARTS_EXPENSE,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->amount_per_item * (int) $model->quantity,
                'description' => $model->article ?: $model->receipt_no,
                'source_table' => 'parts_expense',
                'plate_number' => $model->plate_number,
                'receipt_no' => $model->receipt_no,
                'quantity' => $model->quantity,
                'article' => $model->article,
                'amount_per_item' => (float) $model->amount_per_item,
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            'funds_for_stack_run' => [
                'source_id' => $model->id,
                'row_key' => 'funds_for_stack_run:' . $model->id,
                'type' => self::TYPE_OTHER_EXPENSE,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->amount,
                'description' => $model->remarks,
                'source_table' => 'funds_for_stack_run',
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            'driver_cash_advancement_history' => [
                'source_id' => $model->id,
                'row_key' => 'driver_cash_advancement_history:' . $model->id,
                'type' => self::TYPE_DRIVER_CASH_ADVANCE,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->amount,
                'description' => $model->driver ? trim($model->driver->first_name . ' ' . $model->driver->last_name) : null,
                'source_table' => 'driver_cash_advancement_history',
                'driver_id' => $model->driver_id,
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            'helper_cash_advancement_history' => [
                'source_id' => $model->id,
                'row_key' => 'helper_cash_advancement_history:' . $model->id,
                'type' => self::TYPE_HELPER_CASH_ADVANCE,
                'transaction_date' => $model->transaction_date?->format('Y-m-d'),
                'shift' => $model->shift,
                'amount' => (float) $model->amount,
                'description' => $model->helper ? trim($model->helper->first_name . ' ' . $model->helper->last_name) : null,
                'source_table' => 'helper_cash_advancement_history',
                'helper_id' => $model->helper_id,
                'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $model->deleted_at?->format('Y-m-d H:i:s'),
            ],
            default => throw new \InvalidArgumentException('Unsupported budget source table.'),
        };
    }

    /**
     * @param  string|null  $typeFilter  When set and not "All", filter by budget row type
     */
    private function collectUnifiedRows(
        ?string $dateFrom,
        ?string $dateTo,
        string $shift,
        ?string $typeFilter,
        bool $useCreatedAtFromRequest,
        bool $onlyTrashed = false
    ): Collection {
        $dateFrom = $dateFrom ?? request('transaction_date_from');
        $dateTo = $dateTo ?? request('transaction_date_to');

        $all = collect();

        $issuedQuery = IssuedBudget::query();
        if ($onlyTrashed) {
            $issuedQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($issuedQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($issuedQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($issuedQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'issued_budget:' . $row->id,
                'type' => self::TYPE_BUDGET,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => (float) $row->amount,
                'description' => $row->source,
                'source_table' => 'issued_budget',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $truckQuery = TruckTripExpense::query()->with('helper');
        if ($onlyTrashed) {
            $truckQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($truckQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($truckQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($truckQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $desc = $this->formatTruckExpenseDescription($row);
            $amount = (float) $row->issued_cash_amount;
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'truck_trip_expense:' . $row->id,
                'type' => self::TYPE_TRUCK_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'plate_number' => $row->plate_number,
                'description' => $desc,
                'source_table' => 'truck_trip_expense',
                'cash_on_hand' => (float) $row->cash_on_hand,
                'issued_cash_amount' => $amount,
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $partsQuery = PartsExpense::query();
        if ($onlyTrashed) {
            $partsQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($partsQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($partsQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($partsQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount_per_item * (int) $row->quantity;
            $desc = $row->article ?: $row->receipt_no;
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'parts_expense:' . $row->id,
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
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $fundsQuery = FundsForStackRun::query();
        if ($onlyTrashed) {
            $fundsQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($fundsQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($fundsQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($fundsQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'funds_for_stack_run:' . $row->id,
                'type' => self::TYPE_OTHER_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $row->remarks,
                'source_table' => 'funds_for_stack_run',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $driverCAQuery = DriverCAHistory::query()->with('driver');
        if ($onlyTrashed) {
            $driverCAQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($driverCAQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($driverCAQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($driverCAQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->driver ? trim($row->driver->first_name . ' ' . $row->driver->last_name) : null;
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'driver_cash_advancement_history:' . $row->id,
                'type' => self::TYPE_DRIVER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'driver_cash_advancement_history',
                'driver_id' => $row->driver_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $helperCAQuery = HelperCAHistory::query()->with('helper');
        if ($onlyTrashed) {
            $helperCAQuery->onlyTrashed();
        }
        if ($useCreatedAtFromRequest) {
            $this->applyFilters($helperCAQuery, 'transaction_date', $shift);
        } else {
            $this->applyDateRangeFilters($helperCAQuery, 'transaction_date', $shift, $dateFrom, $dateTo);
        }
        foreach ($helperCAQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->helper ? trim($row->helper->first_name . ' ' . $row->helper->last_name) : null;
            $all->push([
                'source_id' => $row->id,
                'row_key' => 'helper_cash_advancement_history:' . $row->id,
                'type' => self::TYPE_HELPER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => $amount,
                'description' => $desc,
                'source_table' => 'helper_cash_advancement_history',
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s'),
            ]);
        }

        if ($typeFilter && $typeFilter !== 'All') {
            $all = $all->filter(fn($row) => $row['type'] === $typeFilter);
        }

        return $all;
    }

    /**
     * Case-insensitive substring match across type, description, source_table, dates, amounts, and common extra fields.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function filterUnifiedRowsBySearch(Collection $rows, string $search): Collection
    {
        $needle = mb_strtolower(trim($search));
        if ($needle === '') {
            return $rows;
        }

        return $rows->filter(function (array $row) use ($needle) {
            $parts = [
                $row['type'] ?? '',
                $row['description'] ?? '',
                $row['source_table'] ?? '',
                $row['transaction_date'] ?? '',
                $row['shift'] ?? '',
                isset($row['amount']) ? (string) $row['amount'] : '',
                isset($row['created_at']) ? (string) $row['created_at'] : '',
                $row['plate_number'] ?? '',
                $row['receipt_no'] ?? '',
                $row['article'] ?? '',
            ];
            foreach ($parts as $part) {
                if ($part !== '' && str_contains(mb_strtolower((string) $part), $needle)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * Collect all rows from the 6 tables, map to unified shape, sort, and compute totals.
     */
    public function list(int $perPage = 10, ?string $shift = 'All', ?string $type = null, bool $trash = false, ?string $dateFilter = 'daily'): array
    {
        $sorted = $this->sortUnifiedRowsNewestFirst(
            //private function collectUnifiedRows( ?string $dateFrom, ?string $dateTo, string $shift,
            //                                     ?string $typeFilter, bool $useCreatedAtFromRequest, bool $onlyTrashed = false)
            $this->collectUnifiedRows(null, null, $shift, $type, true, $trash)
        );

        $search = request('search');
        if (is_string($search) && trim($search) !== '') {
            $sorted = $this->filterUnifiedRowsBySearch($sorted, $search);
        }

        $sorted = $this->assignUniqueUnifiedRowIds($sorted);

        $total = $sorted->count();
        $totalBudget = $sorted->where('type', self::TYPE_BUDGET)->sum('amount');
        $totalExpense = (float) $sorted->whereIn('type', self::EXPENSE_TYPES)->sum('amount');
        $cashOnHand = $totalBudget - $totalExpense;
        $previousCashOnHand = $this->computeTotalCarryOverCoh($shift, $sorted, $dateFilter);

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
            ['path' => request()->url(), 'pageName' => 'page']
        );
        $paginator->withQueryString();

        $meta = collect($paginator->toArray())
            ->except('data')
            ->except(['first_page_url', 'last_page_url', 'next_page_url', 'prev_page_url'])
            ->all();
        $meta['all'] = $this->getActiveCount();
        $meta['trashed'] = $this->getTrashedCount();

        $response = [
            'data' => $paginator->items(),
            'total_budget' => round($totalBudget, 2),
            'total_expense' => round($totalExpense, 2),
            'cash_on_hand' => round($cashOnHand, 2),
            'previous_cash_on_hand' => $previousCashOnHand,
            'category_totals' => $categoryTotals,
            'meta' => $meta,
        ];

        if ($dailyChart !== null) {
            $response['daily_budget_chart'] = $dailyChart;
        }

        return $response;
    }

    /**
     * Compute the carry-over balance for the selected period using only the relevant date range.
     *
     * Weekly mode uses the selected date range only and resets to zero at the start of a new week.
     * Daily mode walks the shifts in the anchor week's chronological order and carries the balance
     * forward through the same-day Day -> Night sequence and the previous day Night -> current day Day sequence.
     */
    private function computeTotalCarryOverCoh(?string $shiftFilter, Collection $sorted, string $dateFilter = 'daily'): float
    {
        $dateFilter = strtolower($dateFilter ?? 'daily');

        if ($dateFilter === 'weekly') {
            $dateFrom = request('transaction_date_from');
            $dateTo = request('transaction_date_to');

            if (!$dateFrom || !$dateTo) {
                $dateFrom = $sorted->min('transaction_date');
                $dateTo = $sorted->max('transaction_date');
            }

            if (!$dateFrom || !$dateTo) {
                return 0.0;
            }

            $rows = $this->collectUnifiedRowsForDateRange(
                Carbon::parse($dateFrom)->format('Y-m-d'),
                Carbon::parse($dateTo)->format('Y-m-d'),
                'All'
            );

            $rows = $rows->values()->filter(function (array $row): bool {
                return isset($row['transaction_date']) && $row['transaction_date'] !== '';
            });

            $totalBudget = (float) $rows->where('type', self::TYPE_BUDGET)->sum('amount');
            $totalExpense = (float) $rows->whereIn('type', self::EXPENSE_TYPES)->sum('amount');

            return round($totalBudget - $totalExpense, 2);
        }

        $anchor = $this->resolveSummaryAnchorShift($shiftFilter, $sorted);
        if ($anchor === null) {
            return 0.0;
        }

        [$anchorDate, $anchorShift] = $anchor;
        $weekStart = Carbon::parse($anchorDate)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $weekEnd = Carbon::parse($anchorDate)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $weekRows = $this->collectUnifiedRowsForDateRange($weekStart, $weekEnd, 'All');

        $carry = 0.0;
        $currentWeek = Carbon::parse($weekStart);
        $endWeek = Carbon::parse($weekEnd);

        while ($currentWeek->lessThanOrEqualTo($endWeek)) {
            $day = $currentWeek->format('Y-m-d');

            foreach (['Day', 'Night'] as $shift) {
                if ($day === $anchorDate && $shift === $anchorShift) {
                    return round($carry, 2);
                }

                $shiftRows = $weekRows->filter(function (array $row) use ($day, $shift): bool {
                    return ($row['transaction_date'] ?? null) === $day
                        && ($row['shift'] ?? null) === $shift;
                });

                $budget = (float) $shiftRows->where('type', self::TYPE_BUDGET)->sum('amount');
                $expense = (float) $shiftRows->whereIn('type', self::EXPENSE_TYPES)->sum('amount');
                $carry = round($carry + $budget - $expense, 2);
            }

            $currentWeek->addDay();
        }

        return round($carry, 2);
    }

    /**
     * @return array{0: string, 1: string}|null [transaction_date, shift]
     */
    private function resolveSummaryAnchorShift(?string $shiftFilter, Collection $sorted): ?array
    {
        $dateFrom = request('transaction_date_from');

        if ($dateFrom) {
            $anchorDate = Carbon::parse($dateFrom)->format('Y-m-d');
        } elseif ($sorted->isNotEmpty()) {
            $anchorDate = $sorted->min('transaction_date');
        } else {
            return null;
        }

        if ($shiftFilter && $shiftFilter !== 'All' && ShiftChronology::isValidShift($shiftFilter)) {
            $anchorShift = $shiftFilter;
        } else {
            $anchorShift = 'Day';
        }

        return [$anchorDate, $anchorShift];
    }
}
