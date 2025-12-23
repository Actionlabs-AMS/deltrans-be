<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StackRunRequest;
use App\Services\StackRunService;
use App\Services\MessageService;
use App\Models\Container;
use App\Models\StackRun;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Stack Run Management",
 *     description="API endpoints for stack run management"
 * )
 */
class StackRunController extends BaseController
{
    public function __construct(StackRunService $stackRunService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($stackRunService, $messageService);
    }

    /**
     * Display a listing of stack runs.
     * 
     * @OA\Get(
     *     path="/api/stack-runs",
     *     summary="Get list of stack runs",
     *     tags={"Stack Run Management"},
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
     *         description="Search by container size, shipping line name, or CYPA name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of stack runs retrieved successfully",
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
     * Display the specified stack run.
     * 
     * @OA\Get(
     *     path="/api/stack-runs/{id}",
     *     summary="Get a specific stack run",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stack run retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stack run not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Stack run not found.")
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
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/stack-runs",
     *     summary="Create a new stack run",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_line_id", "cypa_id_from", "cypa_id_to", "quantity_of_container", "container_size"},
     *             @OA\Property(property="reference_number", type="string", example="SR-001", description="Reference number (optional)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="quantity_of_container", type="integer", example=2, description="Quantity of containers"),
     *             @OA\Property(property="container_size", type="string", example="20ft", description="Container size (20ft or 40ft)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stack run created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="SR-001", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="quantity_of_container", type="integer", example=2),
     *                 @OA\Property(property="container_size", type="string", example="20ft"),
     *                 @OA\Property(property="total_amount", type="number", example=0),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01 12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01 12:00:00")
     *             )
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
    public function store(StackRunRequest $request)
    {
        try {
            $data = $request->all();
            $stackRun = $this->service->store($data);
            return response($stackRun, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified stack run in storage.
     * 
     * @OA\Put(
     *     path="/api/stack-runs/{id}",
     *     summary="Update a stack run",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reference_number", type="string", example="SR-001", description="Reference number (optional)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="quantity_of_container", type="integer", example=2, description="Quantity of containers"),
     *             @OA\Property(property="container_size", type="string", example="20ft", description="Container size (20ft or 40ft)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stack run updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="SR-001", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="quantity_of_container", type="integer", example=2),
     *                 @OA\Property(property="container_size", type="string", example="20ft"),
     *                 @OA\Property(property="total_amount", type="number", example=0),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01 12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stack run not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Stack run not found.")
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
    public function update(StackRunRequest $request, $id)
    {
        try {
            $data = $request->all();
            $stackRun = $this->service->update($data, $id);
            return response($stackRun, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stack run not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Add a container to a stack run.
     * 
     * @OA\Post(
     *     path="/api/stack-runs/{stackRunId}/containers",
     *     summary="Add a container to a stack run",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="stackRunId",
     *         in="path",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"container_number"},
     *             @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Container added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container added successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stack run not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Stack run not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function addContainer(Request $request, $stackRunId)
    {
        try {
            // Validate stack run exists
            $stackRun = StackRun::findOrFail($stackRunId);

            // Validate request data
            $validator = Validator::make($request->all(), [
                'container_number' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create container - waybill_number will be null initially, can be updated later
            $container = Container::create([
                'container_number' => $request->container_number,
                'stack_run_id' => $stackRunId,
                'waybill_number' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Container added successfully',
                'data' => $container
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stack run not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update a container.
     * 
     * @OA\Put(
     *     path="/api/stack-runs/{stackRunId}/containers/{containerId}",
     *     summary="Update a container",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="stackRunId",
     *         in="path",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="containerId",
     *         in="path",
     *         required=true,
     *         description="Container ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container or stack run not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Container not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateContainer(Request $request, $stackRunId, $containerId)
    {
        try {
            // Validate stack run exists
            $stackRun = StackRun::findOrFail($stackRunId);

            // Validate container exists and belongs to stack run
            $container = Container::where('id', $containerId)
                ->where('stack_run_id', $stackRunId)
                ->firstOrFail();

            // Validate request data - only allow container_number (waybill_number will be updated by other APIs)
            $validator = Validator::make($request->all(), [
                'container_number' => 'sometimes|required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Remove waybill_number if provided (not allowed in this API)
            $data = $request->only(['container_number']);
            unset($data['waybill_number']);

            // Update container - only allow container_number
            $container->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Container updated successfully',
                'data' => $container->fresh()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container or stack run not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Delete a container.
     * 
     * @OA\Delete(
     *     path="/api/stack-runs/{stackRunId}/containers/{containerId}",
     *     summary="Delete a container",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="stackRunId",
     *         in="path",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="containerId",
     *         in="path",
     *         required=true,
     *         description="Container ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container or stack run not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Container not found.")
     *         )
     *     )
     * )
     */
    public function deleteContainer($stackRunId, $containerId)
    {
        try {
            // Validate stack run exists
            $stackRun = StackRun::findOrFail($stackRunId);

            // Validate container exists and belongs to stack run
            $container = Container::where('id', $containerId)
                ->where('stack_run_id', $stackRunId)
                ->firstOrFail();

            // Hard delete the container
            $container->delete();

            return response()->json([
                'success' => true,
                'message' => 'Container deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container or stack run not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get containers based on stack_run_id and optionally waybill_number.
     * 
     * @OA\Get(
     *     path="/api/stack-runs/containers",
     *     summary="Get containers by stack_run_id and optionally waybill_number",
     *     tags={"Stack Run Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="stack_run_id",
     *         in="query",
     *         required=true,
     *         description="Stack Run ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="waybill_number",
     *         in="query",
     *         required=false,
     *         description="Waybill number (optional - if provided, filters by both stack_run_id and waybill_number)",
     *         @OA\Schema(type="string", example="WB-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Containers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function getContainers(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'stack_run_id' => 'required|integer|exists:stack_runs,id',
                'waybill_number' => 'nullable|string|max:255|exists:waybill_details,waybill_number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Container::where('stack_run_id', $request->stack_run_id);

            // If waybill_number is provided, filter by both stack_run_id and waybill_number
            if ($request->has('waybill_number') && $request->waybill_number) {
                $query->where('waybill_number', $request->waybill_number);
            }

            $containers = $query->get();

            return response()->json([
                'success' => true,
                'data' => $containers
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}


