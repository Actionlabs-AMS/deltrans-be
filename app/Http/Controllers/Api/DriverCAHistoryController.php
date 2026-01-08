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
     * description="Filter by transaction date (YYYY-MM-DD) or shift",
     * @OA\Schema(type="string")
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

        $data = $this->service->getDriverHistory($driverId, $perPage);

        return DriverCAHistoryResource::collection($data)
            ->additional([
                'status_code' => 200,
                'message' => 'Cash advance history fetched successfully.'
            ]);
    }
}
