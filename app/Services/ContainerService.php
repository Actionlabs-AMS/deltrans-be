<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Container;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            'waybill_id' => [
                'required',
                'integer',
                Rule::exists('waybill_details', 'id')
                    ->where('booking_id', $bookingId)
                    ->whereNull('deleted_at'),
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $container = Container::create([
            'container_number' => $data['container_number'],
            'booking_id' => $bookingId,
            'waybill_id' => (int) $data['waybill_id'],
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

        // Validate request data
        $validator = Validator::make($data, [
            'container_number' => 'sometimes|required|string|max:255',
            'waybill_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('waybill_details', 'id')
                    ->where('booking_id', $bookingId)
                    ->whereNull('deleted_at'),
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $updateData = array_intersect_key($data, array_flip(['container_number', 'waybill_id']));
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
     * Get containers based on booking_id and optionally waybill_id.
     */
    public function getContainers(array $filters)
    {
        // Validate request data
        $validator = Validator::make($filters, [
            'booking_id' => 'required|integer|exists:bookings,id',
            'waybill_id' => 'nullable|integer|exists:waybill_details,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $query = Container::with(['booking', 'waybill'])
            ->where('booking_id', $filters['booking_id']);

        // If waybill_id is provided, filter by both booking_id and waybill_id
        if (isset($filters['waybill_id']) && $filters['waybill_id']) {
            $query->where('waybill_id', $filters['waybill_id']);
        }

        return $query->get();
    }
}
