<?php

namespace App\Services;

use App\Http\Resources\ShiftBudgetBalanceResource;
use App\Models\ShiftBudgetBalance;
use App\Support\ShiftChronology;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShiftBudgetBalanceService
{
    public function __construct(
        private readonly BudgetShiftAmountsCalculator $amountsCalculator
    ) {
    }

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        $query = ShiftBudgetBalance::query()
            ->orderByDesc('transaction_date')
            ->orderByRaw($this->shiftOrderSql());

        if ($date = request('transaction_date')) {
            $query->whereDate('transaction_date', $date);
        }
        if ($from = request('transaction_date_from')) {
            $query->where('transaction_date', '>=', $from);
        }
        if ($to = request('transaction_date_to')) {
            $query->where('transaction_date', '<=', $to);
        }
        if ($shift = request('shift')) {
            $query->where('shift', $shift);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function showForShift(string $date, string $shift, bool $persist = true): array
    {
        $this->assertValidShift($shift);

        if ($persist) {
            $this->recalculateFrom($date, $shift);
        }

        $balance = ShiftBudgetBalance::query()
            ->whereDate('transaction_date', $date)
            ->where('shift', $shift)
            ->first();

        if (!$balance) {
            $balance = $this->buildUnpersistedPreview($date, $shift);
        }

        return (new ShiftBudgetBalanceResource($balance))->resolve();
    }

    public function recalculateFrom(string $date, string $shift): void
    {
        $this->assertValidShift($shift);

        $startPosition = ShiftChronology::toPosition($date, $shift);
        $endPosition = $this->resolveEndPosition($date, $shift, $startPosition);

        DB::transaction(function () use ($startPosition, $endPosition) {
            for ($position = $startPosition; $position <= $endPosition; $position++) {
                [$d, $s] = ShiftChronology::fromPosition($position);
                $this->computeAndPersist($d, $s);
            }
        });
    }

    /**
     * Recalculate every shift from the first budget activity through the latest transaction date.
     */
    public function recalculateAll(): int
    {
        $minDate = $this->minTransactionDate();
        $maxDate = $this->amountsCalculator->maxTransactionDate();

        if (!$minDate || !$maxDate) {
            return 0;
        }

        $start = ShiftChronology::toPosition($minDate, 'Day');
        $end = ShiftChronology::toPosition($maxDate, 'Night');

        $count = 0;
        DB::transaction(function () use ($start, $end, &$count) {
            for ($position = $start; $position <= $end; $position++) {
                [$d, $s] = ShiftChronology::fromPosition($position);
                $this->computeAndPersist($d, $s);
                $count++;
            }
        });

        return $count;
    }

    public function syncAfterBudgetChange(?string $date, ?string $shift, ?string $previousDate = null, ?string $previousShift = null): void
    {
        if ($previousDate && $previousShift && ShiftChronology::isValidShift($previousShift)) {
            if ($previousDate !== $date || $previousShift !== $shift) {
                $this->recalculateFrom($previousDate, $previousShift);
            }
        }

        if ($date && $shift && ShiftChronology::isValidShift($shift)) {
            $this->recalculateFrom($date, $shift);
        }
    }

    private function computeAndPersist(string $date, string $shift): ShiftBudgetBalance
    {
        $issued = $this->amountsCalculator->sumIssuedBudget($date, $shift);
        $expense = $this->amountsCalculator->sumTotalExpense($date, $shift);

        $previous = ShiftChronology::previous($date, $shift);
        $carried = 0.0;
        $previousDate = null;
        $previousShift = null;

        if ($previous !== null) {
            $previousDate = $previous['date'];
            $previousShift = $previous['shift'];
            $prevRow = ShiftBudgetBalance::query()
                ->whereDate('transaction_date', $previousDate)
                ->where('shift', $previousShift)
                ->first();
            $carried = $prevRow ? (float) $prevRow->remaining_coh : 0.0;
        }

        $totalBudget = round($issued + $carried, 2);
        $remainingCoh = round($totalBudget - $expense, 2);

        return ShiftBudgetBalance::query()->updateOrCreate(
            [
                'transaction_date' => $date,
                'shift' => $shift,
            ],
            [
                'issued_budget' => $issued,
                'carried_from_previous' => round($carried, 2),
                'total_budget' => $totalBudget,
                'total_expense' => $expense,
                'remaining_coh' => $remainingCoh,
                'previous_shift_date' => $previousDate,
                'previous_shift' => $previousShift,
                'computed_at' => now(),
            ]
        );
    }

    private function buildUnpersistedPreview(string $date, string $shift): ShiftBudgetBalance
    {
        $issued = $this->amountsCalculator->sumIssuedBudget($date, $shift);
        $expense = $this->amountsCalculator->sumTotalExpense($date, $shift);
        $previous = ShiftChronology::previous($date, $shift);

        $carried = 0.0;
        $previousDate = null;
        $previousShift = null;

        if ($previous !== null) {
            $previousDate = $previous['date'];
            $previousShift = $previous['shift'];
            $prevRow = ShiftBudgetBalance::query()
                ->whereDate('transaction_date', $previousDate)
                ->where('shift', $previousShift)
                ->first();
            $carried = $prevRow ? (float) $prevRow->remaining_coh : 0.0;
        }

        $totalBudget = round($issued + $carried, 2);

        return new ShiftBudgetBalance([
            'transaction_date' => $date,
            'shift' => $shift,
            'issued_budget' => $issued,
            'carried_from_previous' => round($carried, 2),
            'total_budget' => $totalBudget,
            'total_expense' => $expense,
            'remaining_coh' => round($totalBudget - $expense, 2),
            'previous_shift_date' => $previousDate,
            'previous_shift' => $previousShift,
            'computed_at' => null,
        ]);
    }

    private function resolveEndPosition(string $date, string $shift, int $startPosition): int
    {
        $maxDate = $this->amountsCalculator->maxTransactionDate();
        if (!$maxDate) {
            return $startPosition;
        }

        $endFromData = ShiftChronology::toPosition($maxDate, 'Night');

        $storedMax = ShiftBudgetBalance::query()->max('transaction_date');
        $endFromSnapshots = $storedMax
            ? ShiftChronology::toPosition(
                Carbon::parse($storedMax)->format('Y-m-d'),
                'Night'
            )
            : $startPosition;

        return max($startPosition, $endFromData, $endFromSnapshots);
    }

    private function minTransactionDate(): ?string
    {
        $dates = array_filter([
            \App\Models\IssuedBudget::query()->min('transaction_date'),
            \App\Models\TruckTripExpense::query()->min('transaction_date'),
            \App\Models\PartsExpense::query()->min('transaction_date'),
            \App\Models\FundsForStackRun::query()->min('transaction_date'),
            \App\Models\DriverCAHistory::query()->min('transaction_date'),
            \App\Models\HelperCAHistory::query()->min('transaction_date'),
        ]);

        return $dates === [] ? null : min($dates);
    }

    private function assertValidShift(string $shift): void
    {
        if (!ShiftChronology::isValidShift($shift)) {
            throw new InvalidArgumentException("Invalid shift: {$shift}");
        }
    }

    private function shiftOrderSql(): string
    {
        return "FIELD(shift, 'Day', 'Night', '1st', '2nd')";
    }
}
