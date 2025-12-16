<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Requests\TruckMaintenanceRequest;
use App\Services\TruckMaintenanceService;
use App\Services\MessageService;

// --- SCHEMA DEFINITION EMBEDDED HERE ---
/**
 * @OA\Tag(
 * name="Trucks Maintenance",
 * description="API Endpoints for managing trucks maintenance history"
 * )
 * @OA\Schema(
 * schema="TruckMaintenanceRecord",
 * title="Truck Maintenance Record",
 * description="Details of a single maintenance event for a truck.",
 * required={"id", "receipt_number", "article", "quantity", "price", "maintenance_date", "fleet_truck_plate_number"},
 * @OA\Property(property="id", type="integer", format="int64", description="Unique ID of the maintenance record."),
 * @OA\Property(property="receipt_number", type="string", description="Unique receipt or service number."),
 * @OA\Property(property="article", type="string", description="Description of the part or service."),
 * @OA\Property(property="quantity", type="integer", description="Quantity of the article/part used."),
 * @OA\Property(property="price", type="number", format="float", description="Unit price of the article."),
 * @OA\Property(property="maintenance_date", type="string", format="date", description="The date the maintenance was performed."),
 * @OA\Property(property="fleet_truck_plate_number", type="string", description="The plate number of the truck."),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the record was created."),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the record was last updated.")
 * )
 */


class TruckMaintenanceController extends BaseController
{
    public function __construct(TruckMaintenanceService $truckService, MessageService $messageService)
    {
        parent::__construct($truckService, $messageService);
    }

    /**
     * @OA\Get(
     * path="/api/trucks/{truckId}/maintenance-history",
     * operationId="getTruckMaintenanceHistory",
     * tags={"Truck Maintenance"},
     * summary="Get paginated maintenance records for a specific truck",
     * description="Returns a paginated list of maintenance records for the given truck ID, with searching capabilities on receipt number and article.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="truckId",
     * in="path",
     * description="ID of the truck to retrieve maintenance history for.",
     * required=true,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Page number for pagination",
     * required=false,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Items per page for pagination",
     * required=false,
     * @OA\Schema(type="integer", example=10)
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Search term to filter maintenance records (by receipt_number or article).",
     * required=false,
     * @OA\Schema(type="string", example="Oil Filter")
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TruckMaintenanceRecord")),
     * @OA\Property(property="meta", type="object"),
     * @OA\Property(property="links", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Truck not found"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function getMaintenanceHistory(Request $request, int $truckId)
    {
        // 1. Check if the truck exists
        if (!Truck::find($truckId)) {
            return response()->json([
                'message' => 'Truck not found.'
            ], 404);
        }

        // 2. Extract pagination and search parameters
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        
        // 3. Fetch data from the service layer
        // The service layer must handle the filtering by $truckId AND the $search query.
        $maintenanceRecords = $this->service->listByTruckId(
            $truckId, 
            $perPage, 
            $search
        );

        // 4. Return the paginated data using a Resource Collection
        // Note: ->collection() for paginated data handles the meta/links automatically
        return TruckMaintenanceResource::collection($maintenanceRecords);
    }


}
