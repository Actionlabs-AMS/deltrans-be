<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Services\MessageService;
use App\Http\Requests\HelperCAHistoryRequest;
use App\Services\HelperCAHistoryService;
use App\Http\Resources\HelperCAHistoryResource;
use Carbon\Carbon;

/**
 * @OA\Tag(
 * name="Helper Cash Advance History",
 * description="API Endpoints for managing helper cash advancement records"
 * )
 * * @OA\Schema(
 * schema="HelperCashAdvanceRecord",
 * title="Helper Cash Advance Record",
 * description="Details of a single cash advancement entry for a helper.",
 * required={"id", "amount", "transaction_date", "shift", "helper_id"},
 * @OA\Property(property="id", type="integer", format="int64", description="Unique ID of the cash advance record."),
 * @OA\Property(property="amount", type="number", format="float", example=500.00, description="The amount of cash advanced."),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2024-05-20", description="The date the cash advance was issued."),
 * @OA\Property(property="shift", type="string", example="Day", description="The work shift associated with the advance (e.g., Day, Night)."),
 * @OA\Property(property="helper_id", type="integer", format="int64", description="The ID of the helper who received the advance."),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the record was created."),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the record was last updated."),
 * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Timestamp for soft deletion.")
 * )
 */
class HelperCAHistoryController extends BaseController
{

    public function __construct(HelperCAHistoryService $cashAdvanceService, MessageService $messageService)
    {
        parent::__construct($cashAdvanceService, $messageService);
    }

    // /**
    //  * @OA\Get(
    //  * path="/api/helpers/get-cash-advance/{id}",
    //  * summary="Fetch cash advance details by helper ID",
    //  * tags={"Helper Cash Advance History"},
    //  * security={{"sanctum": {}}},
    //  * @OA\Parameter(
    //  * name="id",
    //  * in="path",
    //  * required=true,
    //  * description="Helper ID",
    //  * @OA\Schema(type="integer", example=1)
    //  * ),
    //  * @OA\Parameter(
    //  * name="search",
    //  * in="query",
    //  * required=false,
    //  * description="Filter by transaction date (YYYY-MM-DD) or shift",
    //  * @OA\Schema(type="string")
    //  * ),
    //  * @OA\Parameter(
    //  * name="per_page",
    //  * in="query",
    //  * required=false,
    //  * @OA\Schema(type="integer", example=10)
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Successful operation",
    //  * @OA\JsonContent(
    //  * type="array",
    //  * @OA\Items(ref="#/components/schemas/HelperCashAdvanceRecord")
    //  * )
    //  * ),               
    //  * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function getHelperCashAdvances(Request $request, $helperId)
    // {
    //     $perPage = $request->query('per_page', 10);
    //     $search = $request->query('search');

    //     $data = $this->service->getHelperHistory($helperId, $perPage);

    //     return HelperCAHistoryResource::collection($data)
    //         ->additional([
    //             'status_code' => 200,
    //             'message' => 'Cash advance history fetched successfully.'
    //         ]);
    // }

    /**
     * @OA\Get(
     * path="/api/helpers/get-cash-advance/{id}",
     * summary="Fetch cash advance details by helper ID",
     * tags={"Helper Cash Advance History"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Helper ID",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * required=false,
     * description="Search by amount or shift",
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="filter_type",
     * in="query",
     * required=false,
     * description="Type of date filter: weekly or monthly",
     * @OA\Schema(type="string", enum={"weekly", "monthly"}, default="weekly")
     * ),
     * @OA\Parameter(
     * name="reference_date",
     * in="query",
     * required=false,
     * description="The base date for filtering (YYYY-MM-DD)",
     * @OA\Schema(type="string", format="date")
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * required=false,
     * @OA\Schema(type="integer", example=10)
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/HelperCashAdvanceRecord")),
     * @OA\Property(property="status_code", type="integer", example=200),
     * @OA\Property(property="message", type="string", example="Cash advance history fetched successfully.")
     * )
     * ),               
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function getHelperCashAdvances(Request $request, $helperId)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $searchTerm = $request->query('search');
            $filterType = $request->query('filter_type', 'weekly');
            $refDate = $request->query('reference_date') ? Carbon::parse($request->query('reference_date')) : now();

            // Calculate Date Range (Standardizing logic across the app)
            if ($filterType === 'weekly') {
                $dateFrom = $refDate->copy()->startOfWeek()->toDateString();
                $dateTo = $refDate->copy()->endOfWeek()->toDateString();
            } else {
                $dateFrom = $refDate->copy()->startOfMonth()->toDateString();
                $dateTo = $refDate->copy()->endOfMonth()->toDateString();
            }

            // Passing standardized params to the service
            $data = $this->service->getHelperHistory(
                $helperId,
                $perPage,
                $searchTerm,
                $dateFrom,
                $dateTo
            );

            return HelperCAHistoryResource::collection($data)
                ->additional([
                    'status_code' => 200,
                    'message' => 'Cash advance history fetched successfully.'
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }

    }
}
