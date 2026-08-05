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
 *     @OA\Property(property="expected_container", type="integer", example=10, description="Expected number of containers for the booking"),
 *     @OA\Property(property="containers_count", type="integer", example=7, description="Number of containers currently added to the booking"),
 *     @OA\Property(property="remaining_container", type="integer", example=3, description="expected_container - actual containers count (may be negative)"),
 *     @OA\Property(property="is_complete", type="boolean", example=false, description="Whether the booking is complete"),
 *     @OA\Property(property="is_ship_in", type="boolean", example=true, description="Whether the booking is ship-in (true=Ship In, false=Ship Out)"),
 *     @OA\Property(property="actual_no_of_waybill", type="integer", example=5, description="Actual number of waybills created for this booking"),
 *     @OA\Property(property="has_soa", type="boolean", example=true, description="Present on by-shipping-line: whether this booking is tagged in an active SOA"),
 *     @OA\Property(property="soa_id", type="integer", example=12, nullable=true, description="Present on by-shipping-line: linked SOA id when has_soa is true"),
 *     @OA\Property(property="soa_dli_sa_number", type="string", example="001-A", nullable=true, description="Present on by-shipping-line: SOA DLI SA number for badge display"),
 *     @OA\Property(property="prepared_by", type="string", example="John Doe", nullable=true, description="Display name of user who prepared (from users/user_meta; POST/PUT accept user ID)"),
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
     *         name="is_ship_in",
     *         in="query",
     *         description="Filter by ship-in status (0=Ship Out, 1=Ship In)",
     *         @OA\Schema(type="integer", example=1)
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
     * Get bookings by shipping line ID, optionally filtered by expected_date range.
     *
     * @OA\Get(
     *     path="/api/bookings/by-shipping-line/{shipping_line_id}",
     *     summary="Get bookings by shipping line",
     *     tags={"Booking Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shipping_line_id",
     *         in="path",
     *         required=true,
     *         description="Shipping line ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="expected_date_from",
     *         in="query",
     *         description="Filter expected_date from (inclusive), format Y-m-d",
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="expected_date_to",
     *         in="query",
     *         description="Filter expected_date to (inclusive), format Y-m-d",
     *         @OA\Schema(type="string", format="date", example="2025-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="is_complete",
     *         in="query",
     *         description="Filter by completion status (0=Incomplete, 1=Complete)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by booking reference number only",
     *         @OA\Schema(type="string", example="RF-483624")
     *     ),
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
     *         description="Bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"), description="Bookings include has_soa, soa_id, soa_dli_sa_number for badge display"),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="total_cost", type="number", format="float", example=125000.00, description="Sum of total_rate_per_client for all waybills in the filtered bookings"),
     *             @OA\Property(property="remaining_balance", type="number", format="float", example=50000.00, description="Sum of total_rate_per_client for waybills where booking is_complete=false"),
     *             @OA\Property(property="total_paid", type="number", format="float", example=75000.00, description="Sum of total_rate_per_client for waybills where booking is_complete=true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipping line not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shipping line not found."),
     *             @OA\Property(property="status_code", type="integer", example=404)
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
    public function byShippingLine($shipping_line_id)
    {
        $validated = request()->validate([
            'expected_date_from' => 'nullable|date',
            'expected_date_to' => 'nullable|date',
            'is_complete' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
        ], [
            'expected_date_from.date' => 'The expected_date_from must be a valid date (Y-m-d).',
            'expected_date_to.date' => 'The expected_date_to must be a valid date (Y-m-d).',
        ]);

        if (!empty($validated['expected_date_from']) && !empty($validated['expected_date_to']) && $validated['expected_date_from'] > $validated['expected_date_to']) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['expected_date_to' => ['The expected_date_to must be on or after expected_date_from.']],
            ], 422);
        }

        if (!\App\Models\ShippingLine::where('id', $shipping_line_id)->exists()) {
            return response()->json([
                'message' => 'Shipping line not found.',
                'status_code' => 404,
            ], 404);
        }

        $perPage = request()->get('per_page', 10);
        $expectedDateFrom = $validated['expected_date_from'] ?? null;
        $expectedDateTo = $validated['expected_date_to'] ?? null;
        $isComplete = array_key_exists('is_complete', $validated) ? (int) $validated['is_complete'] : null;
        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;
        if ($search === '') {
            $search = null;
        }

        return $this->service->listByShippingLine(
            (int) $shipping_line_id,
            $expectedDateFrom,
            $expectedDateTo,
            (int) $perPage,
            $isComplete,
            $search
        );
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
     * Get remaining container breakdown for a booking.
     *
     * @OA\Get(
     *     path="/api/bookings/{id}/remaining-container",
     *     summary="Get remaining container breakdown for a booking",
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
     *         description="Remaining container breakdown retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_id", type="integer", example=1),
     *                 @OA\Property(property="expected_container", type="integer", example=10),
     *                 @OA\Property(property="containers_count", type="integer", example=7),
     *                 @OA\Property(property="remaining_container", type="integer", example=3, description="expected_container - containers_count (may be negative)")
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
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function remainingContainer($id)
    {
        try {
            $payload = $this->service->remainingContainer((int) $id);

            return response()->json([
                'data' => $payload,
            ], 200);
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
 *             required={"shipping_line_id", "cypa_id_from", "cypa_id_to", "expected_container"},
     *             @OA\Property(property="reference_number", type="string", example="RF-483624", description="Reference number (optional, unique)"),
     *             @OA\Property(property="vessel", type="string", example="MSC OSCAR", description="Vessel name (optional)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", description="Expected date (optional)"),
 *             @OA\Property(property="expected_container", type="integer", example=10, description="Expected number of containers (required)"),
     *             @OA\Property(property="is_complete", type="boolean", example=false, description="Whether the booking is complete (optional)"),
     *             @OA\Property(property="is_ship_in", type="boolean", example=true, description="Whether the booking is ship-in (optional)"),
     *             @OA\Property(property="prepared_by", type="integer", example=1, nullable=true, description="User ID who prepared the booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="RF-483624", nullable=true),
     *                 @OA\Property(property="vessel", type="string", example="MSC OSCAR", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", nullable=true),
 *                 @OA\Property(property="expected_container", type="integer", example=10),
 *                 @OA\Property(property="containers_count", type="integer", example=7),
 *                 @OA\Property(property="remaining_container", type="integer", example=3),
     *                 @OA\Property(property="is_complete", type="boolean", example=false, description="Whether the booking is complete"),
     *                 @OA\Property(property="is_ship_in", type="boolean", example=true, description="Whether the booking is ship-in"),
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
            $data = $request->validated();
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
     *             @OA\Property(property="vessel", type="string", example="MSC OSCAR", description="Vessel name (optional)"),
     *             @OA\Property(property="shipping_line_id", type="integer", example=1, description="Shipping line ID"),
     *             @OA\Property(property="cypa_id_from", type="integer", example=1, description="CYPA ID (from)"),
     *             @OA\Property(property="cypa_id_to", type="integer", example=2, description="CYPA ID (to)"),
     *             @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", description="Expected date (optional)"),
     *             @OA\Property(property="expected_container", type="integer", example=10, description="Expected number of containers (optional)"),
     *             @OA\Property(property="is_complete", type="boolean", example=false, description="Whether the booking is complete (optional)"),
     *             @OA\Property(property="is_ship_in", type="boolean", example=true, description="Whether the booking is ship-in (optional)"),
     *             @OA\Property(property="prepared_by", type="integer", example=1, nullable=true, description="User ID who prepared the booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference_number", type="string", example="RF-483624", nullable=true),
     *                 @OA\Property(property="vessel", type="string", example="MSC OSCAR", nullable=true),
     *                 @OA\Property(property="shipping_line_id", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_from", type="integer", example=1),
     *                 @OA\Property(property="cypa_id_to", type="integer", example=2),
     *                 @OA\Property(property="expected_date", type="string", format="date", example="2025-02-10", nullable=true),
 *                 @OA\Property(property="expected_container", type="integer", example=10),
 *                 @OA\Property(property="containers_count", type="integer", example=7),
 *                 @OA\Property(property="remaining_container", type="integer", example=3),
     *                 @OA\Property(property="is_complete", type="boolean", example=false, description="Whether the booking is complete"),
     *                 @OA\Property(property="is_ship_in", type="boolean", example=true, description="Whether the booking is ship-in"),
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
            $data = $request->validated();
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
