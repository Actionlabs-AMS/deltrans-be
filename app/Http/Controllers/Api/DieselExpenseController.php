<?php

namespace App\Http\Controllers\Api;

use App\Services\DieselExpenseService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Diesel Expense",
 *     description="API endpoints for diesel expense management"
 * )
 * @OA\Schema(
 *     schema="DieselExpense",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=1500.00),
 *     @OA\Property(property="purchase_order", type="string", example="PO-2025-001", nullable=true),
 *     @OA\Property(property="waybill_detail_id", type="integer", example=101, nullable=true),
 *     @OA\Property(property="waybill_number", type="string", example="WB-001", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class DieselExpenseController extends BaseController
{
    public function __construct(DieselExpenseService $service, MessageService $messageService)
    {
        parent::__construct($service, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/diesel-expense",
     *     summary="List diesel expenses",
     *     tags={"Diesel Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", description="Search by amount or waybill number", @OA\Schema(type="string")),
     *     @OA\Parameter(name="amount", in="query", description="Exact amount", @OA\Schema(type="number", format="float", example=1500)),
     *     @OA\Parameter(name="amount_min", in="query", description="Minimum amount", @OA\Schema(type="number", format="float", example=1000)),
     *     @OA\Parameter(name="amount_max", in="query", description="Maximum amount", @OA\Schema(type="number", format="float", example=5000)),
     *     @OA\Parameter(name="waybill_number", in="query", description="Filter by related waybill number", @OA\Schema(type="string", example="WB-001")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DieselExpense"))))
     * )
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        return $this->service->list($perPage);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/diesel-expense/{id}",
     *     summary="Get diesel expense by ID",
     *     tags={"Diesel Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/DieselExpense")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Diesel expense not found.'], 404);
        }
    }
}
