<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\WaybillDetailRequest;
use App\Services\WaybillDetailService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Waybill Detail Management",
 *     description="API endpoints for waybill detail management"
 * )
 * @OA\Schema(
 *     schema="WaybillDetail",
 *     title="Waybill Detail Model",
 *     description="A waybill detail resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="waybill_number", type="string", example="WB-001"),
 *     @OA\Property(property="transaction_date", type="string", format="date", example="2025-12-22"),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="booking_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="driver_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="helper_id", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="fixed_expense_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="container_size", type="string", example="20ft", nullable=true),
 *     @OA\Property(property="container_type", type="string", example="DRY", nullable=true),
 *     @OA\Property(property="truck_plate_number", type="string", example="NCK-6498", nullable=true),
 *     @OA\Property(property="pickup_date", type="string", format="date", example="2025-12-22", nullable=true),
 *     @OA\Property(property="delivered_date", type="string", format="date", example="2025-12-23", nullable=true),
 *     @OA\Property(property="no_of_days", type="integer", example=1),
 *     @OA\Property(property="requirements", type="string", nullable=true),
 *     @OA\Property(property="remarks", type="string", nullable=true),
 *     @OA\Property(property="rate", type="number", format="float", example=2500.00),
 *     @OA\Property(property="tax_percent", type="number", format="float", nullable=true),
 *     @OA\Property(property="has_vat", type="boolean", example=true),
 *     @OA\Property(property="total_rate_per_client", type="number", format="float", example=2500.00),
 *     @OA\Property(property="stack_run", type="number", format="float", example=100.00, description="From linked fixed expense when loaded"),
 *     @OA\Property(property="docs_fee", type="number", format="float", example=500.00, nullable=true, description="From linked fixed expense"),
 *     @OA\Property(property="online_booking_fee", type="number", format="float", example=100.00, nullable=true, description="From linked fixed expense"),
 *     @OA\Property(property="expenses", type="number", format="float", example=2000.00, nullable=true, description="From linked fixed expense"),
 *     @OA\Property(property="post_expense_amount", type="number", format="float", example=200.00),
 *     @OA\Property(property="total_expense", type="number", format="float", example=4700.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="WaybillDetailInput",
 *     title="Waybill Detail Input",
 *     description="Data required to create or update a waybill detail",
 *     required={"waybill_number", "transaction_date", "shipping_line_id", "booking_id", "driver_id", "container_size", "truck_plate_number", "fixed_expense_id", "pickup_date", "delivered_date", "no_of_days", "stack_run", "rate"},
 *     @OA\Property(property="waybill_number", type="string", example="WB-001"),
 *     @OA\Property(property="transaction_date", type="string", format="date", example="2025-12-22"),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="driver_id", type="integer", example=1),
 *     @OA\Property(property="helper_id", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="fixed_expense_id", type="integer", example=1),
 *     @OA\Property(property="container_size", type="string", example="20ft"),
 *     @OA\Property(property="container_type", type="string", example="DRY", nullable=true),
 *     @OA\Property(property="truck_plate_number", type="string", example="NCK-6498"),
 *     @OA\Property(property="pickup_date", type="string", format="date", example="2025-12-22"),
 *     @OA\Property(property="delivered_date", type="string", format="date", example="2025-12-23"),
 *     @OA\Property(property="no_of_days", type="integer", example=1),
 *     @OA\Property(property="requirements", type="string", nullable=true),
 *     @OA\Property(property="remarks", type="string", nullable=true),
 *     @OA\Property(property="stack_run", type="number", format="float", example=100.00),
 *     @OA\Property(property="rate", type="number", format="float", example=2500.00),
 *     @OA\Property(property="tax_percent", type="number", format="float", nullable=true),
 *     @OA\Property(property="has_vat", type="boolean", example=true, nullable=true),
 *     @OA\Property(property="total_rate_per_client", type="number", format="float", example=2500.00, nullable=true),
 *     @OA\Property(property="post_expense_amount", type="number", format="float", example=200.00),
 *     @OA\Property(property="total_expense", type="number", format="float", example=4700.00, nullable=true)
 * )
 */
class WaybillDetailController extends BaseController
{
    public function __construct(WaybillDetailService $waybillDetailService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($waybillDetailService, $messageService);
    }

