<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use App\Http\Requests\DriverRequest;
use App\Services\DriverService;
use App\Services\MessageService;
use App\Http\Resources\WaybillDetailResource;

/**
 * @OA\Tag(
 *     name="Driver Management",
 *     description="API endpoints for driver management"
 * )
 */
class DriverController extends BaseController
{
  public function __construct(DriverService $driverService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($driverService, $messageService);
  }

  /**
   * Display a listing of drivers.
   * 
   * @OA\Get(
   *     path="/api/drivers",
   *     summary="Get list of drivers",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Items per page",
   *         @OA\Schema(type="integer", example=10)
   *     ),
   *     @OA\Parameter(
   *         name="search",
   *         in="query",
   *         description="Search by first name, last name, or contact number",
   *         @OA\Schema(type="string")
   *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by is_active (1 for active, 0 for inactive)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
   *     @OA\Response(
   *         response=200,
   *         description="List of drivers retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
   *             @OA\Property(property="meta", type="object"),
   *             @OA\Property(property="links", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function index()
  {
    return parent::index();
  }

  /**
   * Display the specified driver.
   * 
   * @OA\Get(
   *     path="/api/drivers/{id}",
   *     summary="Get a specific driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Driver ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Driver retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Driver not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function show($id)
  {
    return parent::show($id);
  }

  /**
   * Remove the specified driver from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/drivers/{id}",
   *     summary="Delete a driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Driver ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Driver moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Driver not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function destroy($id)
  {
    return parent::destroy($id);
  }

  /**
   * Bulk delete multiple drivers.
   * 
   * @OA\Post(
   *     path="/api/drivers/bulk/delete",
   *     summary="Bulk delete multiple drivers",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of driver IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Drivers deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkDelete(Request $request)
  {
    return parent::bulkDelete($request);
  }

  /**
   * Get trashed drivers.
   * 
   * @OA\Get(
   *     path="/api/archived/drivers",
   *     summary="Get trashed drivers",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed drivers retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getTrashed()
  {
    return parent::getTrashed();
  }

  /**
   * Restore a trashed driver.
   * 
   * @OA\Patch(
   *     path="/api/archived/drivers/restore/{id}",
   *     summary="Restore a trashed driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Driver ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Driver restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Driver not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function restore($id)
  {
    return parent::restore($id);
  }

  /**
   * Bulk restore multiple trashed drivers.
   * 
   * @OA\Post(
   *     path="/api/drivers/bulk/restore",
   *     summary="Bulk restore multiple trashed drivers",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of driver IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Drivers restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been restored.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkRestore(Request $request)
  {
    return parent::bulkRestore($request);
  }

  /**
   * Permanently delete a driver.
   * 
   * @OA\Delete(
   *     path="/api/archived/drivers/{id}",
   *     summary="Permanently delete a driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Driver ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Driver permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Driver not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function forceDelete($id)
  {
    return parent::forceDelete($id);
  }

  /**
   * Bulk permanently delete multiple drivers.
   * 
   * @OA\Post(
   *     path="/api/drivers/bulk/force-delete",
   *     summary="Bulk permanently delete multiple drivers",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of driver IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Drivers permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkForceDelete(Request $request)
  {
    return parent::bulkForceDelete($request);
  }
  
  /**
   * Store a newly created resource in storage.
   * 
   * @OA\Post(
   *     path="/api/drivers",
   *     summary="Create a new driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"first_name", "last_name", "contact_number"},
   *             @OA\Property(property="first_name", type="string", example="Juan", description="Driver first name"),
   *             @OA\Property(property="last_name", type="string", example="Dela Cruz", description="Driver last name"),
   *             @OA\Property(property="contact_number", type="string", example="+63 912 345 6789", description="Driver contact number"),
             *             @OA\Property(property="is_active", type="integer", example=1, description="Driver is_active status (1=Active, 0=Inactive)"),
   *             @OA\Property(property="assigned_truck_plate_numbers", type="array", @OA\Items(type="string"), example={"ABC-1234", "XYZ-5678"}, description="Array of assigned truck plate numbers"),
   *             @OA\Property(property="stack_run", type="array", @OA\Items(type="string"), example={"Route A", "Route B"}, description="Array of stack run routes"),
   *             @OA\Property(property="helpers_id", type="array", @OA\Items(type="integer"), example={1, 2}, description="Array of helper IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Driver created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver has been created successfully."),
   *             @OA\Property(property="driver", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function store(DriverRequest $request)
  {
    try {
      $data = $request->all();
      $driver = $this->service->store($data);
      return response($driver, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Update the specified resource in storage.
   * 
   * @OA\Put(
   *     path="/api/drivers/{id}",
   *     summary="Update a driver",
   *     tags={"Driver Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Driver ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="first_name", type="string", example="Juan", description="Driver first name"),
   *             @OA\Property(property="last_name", type="string", example="Dela Cruz", description="Driver last name"),
   *             @OA\Property(property="contact_number", type="string", example="+63 912 345 6789", description="Driver contact number"),
             *             @OA\Property(property="is_active", type="integer", example=1, description="Driver is_active status (1=Active, 0=Inactive)"),
   *             @OA\Property(property="assigned_truck_plate_numbers", type="array", @OA\Items(type="string"), example={"ABC-1234", "XYZ-5678"}, description="Array of assigned truck plate numbers"),
   *             @OA\Property(property="stack_run", type="array", @OA\Items(type="string"), example={"Route A", "Route B"}, description="Array of stack run routes"),
   *             @OA\Property(property="helpers_id", type="array", @OA\Items(type="integer"), example={1, 2}, description="Array of helper IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Driver updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver has been updated successfully."),
   *             @OA\Property(property="driver", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Driver not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Driver not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function update(DriverRequest $request, int $id)
  {
    try {
      $data = $request->all();
      $driver = $this->service->update($data, $id);
      return response($driver, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
 * Activate the specified driver.
 * * @OA\Patch(
 * path="/api/drivers/activate/{id}",
 * summary="Activate a driver",
 * tags={"Driver Management"},
 * security={{"sanctum": {}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * description="Driver ID",
 * @OA\Schema(type="integer", example=1)
 * ),
 * @OA\Response(
 * response=200,
 * description="Driver activated successfully",
 * @OA\JsonContent(
 * @OA\Property(property="message", type="string", example="Driver status updated successfully."),
 * @OA\Property(property="status", type="integer", example=1)
 * )
 * ),
 * @OA\Response(response=404, description="Driver not found"),
 * @OA\Response(response=401, description="Unauthenticated")
 * )
 */
  public function deactivate($id)                
  {                
    try {                
        // Assumption: The service method now updates is_active to 0
        $this->service->deactivate_driver_by_id($id); 
        
        // Return 200 OK with a message instead of 204 No Content
        // because we are technically updating, not destroying.
        return response()->json([
            'status_code' => 200,                
            'message' => 'Driver deactivated successfully.',                
        ], 200);                

    } catch (\Exception $e) {                
        // This catches if the ID is not found in the service layer
        return response()->json([                
            'status_code' => 404,                
            'message' => 'Driver id not found.',                
        ], 404);                
    }                
  }

