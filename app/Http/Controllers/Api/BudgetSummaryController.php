<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BudgetSummaryRequest;
use App\Services\BudgetSummaryService;

/**
 * @OA\Tag(
 *     name="Budget Summary",
 *     description="Consolidated budget summary from all budget tables with totals"
 * )
 * @OA\Schema(
 *     schema="BudgetSummaryRow",
 *     description="Unified row from any budget table",
 *     @OA\Property(property="id", type="integer", description="Unique index in the current filtered result set (1..N); safe for list keys across source tables"),
 *     @OA\Property(property="source_id", type="integer", description="Primary key in the source_table this row came from"),
 *     @OA\Property(property="row_key", type="string", description="Stable unique key: source_table + ':' + source_id (for dedupe / deep links)"),
 *     @OA\Property(property="type", type="string", enum={"Budget", "Truck Expense", "Parts Expense", "Other Expense", "Driver Cash Advance", "Helper Cash Advance"}),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="shift", type="string", nullable=true),
 *     @OA\Property(property="amount", type="number", description="Same sign as stored in source table (issued budget and expenses are typically positive)"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="source_table", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
 * )
 * @OA\Schema(
 *     schema="BudgetSummaryResponse",
 *     @OA\Property(property="status_code", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Budget summary retrieved successfully."),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BudgetSummaryRow")),
 *     @OA\Property(property="total_budget", type="number", format="float", example=50000.00, description="Sum of income (Issued Budget type) in filtered data"),
 *     @OA\Property(property="total_expense", type="number", format="float", example=30000.00, description="Sum of expenses in filtered data"),
 *     @OA\Property(property="cash_on_hand", type="number", format="float", example=20000.00, description="Total Budget minus Total Expense for filtered data"),
 *     @OA\Property(property="total_carry_over_coh", type="number", format="float", example=2000.00, description="Remaining COH from the shift immediately before the filtered period anchor (same as carried_from_previous on the first shift in range)"),
 *     @OA\Property(property="category_totals", ref="#/components/schemas/DashboardBudgetCategoryTotals"),
 *     @OA\Property(property="daily_budget_chart", type="array", description="Present when transaction_date_from and transaction_date_to are set (or date_from/date_to aliases)", @OA\Items(ref="#/components/schemas/DailyBudgetChartDayRow")),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         description="Laravel-style pagination metadata (aligned with Resource collection responses)",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="from", type="integer", example=1, nullable=true),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(
 *             property="links",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="url", type="string", format="uri", nullable=true),
 *                 @OA\Property(property="label", type="string", example="1"),
 *                 @OA\Property(property="active", type="boolean")
 *             )
 *         ),
 *         @OA\Property(property="path", type="string", format="uri", example="http://localhost/api/budget/summary"),
 *         @OA\Property(property="per_page", type="integer", example=10),
 *         @OA\Property(property="to", type="integer", example=10, nullable=true),
 *         @OA\Property(property="total", type="integer", example=42),
 *         @OA\Property(property="all", type="integer", example=42, description="Total filtered rows (same as total for this endpoint)"),
 *         @OA\Property(property="trashed", type="integer", example=0, description="Always 0; reserved for parity with other list APIs")
 *     )
 * )
 */
class BudgetSummaryController
{
    public function __construct(
        protected BudgetSummaryService $service
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/budget/summary",
     *     summary="Budget summary (consolidated list + totals)",
     *     tags={"Budget Summary"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="shift", in="query", description="Day, Night, or All", @OA\Schema(type="string", enum={"Day", "Night", "All"})),
     *     @OA\Parameter(name="transaction_date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_from", in="query", description="Alias for transaction_date_from (aligns with GET /api/dashboard)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", description="Alias for transaction_date_to", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filter rows by substring (case-insensitive) against type, description, source_table, transaction_date, shift, amount, plate/receipt/article where present",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/BudgetSummaryResponse"))
     * )
     */
    public function index(BudgetSummaryRequest $request)
    {
        $request->validated();
        $shift = $request->get('shift', 'All');
        $type = $request->get('type', 'All');
        $perPage = (int) $request->get('per_page', 10);

        $result = $this->service->list($perPage, $shift, $type);

        return response()->json(array_merge($result, [
            'status_code' => 200,
            'message' => 'Budget summary retrieved successfully.',
        ]));
    }
}
