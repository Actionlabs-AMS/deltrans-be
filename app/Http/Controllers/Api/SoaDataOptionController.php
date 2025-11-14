<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\SoaDataOptionRequest;
use App\Services\SoaDataOptionService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="SOA Data Option Management",
 *     description="API endpoints for SOA data option management"
 * )
 */
class SoaDataOptionController extends BaseController
{
    public function __construct(SoaDataOptionService $soaDataOptionService, MessageService $messageService)
    {
        parent::__construct($soaDataOptionService, $messageService);
    }

    /**
     * Display a listing of SOA data options.
     * 
     * @OA\Get(
     *     path="/api/soa-data-options",
     *     summary="Get list of SOA data options",
     *     tags={"SOA Data Option Management"},
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
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="Filter by parent ID (use 'null' for parent options)",
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of SOA data options retrieved successfully",
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
     * Display the specified SOA data option.
     * 
     * @OA\Get(
     *     path="/api/soa-data-options/{id}",
     *     summary="Get a specific SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="SOA Data Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data option retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOA data option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="SOA data option not found.")
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
     * Store a newly created SOA data option in storage.
     * 
     * @OA\Post(
     *     path="/api/soa-data-options",
     *     summary="Create a new SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1, description="Parent option ID (null for parent options)"),
     *             @OA\Property(property="name", type="string", example="Name", description="Option name"),
     *             @OA\Property(property="description", type="string", example="Shipping line name", description="Option description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="SOA data option created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
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
    public function store(SoaDataOptionRequest $request)
    {
        try {
            $data = $request->all();
            $option = $this->service->store($data);
            return response($option, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified SOA data option in storage.
     * 
     * @OA\Put(
     *     path="/api/soa-data-options/{id}",
     *     summary="Update a SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="SOA Data Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1, description="Parent option ID"),
     *             @OA\Property(property="name", type="string", example="Name", description="Option name"),
     *             @OA\Property(property="description", type="string", example="Shipping line name", description="Option description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data option updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOA data option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="SOA data option not found.")
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
    public function update(SoaDataOptionRequest $request, int $id)
    {
        try {
            $data = $request->all();
            $option = $this->service->update($data, $id);
            return response($option, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Remove the specified SOA data option from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/soa-data-options/{id}",
     *     summary="Delete a SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="SOA Data Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data option moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOA data option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="SOA data option not found.")
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
     * Bulk delete multiple SOA data options.
     * 
     * @OA\Post(
     *     path="/api/soa-data-options/bulk/delete",
     *     summary="Bulk delete multiple SOA data options",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of SOA data option IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data options deleted successfully",
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
     * Get trashed SOA data options.
     * 
     * @OA\Get(
     *     path="/api/archived/soa-data-options",
     *     summary="Get trashed SOA data options",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Trashed SOA data options retrieved successfully",
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
     * Restore a trashed SOA data option.
     * 
     * @OA\Patch(
     *     path="/api/archived/soa-data-options/restore/{id}",
     *     summary="Restore a trashed SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="SOA Data Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data option restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOA data option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="SOA data option not found.")
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
     * Bulk restore multiple trashed SOA data options.
     * 
     * @OA\Post(
     *     path="/api/soa-data-options/bulk/restore",
     *     summary="Bulk restore multiple trashed SOA data options",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of SOA data option IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data options restored successfully",
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
     * Permanently delete a SOA data option.
     * 
     * @OA\Delete(
     *     path="/api/archived/soa-data-options/{id}",
     *     summary="Permanently delete a SOA data option",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="SOA Data Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data option permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOA data option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="SOA data option not found.")
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
     * Bulk permanently delete multiple SOA data options.
     * 
     * @OA\Post(
     *     path="/api/soa-data-options/bulk/force-delete",
     *     summary="Bulk permanently delete multiple SOA data options",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of SOA data option IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SOA data options permanently deleted successfully",
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
     * Get parent options (where parent_id is null).
     * 
     * @OA\Get(
     *     path="/api/options/soa-data-options/parents",
     *     summary="Get parent SOA data options",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Parent options retrieved successfully",
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
    public function getParents()
    {
        try {
            return $this->service->getParents();
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get children options for a specific parent.
     * 
     * @OA\Get(
     *     path="/api/options/soa-data-options/parents/{parentId}/children",
     *     summary="Get children SOA data options for a parent",
     *     tags={"SOA Data Option Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="parentId",
     *         in="path",
     *         required=true,
     *         description="Parent Option ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Children options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Parent option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Parent option not found.")
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
    public function getChildren($parentId)
    {
        try {
            return $this->service->getChildren($parentId);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}

