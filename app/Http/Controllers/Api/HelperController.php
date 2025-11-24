<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Helper;
use Illuminate\Http\Request;
use App\Http\Requests\HelperRequest;
use App\Services\HelperService;
use App\Services\MessageService;

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
   *         name="active_status",
   *         in="query",
   *         description="Filter by active status (1 for active, 0 for inactive)",
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
  public function destroy($id)
  {
    return parent::destroy($id);
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
   *             @OA\Property(property="active_status", type="boolean", example=true, description="Helper active status")
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
   *             @OA\Property(property="active_status", type="boolean", example=true, description="Helper active status")
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
}






