<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Requests\ReportsRequest;
use App\Services\ReportsService;
use App\Services\MessageService;
use App\Http\Resources\ReportsResource;
use App\Http\Resources\IssuedBudgetResource;
use App\Http\Resources\TruckTripExpenseResource;
use App\Http\Resources\PartsExpenseResource;
use App\Http\Resources\CashAdvanceResource;

/**
 * @OA\Tag(
 * name="Reports",
 * description="Endpoints for operational summaries and detailed expense drill-downs"
 * )
 *
 * // --- DATA ROW SCHEMAS ---
 *
 * @OA\Schema(
 * schema="ReportSummaryRow",
 * description="Daily summary row for accounting and operational metrics",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="date", type="string", format="date", example="2026-03-03"),
 * @OA\Property(property="accounting_day", type="number", format="float"),
 * @OA\Property(property="accounting_night", type="number", format="float"),
 * @OA\Property(property="truck_expense", type="number", format="float"),
 * @OA\Property(property="parts_expense", type="number", format="float"),
 * @OA\Property(property="bale_day", type="integer"),
 * @OA\Property(property="bale_night", type="integer")
 * )
 *
 * @OA\Schema(
 * schema="IssuedBudgetRow",
 * description="Detailed record of a single budget issuance used within the response array",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2026-03-03"),
 * @OA\Property(property="shift", type="string", example="Day"),
 * @OA\Property(property="amount", type="number", format="float", example=5000.00),
 * @OA\Property(property="source", type="string", example="Petty Cash")
 * )
 *
 * @OA\Schema(
 * schema="TruckExpenseRow",
 * description="Detailed record of a single truck trip expense",
 * @OA\Property(property="id", type="integer", example=5),
 * @OA\Property(property="shift", type="string", example="Day"),
 * @OA\Property(property="helper_id", type="integer", example=5, nullable=true),
 * @OA\Property(property="helper_name", type="string", example="Manuel Rivera", nullable=true),
 * @OA\Property(property="cash_on_hand", type="number", format="float", example=150.50),
 * @OA\Property(property="issued_cash_amount", type="number", format="float", example=2000.00),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2026-03-03"),
 * )
 * 
 * @OA\Schema(
 * schema="PartsExpenseRow",
 * description="Detailed record of a specific part purchase or maintenance expense",
 * @OA\Property(property="id", type="integer", example=10),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2026-03-03"),
 * @OA\Property(property="shift", type="string", example="Day"),
 * @OA\Property(property="plate_number", type="string", example="NAF 6567"),
 * @OA\Property(property="receipt_number", type="string", example="OR-12345"),
 * @OA\Property(property="article", type="string", example="Tire Sealant"),
 * @OA\Property(property="quantity", type="integer", example=2),
 * @OA\Property(property="amount_per_item", type="number", format="float", example=450.00),
 * @OA\Property(property="total_amount", type="number", format="float", example=900.00)
 * )
 * 
 * @OA\Schema(
 * schema="CashAdvanceRow",
 * description="Unified record for either a driver or helper cash advancement",
 * @OA\Property(property="type", type="integer", example=1, description="1 for Driver, 2 for Helper"),
 * @OA\Property(property="id", type="integer", example=101),
 * @OA\Property(property="amount", type="number", format="float", example=1500.00),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2026-03-03"),
 * @OA\Property(property="transaction_date_formatted", type="string", example="March 03, 2026"),
 * @OA\Property(property="shift", type="string", example="Night"),
 * @OA\Property(property="driver_id", type="integer", nullable=true, example=5),
 * @OA\Property(property="driver_name", type="string", nullable=true, example="Manuel Rivera"),
 * @OA\Property(property="helper_id", type="integer", nullable=true, example=null),
 * @OA\Property(property="helper_name", type="string", nullable=true, example=null),
 * @OA\Property(property="created_at", type="string", example="2026-03-03 23:53:44"),
 * @OA\Property(property="updated_at", type="string", example="2026-03-03 23:53:44"),
 * @OA\Property(property="deleted_at", type="string", example=null, nullable=true)
 * )
 * 
 * // --- RESPONSE SCHEMAS ---
 *
 * @OA\Schema(
 * schema="ReportSummaryResponse",
 * @OA\Property(property="status", type="boolean", example=true),
 * @OA\Property(property="message", type="string", example="Daily report summary retrieved successfully."),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReportSummaryRow")),
 * @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 *
 * @OA\Schema(
 * schema="IssuedBudgetResponse",
 * description="Full response object for the Issued Budget drill-down",
 * @OA\Property(property="status", type="boolean", example=true),
 * @OA\Property(property="message", type="string", example="Detailed issued budget retrieved successfully."),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/IssuedBudgetRow")),
 * @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 * 
 * @OA\Schema(
 * schema="TruckExpenseResponse",
 * description="Full response object for the Truck Expenses drill-down",
 * @OA\Property(property="status", type="boolean", example=true),
 * @OA\Property(property="message", type="string", example="Detailed truck expenses retrieved successfully."),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TruckExpenseRow")),
 * @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 * 
 * @OA\Schema(
 * schema="PartsExpenseResponse",
 * description="Full response object for the Parts Expense drill-down",
 * @OA\Property(property="status", type="boolean", example=true),
 * @OA\Property(property="message", type="string", example="Detailed parts expenses retrieved successfully."),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PartsExpenseRow")),
 * @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 * 
 * @OA\Schema(
 * schema="CashAdvanceResponse",
 * description="Full response object for the Cash Advances drill-down",
 * @OA\Property(property="status", type="boolean", example=true),
 * @OA\Property(property="message", type="string", example="Detailed cash advances retrieved successfully."),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CashAdvanceRow")),
 * @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 *
 * @OA\Schema(
 * schema="PaginationMeta",
 * @OA\Property(property="current_page", type="integer", example=1),
 * @OA\Property(property="from", type="integer", example=1),
 * @OA\Property(property="last_page", type="integer", example=2),
 * @OA\Property(property="path", type="string", example="http://localhost/api/reports/summary"),
 * @OA\Property(property="per_page", type="integer", example=10),
 * @OA\Property(property="to", type="integer", example=10),
 * @OA\Property(property="total", type="integer", example=25)
 * )
 */

