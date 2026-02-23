<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\FundsForStackRunRequest;
use App\Services\FundsForStackRunService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Funds For Stack Run",
 *     description="API endpoints for funds for stack run management"
 * )
 * @OA\Schema(
 *     schema="FundsForStackRun",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shift", type="string", nullable=true),
 *     @OA\Property(property="remarks", type="string", nullable=true),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="FundsForStackRunInput",
 *     required={"amount", "transaction_date"},
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
 *     @OA\Property(property="remarks", type="string"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date")
 * )
 */
class FundsForStackRunController extends BaseController
{
    public function __construct(FundsForStackRunService $service, MessageService $messageService)
    {
        parent::__construct($service, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/funds-for-stack-run",
     *     summary="List funds for stack run",
     *     tags={"Funds For Stack Run"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="transaction_date_from", in="query", description="Transaction date range start", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", description="Transaction date range end", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FundsForStackRun"))))
     * )
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        return $this->service->list($perPage);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/funds-for-stack-run/{id}",
     *     summary="Get funds for stack run by ID",
     *     tags={"Funds For Stack Run"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/FundsForStackRun")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Funds for stack run not found.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/budget/funds-for-stack-run",
     *     summary="Create funds for stack run",
     *     tags={"Funds For Stack Run"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/FundsForStackRunInput")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/FundsForStackRun"))),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(FundsForStackRunRequest $request)
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
     *     path="/api/budget/funds-for-stack-run/{id}",
     *     summary="Update funds for stack run",
     *     tags={"Funds For Stack Run"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/FundsForStackRunInput")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/FundsForStackRun"))),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(FundsForStackRunRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $item = $this->service->update($data, $id);
            return response($item, 200);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Funds for stack run not found.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/budget/funds-for-stack-run/{id}",
     *     summary="Delete funds for stack run (soft)",
     *     tags={"Funds For Stack Run"},
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
