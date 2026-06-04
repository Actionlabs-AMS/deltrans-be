<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ShiftBudgetBalanceRequest;
use App\Http\Resources\ShiftBudgetBalanceResource;
use App\Services\ShiftBudgetBalanceService;
use InvalidArgumentException;

/**
 * @OA\Tag(
 *     name="Shift Budget Balance",
 *     description="Recorded remaining cash on hand (COH) per transaction_date and shift, with carryover from the previous shift"
 * )
 * @OA\Schema(
 *     schema="ShiftBudgetBalance",
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
 *     @OA\Property(property="issued_budget", type="number", format="float", description="Sum of issued_budget for this shift"),
 *     @OA\Property(property="carried_from_previous", type="number", format="float", description="Previous shift remaining_coh"),
 *     @OA\Property(property="total_budget", type="number", format="float", description="issued_budget + carried_from_previous"),
 *     @OA\Property(property="total_expense", type="number", format="float"),
 *     @OA\Property(property="remaining_coh", type="number", format="float", description="total_budget - total_expense; carried to next shift"),
 *     @OA\Property(property="cash_on_hand", type="number", format="float", description="Alias of remaining_coh"),
 *     @OA\Property(property="previous_shift_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="previous_shift", type="string", nullable=true),
 *     @OA\Property(property="computed_at", type="string", format="date-time", nullable=true)
 * )
 */
class ShiftBudgetBalanceController
{
    public function __construct(
        protected ShiftBudgetBalanceService $service
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/budget/shift-balances",
     *     summary="List recorded shift budget balances",
     *     tags={"Shift Budget Balance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="transaction_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="shift", in="query", @OA\Schema(type="string", enum={"Day", "Night", "1st", "2nd"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(ShiftBudgetBalanceRequest $request)
    {
        $request->validated();
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->service->list($perPage);

        return ShiftBudgetBalanceResource::collection($paginator)
            ->additional([
                'status_code' => 200,
                'message' => 'Shift budget balances retrieved successfully.',
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/shift-balances/show",
     *     summary="Get (and refresh) balance for one shift",
     *     tags={"Shift Budget Balance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="transaction_date", in="query", required=true, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="shift", in="query", required=true, @OA\Schema(type="string", enum={"Day", "Night", "1st", "2nd"})),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ShiftBudgetBalance"))),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function show(ShiftBudgetBalanceRequest $request)
    {
        $validated = $request->validated();

        try {
            $data = $this->service->showForShift(
                $validated['transaction_date'],
                $validated['shift'],
                true
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 422, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Shift budget balance retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/budget/shift-balances/recalculate",
     *     summary="Recalculate shift balances from a shift forward, or rebuild all",
     *     tags={"Shift Budget Balance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_date", type="string", format="date"),
     *             @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
     *             @OA\Property(property="recalculate_all", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function recalculate(ShiftBudgetBalanceRequest $request)
    {
        $validated = $request->validated();

        try {
            if (!empty($validated['recalculate_all'])) {
                $count = $this->service->recalculateAll();

                return response()->json([
                    'status_code' => 200,
                    'message' => "Recalculated {$count} shift balance(s).",
                    'recalculated_count' => $count,
                ]);
            }

            $this->service->recalculateFrom(
                $validated['transaction_date'],
                $validated['shift']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 422, 'message' => $e->getMessage()], 422);
        }

        $data = $this->service->showForShift(
            $validated['transaction_date'],
            $validated['shift'],
            false
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Shift budget balances recalculated successfully.',
            'data' => $data,
        ]);
    }
}
