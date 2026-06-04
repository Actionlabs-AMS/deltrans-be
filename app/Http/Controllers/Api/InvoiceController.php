<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Invoice Management",
 *     description="API endpoints for invoice management"
 * )
 * @OA\Schema(
 *     schema="Invoice",
 *     title="Invoice Model",
 *     description="An invoice resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="statement_of_account_id", type="integer", example=1, description="Related statement of account ID"),
 *     @OA\Property(property="statement_of_account", type="object", nullable=true, description="Linked SOA"),
 *     @OA\Property(property="shipping_line", type="object", nullable=true, description="Shipping line from SOA when loaded"),
 *     @OA\Property(property="invoice_number", type="string", example="INV-0001"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="discount", type="number", format="float", example=0.00),
 *     @OA\Property(property="discount_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="vatable_sales", type="number", format="float", example=210000.00, description="Computed: Total Sales − VAT"),
 *     @OA\Property(property="vat", type="number", format="float", example=25200.00, description="Computed: 12% VAT"),
 *     @OA\Property(property="total_sales", type="number", format="float", example=210000.00, description="Computed: net base"),
 *     @OA\Property(property="less_vat", type="number", format="float", example=25200.00, description="Computed"),
 *     @OA\Property(property="net_of_vat", type="number", format="float", example=210000.00, description="Computed: same as vatable_sales"),
 *     @OA\Property(property="total_sales_inclusive", type="number", format="float", example=235200.00, description="Computed: VAT inclusive"),
 *     @OA\Property(property="less_withdrawing_tax", type="number", format="float", example=4200.00, description="Computed: 2% of net of VAT"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=231000.00, description="Computed: total due"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="InvoiceGenerateInput",
 *     title="Generate Invoice Input",
 *     required={"statement_of_account_id"},
 *     @OA\Property(property="statement_of_account_id", type="integer", example=1, description="Statement of account ID"),
 *     @OA\Property(property="invoice_number", type="string", example="INV-0001", nullable=true, description="Auto-generated if not provided"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-15", nullable=true, description="Defaults to current date if not provided"),
 *     @OA\Property(property="discount", type="number", format="float", example=0.00, nullable=true),
 *     @OA\Property(property="discount_id", type="integer", nullable=true, description="Reference to discounts table if exists")
 * )
 * @OA\Schema(
 *     schema="UpdateInvoiceInput",
 *     title="Update Invoice Input",
 *     @OA\Property(property="invoice_number", type="string", example="INV-0001", nullable=true),
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-15", nullable=true),
 *     @OA\Property(property="discount", type="number", format="float", example=0.00, nullable=true),
 *     @OA\Property(property="discount_id", type="integer", nullable=true)
 * )
 */
class InvoiceController extends BaseController
{
    protected $service;

    public function __construct(InvoiceService $invoiceService, MessageService $messageService)
    {
        parent::__construct($invoiceService, $messageService);
        $this->service = $invoiceService;
    }

    /**
     * @OA\Post(
     *     path="/api/invoices/generate",
     *     summary="Generate a new invoice from SOA",
     *     description="Creates an invoice based on a statement of account. Automatically calculates quantity, unit_price, item_description, and financial fields from SOA's waybills and containers.",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/InvoiceGenerateInput")),
     *     @OA\Response(response=201, description="Invoice generated successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/Invoice")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function generate(InvoiceRequest $request)
    {
        try {
            $data = $request->validated();
            $invoice = $this->service->generateInvoice($data);
            $payload = $invoice->toArray();
            $totals = $this->service->getComputedTotals(
                (int) $invoice->statement_of_account_id,
                (float) ($invoice->discount ?? 0)
            );
            return response()->json([
                'success' => true,
                'data' => array_merge($payload, $totals),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/invoices/{id}",
     *     summary="Update an invoice",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(ref="#/components/schemas/UpdateInvoiceInput")),
     *     @OA\Response(response=200, description="Invoice updated successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/Invoice")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function update($id, UpdateInvoiceRequest $request)
    {
        try {
            $data = $request->validated();
            $invoice = $this->service->updateInvoice($id, $data);
            $payload = $invoice->toArray();
            $totals = $this->service->getComputedTotals(
                (int) $invoice->statement_of_account_id,
                (float) ($invoice->discount ?? 0)
            );
            return response()->json([
                'success' => true,
                'data' => array_merge($payload, $totals),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Invoice not found.' ? 404 : 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/soa/{soaId}/invoice/download",
     *     summary="Download Invoice PDF by SOA ID",
     *     description="Download the invoice PDF for the given statement of account. Uses SOA ID so you can download the invoice when you have the SOA context.",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="soaId", in="path", required=true, description="Statement of account (SOA) ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages (upload via POST /invoices/attachments); folder is deleted after download", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function downloadBySoaId($soaId)
    {
        try {
            $invoice = Invoice::where('statement_of_account_id', $soaId)->firstOrFail();
            return $this->download($invoice->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice found for this statement of account.',
            ], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/invoices/{id}/download",
     *     summary="Download Invoice PDF by Invoice ID",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages (upload via POST /invoices/attachments); folder is deleted after download", @OA\Schema(type="boolean", default=false)),
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
            $invoice = Invoice::findOrFail($id);
            $downloadName = $invoice->invoice_number . '.pdf';

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
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
     *     path="/api/invoices/{id}/send-email",
     *     summary="Send Invoice PDF via email",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="include_attachments", in="query", required=false, description="If true, append the authenticated user's uploaded attachment images as extra PDF pages", @OA\Schema(type="boolean", default=false)),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="email", type="string", format="email", nullable=true, description="Custom recipient email (overrides shipping line email)"),
     *         @OA\Property(property="cc", type="array", @OA\Items(type="string", format="email"), nullable=true, description="CC recipients"),
     *         @OA\Property(property="subject", type="string", nullable=true, description="Custom email subject (optional; if empty, standard subject is used)"),
     *         @OA\Property(property="body", type="string", nullable=true, description="Custom email body HTML (optional; if empty, standard body is used)")
     *     )),
     *     @OA\Response(response=200, description="Email sent successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Invoice sent successfully.")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function sendEmail($id, Request $request)
    {
        try {
            $includeAttachments = filter_var($request->input('include_attachments', false), FILTER_VALIDATE_BOOLEAN);
            $attachmentUserId = $includeAttachments ? auth()->id() : null;
            $customEmail = $request->input('email');
            $cc = $request->input('cc', []);
            $subject = $request->input('subject');
            $body = $request->input('body');

            $this->service->sendInvoiceEmail($id, $attachmentUserId, $includeAttachments, $customEmail, $cc, $subject, $body);

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/invoices",
     *     summary="Get list of invoices",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search by invoice number or shipping line name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="shipping_line_id", in="query", description="Filter by shipping line ID (via statement of account)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="statement_of_account_id", in="query", description="Filter by statement of account ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date_from", in="query", description="Filter invoices by invoice date from (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", description="Filter invoices to date", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="List of invoices", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Invoice")),
     *         @OA\Property(property="meta", type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="from", type="integer", nullable=true),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="links", type="array", @OA\Items(type="object", @OA\Property(property="url", type="string", nullable=true), @OA\Property(property="label", type="string"), @OA\Property(property="active", type="boolean"))),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="to", type="integer", nullable=true),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="all", type="integer", example=50),
     *             @OA\Property(property="trashed", type="integer", example=2)
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        try {
            $perPage = request()->get('per_page', 10);
            $result = $this->service->listInvoices($perPage);
            $paginator = $result['paginator'];
            $metaExtra = $result['meta_extra'];
            $items = collect($paginator->items())->map(function ($invoice) {
                $arr = $invoice->toArray();
                $totals = $this->service->getComputedTotals(
                    (int) $invoice->statement_of_account_id,
                    (float) ($invoice->discount ?? 0)
                );
                return array_merge($arr, $totals);
            })->all();

            $paginatorArray = $paginator->toArray();
            $meta = [
                'current_page' => $paginatorArray['current_page'],
                'from' => $paginatorArray['from'],
                'last_page' => $paginatorArray['last_page'],
                'links' => $paginatorArray['links'],
                'path' => $paginatorArray['path'],
                'per_page' => $paginatorArray['per_page'],
                'to' => $paginatorArray['to'],
                'total' => $paginatorArray['total'],
                'all' => $metaExtra['all'],
                'trashed' => $metaExtra['trashed'],
            ];

            return response()->json([
                'data' => $items,
                'meta' => $meta,
            ]);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/invoices/export",
     *     summary="Export invoices as CSV",
     *     description="Download all matching invoices as a CSV file (no pagination). Uses the same filters as the list endpoint.",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="search", in="query", description="Search by invoice number or shipping line name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="shipping_line_id", in="query", description="Filter by shipping line ID (via statement of account)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date_from", in="query", description="Filter invoices by invoice date from (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", description="Filter invoices to date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="CSV file download", @OA\MediaType(mediaType="text/csv", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function export()
    {
        try {
            return $this->service->exportInvoicesCsv();
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/invoices/{id}",
     *     summary="Get a specific invoice",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Invoice retrieved", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/Invoice")
     *     )),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function show($id)
    {
        try {
            $invoice = $this->service->showInvoice($id);
            $data = $invoice->toArray();
            $totals = $this->service->getComputedTotals(
                (int) $invoice->statement_of_account_id,
                (float) ($invoice->discount ?? 0)
            );
            return response()->json([
                'success' => true,
                'data' => array_merge($data, $totals),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Invoice not found.' ? 404 : 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/invoices/attachments",
     *     summary="Upload temp images for Invoice PDF attachments",
     *     description="Upload one or more images to be optionally included as extra pages in Invoice PDF downloads. One folder per user; each new upload replaces the previous. Use include_attachments=true on download to include them; folder is deleted after that download.",
     *     tags={"Invoice Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *             @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"), description="Image files to upload")
     *         )
     *     )),
     *     @OA\Response(response=200, description="Files uploaded successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="3 file(s) uploaded successfully."),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="count", type="integer", example=3),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function storeAttachments(Request $request)
    {
        try {
            $files = $request->file('attachments', []);
            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files uploaded.',
                ], 400);
            }

            $userId = auth()->id();
            $count = $this->service->storeTempAttachmentsForUser($userId, $files);

            return response()->json([
                'success' => true,
                'message' => "{$count} file(s) uploaded successfully.",
                'data' => [
                    'count' => $count,
                    'user_id' => $userId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
