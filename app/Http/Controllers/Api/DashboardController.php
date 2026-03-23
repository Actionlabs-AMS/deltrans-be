<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="DashboardDieselByPlateRow",
 *     title="Diesel by truck plate",
 *     description="One row: sum of diesel_expenses.amount for waybills sharing the same truck_plate_number in the dashboard date range.",
 *     @OA\Property(property="truck_plate_number", type="string", example="NBB-1234"),
 *     @OA\Property(property="amount", type="number", format="float", example=17500.25)
 * )
 * @OA\Schema(
 *     schema="DashboardReceivablesByEntity",
 *     title="Receivables by shipping line",
 *     description="Invoice total_amount (same computation as kpis.sales) grouped by SOA shipping line.",
 *     @OA\Property(property="total", type="number", format="float", example=32984.00),
 *     @OA\Property(
 *         property="entities",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="shipping_line_name", type="string", example="ONE"),
 *             @OA\Property(property="amount", type="number", format="float", example=32984.00)
 *         )
 *     )
 * )
 * @OA\Schema(
 *     schema="DashboardBudgetCategoryTotals",
 *     title="Budget category totals (warehouse panel)",
 *     description="All amounts are filtered by transaction_date between the dashboard date range (except income in daily chart, which uses invoice date).",
 *     @OA\Property(property="issued_budget", type="number", format="float", example=50000.00, description="Sum of issued_budget.amount (transaction_date)"),
 *     @OA\Property(property="truck_trip_budget", type="number", format="float", example=20000.00, description="Sum of truck_trip_expense.issued_cash_amount (transaction_date)"),
 *     @OA\Property(property="parts", type="number", format="float", example=8000.00, description="Sum of parts_expense quantity × amount_per_item (transaction_date)"),
 *     @OA\Property(property="others", type="number", format="float", example=1000.00, description="Sum of funds_for_stack_run.amount (transaction_date)"),
 *     @OA\Property(property="driver_cash_advance", type="number", format="float", example=500.00, description="Sum of driver_cash_advancement_history.amount (transaction_date)"),
 *     @OA\Property(property="helper_cash_advance", type="number", format="float", example=500.00, description="Sum of helper_cash_advancement_history.amount (transaction_date)")
 * )
 * @OA\Schema(
 *     schema="DailyBudgetChartDayRow",
 *     title="Daily budget chart — one day",
 *     description="income = invoice total_amount that day (invoices.date). expense = sum of budget expense rows that day (transaction_date on expense tables).",
 *     @OA\Property(property="date", type="string", format="date", example="2026-03-01"),
 *     @OA\Property(property="income", type="number", format="float", example=320),
 *     @OA\Property(property="expense", type="number", format="float", example=280)
 * )
 * @OA\Schema(
 *     schema="SalesOverviewMonthRow",
 *     title="Sales overview — one month",
 *     description="income = invoice total_amount due for that month (invoice date). waybill_expenses = waybill costs + diesel for waybills in that month (transaction_date).",
 *     @OA\Property(property="month", type="string", example="2025-08", description="Calendar month key Y-m"),
 *     @OA\Property(property="income", type="number", format="float", example=440000),
 *     @OA\Property(property="waybill_expenses", type="number", format="float", example=245000)
 * )
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API endpoints for dashboard statistics"
 * )
 */
class DashboardController extends BaseController
{
    public function __construct(DashboardService $dashboardService, MessageService $messageService)
    {
        parent::__construct($dashboardService, $messageService);
    }

