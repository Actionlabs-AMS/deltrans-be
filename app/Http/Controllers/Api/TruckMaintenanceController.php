<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Requests\TruckMaintenanceRequest;
use App\Services\TruckMaintenanceService;
use App\Services\MessageService;
use App\Models\FleetTruck;
use App\Models\TruckMaintenance;
use App\Http\Resources\TruckMaintenanceResource;

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
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the record was last updated."),
 * @OA\Property(property="truck_id", type="integer", format="int64", description="Unique truck ID of fleet_plate_number.")
 * )
 */


class TruckMaintenanceController extends BaseController
{
    public function __construct(TruckMaintenanceService $truckService, MessageService $messageService)
    {
        parent::__construct($truckService, $messageService);
    }

    // /**
    //  * @OA\Get(
    //  * path="/api/trucks/{truckId}/maintenance-history",
    //  * operationId="getTruckMaintenanceHistory",
    //  * tags={"Truck Maintenance"},
    //  * summary="Get paginated maintenance records for a specific truck",
    //  * description="Returns a paginated list of maintenance records for the given truck ID, with searching and date filtering capabilities.",
    //  * security={{"sanctum": {}}},
    //  * @OA\Parameter(
    //  * name="truckId",
    //  * in="path",
    //  * description="ID of the truck to retrieve maintenance history for.",
    //  * required=true,
    //  * @OA\Schema(type="integer", example=1)
    //  * ),
    //  * @OA\Parameter(
    //  * name="page",
    //  * in="query",
    //  * description="Page number for pagination",
    //  * required=false,
    //  * @OA\Schema(type="integer", example=1)
    //  * ),
    //  * @OA\Parameter(
    //  * name="per_page",
    //  * in="query",
    //  * description="Items per page for pagination",
    //  * required=false,
    //  * @OA\Schema(type="integer", example=10)
    //  * ),
    //  * @OA\Parameter(
    //  * name="search",
    //  * in="query",
    //  * description="Search term to filter maintenance records (by receipt_number or article).",
    //  * required=false,
    //  * @OA\Schema(type="string", example="Oil Filter")
    //  * ),
    //  * @OA\Parameter(
    //  * name="date_from",
    //  * in="query",
    //  * description="Start date filter for the record's creation date (created_at).",
    //  * required=false,
    //  * @OA\Schema(type="string", format="date", example="2025-01-01")
    //  * ),
    //  * @OA\Parameter(
    //  * name="date_to",
    //  * in="query",
    //  * description="End date filter for the record's creation date (created_at).",
    //  * required=false,
    //  * @OA\Schema(type="string", format="date", example="2025-12-31")
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Successful operation",
    //  * @OA\JsonContent(
    //  * type="object",
    //  * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TruckMaintenanceRecord")),
    //  * @OA\Property(property="meta", type="object"),
    //  * @OA\Property(property="links", type="object")
    //  * )
    //  * ),
    //  * @OA\Response(response=404, description="Truck not found"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function getMaintenanceHistory(Request $request, int $truckId)
    // {
    //     // Temporary debug line
    //     \Log::info('Attempting to find Truck ID: ' . $truckId);
    //     // 1. Check if the truck exists
    //     if (!FleetTruck::find($truckId)) {
    //         return response()->json([
    //             'message' => 'Truck not found in fleet truck.'
    //         ], 404);
    //     }

    //     // 1. Retrieve the Plate Number using the provided Truck ID
    //     $truck = FleetTruck::find($truckId);

    //     if (!$truck) {
    //         // This condition is highly likely to be the source of your error.
    //         throw new \Exception("Truck with ID {$truckId} not found in the fleet.", 404);
    //     }
        
    //     $plateNumber = $truck->plate_number;

    //     // 2. Extract pagination, search, and date filters
    //     $perPage = $request->get('per_page', 10);
    //     $search = $request->get('search');
        
    //     // START: NEW FILTER EXTRACTION
    //     $dateFrom = $request->get('date_from');
    //     $dateTo = $request->get('date_to');
    //     // END: NEW FILTER EXTRACTION

    //     // 3. Fetch data from the service layer, passing all filters
    //     $maintenanceRecords = $this->service->listByTruckId(
    //         $truckId, 
    //         $perPage, 
    //         $search,
    //         $dateFrom, // New parameter
    //         $dateTo    // New parameter
    //     );

    //     // 4. Return the paginated data using a Resource Collection
    //     return TruckMaintenanceResource::collection($maintenanceRecords);
    // }

