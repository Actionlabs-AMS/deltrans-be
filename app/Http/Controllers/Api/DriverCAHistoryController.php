<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Services\MessageService;
use App\Http\Requests\DriverCAHistoryRequest;
use App\Services\DriverCAHistoryService;
use App\Http\Resources\DriverCAHistoryResource;

/**
 * @OA\Tag(
 * name="Driver Cash Advance History",
 * description="API Endpoints for managing driver cash advancement records"
 * )
 * * @OA\Schema(
 * schema="DriverCashAdvanceRecord",
 * title="Driver Cash Advance Record",
 * description="Details of a single cash advancement entry for a driver.",
 * required={"id", "amount", "transaction_date", "shift", "driver_id"},
 * @OA\Property(property="id", type="integer", format="int64", description="Unique ID of the cash advance record."),
 * @OA\Property(property="amount", type="number", format="float", example=500.00, description="The amount of cash advanced."),
 * @OA\Property(property="transaction_date", type="string", format="date", example="2024-05-20", description="The date the cash advance was issued."),
 * @OA\Property(property="shift", type="string", example="Day", description="The work shift associated with the advance (e.g., Day, Night)."),
 * @OA\Property(property="driver_id", type="integer", format="int64", description="The ID of the driver who received the advance."),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the record was created."),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the record was last updated."),
 * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Timestamp for soft deletion.")
 * )
 */
class DriverCAHistoryController extends BaseController
{

    public function __construct(DriverCAHistoryService $cashAdvanceService, MessageService $messageService)
    {
        parent::__construct($cashAdvanceService, $messageService);
    }

    // /**
    //  * @OA\Get(
    //  * path="/api/drivers/get-cash-advance/{id}",
    //  * summary="Fetch cash advance details by driver ID",
    //  * tags={"Driver Cash Advance History"},
    //  * security={{"sanctum": {}}},
    //  * @OA\Parameter(
    //  * name="id",
    //  * in="path",
    //  * required=true,
    //  * description="Driver ID",
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
    //  * @OA\Items(ref="#/components/schemas/DriverCashAdvanceRecord")
    //  * )
    //  * ),               
    //  * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function getCashAdvances(Request $request, $driverId)
    // {
    //     $perPage = $request->query('per_page', 10);
    //     $search = $request->query('search');

    //     $data = $this->service->getDriverHistory($driverId, $perPage);

    //     return DriverCAHistoryResource::collection($data)
    //         ->additional([
    //             'status_code' => 200,
    //             'message' => 'Cash advance history fetched successfully.'
    //         ]);
    // }

    /**
     * @OA\Get(
     * path="/api/drivers/get-cash-advance/{id}",
     * summary="Fetch cash advance details by driver ID",
     * tags={"Driver Cash Advance History"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Driver ID",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * required=false,
     * description="Filter by shift or keyword",
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="filter_type",
     * in="query",
     * description="Filter scope: weekly or monthly",
     * required=false,
     * @OA\Schema(type="string", enum={"weekly", "monthly"}, default="weekly")
     * ),
     * @OA\Parameter(
     * name="reference_date",
     * in="query",
     * description="The anchor date (YYYY-MM-DD) for the filter range",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2026-01-26")
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
     * type="array",
     * @OA\Items(ref="#/components/schemas/DriverCashAdvanceRecord")
     * )
     * ), 
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function getCashAdvances(Request $request, $driverId)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');
        $filterType = $request->query('filter_type', 'weekly');
        $referenceDate = $request->query('reference_date');

        $dateFrom = null;
        $dateTo = null;

        // Calculate Date Range using Carbon
        if ($referenceDate) {
            $date = \Carbon\Carbon::parse($referenceDate);

            if ($filterType === 'monthly') {
                $dateFrom = $date->copy()->startOfMonth()->toDateString();
                $dateTo = $date->copy()->endOfMonth()->toDateString();
            } else {
                // Weekly: Start of week (Monday) to End of week (Sunday)
                $dateFrom = $date->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
                $dateTo = $date->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
            }
        }

        // Pass parameters to the service
        $data = $this->service->getDriverHistory(
            $driverId, 
            $perPage, 
            $search, 
            $dateFrom, 
            $dateTo
        );

        return DriverCAHistoryResource::collection($data)
            ->additional([
                'status_code' => 200,
                'message' => 'Cash advance history fetched successfully.'
            ]);
    }
}
