<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Requests\TruckRequest;
use App\Services\TruckService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 * name="Trucks Management",
 * description="API Endpoints for managing trucks"
 * )
 * @OA\Schema(
 * schema="Truck",
 * title="Truck Model",
 * description="A truck resource",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="plate_number", type="string", example="ABC-1234"),
 * @OA\Property(property="condition", type="string", example="Good"),
 * @OA\Property(property="is_active", type="integer", example=1, description="1=Active, 0=Inactive"),
 * @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 * @OA\Schema(
 * schema="TruckInput",
 * title="Truck Input",
 * description="Data required to create or update a truck",
 * required={"plate_number", "condition", "is_active"},
 * @OA\Property(property="plate_number", type="string", example="XYZ-5678"),
 * @OA\Property(property="condition", type="string", example="Good/Maintenance"),
 * @OA\Property(property="is_active", type="integer", example=1, description="1=Active, 0=Inactive"),
 * )
 */
class TruckController extends BaseController
{
    public function __construct(TruckService $truckService, MessageService $messageService)
    {
        parent::__construct($truckService, $messageService);
    }

    /**
     * @OA\Get(
     * path="/api/trucks/truck-list",
     * operationId="getTrucksList",
     * tags={"Trucks Management"},
     * summary="Get list of trucks",
     * description="Returns a paginated list of all trucks with optional filtering via the 'search' parameter.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Page number",
     * required=false,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Items per page",
     * required=false,
     * @OA\Schema(type="integer", example=10)
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Search term to filter trucks (e.g., by plate number or condition).",
     * required=false,
     * @OA\Schema(type="string", example="NCK6498")
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Truck")
     * )
     * ),               
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        //return parent::index();
        $request = request();
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        // ... get other filters

        return $this->service->list($perPage, $search);
    }

    // `create()` is not an API method and is intentionally left without Swagger docs

    /**
     * @OA\Post(
     * path="/api/trucks/add-truck",
     * operationId="storeTruck",
     * tags={"Trucks Management"},
     * summary="Create a new truck",
     * description="Creates a new truck resource in storage.",
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Truck data to store",
     * @OA\JsonContent(ref="#/components/schemas/TruckInput")
     * ),
     * @OA\Response(
     * response=201,
     * description="Truck created successfully",
     * @OA\JsonContent(ref="#/components/schemas/Truck")
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    // public function store(TruckRequest $request)
    // {
    //     try {
    //         $data = $request->all();
    //         $truck = $this->service->store($data);
    //         return response($truck, 201);
    //     } catch (\Exception $e) {
    //         //return $this->messageService->responseError();
    //         // This catch block is primarily for service-level or database errors (not validation errors)
    //         return response()->json([
    //             'status_code' => 500,
    //             'message' => 'An unexpected server error occurred.',
    //         ], 500);
    //     }
    // }
    public function store(TruckRequest $request)
    {
        try {
            $data = $request->all();
            $result = $this->service->store($data);

            // Determine status code: 200 for restored, 201 for brand new
            $statusCode = ($result['message'] === 'Truck created successfully.') ? 201 : 200;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An unexpected server error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/trucks/get-truck-by-id/{id}",
     * operationId="getTruckById",
     * tags={"Trucks Management"},
     * summary="Get a specific truck",
     * description="Returns a single truck resource by its ID.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the truck to return",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=201,
     * description="Successful operation",
     * @OA\JsonContent(ref="#/components/schemas/Truck")
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function show($id)
    {
        //TODO create catch for error messages
        //return parent::show($id);
        
        try {
            //$data = $request->all();
            $truck = $this->service->get_truck_by_id($id);
            return response($truck, 201);
        } catch (\Exception $e) {
            return response([
                'status_code' => 404,
                'message' => 'Truck id not found.',
            ], 404);
        }


    }

    // `edit()` is not an API method and is intentionally left without Swagger docs

    /**
     * @OA\Put(
     * path="/api/trucks/update-truck/{id}",
     * operationId="updateTruck",
     * tags={"Trucks Management"},
     * summary="Update an existing truck",
     * description="Updates an existing truck resource by its ID.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the truck to update",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Truck data to update (uses TruckRequest for validation)",
     * @OA\JsonContent(ref="#/components/schemas/TruckInput")
     * ),
     * @OA\Response(
     * response=201,
     * description="Truck updated successfully",
     * @OA\JsonContent(ref="#/components/schemas/Truck")
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function update(TruckRequest $request, string $id)
    {
        try {
            $data = $request->all();
            $truck = $this->service->update($data, $id);
            return response($truck, 201);
        } catch (\Exception $e) {
            //return $this->messageService->responseError();
             return response([
                'status_code' => 404,
                'message' => 'Truck id not found.',
            ], 404);
        }
    }

    /**
     * @OA\Patch(
     * path="/api/trucks/deactivate-truck/{id}",                
     * operationId="deactivateTruck",
     * tags={"Trucks Management"},
     * summary="Deactivate a truck (Logical Delete)",
     * description="Updates the truck is_active to 0 (inactive) by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the truck to deactivate",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,                
     * description="Truck successfully deactivated",
     * @OA\JsonContent(
     * @OA\Property(property="status_code", type="integer", example=200),
     * @OA\Property(property="message", type="string", example="Truck deactivated successfully.")
     * )
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function deactivate($id)                
    {                
        try {                
            // Assumption: The service method now updates is_active to 0
            $this->service->deactivate_truck_by_id($id); 
            
            // Return 200 OK with a message instead of 204 No Content
            // because we are technically updating, not destroying.
            return response()->json([
                'status_code' => 200,                
                'message' => 'Truck deactivated successfully.',                
            ], 200);                

        } catch (\Exception $e) {                
            // This catches if the ID is not found in the service layer
            return response()->json([                
                'status_code' => 404,                
                'message' => 'Truck id not found.',                
            ], 404);                
        }                
    }

    /**
     * @OA\Patch(
     * path="/api/trucks/activate-truck/{id}",                
     * operationId="activateTruck",
     * tags={"Trucks Management"},
     * summary="Activate a truck (Restore)",
     * description="Updates the truck is_active to 1 (active) by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the truck to activate",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,                
     * description="Truck successfully activated",
     * @OA\JsonContent(
     * @OA\Property(property="status_code", type="integer", example=200),
     * @OA\Property(property="message", type="string", example="Truck activated successfully.")
     * )
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function restore($id)                
    {                
        try {                
            // Call the service method to update is_active to 1
            $this->service->activate_truck_by_id($id); 
            
            return response()->json([
                'status_code' => 200,                
                'message' => 'Truck activated successfully.',                
            ], 200);                

        } catch (\Exception $e) {                
            // Catches if the ID is not found in the service layer
            return response()->json([                
                'status_code' => 404,                
                'message' => 'Truck id not found.',                
            ], 404);                
        }                
    }

    /**
     * @OA\Delete(
     * path="/api/trucks/truck-list/{id}",
     * operationId="destroyTruck",
     * tags={"Trucks Management"},
     * summary="Soft delete a truck record",
     * description="Moves a truck record to the trash using its ID.",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the truck to delete",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Truck successfully deleted",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Truck record has been successfully deleted."),
     * @OA\Property(property="id", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function destroy($id)
    {
        try {
            // Grab the IDs from the route
            $truckId = (int) request()->route('id');

            // Delegate the work to the service
            // Assuming $this->service is defined in your constructor or BaseController
            $this->service->delete_truck_by_id($truckId);

            return response()->json([
                'status' => true,
                'message' => 'Truck record has been successfully deleted.',
                'id' => $truckId
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

    /**
     * @OA\Get(
     * path="/api/trucks/active-list",
     * operationId="getActiveTruckList",
     * tags={"Trucks Management"},
     * summary="Get list of all active trucks",
     * description="Returns a list of trucks where is_active is 1",
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="boolean", example=true),
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="plate_number", type="string", example="ABC-1234")
     * ))
     * )
     * ),
     * @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getActiveTruckList()
    {
        try {
            $trucks = $this->service->getActiveTrucks();

            return response()->json([
                'status' => true,
                'message' => 'Active trucks retrieved successfully.',
                'data' => $trucks
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve truck list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * @OA\Post(
    //  * path="/api/trucks/bulk-delete",
    //  * operationId="bulkDeleteTrucks",
    //  * tags={"Trucks Management"},
    //  * summary="Bulk soft delete trucks",
    //  * description="Soft deletes multiple truck resources using an array of IDs.",
    //  * @OA\RequestBody(
    //  * required=true,
    //  * description="Array of truck IDs to delete",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
    //  * )
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Bulk delete successful",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="message", type="string", example="Resources deleted successfully.")
    //  * )
    //  * ),
    //  * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function bulkDelete(Request $request)
    // {
    //     return parent::bulkDelete($request);
    // }

    // /**
    //  * @OA\Get(
    //  * path="/api/trucks/trashed",
    //  * operationId="getTrashedTrucks",
    //  * tags={"Trucks Management"},
    //  * summary="Get list of soft-deleted trucks",
    //  * description="Returns a list of trucks that have been soft-deleted.",
    //  * @OA\Response(
    //  * response=200,
    //  * description="Successful operation",
    //  * @OA\JsonContent(
    //  * type="array",
    //  * @OA\Items(ref="#/components/schemas/Truck")
    //  * )
    //  * ),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function getTrashed()
    // {
    //     return parent::getTrashed();
    // }

    // /**
    //  * @OA\Post(
    //  * path="/api/trucks/restore/{id}",
    //  * operationId="restoreTruck",
    //  * tags={"Trucks Management"},
    //  * summary="Restore a soft-deleted truck",
    //  * description="Restores a single soft-deleted truck resource by its ID.",
    //  * @OA\Parameter(
    //  * name="id",
    //  * in="path",
    //  * description="ID of the truck to restore",
    //  * required=true,
    //  * @OA\Schema(type="integer")
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Truck restored successfully",
    //  * @OA\JsonContent(ref="#/components/schemas/Truck")
    //  * ),
    //  * @OA\Response(response=404, ref="#/components/responses/NotFound"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function restore($id)
    // {
    //     return parent::restore($id);
    // }

    // /**
    //  * @OA\Post(
    //  * path="/api/trucks/bulk-restore",
    //  * operationId="bulkRestoreTrucks",
    //  * tags={"Trucks Management"},
    //  * summary="Bulk restore trucks",
    //  * description="Restores multiple soft-deleted truck resources using an array of IDs.",
    //  * @OA\RequestBody(
    //  * required=true,
    //  * description="Array of truck IDs to restore",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
    //  * )
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Bulk restore successful",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="message", type="string", example="Resources restored successfully.")
    //  * )
    //  * ),
    //  * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function bulkRestore(Request $request)
    // {
    //     return parent::bulkRestore($request);
    // }

    // /**
    //  * @OA\Delete(
    //  * path="/api/trucks/force-delete/{id}",
    //  * operationId="forceDeleteTruck",
    //  * tags={"Trucks Management"},
    //  * summary="Permanently delete a truck",
    //  * description="Permanently removes a truck resource from storage by its ID (must be soft-deleted first).",
    //  * @OA\Parameter(
    //  * name="id",
    //  * in="path",
    //  * description="ID of the truck to permanently delete",
    //  * required=true,
    //  * @OA\Schema(type="integer")
    //  * ),
    //  * @OA\Response(
    //  * response=204,
    //  * description="Truck permanently deleted (No Content)"
    //  * ),
    //  * @OA\Response(response=404, ref="#/components/responses/NotFound"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function forceDelete($id)
    // {
    //     //return parent::forceDelete($id);
    //     try {
    //         $truck = $this->service->delete_truck_by_id($id);
    //         return response($truck, 204);
    //     } catch (\Exception $e) {
    //         //return $this->messageService->responseError();
    //          return response([
    //             'status_code' => 404,
    //             'message' => 'Truck id not found.',
    //         ], 404);
    //     }
    // }

    // /**
    //  * @OA\Post(
    //  * path="/api/trucks/bulk-force-delete",
    //  * operationId="bulkForceDeleteTrucks",
    //  * tags={"Trucks Management"},
    //  * summary="Bulk permanent delete trucks",
    //  * description="Permanently removes multiple truck resources using an array of IDs (must be soft-deleted first).",
    //  * @OA\RequestBody(
    //  * required=true,
    //  * description="Array of truck IDs to permanently delete",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
    //  * )
    //  * ),
    //  * @OA\Response(
    //  * response=200,
    //  * description="Bulk permanent delete successful",
    //  * @OA\JsonContent(
    //  * @OA\Property(property="message", type="string", example="Resources permanently deleted successfully.")
    //  * )
    //  * ),
    //  * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
    //  * @OA\Response(response=500, ref="#/components/responses/GeneralError")
    //  * )
    //  */
    // public function bulkForceDelete(Request $request)
    // {
    //     return parent::bulkForceDelete($request);
    // }

    
}