<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckBookingIdsRequest;
use App\Services\MessageService;
use App\Services\SoaBillingCheckerService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="SOA, Billing & Invoice - Booking Validator",
 *     description="Validate bookings for SOA, Billing, or Invoice (type 1, 2, or 3). Condition: a booking is valid only if it has at least one waybill (waybill_details with that booking_id). Returns valid and invalid bookings with reasons."
 * )
 * @OA\Schema(
 *     schema="BookingCheckerValidateInput",
 *     title="Validate Bookings for SOA/Billing/Invoice",
 *     required={"type"},
 *     @OA\Property(property="booking_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of booking IDs (preferred when provided; if present, statement_of_account_id will be ignored)"),
 *     @OA\Property(property="statement_of_account_id", type="integer", example=10, description="Statement of Account ID to validate against (used only when booking_ids is not provided)"),
 *     @OA\Property(property="type", type="integer", example=2, description="1 = SOA, 2 = Billing, 3 = Invoice")
 * )
 * @OA\Schema(
 *     schema="BookingCheckerValidateResponse",
 *     title="Validate Bookings Response",
 *     @OA\Property(property="valid_bookings", type="array", @OA\Items(type="object"), description="Bookings that pass the condition for the given type"),
 *     @OA\Property(property="invalid_bookings", type="array", @OA\Items(
 *         @OA\Property(property="booking", type="object", description="Booking object"),
 *         @OA\Property(property="reason", type="string", description="Why the booking is invalid (e.g. no waybills, already has SOA/Billing/Invoice)")
 *     ), description="Bookings that fail with reason"),
 *     @OA\Property(property="has_soa", type="boolean", description="For type 2 and 3 only. True if any selected booking has an SOA, false if all have no SOA. Default false.")
 * )
 */
class SoaBillingCheckerController extends Controller
{
    public function __construct(
        protected SoaBillingCheckerService $checkerService,
        protected MessageService $messageService
    ) {
    }

    /**
     * Validate bookings for SOA (type 1), Billing (type 2), or Invoice (type 3).
     * Returns valid_bookings and invalid_bookings (with reason per invalid).
     *
     * @OA\Post(
     *     path="/api/soa-billing-check/validate",
     *     summary="Validate bookings for SOA, Billing, or Invoice",
     *     tags={"SOA, Billing & Invoice - Booking Validator"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BookingCheckerValidateInput")
     *     ),
     *     @OA\Response(response=200, description="Validation result", @OA\JsonContent(ref="#/components/schemas/BookingCheckerValidateResponse")),
     *     @OA\Response(response=422, description="Validation error (invalid request body)"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function validateBookings(CheckBookingIdsRequest $request): JsonResponse
    {
        try {
            $result = $this->checkerService->validateBookings(
                $request->input('booking_ids', []),
                (int) $request->input('type')
            );
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error has occurred, please reload the page or try again later. Please contact the administrator if error has re-occured.',
                'status' => false,
                'status_code' => 422,
            ], 422);
        }
    }
}
