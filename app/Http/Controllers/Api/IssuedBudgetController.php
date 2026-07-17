<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IssuedBudgetRequest;
use App\Services\IssuedBudgetService;
use App\Services\MessageService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Issued Budget",
 *     description="API endpoints for issued budget management"
 * )
 * @OA\Schema(
 *     schema="IssuedBudget",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shift", type="string", nullable=true, example="Day"),
 *     @OA\Property(property="transaction_date", type="string", format="date", example="2026-01-27"),
 *     @OA\Property(property="amount", type="number", format="float", example=5000.00),
 *     @OA\Property(property="source", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="IssuedBudgetInput",
 *     required={"transaction_date", "amount"},
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="source", type="string")
 * )
 */
class IssuedBudgetController extends BaseController
{
    public function __construct(IssuedBudgetService $service, MessageService $messageService)
    {
        parent::__construct($service, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/issued-budget",
     *     summary="List issued budgets",
     *     tags={"Issued Budget"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="transaction_date_from", in="query", description="Transaction date range start", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", description="Transaction date range end", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/IssuedBudget"))))
     * )
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        return $this->service->list($perPage);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/issued-budget/{id}",
     *     summary="Get issued budget by ID",
     *     tags={"Issued Budget"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/IssuedBudget")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Issued budget not found.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/budget/issued-budget",
     *     summary="Create issued budget",
     *     tags={"Issued Budget"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/IssuedBudgetInput")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/IssuedBudget"))),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(IssuedBudgetRequest $request)
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
     *     path="/api/budget/issued-budget/{id}",
     *     summary="Update issued budget",
     *     tags={"Issued Budget"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/IssuedBudgetInput")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/IssuedBudget"))),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(IssuedBudgetRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $item = $this->service->update($data, $id);
            return response($item, 200);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Issued budget not found.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/budget/issued-budget/{id}",
     *     summary="Delete issued budget (soft)",
     *     tags={"Issued Budget"},
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

    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
    }

    public function getTrashed()
    {
        return parent::getTrashed();
    }

    public function restore($id)
    {
        return parent::restore($id);
    }

    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    public function forceDelete($id)
    {
        return parent::forceDelete($id);
    }

    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }
}
