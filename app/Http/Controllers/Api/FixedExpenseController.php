<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\FixedExpenseRequest;
use App\Services\FixedExpenseService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Fixed Expense Management",
 *     description="API endpoints for fixed expense management"
 * )
 * @OA\Schema(
 *     schema="FixedExpense",
 *     title="Fixed Expense Model",
 *     description="A fixed expense resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="cypa_id_from", type="integer", example=1),
 *     @OA\Property(property="cypa_id_to", type="integer", example=2),
 *     @OA\Property(property="container_size", type="string", example="20ft"),
 *     @OA\Property(property="docs_fee", type="number", format="float", example=500.00, description="Documentation fee amount"),
 *     @OA\Property(property="online_booking_fee", type="number", format="float", example=100.00, description="Online booking fee amount"),
 *     @OA\Property(property="stack_run", type="number", format="float", example=1500.00, description="Stack run amount"),
 *     @OA\Property(property="expenses", type="number", format="float", example=2000.00, description="Other expenses amount"),
 *     @OA\Property(property="total_expenses", type="number", format="float", example=4100.00, description="Total expenses (auto-calculated: docs_fee + online_booking_fee + stack_run + expenses)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="FixedExpenseInput",
 *     title="Fixed Expense Input",
 *     description="Data required to create or update a fixed expense",
 *     required={"shipping_line_id", "cypa_id_from", "cypa_id_to", "container_size"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="cypa_id_from", type="integer", example=1),
 *     @OA\Property(property="cypa_id_to", type="integer", example=2),
 *     @OA\Property(property="container_size", type="string", example="20ft"),
 *     @OA\Property(property="docs_fee", type="number", format="float", example=500.00),
 *     @OA\Property(property="online_booking_fee", type="number", format="float", example=100.00),
 *     @OA\Property(property="stack_run", type="number", format="float", example=1500.00),
 *     @OA\Property(property="expenses", type="number", format="float", example=2000.00)
 * )
 */
class FixedExpenseController extends BaseController
{
    public function __construct(FixedExpenseService $fixedExpenseService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($fixedExpenseService, $messageService);
    }

    /**
     * Display a listing of fixed expenses.
     * 
     * @OA\Get(
     *     path="/api/fixed-expenses",
     *     summary="Get list of fixed expenses",
     *     tags={"Fixed Expense Management"},
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
     *     @OA\Parameter(
     *         name="shipping_line_id",
     *         in="query",
     *         description="Filter by shipping line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="cypa_id_from",
     *         in="query",
     *         description="Filter by CYPA ID (from)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="cypa_id_to",
     *         in="query",
     *         description="Filter by CYPA ID (to)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="container_size",
     *         in="query",
     *         description="Filter by container size",
     *         @OA\Schema(type="string", example="20ft")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of fixed expenses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FixedExpense")),
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
     * Display the specified fixed expense.
     * 
     * @OA\Get(
     *     path="/api/fixed-expenses/{id}",
     *     summary="Get a specific fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fixed Expense ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expense retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/FixedExpense")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fixed expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixed expense not found.")
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
            $fixedExpense = $this->service->show($id);
            return response($fixedExpense, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Fixed expense not found.',
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/fixed-expenses",
     *     summary="Create a new fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FixedExpenseInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fixed expense created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/FixedExpense")
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
    public function store(FixedExpenseRequest $request)
    {
        try {
            $data = $request->validated();
            $fixedExpense = $this->service->store($data);
            return response($fixedExpense, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/fixed-expenses/{id}",
     *     summary="Update a fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fixed Expense ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FixedExpenseInput")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expense updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/FixedExpense")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fixed expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixed expense not found.")
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
    public function update(FixedExpenseRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $fixedExpense = $this->service->update($data, $id);
            return response($fixedExpense, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Fixed expense not found.',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/fixed-expenses/{id}",
     *     summary="Delete a fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fixed Expense ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expense moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fixed expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixed expense not found.")
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
     * Bulk delete multiple fixed expenses.
     * 
     * @OA\Post(
     *     path="/api/fixed-expenses/bulk/delete",
     *     summary="Bulk delete multiple fixed expenses",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of fixed expense IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expenses deleted successfully",
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
     * Get trashed fixed expenses.
     * 
     * @OA\Get(
     *     path="/api/archived/fixed-expenses",
     *     summary="Get trashed fixed expenses",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Trashed fixed expenses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FixedExpense"))
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
     * Restore a trashed fixed expense.
     * 
     * @OA\Patch(
     *     path="/api/archived/fixed-expenses/restore/{id}",
     *     summary="Restore a trashed fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fixed Expense ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expense restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object", ref="#/components/schemas/FixedExpense")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fixed expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixed expense not found.")
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
     * Bulk restore multiple trashed fixed expenses.
     * 
     * @OA\Post(
     *     path="/api/fixed-expenses/bulk/restore",
     *     summary="Bulk restore multiple trashed fixed expenses",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of fixed expense IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expenses restored successfully",
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
     * Permanently delete a fixed expense.
     * 
     * @OA\Delete(
     *     path="/api/archived/fixed-expenses/{id}",
     *     summary="Permanently delete a fixed expense",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fixed Expense ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expense permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fixed expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixed expense not found.")
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
     * Bulk permanently delete multiple fixed expenses.
     * 
     * @OA\Post(
     *     path="/api/fixed-expenses/bulk/force-delete",
     *     summary="Bulk permanently delete multiple fixed expenses",
     *     tags={"Fixed Expense Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of fixed expense IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixed expenses permanently deleted successfully",
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