class ReportsController extends BaseController
{
    public function __construct(ReportsService $reportService, MessageService $messageService)
    {
        parent::__construct($reportService, $messageService);
    }


    /**
     * @OA\Get(
     * path="/api/reports/summary",
     * operationId="getReportSummary",
     * tags={"Reports"},
     * summary="Get transport summary report",
     * description="Returns a paginated list of daily accounting and operational summaries with optional filtering by date range and date-specific search.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="Filter start date (YYYY-MM-DD)",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2026-02-01")
     * ),
     * @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="Filter end date (YYYY-MM-DD)",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2026-02-28")
     * ),
     * @OA\Parameter(
     * name="filter_type",
     * in="query",
     * description="Filter type (weekly/monthly)",
     * required=false,
     * @OA\Schema(type="string", enum={"weekly", "monthly"}, default="weekly")
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Search for a specific date (e.g., '2026-03' or '05')",
     * required=false,
     * @OA\Schema(type="string", example="2026-03-04")
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(ref="#/components/schemas/ReportSummaryResponse")
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=401, description="Unauthenticated"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        try {
            $reportData = $this->service->getSummaryReport(
                request('start_date'), 
                request('end_date'),
                request('filter_type', 'weekly'),
                request('search')
            );

            // 2. Extract the underlying paginator to build custom meta
            $paginator = $reportData->resource;

            // 3. Construct the response explicitly
            return response()->json([
                'status' => true,
                'message' => 'Daily report summary retrieved successfully.',
                // data is now a sibling to meta
                'data' => $reportData, 
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from'         => $paginator->firstItem(),
                    'last_page'    => $paginator->lastPage(),
                    'path'         => $paginator->path(),
                    'per_page'     => $paginator->perPage(),
                    'to'           => $paginator->lastItem(),
                    'total'        => $paginator->total(),
                    'all'          => $paginator->total(),
                    'trashed'      => 0,

                ],
            ], 200);
            

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/reports/issued-budget",
     * operationId="getIssuedBudget",
     * tags={"Reports"},
     * summary="Get detailed issued budget records",
     * description="Returns searchable budget records for a specific date selected from the summary report.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="transaction_date",
     * in="query",
     * description="The specific date (YYYY-MM-DD)",
     * required=true,
     * @OA\Schema(type="string", format="date", example="2026-02-28")
     * ),
     * @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=10)),
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(ref="#/components/schemas/IssuedBudgetResponse")
     * ),
     * @OA\Response(response=400, description="Bad Request"),
     * @OA\Response(response=401, description="Unauthenticated"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function getIssuedBudget()
    {
        try {
            $perPage = request('per_page', 10);
            $transactionDate = request('transaction_date');
            $search = request('search'); 

            if (!$transactionDate) {
                throw new \Exception('Transaction date is required to view detailed budget.');
            }

            $formattedDate = \Carbon\Carbon::parse($transactionDate)->toDateString();

            // Pass perPage, date, and search to the service layer
            $issuedBudget = $this->service->get_issued_budget(
                $perPage,
                $formattedDate,
                $search
            );

            return IssuedBudgetResource::collection($issuedBudget);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    /**
     * @OA\Get(
     * path="/api/reports/truck-trip-expense",
     * operationId="getTruckExpense",
     * tags={"Reports"},
     * summary="Get detailed truck expense records",
     * description="Returns searchable truck expense records for a specific transaction date.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="transaction_date",
     * in="query",
     * description="The specific date to filter expenses (YYYY-MM-DD)",
     * required=true,
     * @OA\Schema(type="string", format="date", example="2026-03-03")
     * ),
     * @OA\Parameter(
     * name="search", 
     * in="query", 
     * description="Search by helper name or other details",
     * required=false, 
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="per_page", 
     * in="query", 
     * description="Number of items per page",
     * required=false, 
     * @OA\Schema(type="integer", default=10)
     * ),
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(ref="#/components/schemas/TruckExpenseResponse")
     * ),
     * @OA\Response(
     * response=400, 
     * description="Bad Request - Transaction date is required or invalid"
     * ),
     * @OA\Response(
     * response=401, 
     * description="Unauthenticated"
     * ),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */

