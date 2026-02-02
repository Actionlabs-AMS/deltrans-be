<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Helper;
use Illuminate\Http\Request;
use App\Http\Requests\HelperRequest;
use App\Services\HelperService;
use App\Services\MessageService;
use App\Http\Resources\WaybillDetailResource;

/**
 * @OA\Tag(
 *     name="Helper Management",
 *     description="API endpoints for helper management"
 * )
 */
class HelperController extends BaseController
{
  public function __construct(HelperService $helperService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($helperService, $messageService);
  }

  /**
   * Display a listing of helpers.
   * 
   * @OA\Get(
   *     path="/api/helpers",
   *     summary="Get list of helpers",
   *     tags={"Helper Management"},
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
   *         description="List of helpers retrieved successfully",
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
   * Display the specified helper.
   * 
   * @OA\Get(
   *     path="/api/helpers/{id}",
   *     summary="Get a specific helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Helper ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helper retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Helper not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper not found.")
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
   * Remove the specified helper from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/helpers/{id}",
   *     summary="Delete a helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Helper ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helper moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Helper not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper not found.")
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
  // public function destroy($id)
  // {
  //   return parent::destroy($id);
  // }
  public function destroy($id)
  {
      try {
          // Grab the IDs from the route
          $driverId = (int) request()->route('id');

          // Delegate the work to the service
          // Assuming $this->service is defined in your constructor or BaseController
          $this->service->delete_helper_by_id($driverId);

          return response()->json([
              'status' => true,
              'message' => 'Helper record has been successfully deleted.',
              'id' => $driverId
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
   * Bulk delete multiple helpers.
   * 
   * @OA\Post(
   *     path="/api/helpers/bulk/delete",
   *     summary="Bulk delete multiple helpers",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of helper IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helpers deleted successfully",
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
   * Get trashed helpers.
   * 
   * @OA\Get(
   *     path="/api/archived/helpers",
   *     summary="Get trashed helpers",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed helpers retrieved successfully",
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
   * Restore a trashed helper.
   * 
   * @OA\Patch(
   *     path="/api/archived/helpers/restore/{id}",
   *     summary="Restore a trashed helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Helper ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helper restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Helper not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper not found.")
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
   * Bulk restore multiple trashed helpers.
   * 
   * @OA\Post(
   *     path="/api/helpers/bulk/restore",
   *     summary="Bulk restore multiple trashed helpers",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of helper IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helpers restored successfully",
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
   * Permanently delete a helper.
   * 
   * @OA\Delete(
   *     path="/api/archived/helpers/{id}",
   *     summary="Permanently delete a helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Helper ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helper permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Helper not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper not found.")
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
   * Bulk permanently delete multiple helpers.
   * 
   * @OA\Post(
   *     path="/api/helpers/bulk/force-delete",
   *     summary="Bulk permanently delete multiple helpers",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of helper IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Helpers permanently deleted successfully",
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
   *     path="/api/helpers",
   *     summary="Create a new helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"first_name", "last_name", "contact_number"},
   *             @OA\Property(property="first_name", type="string", example="Juan", description="Helper first name"),
   *             @OA\Property(property="last_name", type="string", example="Dela Cruz", description="Helper last name"),
   *             @OA\Property(property="contact_number", type="string", example="+63 912 345 6789", description="Helper contact number"),
             *             @OA\Property(property="is_active", type="integer", example=1, description="Helper is_active status (1=Active, 0=Inactive)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Helper created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper has been created successfully."),
   *             @OA\Property(property="helper", type="object")
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
  public function store(HelperRequest $request)
  {
    try {
      $data = $request->all();
      $helper = $this->service->store($data);
      return response($helper, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Update the specified resource in storage.
   * 
   * @OA\Put(
   *     path="/api/helpers/{id}",
   *     summary="Update a helper",
   *     tags={"Helper Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Helper ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="first_name", type="string", example="Juan", description="Helper first name"),
   *             @OA\Property(property="last_name", type="string", example="Dela Cruz", description="Helper last name"),
   *             @OA\Property(property="contact_number", type="string", example="+63 912 345 6789", description="Helper contact number"),
             *             @OA\Property(property="is_active", type="integer", example=1, description="Helper is_active status (1=Active, 0=Inactive)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Helper updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper has been updated successfully."),
   *             @OA\Property(property="helper", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Helper not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Helper not found.")
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
  public function update(HelperRequest $request, int $id)
  {
    try {
      $data = $request->all();
      $helper = $this->service->update($data, $id);
      return response($helper, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

    /**
   * Activate the specified helper.
   * * @OA\Patch(
   * path="/api/helpers/activate/{id}",
   * summary="Activate a helper",
   * tags={"Helper Management"},
   * security={{"sanctum": {}}},
   * @OA\Parameter(
   * name="id",
   * in="path",
   * required=true,
   * description="Helper ID",
   * @OA\Schema(type="integer", example=1)
   * ),
   * @OA\Response(
   * response=200,
   * description="Helper activated successfully",
   * @OA\JsonContent(
   * @OA\Property(property="status_code", type="integer", example=200),
   * @OA\Property(property="message", type="string", example="Helper activated successfully.")
   * )
   * ),
   * @OA\Response(response=404, description="Helper not found"),
   * @OA\Response(response=401, description="Unauthenticated")
   * )
   */
  public function activate($id)
  {
      try {
          $this->service->activate_helper_by_id($id);

          return response()->json([
              'status_code' => 200,
              'message' => 'Helper activated successfully.',
          ], 200);

      } catch (\Exception $e) {
          return response()->json([
              'status_code' => 404,
              'message' => 'Helper id not found.',
          ], 404);
      }
  }

