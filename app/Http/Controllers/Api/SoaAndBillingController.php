<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SoaAndBillingRequest;
use App\Http\Requests\BillingStatementRequest;
use App\Services\SoaAndBillingService;
use App\Services\MessageService;
use App\Models\StatementOfAccount;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="SOA and Billing Management",
 *     description="API endpoints for SOA and billing management"
 * )
 * @OA\Schema(
 *     schema="soa_and_billing",
 *     title="SOA and Billing",
 *     description="Statement of account resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="work_order", type="string", example="WO-001", nullable=true),
 *     @OA\Property(property="total_amount", type="number", format="float", example=15000.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="SoaAndBillingGenerateInput",
 *     title="Generate SOA Input",
 *     required={"shipping_line_id", "dli_sa_number", "booking_id"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="work_order", type="string", example="WO-001", nullable=true)
 * )
 * @OA\Schema(
 *     schema="BillingStatement",
 *     title="Billing Statement Model",
 *     description="A billing statement resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="prepared_by", type="integer", example=1, description="User ID who prepared the billing statement"),
 *     @OA\Property(property="billing_statement_no", type="string", example="BS-2024-001"),
 *     @OA\Property(property="payment_term", type="string", example="Net 30", nullable=true),
 *     @OA\Property(property="ci_date", type="string", format="date", example="2024-01-15", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-15", nullable=true),
 *     @OA\Property(property="bus_style", type="string", example="Business Style", nullable=true),
 *     @OA\Property(property="has_details", type="boolean", example=false),
 *     @OA\Property(property="is_paid", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="BillingStatementGenerateInput",
 *     title="Generate Billing Statement Input",
 *     required={"shipping_line_id", "booking_id", "billing_statement_no"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="billing_statement_no", type="string", example="BS-2024-001"),
 *     @OA\Property(property="prepared_by", type="integer", example=1, nullable=true, description="User ID (defaults to authenticated user if not provided)"),
 *     @OA\Property(property="payment_term", type="string", example="Net 30", nullable=true),
 *     @OA\Property(property="ci_date", type="string", format="date", example="2024-01-15", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-15", nullable=true),
 *     @OA\Property(property="bus_style", type="string", example="Business Style", nullable=true),
 *     @OA\Property(property="has_details", type="boolean", example=false, nullable=true),
 *     @OA\Property(property="is_paid", type="boolean", example=false, nullable=true)
 * )
 */
class SoaAndBillingController extends BaseController
{
    public function __construct(SoaAndBillingService $soaService, MessageService $messageService)
    {
        parent::__construct($soaService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/soa",
     *     summary="Get list of statement of accounts",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search by DLI SA number, work order, or shipping line name", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of statement of accounts", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/soa_and_billing")),
     *         @OA\Property(property="meta", type="object"),
     *         @OA\Property(property="links", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        try {
            $perPage = request()->get('per_page', 10);
            return $this->service->list($perPage, false);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/soa/generate",
     *     summary="Generate a new statement of account",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SoaAndBillingGenerateInput")),
     *     @OA\Response(response=201, description="Statement of account generated", @OA\JsonContent(ref="#/components/schemas/soa_and_billing")),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function generate(SoaAndBillingRequest $request)
    {
        try {
            $data = $request->validated();
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
     * @OA\Get(
     *     path="/api/soa/{id}",
     *     summary="Get a specific statement of account",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Statement of account retrieved", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/soa_and_billing")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function show($id)
    {
        try {
            return $this->service->show($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Statement of account not found.' ? 404 : 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/soa/{id}/download",
     *     summary="Download Statement of Account PDF",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function download($id)
    {
        try {
            $filePath = $this->service->generatePdf($id);

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PDF file.',
                ], 500);
            }

            $soa = StatementOfAccount::findOrFail($id);
            $downloadName = $soa->dli_sa_number . '.pdf';

            return Storage::disk('public')->download($filePath, $downloadName, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statement of account not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/billing-statements",
     *     summary="Get list of billing statements",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search by billing statement number, payment term, bus style, or shipping line name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="shipping_line_id", in="query", description="Filter by shipping line ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="booking_id", in="query", description="Filter by booking ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_paid", in="query", description="Filter by payment status (0=unpaid, 1=paid)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of billing statements", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BillingStatement")),
     *         @OA\Property(property="meta", type="object",
     *             @OA\Property(property="all", type="integer", example=10),
     *             @OA\Property(property="trashed", type="integer", example=2)
     *         ),
     *         @OA\Property(property="links", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsIndex()
    {
        try {
            $perPage = request()->get('per_page', 10);
            return $this->service->listBillingStatements($perPage, false);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/billing-statements/generate",
     *     summary="Generate a new billing statement",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BillingStatementGenerateInput")),
     *     @OA\Response(response=201, description="Billing statement generated", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/BillingStatement")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsGenerate(BillingStatementRequest $request)
    {
        try {
            $data = $request->validated();
            $billingStatement = $this->service->generateBillingStatement($data);
            return response($billingStatement, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/billing-statements/{id}",
     *     summary="Get a specific billing statement",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Billing statement retrieved", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/BillingStatement")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsShow($id)
    {
        try {
            return $this->service->showBillingStatement($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Billing statement not found.' ? 404 : 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/billing-statements/{id}/download",
     *     summary="Download Billing Statement PDF",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Billing Statement ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsDownload($id)
    {
        try {
            $filePath = $this->service->generateBillingStatementPdf($id);

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PDF file.',
                ], 500);
            }

            $billingStatement = \App\Models\BillingStatement::findOrFail($id);
            $downloadName = $billingStatement->billing_statement_no . '.pdf';

            return Storage::disk('public')->download($filePath, $downloadName, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Billing statement not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
