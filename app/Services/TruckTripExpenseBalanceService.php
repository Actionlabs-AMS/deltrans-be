<?php

namespace App\Services;

use App\Models\TruckTripExpense;
use Illuminate\Validation\ValidationException;

class TruckTripExpenseBalanceService
{
    public function initializeRemainingAmount(float $cashOnHand, float $issuedCashAmount): float
    {
        return round($cashOnHand + $issuedCashAmount, 2);
    }

    public function decrementRemainingAmount(int $tripId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $trip = TruckTripExpense::query()->lockForUpdate()->findOrFail($tripId);
        $newRemainingAmount = round((float) $trip->remaining_amount - $amount, 2);

        if ($newRemainingAmount < 0) {
            throw ValidationException::withMessages([
                'actual_truck_trip_expense_amount' => [
                    'The selected truck trip expense has insufficient remaining amount.',
                ],
            ]);
        }

        $trip->update(['remaining_amount' => $newRemainingAmount]);
    }

    public function incrementRemainingAmount(int $tripId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $trip = TruckTripExpense::query()->lockForUpdate()->findOrFail($tripId);

        $trip->update([
            'remaining_amount' => round((float) $trip->remaining_amount + $amount, 2),
        ]);
    }
}