  /**
   * Deactivate the specified helper.
   * * @OA\Patch(
   * path="/api/helpers/deactivate/{id}",
   * summary="Deactivate a helper",
   * tags={"Helper Management"},
   * security={{"sanctum": {}}},
   * @OA\Parameter(
   * name="id",
   * in="path",
   * required=true,
   * description="Helper ID",
   * @OA\Schema(type="integer", example=1)
   * ),
   * @OA\Response(
   * response=200,
   * description="Helper deactivated successfully",
   * @OA\JsonContent(
   * @OA\Property(property="status_code", type="integer", example=200),
   * @OA\Property(property="message", type="string", example="Helper deactivated successfully.")
   * )
   * ),
   * @OA\Response(response=404, description="Helper not found"),
   * @OA\Response(response=401, description="Unauthenticated")
   * )
   */
  public function deactivate($id)
  {
      try {
          $this->service->deactivate_helper_by_id($id);

          return response()->json([
              'status_code' => 200,
              'message' => 'Helper deactivated successfully.',
          ], 200);

      } catch (\Exception $e) {
          return response()->json([
              'status_code' => 404,
              'message' => 'Helper id not found.',
          ], 404);
      }
  }

    /**
   * Get specific helper details.
   *
   * @OA\Get(
   * path="/api/helpers/details/{id}",
   * summary="Get helper details by ID",
   * tags={"Helper Management"},
   * security={{"sanctum": {}}},
   * @OA\Parameter(
   * name="id",
   * in="path",
   * required=true,
   * description="Helper ID",
   * @OA\Schema(type="integer", example=1)
   * ),
   * @OA\Response(
   * response=200,
   * description="Helper details retrieved successfully",
   * @OA\JsonContent(
   * @OA\Property(property="status_code", type="integer", example=200),
   * @OA\Property(property="message", type="string", example="Helper details retrieved successfully."),
   * @OA\Property(
   * property="data",
   * type="object",
   * @OA\Property(property="id", type="integer", example=1),
   * @OA\Property(property="first_name", type="string", example="Juan"),
   * @OA\Property(property="last_name", type="string", example="Luna"),
   * @OA\Property(property="contact_number", type="string", example="09123456789"),
   * @OA\Property(property="is_active", type="integer", example=1),
   * @OA\Property(property="created_at", type="string", format="date-time"),
   * @OA\Property(property="updated_at", type="string", format="date-time")
   * )
   * )
   * ),
   * @OA\Response(response=404, description="Helper not found"),
   * @OA\Response(response=401, description="Unauthenticated")
   * )
   */

  public function getHelperDetails($id)
  {
      try {
          $helper = $this->service->getHelperById($id);

          return response()->json([
              'success' => true,
              'data' => $helper
          ], 200);

      } catch (\Exception $e) {
          return response()->json([
              'status_code' => 404,
              'message' => 'Helper not found.'
          ], 404);
      }
  }