    /**
     * @OA\Get(
     * path="/api/trucks/{truckId}/maintenance-history",
     * operationId="getTruckMaintenanceHistory",
     * tags={"Truck Maintenance"},
     * summary="Get paginated maintenance records for a specific truck",
     * description="Returns a paginated list of maintenance records for the given truck ID. Supports keyword searching and smart date filtering (weekly/monthly) based on a reference date.",
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
     * @OA\Parameter(
     * name="filter_type",
     * in="query",
     * description="The scope of the date filter. Used in conjunction with reference_date.",
     * required=false,
     * @OA\Schema(type="string", enum={"weekly", "monthly"}, default="weekly")
     * ),
     * @OA\Parameter(
     * name="reference_date",
     * in="query",
     * description="The anchor date used to calculate the week (Mon-Sun) or month range.",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2026-01-26")
     * ),
     * @OA\Parameter(
     * name="date_from",
     * in="query",
     * description="Manual start date filter (YYYY-MM-DD). Overridden if reference_date is provided.",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2025-01-01")
     * ),
     * @OA\Parameter(
     * name="date_to",
     * in="query",
     * description="Manual end date filter (YYYY-MM-DD). Overridden if reference_date is provided.",
     * required=false,
     * @OA\Schema(type="string", format="date", example="2025-12-31")
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
        $truck = FleetTruck::find($truckId);
        if (!$truck) {
            return response()->json(['message' => 'Truck not found.'], 404);
        }

        // 2. Extract standard parameters
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        
        // 3. 🌟 NEW: Extract Filter Type and Reference Date
        $filterType = $request->get('filter_type', 'weekly'); // default to weekly
        $referenceDate = $request->get('reference_date'); // e.g., "2026-01-26"

        // Initialize date boundaries
        $dateFrom = null;
        $dateTo = null;

        if ($referenceDate) {
            $date = \Carbon\Carbon::parse($referenceDate);

            if ($filterType === 'weekly') {
                // 🌟 Calculate Monday to Sunday
                $dateFrom = $date->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
                $dateTo = $date->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
            } elseif ($filterType === 'monthly') {
                // 🌟 Calculate Full Month
                $dateFrom = $date->copy()->startOfMonth()->toDateString();
                $dateTo = $date->copy()->endOfMonth()->toDateString();
            }
        } else {
            // Fallback to your original manual filters if reference_date isn't provided
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
        }

        // 4. Fetch data from service layer
        // Note: Ensure listByTruckId is updated to filter by 'maintenance_date' 
        // instead of 'created_at' to match your table data.
        $maintenanceRecords = $this->service->listByTruckId(
            $truckId, 
            $perPage, 
            $search,
            $dateFrom, 
            $dateTo
        );

        return TruckMaintenanceResource::collection($maintenanceRecords);
    }

    /**
     * @OA\Post(
     * tags={"Truck Maintenance"},
     * path="/api/trucks/truck-maintenance",
     * operationId="storeTruckMaintenance",
     * summary="Create a new truck maintenance record",
     * description="Saves a new maintenance entry for a specific truck using its plate number.",
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"receipt_number", "article", "quantity", "price", "maintenance_date", "fleet_truck_plate_number"},
     * @OA\Property(property="receipt_number", type="string", example="REC-12345"),
     * @OA\Property(property="article", type="string", example="Engine Oil Change"),
     * @OA\Property(property="quantity", type="integer", example=1),
     * @OA\Property(property="price", type="number", format="float", example=1500.50),
     * @OA\Property(property="maintenance_date", type="string", format="date", example="2025-12-16"),
     * @OA\Property(property="fleet_truck_plate_number", type="string", example="YZA-8901")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Maintenance record created successfully",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="receipt_number", type="string", example="REC-12345"),
     * @OA\Property(property="article", type="string", example="Engine Oil Change"),
     * @OA\Property(property="quantity", type="integer", example=1),
     * @OA\Property(property="price", type="string", example="1500.50"),
     * @OA\Property(property="maintenance_date", type="string", example="2025-12-16"),
     * @OA\Property(property="fleet_truck_plate_number", type="string", example="YZA-8901"),
     * @OA\Property(property="created_at", type="string", example="2025-12-16T14:05:22.000000Z"),
     * @OA\Property(property="updated_at", type="string", example="2025-12-16T14:05:22.000000Z")
     * )
     * ),
     * @OA\Response(response=422, description="Validation Error"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     * )
     * )
     */
    public function store(TruckMaintenanceRequest $request)
    {
        try {
            $data = $request->all();
            $truck = $this->service->store($data);
            return response($truck, 201);
        } catch (\Exception $e) {
            //return $this->messageService->responseError();
            // This catch block is primarily for service-level or database errors (not validation errors)
            return response()->json([
                'status_code' => 500,
                'message' => 'An unexpected server error occurred.',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     * tags={"Truck Maintenance"},
     * path="/api/trucks/{truckId}/maintenance-history/{id}",
     * operationId="destroyTruckMaintenance",
     * summary="Soft delete a specific truck maintenance record",
     * description="Marks a specific maintenance record as deleted for a given truck.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="truckId",
     * in="path",
     * description="ID of the truck",
     * required=true,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the maintenance record to delete",
     * required=true,
     * @OA\Schema(type="integer", example=101)
     * ),
     * @OA\Response(
     * response=200,
     * description="Maintenance record deleted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Maintenance record has been successfully deleted."),
     * @OA\Property(property="id", type="integer", example=101)
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Record or Truck not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Resource not found.")
     * )
     * ),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    // public function destroy($id)
    // {
    //     return parent::destroy($id);
    // }
    
    // public function destroy($id)
    // {
    //     try {
    //         $truckId = request()->route('truckId');
    //         $maintenanceId = request()->route('id');

    //         // 1. Find the truck to get its actual plate_number
    //         $truck = FleetTruck::find($truckId);
            
    //         // 2. Find the maintenance record
    //         $maintenance = TruckMaintenance::find($maintenanceId);

    //         if (!$maintenance || !$truck) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => "Record or Truck not found. Truck ID: {$truckId}, Maintenance ID: {$maintenanceId}"
    //             ], 404);
    //         }

    //         // 3. Ownership Check: Compare plate numbers instead of IDs
    //         // Adjust 'fleet_truck_plate_number' to match the column name in your maintenance table
    //         if ($maintenance->fleet_truck_plate_number !== $truck->plate_number) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'This record does not belong to the specified truck plate number.',
    //                 'debug' => [
    //                     'record_plate' => $maintenance->fleet_truck_plate_number,
    //                     'truck_plate' => $truck->plate_number
    //                 ]
    //             ], 403);
    //         }

    //         // 4. Perform the deletion
    //         $maintenance->delete();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Maintenance record has been successfully deleted.',
    //             'id' => $maintenanceId
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "An error has occurred: " . $e->getMessage()
    //         ], 500); 
    //     }
    // }

    public function destroy($id)
    {
        try {
            // Grab the IDs from the route
            $truckId = (int) request()->route('truckId');
            $maintenanceId = (int) request()->route('id');

            // Delegate the work to the service
            // Assuming $this->service is defined in your constructor or BaseController
            $this->service->deleteMaintenance($truckId, $maintenanceId);

            return response()->json([
                'status' => true,
                'message' => 'Maintenance record has been successfully deleted.',
                'id' => $maintenanceId
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found.'
            ], 404);
        } catch (\Exception $e) {
            // If the Service threw a 403, we use that code, otherwise default to 500
            $code = $e->getCode() == 403 ? 403 : 500;
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'status_code' => $code
            ], $code);
        }
    }

    // /**
    //  * @OA\Put(
    //  * path="/api/trucks/{truckId}/maintenance-history/{id}",
    //  * tags={"Truck Maintenance"},
    //  * summary="Update an existing maintenance record",
    //  * security={{"sanctum": {}}},
    //  * @OA\Parameter(name="truckId", in="path", required=true, @OA\Schema(type="integer")),
    //  * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TruckMaintenanceRecord")),
    //  * @OA\Response(response=200, description="Updated successfully"),
    //  * @OA\Response(response=403, description="Ownership mismatch"),
    //  * @OA\Response(response=404, description="Not found")
    //  * )
    //  */
    // public function update(TruckMaintenanceRequest $request, $id)
    // {
    //     try {
    //         // Grab IDs from the route
    //         $truckId = (int) request()->route('truckId');
    //         $maintenanceId = (int) request()->route('id') ?? $id;

    //         // Delegate to service
    //         $updatedRecord = $this->service->updateMaintenance(
    //             $truckId, 
    //             $maintenanceId, 
    //             $request->validated() // Uses rules defined in your TruckMaintenanceRequest
    //         );

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Maintenance record updated successfully.',
    //             'data' => $updatedRecord
    //         ], 200);

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json(['status' => false, 'message' => 'Record not found.'], 404);
    //     } catch (\Exception $e) {
    //         $code = $e->getCode() == 403 ? 403 : 500;
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], $code);
    //     }
    // }
    /**
     * @OA\Put(
     * path="/api/trucks/truck-maintenance/{id}",
     * tags={"Truck Maintenance"},
     * summary="Update an existing maintenance record",
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TruckMaintenanceRecord")),
     * @OA\Response(response=200, description="Updated successfully"),
     * @OA\Response(response=403, description="Ownership mismatch"),
     * @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(TruckMaintenanceRequest $request, $id)
    {
        try {
            // The service now handles finding and attaching the truck_id
            $updatedRecord = $this->service->updateMaintenance(
                (int) $id, 
                $request->validated() 
            );

            return response()->json([
                'status' => true,
                'message' => 'Maintenance record updated successfully.',
                'data' => $updatedRecord
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
     * path="/api/trucks/truck-maintenance/{id}",
     * tags={"Truck Maintenance"},
     * summary="Get specific maintenance record details",
     * description="Retrieves the full details of a single maintenance record by its ID.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID of the maintenance record",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=201,
     * description="Maintenance details retrieved successfully",
     * @OA\JsonContent(ref="#/components/schemas/TruckMaintenanceRecord")
     * ),
     * @OA\Response(
     * response=404,
     * description="Maintenance ID not found",
     * @OA\JsonContent(
     * @OA\Property(property="status_code", type="integer", example=404),
     * @OA\Property(property="message", type="string", example="Truck maintenance id not found.")
     * )
     * )
     * )
     */
    public function show($id)
    {
        //TODO create catch for error messages
        //return parent::show($id);
        
        try {
            //$data = $request->all();
            $truckmaintenance = $this->service->get_truck_maintenance_by_id($id);
            return response($truckmaintenance, 201);
        } catch (\Exception $e) {
            return response([
                'status_code' => 404,
                'message' => 'Truck maintenance id not found.',
            ], 404);
        }
    }




}
