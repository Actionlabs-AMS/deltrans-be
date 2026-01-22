<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GenerateSoaRequest;
use App\Services\StatementOfAccountService;
use App\Services\MessageService;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Statement of Accounts",
 *     description="API endpoints for managing statement of accounts"
 * )
 * @OA\Schema(
 *     schema="ShippingLine",
 *     title="Shipping Line Model",
 *     description="A shipping line resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Maersk Line"),
 *     @OA\Property(property="email_address", type="string", example="contact@maersk.com"),
 *     @OA\Property(property="address", type="string", example="123 Shipping St", nullable=true),
 *     @OA\Property(property="contact_name", type="string", example="John Doe", nullable=true),
 *     @OA\Property(property="contact_mobile", type="string", example="+1234567890", nullable=true),
 *     @OA\Property(property="landlines", type="array", @OA\Items(type="string"), example={"123-456-7890"}, nullable=true),
 *     @OA\Property(property="fax_no", type="string", example="123-456-7891", nullable=true),
 *     @OA\Property(property="tin", type="string", example="DK-12345678", nullable=true),
 *     @OA\Property(property="shipping_lines_template", type="array", @OA\Items(type="object"), nullable=true),
 *     @OA\Property(property="transaction_information_template", type="array", @OA\Items(type="object"), nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 * @OA\Schema(
 *     schema="StatementOfAccount",
 *     title="Statement of Account Model",
 *     description="A statement of account resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="shipping_line", type="object", ref="#/components/schemas/ShippingLine"),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="booking_id", type="integer", example=1, description="Required - booking must have waybills"),
 *     @OA\Property(property="booking", type="object", ref="#/components/schemas/Booking"),
 *     @OA\Property(property="waybills", type="array", @OA\Items(ref="#/components/schemas/WaybillDetail")),
 *     @OA\Property(property="total_amount", type="number", format="float", example=15000.00, description="Total amount calculated from waybills' total_rate_per_client"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="GenerateSoaInput",
 *     title="Generate SOA Input",
 *     description="Data required to generate a statement of account",
 *     required={"shipping_line_id", "dli_sa_number", "booking_id"},
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
 *     @OA\Property(property="booking_id", type="integer", example=1)
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
     * Display the specified statement of account with booking and waybills.
     * 
     * @OA\Get(
     *     path="/api/statement-of-accounts/{id}",
     *     summary="Get a specific statement of account with booking and waybills",
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
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="shipping_line", type="object", ref="#/components/schemas/ShippingLine"),
     *                 @OA\Property(property="dli_sa_number", type="string", example="SA-2024-001"),
     *                 @OA\Property(property="booking_id", type="integer", example=1),
     *                 @OA\Property(property="booking", type="object", ref="#/components/schemas/Booking"),
     *                 @OA\Property(property="waybills", type="array", @OA\Items(ref="#/components/schemas/WaybillDetail")),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=15000.00, description="Total amount calculated from waybills' total_rate_per_client"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
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
     * Download PDF for Statement of Account.
     * 
     * @OA\Get(
     *     path="/api/statement-of-accounts/{id}/download",
     *     summary="Download Statement of Account PDF",
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
     *         description="PDF file download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function download($id)
    {
        try {
            // Generate PDF
            $filePath = $this->service->generatePdf($id);

            // Verify file was created
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PDF file.',
                ], 500);
            }

            // Get SOA for filename
            $soa = \App\Models\StatementOfAccount::findOrFail($id);
            $downloadName = $soa->dli_sa_number . '.pdf';

            // Return file download
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
}