  // /**
  //  * Fetch waybill details by driver ID with unified search.
  //  * * @OA\Get(
  //  * path="/api/helpers/get-waybill/{id}",
  //  * summary="Fetch waybill details by driver ID",
  //  * tags={"Helper Management"},
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
  //  * description="Search by Waybill #, Plate #, or Date (YYYY-MM-DD)",
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
  //  * description="Waybill details fetched successfully",
  //  * @OA\JsonContent(
  //  * @OA\Property(property="status_code", type="integer", example=200),
  //  * @OA\Property(property="message", type="string", example="Waybill details fetched successfully."),
  //  * @OA\Property(property="data", type="object")
  //  * )
  //  * )
  //  * )
  //  */
  // public function getWaybillByHelperId($id)
  // {
  //     try {
  //       // Note: We pass the requested per_page or default to 10
  //       $perPage = request('per_page', 10);
  //       $waybills = $this->service->get_waybills_by_helper_id($id, $perPage);

  //       return WaybillDetailResource::collection($waybills);

  //   } catch (\Exception $e) {
  //       return response()->json([
  //           'status_code' => 404,
  //           'message' => $e->getMessage(),
  //       ], 404);
  //   }
  // }
  /**
   * Fetch waybill details by helper ID with unified search and date filtering.
   * * @OA\Get(
   * path="/api/helpers/get-waybill/{id}",
   * summary="Fetch waybill details by helper ID",
   * tags={"Helper Management"},
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
   * description="Search by Waybill # or Plate #",
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
   * @OA\Schema(type="string", format="date", example="2026-01-28")
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
   * @OA\Property(property="data", type="array", @OA\Items(type="object"))
   * )
   * ),
   * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
   * @OA\Response(response=500, ref="#/components/responses/GeneralError")
   * )
   */
  public function getWaybillByHelperId($id)
  {
      try {
          $perPage = request('per_page', 10);
          $searchTerm = request('search');
          $filterType = request('filter_type', 'weekly');
          $refDate = request('reference_date') ? \Carbon\Carbon::parse(request('reference_date')) : now();

          // Calculate Date Range based on UI Filter Type
          if ($filterType === 'weekly') {
              $dateFrom = $refDate->copy()->startOfWeek()->toDateString();
              $dateTo = $refDate->copy()->endOfWeek()->toDateString();
          } else {
              $dateFrom = $refDate->copy()->startOfMonth()->toDateString();
              $dateTo = $refDate->copy()->endOfMonth()->toDateString();
          }

          $waybills = $this->service->get_waybills_by_helper_id(
              $id, 
              $perPage, 
              $searchTerm, 
              $dateFrom, 
              $dateTo
          );

          return WaybillDetailResource::collection($waybills);

      } catch (\Exception $e) {
          return response()->json([
              'status_code' => 500,
              'message' => $e->getMessage(),
          ], 500);
      }
  }

   /**
   * @OA\Get(
   * path="/api/helpers/active-list",
   * operationId="getActiveHelperList",
   * tags={"Helper Management"},
   * summary="Get list of all active helpers",
   * description="Returns a list of helpers where is_active is 1",
   * security={{"sanctum": {}}},
   * @OA\Response(
   * response=200,
   * description="Successful operation",
   * @OA\JsonContent(
   * @OA\Property(property="status", type="boolean", example=true),
   * @OA\Property(property="message", type="string", example="Active helpers retrieved successfully."),
   * @OA\Property(property="data", type="array", @OA\Items(
   * @OA\Property(property="id", type="integer", example=1),
   * @OA\Property(property="first_name", type="string", example="John"),
   * @OA\Property(property="last_name", type="string", example="Doe"),
   * ))
   * )
   * ),
   * @OA\Response(response=400, ref="#/components/responses/BadRequest"),               
   * @OA\Response(response=500, ref="#/components/responses/GeneralError")
   * )
   */
  public function getActiveHelperList()
  {
      try {
          $helpers = $this->service->getActiveHelpers();

          return response()->json([
              'status' => true,
              'message' => 'Active helpers retrieved successfully.',
              'data' => $helpers
          ], 200);

      } catch (\Exception $e) {
          return response()->json([
              'status' => false,
              'message' => 'Failed to retrieve helpers list.',
              'error' => $e->getMessage()
          ], 500);
      }
  }
 
}









