<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ContainerService;
use App\Services\MessageService;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Container Management",
 *     description="API endpoints for container management"
 * )
 * @OA\Schema(
 *     schema="Container",
 *     title="Container Model",
 *     description="A container resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="booking_id", type="integer", example=1, description="Booking ID (client input)"),
 *     @OA\Property(property="waybill_id", type="integer", example=1, description="Waybill ID for this booking (required)"),
 *     @OA\Property(property="container_number", type="string", example="CONT-001"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
 * )
 * @OA\Schema(
 *     schema="ContainerCreateInput",
 *     title="Container Create Input",
 *     description="Client input for adding a container",
 *     required={"container_number","waybill_id"},
 *     @OA\Property(property="booking_id", type="integer", example=1, description="Booking ID (required; provided via path parameter for POST /bookings/{bookingId}/containers)"),
 *     @OA\Property(property="waybill_id", type="integer", example=1, description="Waybill ID (must belong to this booking)"),
 *     @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
 * )
 * @OA\Schema(
 *     schema="ContainerUpdateInput",
 *     title="Container Update Input",
 *     description="Client input for updating a container",
 *     @OA\Property(property="waybill_id", type="integer", example=1, description="Waybill ID (required when updating linkage; must belong to this booking)"),
 *     @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
 * )
 */
class ContainerController extends Controller
{
    protected $containerService;
    protected $messageService;

    public function __construct(ContainerService $containerService, MessageService $messageService)
    {
        $this->containerService = $containerService;
        $this->messageService = $messageService;
    }

    /**
     * Add a container to a booking.
     * 
     * @OA\Post(
     *     path="/api/bookings/{bookingId}/containers",
     *     summary="Add a container to a booking",
     *     tags={"Container Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *             required={"container_number","waybill_id"},
 *             @OA\Property(property="booking_id", type="integer", example=1, description="Booking ID (client input; may also be provided via path)"),
 *             @OA\Property(property="waybill_id", type="integer", example=1, description="Waybill ID (must belong to this booking)"),
     *             @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Container added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container added successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Container")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Booking not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function addContainer(Request $request, $bookingId)
    {
        try {
            $container = $this->containerService->addContainer($request->all(), $bookingId);

            return response()->json([
                'success' => true,
                'message' => 'Container added successfully',
                'data' => $container
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update a container.
     * 
     * @OA\Put(
     *     path="/api/bookings/{bookingId}/containers/{containerId}",
     *     summary="Update a container",
     *     tags={"Container Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="containerId",
     *         in="path",
     *         required=true,
     *         description="Container ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="waybill_id", type="integer", example=1, description="Waybill ID (when sent, must belong to this booking)"),
     *             @OA\Property(property="container_number", type="string", example="CONT-001", description="Container number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Container")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container or booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Container or booking not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateContainer(Request $request, $bookingId, $containerId)
    {
        try {
            $container = $this->containerService->updateContainer($request->all(), $bookingId, $containerId);

            return response()->json([
                'success' => true,
                'message' => 'Container updated successfully',
                'data' => $container
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container or booking not found.'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Delete a container.
     * 
     * @OA\Delete(
     *     path="/api/bookings/{bookingId}/containers/{containerId}",
     *     summary="Delete a container",
     *     tags={"Container Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="containerId",
     *         in="path",
     *         required=true,
     *         description="Container ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Container deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container or booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Container or booking not found.")
     *         )
     *     )
     * )
     */
    public function deleteContainer($bookingId, $containerId)
    {
        try {
            $this->containerService->deleteContainer($bookingId, $containerId);

            return response()->json([
                'success' => true,
                'message' => 'Container deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container or booking not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get a single container by ID.
     * 
     * @OA\Get(
     *     path="/api/containers/{id}",
     *     summary="Get a specific container",
     *     tags={"Container Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Container ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Container")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Container not found.")
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
        try {
            $container = $this->containerService->getContainer($id);

            return response()->json([
                'success' => true,
                'data' => $container
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container not found.'
            ], 404);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get containers based on booking_id and optionally waybill_id.
     * 
     * @OA\Get(
     *     path="/api/containers",
     *     summary="Get containers by booking_id and optionally waybill_id",
     *     tags={"Container Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="booking_id",
     *         in="query",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="waybill_id",
     *         in="query",
     *         required=false,
     *         description="Waybill ID (optional - if provided, filters by both booking_id and waybill_id)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Containers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Container"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function getContainers(Request $request)
    {
        try {
            $containers = $this->containerService->getContainers($request->all());

            return response()->json([
                'success' => true,
                'data' => $containers
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}
