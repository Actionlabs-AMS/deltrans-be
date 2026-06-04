<?php

namespace App\Observers;

use App\Services\ShiftBudgetBalanceService;
use Illuminate\Database\Eloquent\Model;

class BudgetShiftBalanceObserver
{
    public function __construct(
        private readonly ShiftBudgetBalanceService $shiftBudgetBalanceService
    ) {
    }

    public function created(Model $model): void
    {
        $this->sync($model);
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged(['transaction_date', 'shift'])) {
            $this->syncFromOriginal($model);
        }
        $this->sync($model);
    }

    public function deleted(Model $model): void
    {
        $this->sync($model);
    }

    public function restored(Model $model): void
    {
        $this->sync($model);
    }

    private function sync(Model $model): void
    {
        $date = $this->formatDate($model->getAttribute('transaction_date'));
        $shift = $model->getAttribute('shift');

        $this->shiftBudgetBalanceService->syncAfterBudgetChange($date, $shift);
    }

    private function syncFromOriginal(Model $model): void
    {
        $date = $this->formatDate($model->getOriginal('transaction_date'));
        $shift = $model->getOriginal('shift');

        $this->shiftBudgetBalanceService->syncAfterBudgetChange($date, $shift);
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }
}
