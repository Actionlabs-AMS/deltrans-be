<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\ShippingLineRequest;
use App\Services\ShippingLineService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Shipping Line Management",
 *     description="API endpoints for shipping line management"
 * )
 */
class ShippingLineController extends BaseController
{
    public function __construct(ShippingLineService $shippingLineService, MessageService $messageService)
    {
        parent::__construct($shippingLineService, $messageService);
    }

    /**
     * Display a listing of shipping lines.
     * 
     * @OA\Get(
     *     path="/api/shipping-lines",
     *     summary="Get list of shipping lines",
     *     tags={"Shipping Line Management"},
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
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of shipping lines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="links", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * Display the specified shipping line.
     * 
     * @OA\Get(
     *     path="/api/shipping-lines/{id}",
     *     summary="Get a specific shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shipping Line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping line retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        return parent::show($id);
    }

    /**
     * Store a newly created shipping line in storage.
     * 
     * @OA\Post(
     *     path="/api/shipping-lines",
     *     summary="Create a new shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email_address"},
     *             @OA\Property(property="name", type="string", example="Maersk Line", description="Shipping line name"),
     *             @OA\Property(property="email_address", type="string", example="contact@maersk.com", description="Email address"),
     *             @OA\Property(property="address", type="string", example="123 Shipping St", description="Address"),
     *             @OA\Property(property="contact_name", type="string", example="John Doe", description="Contact person name"),
     *             @OA\Property(property="contact_mobile", type="string", example="+1234567890", description="Contact mobile number"),
     *             @OA\Property(property="landlines", type="array", @OA\Items(type="string"), example={"123-456-7890", "098-765-4321"}, description="Array of landline numbers"),
     *             @OA\Property(property="fax_no", type="string", example="123-456-7891", description="Fax number"),
     *             @OA\Property(property="tin", type="string", example="123456789", description="Tax Identification Number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shipping line created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function store(ShippingLineRequest $request)
    {
        try {
            $data = $request->all();
            $shippingLine = $this->service->store($data);
            return response($shippingLine, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified shipping line in storage.
     * 
     * @OA\Put(
     *     path="/api/shipping-lines/{id}",
     *     summary="Update a shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shipping Line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Maersk Line", description="Shipping line name"),
     *             @OA\Property(property="email_address", type="string", example="contact@maersk.com", description="Email address"),
     *             @OA\Property(property="address", type="string", example="123 Shipping St", description="Address"),
     *             @OA\Property(property="contact_name", type="string", example="John Doe", description="Contact person name"),
     *             @OA\Property(property="contact_mobile", type="string", example="+1234567890", description="Contact mobile number"),
     *             @OA\Property(property="landlines", type="array", @OA\Items(type="string"), example={"123-456-7890", "098-765-4321"}, description="Array of landline numbers"),
     *             @OA\Property(property="fax_no", type="string", example="123-456-7891", description="Fax number"),
     *             @OA\Property(property="tin", type="string", example="123456789", description="Tax Identification Number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping line updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function update(ShippingLineRequest $request, int $id)
    {
        try {
            $data = $request->all();
            $shippingLine = $this->service->update($data, $id);
            return response($shippingLine, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Remove the specified shipping line from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/shipping-lines/{id}",
     *     summary="Delete a shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shipping Line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping line moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * Bulk delete multiple shipping lines.
     * 
     * @OA\Post(
     *     path="/api/shipping-lines/bulk/delete",
     *     summary="Bulk delete multiple shipping lines",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of shipping line IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping lines deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resources have been deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
    }

    /**
     * Get trashed shipping lines.
     * 
     * @OA\Get(
     *     path="/api/archived/shipping-lines",
     *     summary="Get trashed shipping lines",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Trashed shipping lines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getTrashed()
    {
        return parent::getTrashed();
    }

    /**
     * Restore a trashed shipping line.
     * 
     * @OA\Patch(
     *     path="/api/archived/shipping-lines/restore/{id}",
     *     summary="Restore a trashed shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shipping Line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping line restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        return parent::restore($id);
    }

    /**
     * Bulk restore multiple trashed shipping lines.
     * 
     * @OA\Post(
     *     path="/api/shipping-lines/bulk/restore",
     *     summary="Bulk restore multiple trashed shipping lines",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of shipping line IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping lines restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resources have been restored.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    /**
     * Permanently delete a shipping line.
     * 
     * @OA\Delete(
     *     path="/api/archived/shipping-lines/{id}",
     *     summary="Permanently delete a shipping line",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shipping Line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping line permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function forceDelete($id)
    {
        return parent::forceDelete($id);
    }

    /**
     * Bulk permanently delete multiple shipping lines.
     * 
     * @OA\Post(
     *     path="/api/shipping-lines/bulk/force-delete",
     *     summary="Bulk permanently delete multiple shipping lines",
     *     tags={"Shipping Line Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of shipping line IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping lines permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resources have been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }
}