    public function getTruckExpense()
    {
        try {
            $perPage = request('per_page', 10);
            $transactionDate = request('transaction_date');
            $search = request('search'); 

            if (!$transactionDate) {
                throw new \Exception('Transaction date is required to view detailed budget.');
            }

            $formattedDate = \Carbon\Carbon::parse($transactionDate)->toDateString();

            // Pass perPage, date, and search to the service layer
            $truckExpense = $this->service->get_truck_expense(
                $perPage,
                $formattedDate,
                $search
            );

            return TruckTripExpenseResource::collection($truckExpense);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     * path="/api/reports/parts-expense",
     * operationId="getPartsExpense",
     * tags={"Reports"},
     * summary="Get detailed parts expense records",
     * description="Returns searchable records of truck parts and maintenance expenses for a specific date.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="transaction_date",
     * in="query",
     * description="The specific date to filter parts expenses (YYYY-MM-DD)",
     * required=true,
     * @OA\Schema(type="string", format="date", example="2026-03-03")
     * ),
     * @OA\Parameter(
     * name="search", 
     * in="query", 
     * description="Search by plate number, receipt number, or article",
     * required=false, 
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="per_page", 
     * in="query", 
     * description="Number of items per page",
     * required=false, 
     * @OA\Schema(type="integer", default=10)
     * ),
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(ref="#/components/schemas/PartsExpenseResponse")
     * ),
     * @OA\Response(
     * response=400, 
     * description="Bad Request - Transaction date is required or invalid"
     * ),
     * @OA\Response(
     * response=401, 
     * description="Unauthenticated"
     * ),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */

    public function getPartsExpense()
    {
        try {
            $perPage = request('per_page', 10);
            $transactionDate = request('transaction_date');
            $search = request('search'); 

            if (!$transactionDate) {
                throw new \Exception('Transaction date is required to view detailed budget.');
            }

            $formattedDate = \Carbon\Carbon::parse($transactionDate)->toDateString();

            // Pass perPage, date, and search to the service layer
            $partsExpense = $this->service->get_parts_expense(
                $perPage,
                $formattedDate,
                $search
            );

            return PartsExpenseResource::collection($partsExpense);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     * path="/api/reports/cash-advances",
     * operationId="getReportCashAdvances",
     * tags={"Reports"},
     * summary="Get detailed cash advance records",
     * description="Returns a unified list of cash advances for both drivers and helpers for a specific transaction date.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="transaction_date",
     * in="query",
     * description="The specific date to filter cash advances (YYYY-MM-DD)",
     * required=true,
     * @OA\Schema(type="string", format="date", example="2026-03-03")
     * ),
     * @OA\Parameter(
     * name="search", 
     * in="query", 
     * description="Search by name or shift",
     * required=false, 
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="per_page", 
     * in="query", 
     * description="Number of items per page",
     * required=false, 
     * @OA\Schema(type="integer", default=10)
     * ),
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(ref="#/components/schemas/CashAdvanceResponse")
     * ),
     * @OA\Response(
     * response=400, 
     * description="Bad Request - Transaction date is required or invalid"
     * ),
     * @OA\Response(
     * response=401, 
     * description="Unauthenticated"
     * ),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */


    public function getReportCashAdvances()
    {
        try {
            $perPage = request('per_page', 10);
            $transactionDate = request('transaction_date');
            $search = request('search');

            if (!$transactionDate) {
                throw new \Exception('Transaction date is required to view cash advances.');
            }

            $formattedDate = \Carbon\Carbon::parse($transactionDate)->toDateString();

            // Pass params to service
            $advances = $this->service->get_cash_advances(
                $perPage,
                $formattedDate,
                $search
            );

            return CashAdvanceResource::collection($advances);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


}