    /**
     * Get the combined dashboard payload.
     *
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Get the combined dashboard payload",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Single dashboard date filter",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Dashboard date range start",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Dashboard date range end",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Fallback year filter for chart-style requests",
     *         @OA\Schema(type="integer", example=2025)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard payload retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="date_from", type="string", format="date", example="2025-08-01"),
     *                 @OA\Property(property="date_to", type="string", format="date", example="2025-08-31"),
     *                 @OA\Property(
     *                     property="kpis",
     *                     type="object",
     *                     @OA\Property(property="shipping_line_count", type="integer", example=14, description="Total count of shipping_lines (not filtered by date)"),
     *                     @OA\Property(property="waybills_completed", type="integer", example=30000, description="Waybill details in range where booking.is_complete = true"),
     *                     @OA\Property(property="waybills_total", type="integer", example=32984, description="All waybill details in range"),
     *                     @OA\Property(property="waybills_remaining", type="integer", example=984, description="waybills_total minus waybills_completed"),
     *                     @OA\Property(property="sales", type="number", format="float", example=20000000, description="Sum of invoice total amount due in range (same logic as invoice PDF)"),
     *                     @OA\Property(property="waybill_expenses", type="number", format="float", example=2000000, description="total_expense + actual_truck_trip_expense_amount + diesel_expense.amount for waybills in range"),
     *                     @OA\Property(property="parts_expense", type="number", format="float", example=50000, description="Sum of quantity * amount_per_item in parts_expense in range"),
     *                     @OA\Property(property="diesel_expense", type="number", format="float", example=15000, description="Sum of diesel_expenses.amount for waybills in date range"),
     *                     @OA\Property(property="overdue_count", type="integer", example=3, description="Count of overdue items (billing due_date or SOA+waybill no_of_days before filter end)")
     *                 ),
     *                 @OA\Property(
     *                     property="sales_overview",
     *                     type="array",
     *                     description="One entry per month in the filter range (chronological)",
     *                     @OA\Items(ref="#/components/schemas/SalesOverviewMonthRow"),
     *                     example={
     *                         {"month": "2025-08", "income": 440000, "waybill_expenses": 245000}
     *                     }
     *                 ),
     *                 @OA\Property(property="overdue_count", type="integer", example=3, description="Count of overdue items (billing due_date or SOA+waybill no_of_days before filter end)"),
     *                 @OA\Property(
     *                     property="overdue_payments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="shipping_line_name", type="string", example="OOCL"),
     *                         @OA\Property(property="transaction_no", type="string", example="BS-2025-0001"),
     *                         @OA\Property(property="overdue_payment_date", type="string", format="date", example="2025-08-15"),
     *                         @OA\Property(property="overdue_payment_amount", type="number", format="float", example=1000000),
     *                         @OA\Property(property="billing_statement_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="statement_of_account_id", type="integer", example=10),
     *                         @OA\Property(property="due_date", type="string", format="date", example="2025-08-15", description="Alias of overdue_payment_date"),
     *                         @OA\Property(property="soa_number", type="string", example="SOA-0001")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="diesel_by_plate",
     *                     type="array",
     *                     description="Diesel totals grouped by waybill truck_plate_number (empty/null plates appear as Unassigned).",
     *                     @OA\Items(ref="#/components/schemas/DashboardDieselByPlateRow"),
     *                     example={
     *                         {"truck_plate_number": "NBB-1234", "amount": 15000},
     *                         {"truck_plate_number": "XYZ-9999", "amount": 20000}
     *                     }
     *                 ),
     *                 @OA\Property(property="receivables_by_entity", ref="#/components/schemas/DashboardReceivablesByEntity"),
     *                 @OA\Property(property="budget_category_totals", ref="#/components/schemas/DashboardBudgetCategoryTotals"),
     *                 @OA\Property(
     *                     property="daily_budget_chart",
     *                     type="array",
     *                     description="One entry per calendar day from date_from through date_to (inclusive), ordered by date",
     *                     @OA\Items(ref="#/components/schemas/DailyBudgetChartDayRow"),
     *                     example={
     *                         {"date": "2026-03-01", "income": 320, "expense": 280},
     *                         {"date": "2026-03-02", "income": 0, "expense": 150}
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $data = $this->service->getDashboard($this->getFilters($request));

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Get dashboard KPI cards.
     *
     * @OA\Get(
     *     path="/api/dashboard/kpis",
     *     summary="Get dashboard KPI cards",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="year", in="query", @OA\Schema(type="integer", example=2025)),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard KPIs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="date_from", type="string", format="date", example="2025-08-01"),
     *                 @OA\Property(property="date_to", type="string", format="date", example="2025-08-31"),
     *                 @OA\Property(
     *                     property="kpis",
     *                     type="object",
     *                     @OA\Property(property="shipping_line_count", type="integer", example=14),
     *                     @OA\Property(property="waybills_completed", type="integer", example=30000),
     *                     @OA\Property(property="waybills_total", type="integer", example=32984),
     *                     @OA\Property(property="waybills_remaining", type="integer", example=984),
     *                     @OA\Property(property="sales", type="number", format="float", example=20000000),
     *                     @OA\Property(property="waybill_expenses", type="number", format="float", example=2000000),
     *                     @OA\Property(property="parts_expense", type="number", format="float", example=50000),
     *                     @OA\Property(property="diesel_expense", type="number", format="float", example=15000),
     *                     @OA\Property(property="overdue_count", type="integer", example=3, description="Count of overdue items (billing or SOA+waybill due before filter end)")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getKpis(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilters($request);
            $dashboard = $this->service->getDashboard($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'date_from' => $dashboard['date_from'],
                    'date_to' => $dashboard['date_to'],
                    'kpis' => $dashboard['kpis'],
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Get the sales overview chart payload.
     *
     * @OA\Get(
     *     path="/api/dashboard/sales-overview",
     *     summary="Get dashboard sales overview chart data",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="year", in="query", @OA\Schema(type="integer", example=2025)),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard sales overview retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="sales_overview",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/SalesOverviewMonthRow"),
     *                     example={
     *                         {"month": "2025-08", "income": 440000, "waybill_expenses": 245000}
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getSalesOverview(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'sales_overview' => $this->service->getSalesOverview($this->getFilters($request)),
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Get the overdue payments table payload.
     *
     * @OA\Get(
     *     path="/api/dashboard/overdue-payments",
     *     summary="Get dashboard overdue payments table data",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="year", in="query", @OA\Schema(type="integer", example=2025)),
     *     @OA\Response(
     *         response=200,
     *         description="Overdue payments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="overdue_count", type="integer", example=3),
     *                 @OA\Property(
     *                     property="overdue_payments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="shipping_line_name", type="string", example="OOCL"),
     *                         @OA\Property(property="transaction_no", type="string", example="BS-2025-0001"),
     *                         @OA\Property(property="overdue_payment_date", type="string", format="date", example="2025-08-15"),
     *                         @OA\Property(property="overdue_payment_amount", type="number", format="float", example=1000000),
     *                         @OA\Property(property="billing_statement_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="statement_of_account_id", type="integer", example=10),
     *                         @OA\Property(property="due_date", type="string", format="date", example="2025-08-15"),
     *                         @OA\Property(property="soa_number", type="string", example="SOA-0001")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getOverduePayments(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilters($request);
            $overduePayments = $this->service->getOverduePayments($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'overdue_count' => count($overduePayments),
                    'overdue_payments' => $overduePayments,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Get enhanced dashboard statistics for widgets and legacy consumers.
     *
     * @OA\Get(
     *     path="/api/dashboard/stats",
     *     summary="Get enhanced dashboard statistics",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="year", in="query", @OA\Schema(type="integer", example=2025)),
     *     @OA\Response(
     *         response=200,
     *         description="Enhanced dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="shipping_line_count", type="integer", example=14),
     *                 @OA\Property(property="waybills_completed", type="integer", example=30000),
     *                 @OA\Property(property="waybills_total", type="integer", example=32984),
     *                 @OA\Property(property="waybills_remaining", type="integer", example=984),
     *                 @OA\Property(property="sales", type="number", format="float", example=20000000),
     *                 @OA\Property(property="waybill_expenses", type="number", format="float", example=2000000),
     *                 @OA\Property(property="parts_expense", type="number", format="float", example=50000),
     *                 @OA\Property(property="diesel_expense", type="number", format="float", example=15000),
     *                 @OA\Property(property="overdue_count", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->service->getEnhancedStats($this->getFilters($request));

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Extract supported dashboard filters from the request.
     */
    private function getFilters(Request $request): array
    {
        return $request->validate([
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
    }

    /**
     * Return the shared dashboard error payload.
     */
    private function errorResponse(): JsonResponse
    {
        return $this->messageService->responseError();
    }
}