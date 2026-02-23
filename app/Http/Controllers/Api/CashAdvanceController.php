<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCashAdvanceRequest;
use App\Models\BudgetTransaction;
use App\Models\DriverCAHistory;
use App\Models\HelperCAHistory;
use Illuminate\Http\JsonResponse;

/**
 * Cash Advance API.
 * Type 4 (Advance Expense): type 1 = driver (driver_cash_advancement_history), type 2 = helper (helper_cash_advancement_history).
 *
 * @OA\Tag(name="Cash Advance", description="Add cash advance for driver (type=1) or helper (type=2); links to budget_transactions type 4.")
 */
class CashAdvanceController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/cash-advances",
     *     summary="Add cash advance (driver or helper)",
     *     tags={"Cash Advance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"type","amount","transaction_date"},
     *         @OA\Property(property="type", type="integer", enum={1, 2}, description="1=driver (driver_cash_advancement_history), 2=helper (helper_cash_advancement_history)"),
     *         @OA\Property(property="driver_id", type="integer", description="Required when type=1"),
     *         @OA\Property(property="helper_id", type="integer", description="Required when type=2"),
     *         @OA\Property(property="amount", type="number", example=500.00),
     *         @OA\Property(property="transaction_date", type="string", format="date", example="2026-02-20"),
     *         @OA\Property(property="shift", type="integer", enum={0, 1}, description="0=morning, 1=night (optional)"),
     *         @OA\Property(property="description", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=201, description="Cash advance created"),
     *     @OA\Response(response=422, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function store(StoreCashAdvanceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = (int) $validated['type'];
        $shift = isset($validated['shift']) ? (int) $validated['shift'] : BudgetTransaction::SHIFT_MORNING;

        $budgetTransaction = BudgetTransaction::create([
            'shift' => $shift,
            'transaction_type' => BudgetTransaction::TYPE_ADVANCE_EXPENSE,
            'description' => $validated['description'] ?? null,
        ]);

        $shiftLabel = $shift === BudgetTransaction::SHIFT_NIGHT ? 'Night' : 'Day';

        if ($type === 1) {
            $record = DriverCAHistory::create([
                'budget_transaction_id' => $budgetTransaction->id,
                'driver_id' => $validated['driver_id'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'shift' => $shiftLabel,
            ]);
            $record->load(['budgetTransaction', 'driver']);
        } else {
            $record = HelperCAHistory::create([
                'budget_transaction_id' => $budgetTransaction->id,
                'helper_id' => $validated['helper_id'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'shift' => $shiftLabel,
            ]);
            $record->load(['budgetTransaction', 'helper']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cash advance created successfully.',
            'data' => $record,
        ], 201);
    }
}
