<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GenerateSoaRequest;
use App\Services\StatementOfAccountService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Statement of Accounts",
 *     description="API endpoints for managing statement of accounts"
 * )
 * @OA\Schema(
 *     schema="StatementOfAccount",
 *     title="Statement of Account Model",
 *     description="A statement of account resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="soa_coverage_from", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="soa_coverage_to", type="string", format="date", example="2024-01-31"),
 *     @OA\Property(property="waybill_id", type="array", @OA\Items(type="integer"), example={1, 2, 3}),
 *     @OA\Property(property="signature", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="GenerateSoaInput",
 *     title="Generate SOA Input",
 *     description="Data required to generate a statement of account",
 *     required={"shipping_line_id", "dli_sa_number", "soa_coverage_from", "soa_coverage_to"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="soa_coverage_from", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="soa_coverage_to", type="string", format="date", example="2024-01-31"),
 *     @OA\Property(property="waybill_id", type="array", @OA\Items(type="integer"), example={1, 2, 3}),
 *     @OA\Property(property="signature", type="boolean", example=false)
 * )
 */
class StatementOfAccountController extends BaseController
{
    public function __construct(StatementOfAccountService $soaService, MessageService $messageService)
    {
        parent::__construct($soaService, $messageService);
    }

    /**
     * Display a listing of statement of accounts.
     * 
     * @OA\Get(
     *     path="/api/statement-of-accounts",
     *     summary="Get list of statement of accounts",
     *     tags={"Statement of Accounts"},
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
     *         description="Search by DLI SA number or shipping line name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of statement of accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/StatementOfAccount")),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="links", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        try {
            $request = request();
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');

            return $this->service->list($perPage, false);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Generate a new statement of account.
     * 
     * @OA\Post(
     *     path="/api/statement-of-accounts/generate",
     *     summary="Generate a new statement of account",
     *     tags={"Statement of Accounts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Statement of account data to generate",
     *         @OA\JsonContent(ref="#/components/schemas/GenerateSoaInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Statement of account generated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/StatementOfAccount")
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function generate(GenerateSoaRequest $request)
    {
        try {
            $data = $request->validated();

            // Set default signature to false if not provided
            if (!isset($data['signature'])) {
                $data['signature'] = false;
            }

            $soa = $this->service->generate($data);

            return response($soa, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified statement of account.
     * 
     * @OA\Get(
     *     path="/api/statement-of-accounts/{id}",
     *     summary="Get a specific statement of account",
     *     tags={"Statement of Accounts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the statement of account",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statement of account retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/StatementOfAccount")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function show($id)
    {
        return parent::show($id);
    }
}






