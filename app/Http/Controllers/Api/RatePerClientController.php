<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\RatePerClientRequest;
use App\Services\RatePerClientService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Rate Per Client Management",
 *     description="API endpoints for rate per client management"
 * )
 * @OA\Schema(
 *     schema="RatePerClient",
 *     title="Rate Per Client Model",
 *     description="A rate per client resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="no_of_days", type="integer", example=7),
 *     @OA\Property(property="requirements", type="string", example="Standard documentation", nullable=true),
 *     @OA\Property(property="remarks", type="string", example="Standard rate for 7 days", nullable=true),
 *     @OA\Property(property="cypa_id", type="integer", example=0, description="0 = all CYPA"),
 *     @OA\Property(property="stack_run", type="number", format="float", example=1000.00, description="Stack run amount"),
 *     @OA\Property(property="container_size", type="string", example="20ft"),
 *     @OA\Property(property="rate", type="number", format="float", example=5000.00, description="Rate amount"),
 *     @OA\Property(property="tax_percent", type="number", format="float", example=12.00, nullable=true, description="Tax percentage (for SOA)"),
 *     @OA\Property(property="is_active", type="integer", example=1, description="1=Active, 0=Inactive"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="RatePerClientInput",
 *     title="Rate Per Client Input",
 *     description="Data required to create or update a rate per client",
 *     required={"shipping_line_id", "no_of_days", "cypa_id", "stack_run", "container_size", "rate"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="no_of_days", type="integer", example=7),
 *     @OA\Property(property="requirements", type="string", example="Standard documentation"),
 *     @OA\Property(property="remarks", type="string", example="Standard rate for 7 days"),
 *     @OA\Property(property="cypa_id", type="integer", example=0, description="0 = all CYPA"),
 *     @OA\Property(property="stack_run", type="number", format="float", example=1000.00, description="Stack run amount"),
 *     @OA\Property(property="container_size", type="string", example="20ft"),
 *     @OA\Property(property="rate", type="number", format="float", example=5000.00, description="Rate amount"),
 *     @OA\Property(property="tax_percent", type="number", format="float", example=12.00, nullable=true, description="Tax percentage (for SOA)"),
 *     @OA\Property(property="is_active", type="integer", example=1)
 * )
 */
class RatePerClientController extends BaseController
{
    public function __construct(RatePerClientService $ratePerClientService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($ratePerClientService, $messageService);
    }

    /**
     * Display a listing of rate per clients.
     * 
     * @OA\Get(
     *     path="/api/rate-per-clients",
     *     summary="Get list of rate per clients",
     *     tags={"Rate Per Client Management"},
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
     *         description="Search by container size, requirements, remarks, shipping line name, or CYPA name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by is_active (1 for active, 0 for inactive)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="shipping_line_id",
     *         in="query",
     *         description="Filter by shipping line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="cypa_id",
     *         in="query",
     *         description="Filter by CYPA ID (0 = all)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="container_size",
     *         in="query",
     *         description="Filter by container size",
     *         @OA\Schema(type="string", example="20ft")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of rate per clients retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatePerClient")),
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
        $request = request();
        $perPage = $request->get('per_page', 10);

        return $this->service->list($perPage);
    }

    /**
     * Display the specified rate per client.
     * 
     * @OA\Get(
     *     path="/api/rate-per-clients/{id}",
     *     summary="Get a specific rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rate Per Client ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per client retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/RatePerClient")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate per client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rate per client not found.")
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
        try {
            $ratePerClient = $this->service->show($id);
            return response($ratePerClient, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Rate per client not found.',
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/rate-per-clients",
     *     summary="Create a new rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePerClientInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rate per client created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/RatePerClient")
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
    public function store(RatePerClientRequest $request)
    {
        try {
            $data = $request->validated();
            $ratePerClient = $this->service->store($data);
            return response($ratePerClient, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/rate-per-clients/{id}",
     *     summary="Update a rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rate Per Client ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePerClientInput")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per client updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/RatePerClient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate per client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rate per client not found.")
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
    public function update(RatePerClientRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $ratePerClient = $this->service->update($data, $id);
            return response($ratePerClient, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Rate per client not found.',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/rate-per-clients/{id}",
     *     summary="Delete a rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rate Per Client ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per client moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate per client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rate per client not found.")
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
     * Bulk delete multiple rate per clients.
     * 
     * @OA\Post(
     *     path="/api/rate-per-clients/bulk/delete",
     *     summary="Bulk delete multiple rate per clients",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of rate per client IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per clients deleted successfully",
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
     * Get trashed rate per clients.
     * 
     * @OA\Get(
     *     path="/api/archived/rate-per-clients",
     *     summary="Get trashed rate per clients",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Trashed rate per clients retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatePerClient"))
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
     * Restore a trashed rate per client.
     * 
     * @OA\Patch(
     *     path="/api/archived/rate-per-clients/restore/{id}",
     *     summary="Restore a trashed rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rate Per Client ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per client restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object", ref="#/components/schemas/RatePerClient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate per client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rate per client not found.")
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
     * Bulk restore multiple trashed rate per clients.
     * 
     * @OA\Post(
     *     path="/api/rate-per-clients/bulk/restore",
     *     summary="Bulk restore multiple trashed rate per clients",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of rate per client IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per clients restored successfully",
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
     * Permanently delete a rate per client.
     * 
     * @OA\Delete(
     *     path="/api/archived/rate-per-clients/{id}",
     *     summary="Permanently delete a rate per client",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rate Per Client ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per client permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate per client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rate per client not found.")
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
     * Bulk permanently delete multiple rate per clients.
     * 
     * @OA\Post(
     *     path="/api/rate-per-clients/bulk/force-delete",
     *     summary="Bulk permanently delete multiple rate per clients",
     *     tags={"Rate Per Client Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of rate per client IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate per clients permanently deleted successfully",
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
}

