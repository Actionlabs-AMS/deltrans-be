<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BookingRequest;
use App\Services\BookingService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Booking Management",
 *     description="API endpoints for booking management"
 * )
 * @OA\Schema(
 *     schema="Booking",
 *     title="Booking Model",
 *     description="A booking resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="reference_number", type="string", example="RF-483624", nullable=true),
 *     @OA\Property(property="vessel", type="string", example="MSC OSCAR", nullable=true),
 *     @OA\Property(property="shipping_line_id", type="integer", example=1),
 *     @OA\Property(property="cypa_id_from", type="integer", example=1),
 *     @OA\Property(property="cypa_id_to", type="integer", example=2),
 *     @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", nullable=true),
 *     @OA\Property(property="is_complete", type="integer", example=0, description="0=Incomplete, 1=Complete"),
 *     @OA\Property(property="actual_no_of_waybill", type="integer", example=5, description="Actual number of waybills created for this booking"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 */
class BookingController extends BaseController
{
    public function __construct(BookingService $bookingService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($bookingService, $messageService);
    }

    /**
     * Display a listing of bookings.
     * 
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="Get list of bookings",
     *     tags={"Booking Management"},
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
     *         description="Search by reference number, shipping line name, or CYPA name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="shipping_line_id",
     *         in="query",
     *         description="Filter by shipping line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="cypa_id_from",
     *         in="query",
     *         description="Filter by CYPA ID (from)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="cypa_id_to",
     *         in="query",
     *         description="Filter by CYPA ID (to)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="is_complete",
     *         in="query",
     *         description="Filter by completion status (0=Incomplete, 1=Complete)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="expected_date",
     *         in="query",
     *         description="Filter by expected date",
     *         @OA\Schema(type="string", format="date", example="2025-02-10")
     *     ),
     *     @OA\Parameter(
     *         name="vessel",
     *         in="query",
     *         description="Filter by vessel name (partial match)",
     *         @OA\Schema(type="string", example="MSC")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="all", type="integer", example=10),
     *                 @OA\Property(property="trashed", type="integer", example=2)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string", example="http://example.com/api/bookings?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://example.com/api/bookings?page=5"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://example.com/api/bookings?page=2")
     *             )
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
     * Display the specified booking.
     * 
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     summary="Get a specific booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found.")
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
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/bookings",
     *     summary="Create a new booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_line_id", "cypa_id_from", "cypa_id_to"},
     *             @OA\Property(property="reference_number", type="string", example="RF-483624", description="Reference number (optional, unique)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", description="Expected date (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="RF-483624", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", nullable=true),
     *                 @OA\Property(property="is_complete", type="integer", example=0, description="0=Incomplete, 1=Complete"),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01 12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01 12:00:00")
     *             )
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
    public function store(BookingRequest $request)
    {
        try {
            $data = $request->all();
            $booking = $this->service->store($data);
            return response($booking, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified booking in storage.
     * 
     * @OA\Put(
     *     path="/api/bookings/{id}",
     *     summary="Update a booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reference_number", type="string", example="RF-483624", description="Reference number (optional)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", description="Expected date (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="RF-483624", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", nullable=true),
     *                 @OA\Property(property="is_complete", type="integer", example=0, description="0=Incomplete, 1=Complete"),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01 12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found.")
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
    public function update(BookingRequest $request, $id)
    {
        try {
            $data = $request->all();
            $booking = $this->service->update($data, $id);
            return response($booking, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Remove the specified booking from storage (soft delete).
     * 
     * @OA\Delete(
     *     path="/api/bookings/{id}",
     *     summary="Delete a booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found.")
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
     * Bulk delete multiple bookings.
     * 
     * @OA\Post(
     *     path="/api/bookings/bulk/delete",
     *     summary="Bulk delete multiple bookings",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of booking IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookings deleted successfully",
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
     * Get trashed bookings.
     * 
     * @OA\Get(
     *     path="/api/archived/bookings",
     *     summary="Get trashed bookings",
     *     tags={"Booking Management"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Trashed bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"))
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
     * Restore a trashed booking.
     * 
     * @OA\Patch(
     *     path="/api/archived/bookings/restore/{id}",
     *     summary="Restore a trashed booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been restored."),
     *             @OA\Property(property="resource", type="object", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found.")
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
     * Bulk restore multiple trashed bookings.
     * 
     * @OA\Post(
     *     path="/api/bookings/bulk/restore",
     *     summary="Bulk restore multiple trashed bookings",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of booking IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookings restored successfully",
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
     * Permanently delete a booking.
     * 
     * @OA\Delete(
     *     path="/api/archived/bookings/{id}",
     *     summary="Permanently delete a booking",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking permanently deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found.")
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
     * Bulk permanently delete multiple bookings.
     * 
     * @OA\Post(
     *     path="/api/bookings/bulk/force-delete",
     *     summary="Bulk permanently delete multiple bookings",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of booking IDs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookings permanently deleted successfully",
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
