<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
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
     *                     @OA\Property(property="shipping_lines", type="integer", example=14),
     *                     @OA\Property(property="waybills", type="integer", example=30000),
     *                     @OA\Property(property="waybills_total", type="integer", example=32984),
     *                     @OA\Property(property="sales", type="number", format="float", example=20000000),
     *                     @OA\Property(property="expenses", type="number", format="float", example=2000000)
     *                 ),
     *                 @OA\Property(
     *                     property="sales_overview",
     *                     type="object",
     *                     @OA\Property(
     *                         property="months",
     *                         type="array",
     *                         @OA\Items(type="string", example="2025-08")
     *                     ),
     *                     @OA\Property(
     *                         property="income",
     *                         type="array",
     *                         @OA\Items(type="number", format="float", example=440000)
     *                     ),
     *                     @OA\Property(
     *                         property="expenses",
     *                         type="array",
     *                         @OA\Items(type="number", format="float", example=245000)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="overdue_payments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="shipping_line_name", type="string", example="OOCL"),
     *                         @OA\Property(property="transaction_no", type="string", example="BS-2025-0001"),
     *                         @OA\Property(property="overdue_payment_date", type="string", format="date", example="2025-08-15"),
     *                         @OA\Property(property="overdue_payment_amount", type="number", format="float", example=1000000),
     *                         @OA\Property(property="billing_statement_id", type="integer", example=1),
     *                         @OA\Property(property="statement_of_account_id", type="integer", example=10)
     *                     )
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
     *                     @OA\Property(property="shipping_lines", type="integer", example=14),
     *                     @OA\Property(property="waybills", type="integer", example=30000),
     *                     @OA\Property(property="waybills_total", type="integer", example=32984),
     *                     @OA\Property(property="sales", type="number", format="float", example=20000000),
     *                     @OA\Property(property="expenses", type="number", format="float", example=2000000)
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
     *                     type="object",
     *                     @OA\Property(
     *                         property="months",
     *                         type="array",
     *                         @OA\Items(type="string", example="2025-08")
     *                     ),
     *                     @OA\Property(
     *                         property="income",
     *                         type="array",
     *                         @OA\Items(type="number", format="float", example=440000)
     *                     ),
     *                     @OA\Property(
     *                         property="expenses",
     *                         type="array",
     *                         @OA\Items(type="number", format="float", example=245000)
     *                     )
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
     *     @OA\Response(
     *         response=200,
     *         description="Overdue payments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="overdue_payments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="shipping_line_name", type="string", example="OOCL"),
     *                         @OA\Property(property="transaction_no", type="string", example="BS-2025-0001"),
     *                         @OA\Property(property="overdue_payment_date", type="string", format="date", example="2025-08-15"),
     *                         @OA\Property(property="overdue_payment_amount", type="number", format="float", example=1000000),
     *                         @OA\Property(property="billing_statement_id", type="integer", example=1),
     *                         @OA\Property(property="statement_of_account_id", type="integer", example=10)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getOverduePayments(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'overdue_payments' => $this->service->getOverduePayments(),
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
     *                 @OA\Property(property="total_users", type="integer", example=150),
     *                 @OA\Property(property="total_media", type="integer", example=234),
     *                 @OA\Property(property="total_categories", type="integer", example=45),
     *                 @OA\Property(property="total_tags", type="integer", example=128),
     *                 @OA\Property(property="shipping_lines", type="integer", example=14),
     *                 @OA\Property(property="waybills", type="integer", example=30000),
     *                 @OA\Property(property="waybills_total", type="integer", example=32984),
     *                 @OA\Property(property="sales", type="number", format="float", example=20000000),
     *                 @OA\Property(property="expenses", type="number", format="float", example=2000000)
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