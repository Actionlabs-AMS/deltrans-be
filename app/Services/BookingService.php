<?php

namespace App\Services;

use App\Models\Booking;
use App\Http\Resources\BookingResource;

class BookingService extends BaseService
{
    public function __construct()
    {
        // Pass the BookingResource class to the parent constructor
        parent::__construct(new BookingResource(new Booking), new Booking());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        // Remove is_complete from data if provided
        // This field is fillable but not allowed in this API (other APIs will update it)
        unset($data['is_complete']);

        // Create the model - defaults will be applied from model $attributes
        $model = $this->model::create($data);

        return $this->resource::make($model->fresh());
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with(['shippingLine', 'cypaFrom', 'cypaTo', 'containers'])
            ->findOrFail($id);

        // Count waybills for this booking
        $actualNoOfWaybill = \Illuminate\Support\Facades\DB::table('waybill_details')
            ->where('booking_id', $id)
            ->whereNull('deleted_at')
            ->count();

        // Add the count to the model as an attribute
        $model->actual_no_of_waybill = $actualNoOfWaybill;

        return $this->resource::make($model);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, $id)
    {
        // Remove is_complete from data if provided
        // This field is fillable but not allowed in this API (other APIs will update it)
        unset($data['is_complete']);

        $model = $this->model::findOrFail($id);
        $model->update($data);
        return $this->resource::make($model->fresh()->load(['shippingLine', 'cypaFrom', 'cypaTo', 'containers']));
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allBookings = $this->getTotalCount();
            $trashedBookings = $this->getTrashedCount();

            $query = Booking::query()->with(['shippingLine', 'cypaFrom', 'cypaTo']);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('reference_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('vessel', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaFrom', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaTo', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Filter by vessel
            if (request('vessel')) {
                $query->where('vessel', 'LIKE', '%' . request('vessel') . '%');
            }

            // Filter by shipping_line_id
            if (request('shipping_line_id')) {
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            // Filter by cypa_id_from
            if (request('cypa_id_from')) {
                $query->where('cypa_id_from', request('cypa_id_from'));
            }

            // Filter by cypa_id_to
            if (request('cypa_id_to')) {
                $query->where('cypa_id_to', request('cypa_id_to'));
            }

            // Filter by is_complete
            if (request()->has('is_complete')) {
                $query->where('is_complete', request('is_complete'));
            }

            // Filter by expected_date
            if (request('expected_date')) {
                $query->whereDate('expected_date', request('expected_date'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return BookingResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allBookings, 'trashed' => $trashedBookings]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch bookings: ' . $e->getMessage());
        }
    }
}
