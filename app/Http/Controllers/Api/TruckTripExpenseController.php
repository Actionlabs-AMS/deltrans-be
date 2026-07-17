<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TruckTripExpenseRequest;
use App\Services\TruckTripExpenseService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Truck Trip Expense",
 *     description="API endpoints for truck trip expense management"
 * )
 * @OA\Schema(
 *     schema="TruckTripExpense",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shift", type="string", nullable=true),
 *     @OA\Property(property="plate_number", type="string", nullable=true, description="Truck plate number"),
 *     @OA\Property(property="helper_id", type="integer", nullable=true),
 *     @OA\Property(property="helper_name", type="string", nullable=true),
 *     @OA\Property(property="cash_on_hand", type="number", format="float"),
 *     @OA\Property(property="issued_cash_amount", type="number", format="float"),
 *     @OA\Property(property="remaining_amount", type="number", format="float", readOnly=true),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="TruckTripExpenseInput",
 *     required={"transaction_date"},
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"}),
 *     @OA\Property(property="plate_number", type="string", nullable=true, description="Truck plate number"),
 *     @OA\Property(property="helper_id", type="integer"),
 *     @OA\Property(property="cash_on_hand", type="number", format="float"),
 *     @OA\Property(property="issued_cash_amount", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date")
 * )
 */
class TruckTripExpenseController extends BaseController
{
    public function __construct(TruckTripExpenseService $service, MessageService $messageService)
    {
        parent::__construct($service, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/truck-trip-expense",
     *     summary="List truck trip expenses",
     *     tags={"Truck Trip Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="plate_number", in="query", description="Filter by truck plate number", @OA\Schema(type="string")),
     *     @OA\Parameter(name="helper_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="transaction_date_from", in="query", description="Transaction date range start", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", description="Transaction date range end", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TruckTripExpense"))))
     * )
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        return $this->service->list($perPage);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/truck-trip-expense/latest",
     *     summary="Get latest truck trip expense by plate number and helper",
     *     description="Returns the most recent truck trip expense for the given plate_number and helper_id. remaining_amount is kept in sync when waybills are created, updated, or deleted.",
     *     tags={"Truck Trip Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="plate_number", in="query", required=true, description="Truck plate number", @OA\Schema(type="string")),
     *     @OA\Parameter(name="helper_id", in="query", required=true, description="Helper ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TruckTripExpense"))),
     *     @OA\Response(response=404, description="No truck trip expense found for the given plate_number and helper_id"),
     *     @OA\Response(response=422, description="Validation error (missing or invalid plate_number or helper_id)")
     * )
     */
    public function latest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => ['required', 'string', 'max:255'],
            'helper_id' => ['required', 'integer', 'exists:helpers,id'],
        ], [
            'plate_number.required' => 'Plate number is required.',
            'helper_id.required' => 'Helper ID is required.',
            'helper_id.exists' => 'The selected helper does not exist.',
        ]);

        $resource = $this->service->getLatestByPlateAndHelper(
            $validated['plate_number'],
            (int) $validated['helper_id']
        );

        if ($resource === null) {
            return response()->json([
                'message' => 'No truck trip expense found for the given plate number and helper.',
            ], 404);
        }

        return response()->json(['data' => $resource]);
    }

    /**
     * @OA\Get(
     *     path="/api/budget/truck-trip-expense/{id}",
     *     summary="Get truck trip expense by ID",
     *     tags={"Truck Trip Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/TruckTripExpense")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Truck trip expense not found.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/budget/truck-trip-expense",
     *     summary="Create truck trip expense",
     *     tags={"Truck Trip Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TruckTripExpenseInput")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TruckTripExpense"))),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(TruckTripExpenseRequest $request)
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
     *     path="/api/budget/truck-trip-expense/{id}",
     *     summary="Update truck trip expense",
     *     tags={"Truck Trip Expense"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TruckTripExpenseInput")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TruckTripExpense"))),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(TruckTripExpenseRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $item = $this->service->update($data, $id);
            return response($item, 200);
        } catch (\Exception $e) {
            return response()->json(['status_code' => 404, 'message' => 'Truck trip expense not found.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/budget/truck-trip-expense/{id}",
     *     summary="Delete truck trip expense (soft)",
     *     tags={"Truck Trip Expense"},
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
