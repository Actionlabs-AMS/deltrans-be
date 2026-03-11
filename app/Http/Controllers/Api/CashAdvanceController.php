<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\CashAdvanceRequest;
use App\Services\CashAdvanceService;
use App\Http\Resources\CashAdvanceResource;

/**
 * @OA\Tag(
 *     name="Cash Advance (Unified)",
 *     description="Unified API for driver and helper cash advancement. type: 1 = driver, 2 = helper. Rule: only one cash advance per (transaction_date + shift) per driver or helper—e.g. helper_id 1 cannot have two Day-shift requests on the same transaction_date. GET: type 0 or null returns both; POST/PATCH/DELETE: type is required."
 * )
 * @OA\Schema(
 *     schema="CashAdvanceRecord",
 *     @OA\Property(property="type", type="integer", description="1=driver, 2=helper"),
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="transaction_date_formatted", type="string"),
 *     @OA\Property(property="shift", type="string"),
 *     @OA\Property(property="driver_id", type="integer", nullable=true),
 *     @OA\Property(property="driver_name", type="string", nullable=true),
 *     @OA\Property(property="helper_id", type="integer", nullable=true),
 *     @OA\Property(property="helper_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="CashAdvanceStoreInput",
 *     required={"type", "requestor_id", "amount", "transaction_date", "shift"},
 *     @OA\Property(property="type", type="integer", enum={1, 2}, description="1=driver, 2=helper (required)"),
 *     @OA\Property(property="requestor_id", type="integer", description="Driver ID when type=1, Helper ID when type=2 (required)"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"})
 * )
 */
class CashAdvanceController
{
    public function __construct(
        protected CashAdvanceService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/cash-advances",
     *     summary="List cash advances",
     *     tags={"Cash Advance (Unified)"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="type", in="query", description="0 or null = both tables, 1 = driver only, 2 = helper only", @OA\Schema(type="integer", enum={0, 1, 2})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="transaction_date_from", in="query", description="Transaction date range start (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="transaction_date_to", in="query", description="Transaction date range end (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="created_at_from", in="query", description="Created at range start (YYYY-MM-DD or datetime)", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="created_at_to", in="query", description="Created at range end (YYYY-MM-DD or datetime)", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CashAdvanceRecord"))))
     * )
     */
    public function index(CashAdvanceRequest $request)
    {
        $request->validated();
        $type = $request->has('type') ? (int) $request->type : null;
        if ($type === 0) {
            $type = null;
        }
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search');
        $transactionDateFrom = $request->get('transaction_date_from');
        $transactionDateTo = $request->get('transaction_date_to');
        $createdAtFrom = $request->get('created_at_from');
        $createdAtTo = $request->get('created_at_to');

        $paginator = $this->service->list($type, $perPage, $search, $transactionDateFrom, $transactionDateTo, $createdAtFrom, $createdAtTo);

        return CashAdvanceResource::collection($paginator)
            ->additional([
                'status_code' => 200,
                'message' => 'Cash advance history fetched successfully.',
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/cash-advances/{id}",
     *     summary="Get a single cash advance by ID (type required in query)",
     *     tags={"Cash Advance (Unified)"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=true, description="1=driver, 2=helper", @OA\Schema(type="integer", enum={1, 2})),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/CashAdvanceRecord")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|integer|in:1,2',
        ]);
        $type = (int) $request->query('type');
        try {
            $data = $this->service->show($type, (int) $id);
            return response()->json(array_merge($data, ['status_code' => 200, 'message' => 'Cash advance fetched successfully.']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status_code' => 404, 'message' => 'Cash advance not found.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cash-advances",
     *     summary="Create a cash advance (type required: 1=driver, 2=helper)",
     *     tags={"Cash Advance (Unified)"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CashAdvanceStoreInput")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CashAdvanceRecord"))),
     *     @OA\Response(response=422, description="Validation error (e.g. duplicate: same driver/helper already has a cash advance for this shift on this transaction_date)")
     * )
     */
    public function store(CashAdvanceRequest $request)
    {
        $data = $request->validated();
        $record = $this->service->store($data);
        return response()->json([
            'data' => $record,
            'status_code' => 201,
            'message' => 'Cash advance created successfully.',
        ], 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/cash-advances/{id}",
     *     summary="Update a cash advance (type required: 1=driver, 2=helper)",
     *     tags={"Cash Advance (Unified)"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="integer", enum={1, 2})),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="type", type="integer", enum={1, 2}),
     *         @OA\Property(property="amount", type="number"),
     *         @OA\Property(property="transaction_date", type="string", format="date"),
     *         @OA\Property(property="shift", type="string", enum={"Day", "Night", "1st", "2nd"})
     *     )),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CashAdvanceRecord"))),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(CashAdvanceRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $record = $this->service->update($data, (int) $id);
            return response()->json([
                'data' => $record,
                'status_code' => 200,
                'message' => 'Cash advance updated successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status_code' => 404, 'message' => 'Cash advance not found.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/cash-advances/{id}",
     *     summary="Delete a cash advance (type required: 1=driver, 2=helper)",
     *     tags={"Cash Advance (Unified)"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="integer", enum={1, 2})),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|integer|in:1,2',
        ]);
        $type = (int) $request->query('type');
        try {
            $this->service->destroy((int) $type, (int) $id);
            return response()->json([
                'status_code' => 200,
                'message' => 'Cash advance deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status_code' => 404, 'message' => 'Cash advance not found.'], 404);
        }
    }
}
