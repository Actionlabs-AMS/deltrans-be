<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SoaAndBillingRequest;
use App\Http\Requests\GenerateSoaAndBillingRequest;
use App\Http\Requests\UpdateSoaRequest;
use App\Http\Requests\BillingStatementRequest;
use App\Http\Requests\UpdateBillingStatementRequest;
use App\Http\Requests\StoreTempAttachmentsRequest;
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
 *     @OA\Property(property="statement_of_account_id", type="integer", example=1, description="Related statement of account ID"),
 *     @OA\Property(property="statement_of_account", type="object", nullable=true, description="Linked SOA (id, dli_sa_number, work_order, booking_id, shipping_line_id)"),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1, nullable=true, description="From statement_of_accounts.shipping_line_id"),
 *     @OA\Property(property="booking_id", type="integer", example=1, nullable=true, description="From statement_of_accounts.booking_id"),
 *     @OA\Property(property="shipping_line", type="object", nullable=true, description="Shipping line from SOA when loaded"),
 *     @OA\Property(property="booking", type="object", nullable=true, description="Booking from SOA when loaded"),
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
 *     required={"statement_of_account_id", "billing_statement_no"},
 *     @OA\Property(property="statement_of_account_id", type="integer", example=1, description="Statement of account ID"),
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
     * @OA\Put(
     *     path="/api/soa/{id}",
     *     summary="Update a statement of account",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="shipping_line_id", type="integer", example=1),
     *         @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
     *         @OA\Property(property="booking_id", type="integer", example=1),
     *         @OA\Property(property="work_order", type="string", example="WO-001", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Statement of account updated", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/soa_and_billing")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function update(UpdateSoaRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $soa = $this->service->updateSoa($id, $data);
            return response($soa, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Statement of account not found.' ? 404 : 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/soa/{id}/download",
     *     summary="Download Statement of Account PDF",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages (upload via POST /soa-and-billing/attachments); folder is deleted after download", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function download($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;

            $pdfOutput = $this->service->generatePdf($id, $attachmentUserId, $includeAttachments);

            $soa = StatementOfAccount::findOrFail($id);
            $downloadName = $soa->dli_sa_number . '.pdf';

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
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
     *     @OA\Parameter(name="statement_of_account_id", in="query", description="Filter by statement of account ID", @OA\Schema(type="integer")),
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
     * @OA\Put(
     *     path="/api/billing-statements/{id}",
     *     summary="Update a billing statement",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="statement_of_account_id", type="integer", example=1),
     *         @OA\Property(property="billing_statement_no", type="string", example="BS-2024-001"),
     *         @OA\Property(property="prepared_by", type="integer", nullable=true),
     *         @OA\Property(property="payment_term", type="string", nullable=true),
     *         @OA\Property(property="ci_date", type="string", format="date", nullable=true),
     *         @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *         @OA\Property(property="bus_style", type="string", nullable=true),
     *         @OA\Property(property="has_details", type="boolean", nullable=true),
     *         @OA\Property(property="is_paid", type="boolean", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Billing statement updated", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/BillingStatement")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsUpdate(UpdateBillingStatementRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $billingStatement = $this->service->updateBillingStatement($id, $data);
            return response($billingStatement, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Billing statement not found.' ? 404 : 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/billing-statements/{id}/download",
     *     summary="Download Billing Statement PDF",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Billing Statement ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages; folder is deleted after download", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function billingStatementsDownload($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;

            $pdfOutput = $this->service->generateBillingStatementPdf($id, $attachmentUserId, $includeAttachments);

            $billingStatement = \App\Models\BillingStatement::findOrFail($id);
            $downloadName = $billingStatement->billing_statement_no . '.pdf';

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
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

    /**
     * @OA\Post(
     *     path="/api/soa-and-billing/generate",
     *     summary="Generate SOA and Billing Statement in one request",
     *     description="Combined endpoint: creates Statement of Account first, then Billing Statement linked to it. One request body with SOA + Billing fields.",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"shipping_line_id", "dli_sa_number", "booking_id", "billing_statement_no"},
     *         @OA\Property(property="shipping_line_id", type="integer", example=1),
     *         @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
     *         @OA\Property(property="booking_id", type="integer", example=1),
     *         @OA\Property(property="work_order", type="string", example="WO-001", nullable=true),
     *         @OA\Property(property="billing_statement_no", type="string", example="BS-2024-001"),
     *         @OA\Property(property="prepared_by", type="integer", nullable=true),
     *         @OA\Property(property="payment_term", type="string", nullable=true),
     *         @OA\Property(property="ci_date", type="string", format="date", nullable=true),
     *         @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *         @OA\Property(property="bus_style", type="string", nullable=true),
     *         @OA\Property(property="has_details", type="boolean", nullable=true),
     *         @OA\Property(property="is_paid", type="boolean", nullable=true)
     *     )),
     *     @OA\Response(response=201, description="SOA and Billing created", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="soa", ref="#/components/schemas/soa_and_billing"),
     *             @OA\Property(property="billing", ref="#/components/schemas/BillingStatement")
     *         )
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function generateSoaAndBilling(GenerateSoaAndBillingRequest $request)
    {
        try {
            $data = $request->validated();
            $result = $this->service->generateSoaAndBilling($data);
            return response()->json([
                'data' => [
                    'soa' => $result['soa'],
                    'billing' => $result['billing'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/soa-and-billing/{id}/download",
     *     summary="Download Billing Statement + SOA (2-page PDF)",
     *     description="Returns a single PDF: Page 1 = Billing Statement, Page 2 = Statement of Account. {id} = billing_statement_id. Use include_attachments=true to append the user's uploaded images as extra pages.",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Billing Statement ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages; folder is deleted after download", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(response=200, description="PDF file download (2 pages: Billing then SOA)", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function downloadBillingAndSoa($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;

            $pdfOutput = $this->service->generateBillingAndSoaCombinedPdf($id, $attachmentUserId, $includeAttachments);

            $billingStatement = \App\Models\BillingStatement::with('statementOfAccount')->findOrFail($id);
            $soa = $billingStatement->statementOfAccount;
            $downloadName = ($billingStatement->billing_statement_no ?? 'billing') . '_' . ($soa->dli_sa_number ?? 'soa') . '.pdf';

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
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

    /**
     * Upload temp images for SOA/Billing PDF attachments. Use include_attachments=true on download to include them.
     *
     * @OA\Post(
     *     path="/api/soa-and-billing/attachments",
     *     summary="Upload temp attachment images",
     *     description="Upload one or more images to be optionally included as extra pages in SOA/Billing PDF downloads. One folder per user; each new upload replaces the previous. Use include_attachments=true on download to include them; folder is deleted after that download.",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *         required={"images[]"},
     *         @OA\Property(property="images[]", type="array", @OA\Items(type="string", format="binary"), description="Image files (jpeg, png, jpg, gif, webp; max 5MB each; max 10 files). Use field name 'images[]' so multiple files are received as an array.")
     *     ))),
     *     @OA\Response(response=200, description="Upload result", @OA\JsonContent(
     *         @OA\Property(property="expires_at", type="string", format="date-time"),
     *         @OA\Property(property="count", type="integer", example=2)
     *     )),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function storeAttachments(StoreTempAttachmentsRequest $request)
    {
        try {
            $userId = auth()->id();
            $files = $request->file('images');
            $files = is_array($files) ? $files : ($files ? [$files] : []);
            $count = $this->service->storeTempAttachmentsForUser($userId, $files);

            $expiresAt = now()->addHours(2)->toIso8601String();

            return response()->json([
                'expires_at' => $expiresAt,
                'count' => $count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/soa/{id}/send-email",
     *     summary="Send SOA PDF via email",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="SOA ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages", @OA\Schema(type="boolean", default=false)),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="email", type="string", format="email", nullable=true, description="Custom recipient email (overrides shipping line email)"),
     *         @OA\Property(property="cc", type="array", @OA\Items(type="string", format="email"), nullable=true, description="CC recipients")
     *     )),
     *     @OA\Response(response=200, description="Email sent successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="SOA email sent successfully")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function sendSoaEmail($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;
            $customEmail = request()->input('email');
            $cc = request()->input('cc', []);

            $this->service->sendSoaEmail($id, $attachmentUserId, $includeAttachments, $customEmail, $cc);

            return response()->json([
                'success' => true,
                'message' => 'SOA email sent successfully',
            ], 200);
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
     * @OA\Post(
     *     path="/api/billing-statements/{id}/send-email",
     *     summary="Send Billing Statement PDF via email",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Billing Statement ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages", @OA\Schema(type="boolean", default=false)),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="email", type="string", format="email", nullable=true, description="Custom recipient email (overrides shipping line email)"),
     *         @OA\Property(property="cc", type="array", @OA\Items(type="string", format="email"), nullable=true, description="CC recipients")
     *     )),
     *     @OA\Response(response=200, description="Email sent successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Billing Statement email sent successfully")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function sendBillingStatementEmail($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;
            $customEmail = request()->input('email');
            $cc = request()->input('cc', []);

            $this->service->sendBillingStatementEmail($id, $attachmentUserId, $includeAttachments, $customEmail, $cc);

            return response()->json([
                'success' => true,
                'message' => 'Billing Statement email sent successfully',
            ], 200);
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

    /**
     * @OA\Post(
     *     path="/api/soa-and-billing/{id}/send-email",
     *     summary="Send Combined Billing Statement + SOA PDF via email",
     *     tags={"SOA and Billing Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Billing Statement ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages", @OA\Schema(type="boolean", default=false)),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="email", type="string", format="email", nullable=true, description="Custom recipient email (overrides shipping line email)"),
     *         @OA\Property(property="cc", type="array", @OA\Items(type="string", format="email"), nullable=true, description="CC recipients")
     *     )),
     *     @OA\Response(response=200, description="Email sent successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Combined Billing Statement & SOA email sent successfully")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function sendBillingAndSoaEmail($id)
    {
        try {
            $includeAttachments = filter_var(request()->query('include_attachments'), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;
            $customEmail = request()->input('email');
            $cc = request()->input('cc', []);

            $this->service->sendBillingAndSoaEmail($id, $attachmentUserId, $includeAttachments, $customEmail, $cc);

            return response()->json([
                'success' => true,
                'message' => 'Combined Billing Statement & SOA email sent successfully',
            ], 200);
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
