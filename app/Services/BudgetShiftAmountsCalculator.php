<?php

namespace App\Services;

use App\Models\DriverCAHistory;
use App\Models\FundsForStackRun;
use App\Models\HelperCAHistory;
use App\Models\IssuedBudget;
use App\Models\PartsExpense;
use App\Models\TruckTripExpense;
use App\Support\ShiftChronology;
use Illuminate\Database\Eloquent\Builder;

/**
 * Per-shift totals from the six budget source tables (aligned with BudgetSummaryService).
 */
class BudgetShiftAmountsCalculator
{
    public function sumIssuedBudget(string $date, string $shift): float
    {
        return round((float) $this->baseQuery(IssuedBudget::query(), $date, $shift)->sum('amount'), 2);
    }

    public function sumTotalExpense(string $date, string $shift): float
    {
        $truck = (float) $this->baseQuery(TruckTripExpense::query(), $date, $shift)->sum('issued_cash_amount');

        $parts = (float) $this->baseQuery(PartsExpense::query(), $date, $shift)
            ->sum(\Illuminate\Support\Facades\DB::raw('quantity * amount_per_item'));

        $stack = (float) $this->baseQuery(FundsForStackRun::query(), $date, $shift)->sum('amount');
        $driver = (float) $this->baseQuery(DriverCAHistory::query(), $date, $shift)->sum('amount');
        $helper = (float) $this->baseQuery(HelperCAHistory::query(), $date, $shift)->sum('amount');

        return round($truck + $parts + $stack + $driver + $helper, 2);
    }

    /**
     * Shifts that share the same carryover slot (Day with 1st, Night with 2nd).
     *
     * @return array<int, string>
     */
    public static function shiftsInSameSlot(string $shift): array
    {
        return ShiftChronology::shiftIndex($shift) === 0
            ? ['Day', '1st']
            : ['Night', '2nd'];
    }

    /**
     * Latest transaction_date across all budget source tables.
     */
    public function maxTransactionDate(): ?string
    {
        $dates = array_filter([
            IssuedBudget::query()->max('transaction_date'),
            TruckTripExpense::query()->max('transaction_date'),
            PartsExpense::query()->max('transaction_date'),
            FundsForStackRun::query()->max('transaction_date'),
            DriverCAHistory::query()->max('transaction_date'),
            HelperCAHistory::query()->max('transaction_date'),
        ]);

        if ($dates === []) {
            return null;
        }

        return max($dates);
    }

    private function baseQuery(Builder $query, string $date, string $shift): Builder
    {
        return $query
            ->whereDate('transaction_date', $date)
            ->whereIn('shift', self::shiftsInSameSlot($shift));
    }
}