    /**
     * Display a listing of waybill details.
     * 
     * @OA\Get(
     *     path="/api/waybill-details",
     *     summary="Get list of waybill details",
     *     tags={"Waybill Detail Management"},
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
     *         description="Search by waybill number, container size, container type, shipping line name, driver name, helper name, or truck plate number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="shipping_line_id",
     *         in="query",
     *         description="Filter by shipping line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="booking_id",
     *         in="query",
     *         description="Filter by booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="driver_id",
     *         in="query",
     *         description="Filter by driver ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="helper_id",
     *         in="query",
     *         description="Filter by helper ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="truck_plate_number",
     *         in="query",
     *         description="Filter by truck plate number",
     *         @OA\Schema(type="string", example="NCK-6498")
     *     ),
     *     @OA\Parameter(
     *         name="container_size",
     *         in="query",
     *         description="Filter by container size",
     *         @OA\Schema(type="string", example="20ft")
     *     ),
     *     @OA\Parameter(
     *         name="container_type",
     *         in="query",
     *         description="Filter by container type",
     *         @OA\Schema(type="string", example="DRY")
     *     ),
     *     @OA\Parameter(
     *         name="transaction_date",
     *         in="query",
     *         description="Filter by transaction date",
     *         @OA\Schema(type="string", format="date", example="2025-12-22")
     *     ),
     *     @OA\Parameter(
     *         name="pickup_date",
     *         in="query",
     *         description="Filter by pickup date",
     *         @OA\Schema(type="string", format="date", example="2025-12-22")
     *     ),
     *     @OA\Parameter(
     *         name="delivered_date",
     *         in="query",
     *         description="Filter by delivered date",
     *         @OA\Schema(type="string", format="date", example="2025-12-23")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of waybill details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WaybillDetail")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="all", type="integer", example=10),
     *                 @OA\Property(property="trashed", type="integer", example=2)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string", example="http://example.com/api/waybill-details?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://example.com/api/waybill-details?page=5"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://example.com/api/waybill-details?page=2")
     *             )
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
     * Display the specified waybill detail.
     * 
     * @OA\Get(
     *     path="/api/waybill-details/{id}",
     *     summary="Get a specific waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Waybill Detail ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill detail retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/WaybillDetail")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Waybill detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Waybill detail not found.")
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
            $waybillDetail = $this->service->show($id);
            return response($waybillDetail, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Waybill detail not found.',
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/waybill-details",
     *     summary="Create a new waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WaybillDetailInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Waybill detail created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/WaybillDetail")
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
    public function store(WaybillDetailRequest $request)
    {
        try {
            $data = $request->validated();
            $waybillDetail = $this->service->store($data);
            return response($waybillDetail, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/waybill-details/{id}",
     *     summary="Update a waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Waybill Detail ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WaybillDetailInput")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill detail updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/WaybillDetail")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Waybill detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Waybill detail not found.")
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
    public function update(WaybillDetailRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $waybillDetail = $this->service->update($data, $id);
            return response($waybillDetail, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Waybill detail not found.',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/waybill-details/{id}",
     *     summary="Delete a waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Waybill Detail ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill detail moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Waybill detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Waybill detail not found.")
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
     * Bulk delete multiple waybill details.
     * 
     * @OA\Post(
     *     path="/api/waybill-details/bulk/delete",
     *     summary="Bulk delete multiple waybill details",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of waybill detail IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill details deleted successfully",
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
     * Get trashed waybill details.
     * 
     * @OA\Get(
     *     path="/api/archived/waybill-details",
     *     summary="Get trashed waybill details",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Trashed waybill details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WaybillDetail"))
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
     * Restore a trashed waybill detail.
     * 
     * @OA\Patch(
     *     path="/api/archived/waybill-details/restore/{id}",
     *     summary="Restore a trashed waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Waybill Detail ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill detail restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object", ref="#/components/schemas/WaybillDetail")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Waybill detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Waybill detail not found.")
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
     * Bulk restore multiple trashed waybill details.
     * 
     * @OA\Post(
     *     path="/api/waybill-details/bulk/restore",
     *     summary="Bulk restore multiple trashed waybill details",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of waybill detail IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill details restored successfully",
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
     * Permanently delete a waybill detail.
     * 
     * @OA\Delete(
     *     path="/api/archived/waybill-details/{id}",
     *     summary="Permanently delete a waybill detail",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Waybill Detail ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill detail permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Waybill detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Waybill detail not found.")
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
     * Bulk permanently delete multiple waybill details.
     * 
     * @OA\Post(
     *     path="/api/waybill-details/bulk/force-delete",
     *     summary="Bulk permanently delete multiple waybill details",
     *     tags={"Waybill Detail Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of waybill detail IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waybill details permanently deleted successfully",
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

