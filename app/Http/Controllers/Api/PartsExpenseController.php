<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PartsExpenseRequest;
use App\Services\PartsExpenseService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Parts Expense",
 *     description="API endpoints for parts expense management"
 * )
 * @OA\Schema(
 *     schema="PartsExpense",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shift", type="string", nullable=true),
 *     @OA\Property(property="plate_number", type="string", nullable=true),
 *     @OA\Property(property="receipt_no", type="string", nullable=true),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="article", type="string", nullable=true),
 *     @OA\Property(property="amount_per_item", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="PartsExpenseInput",
 *     required={"transaction_date"},
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
 *     @OA\Property(property="plate_number", type="string"),
 *     @OA\Property(property="receipt_no", type="string"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="article", type="string"),
 *     @OA\Property(property="amount_per_item", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date")
 * )
 */
class PartsExpenseController extends BaseController
{
    public function __construct(PartsExpenseService $service, MessageService $messageService)
    {
        parent::__construct($service, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/parts-expense",
     *     summary="List parts expenses",
     *     tags={"Parts Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="plate_number", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="transaction_date_from", in="query", description="Transaction date range start", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", description="Transaction date range end", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PartsExpense"))))
     * )
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        return $this->service->list($perPage);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/parts-expense/{id}",
     *     summary="Get parts expense by ID",
     *     tags={"Parts Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/PartsExpense")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Parts expense not found.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/budget/parts-expense",
     *     summary="Create parts expense",
     *     tags={"Parts Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PartsExpenseInput")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/PartsExpense"))),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(PartsExpenseRequest $request)
    {
        try {
            $data = $request->validated();
            $item = $this->service->store($data);
            return response($item, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/budget/parts-expense/{id}",
     *     summary="Update parts expense",
     *     tags={"Parts Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PartsExpenseInput")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/PartsExpense"))),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(PartsExpenseRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $item = $this->service->update($data, $id);
            return response($item, 200);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Parts expense not found.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/budget/parts-expense/{id}",
     *     summary="Delete parts expense (soft)",
     *     tags={"Parts Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Moved to trash"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
