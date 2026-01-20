<?php

namespace App\Services;

use App\Models\Container;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContainerService
{
    /**
     * Add a container to a booking.
     */
    public function addContainer(array $data, int $bookingId)
    {
        // Validate booking exists
        $booking = Booking::findOrFail($bookingId);

        // Validate request data
        $validator = Validator::make($data, [
            'container_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create container - waybill_number will be null initially, can be updated later
        $container = Container::create([
            'container_number' => $data['container_number'],
            'booking_id' => $bookingId,
            'waybill_number' => null,
        ]);

        return $container;
    }

    /**
     * Update a container.
     */
    public function updateContainer(array $data, int $bookingId, int $containerId)
    {
        // Validate booking exists
        $booking = Booking::findOrFail($bookingId);

        // Validate container exists and belongs to booking
        $container = Container::where('id', $containerId)
            ->where('booking_id', $bookingId)
            ->firstOrFail();

        // Validate request data - only allow container_number (waybill_number will be updated by other APIs)
        $validator = Validator::make($data, [
            'container_number' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Remove waybill_number if provided (not allowed in this API)
        $updateData = array_intersect_key($data, array_flip(['container_number']));

        // Update container - only allow container_number
        $container->update($updateData);

        return $container->fresh();
    }

    /**
     * Delete a container.
     */
    public function deleteContainer(int $bookingId, int $containerId)
    {
        // Validate booking exists
        $booking = Booking::findOrFail($bookingId);

        // Validate container exists and belongs to booking
        $container = Container::where('id', $containerId)
            ->where('booking_id', $bookingId)
            ->firstOrFail();

        // Hard delete the container
        $container->delete();

        return true;
    }

    /**
     * Get a single container by ID.
     */
    public function getContainer(int $containerId)
    {
        $container = Container::with(['booking', 'waybill'])
            ->findOrFail($containerId);

        return $container;
    }

    /**
     * Get containers based on booking_id and optionally waybill_number.
     */
    public function getContainers(array $filters)
    {
        // Validate request data
        $validator = Validator::make($filters, [
            'booking_id' => 'required|integer|exists:bookings,id',
            'waybill_number' => 'nullable|string|max:255|exists:waybill_details,waybill_number',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $query = Container::with(['booking', 'waybill'])
            ->where('booking_id', $filters['booking_id']);

        // If waybill_number is provided, filter by both booking_id and waybill_number
        if (isset($filters['waybill_number']) && $filters['waybill_number']) {
            $query->where('waybill_number', $filters['waybill_number']);
        }

        return $query->get();
    }
}