  /**
   * Deactivate the specified driver.
   * * @OA\Patch(
   * path="/api/drivers/deactivate/{id}",
   * summary="Deactivate a driver",
   * tags={"Driver Management"},
   * security={{"sanctum": {}}},
   * @OA\Parameter(
   * name="id",
   * in="path",
   * required=true,
   * description="Driver ID",
   * @OA\Schema(type="integer", example=1)
   * ),
   * @OA\Response(
   * response=200,
   * description="Driver deactivated successfully",
   * @OA\JsonContent(
   * @OA\Property(property="message", type="string", example="Driver status updated successfully."),
   * @OA\Property(property="status", type="integer", example=0)
   * )
   * ),
   * @OA\Response(response=404, description="Driver not found"),
   * @OA\Response(response=401, description="Unauthenticated")
   * )
   */

  public function activate($id)                
  {                
    try {                
        // Assumption: The service method now updates is_active to 0
        $this->service->activate_driver_by_id($id); 
        
        // Return 200 OK with a message instead of 204 No Content
        // because we are technically updating, not destroying.
        return response()->json([
            'status_code' => 200,                
            'message' => 'Driver activated successfully.',                
        ], 200);                

    } catch (\Exception $e) {                
        // This catches if the ID is not found in the service layer
        return response()->json([                
            'status_code' => 404,                
            'message' => 'Driver id not found.',                
        ], 404);                
    }                
  }

  /**
   * Fetch waybill details by driver ID with unified search.
   * * @OA\Get(
   * path="/api/drivers/get-waybill/{id}",
   * summary="Fetch waybill details by driver ID",
   * tags={"Driver Management"},
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
   * description="Search by Waybill #, Plate #, or Date (YYYY-MM-DD)",
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
   * description="Waybill details fetched successfully",
   * @OA\JsonContent(
   * @OA\Property(property="status_code", type="integer", example=200),
   * @OA\Property(property="message", type="string", example="Waybill details fetched successfully."),
   * @OA\Property(property="data", type="object")
   * )
   * )
   * )
   */
  public function getWaybillByDriverId($id)
  {
      try {
        // Note: We pass the requested per_page or default to 10
        $perPage = request('per_page', 10);
        $waybills = $this->service->get_waybills_by_driver_id($id, $perPage);

        return WaybillDetailResource::collection($waybills);

    } catch (\Exception $e) {
        return response()->json([
            'status_code' => 404,
            'message' => $e->getMessage(),
        ], 404);
    }
  }
}

