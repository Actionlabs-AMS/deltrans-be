<?php

namespace App\Services;

use App\Models\WaybillDetail;
use App\Http\Resources\WaybillDetailResource;

class WaybillDetailService extends BaseService
{
    public function __construct()
    {
        // Pass the WaybillDetailResource class to the parent constructor
        parent::__construct(new WaybillDetailResource(new WaybillDetail), new WaybillDetail());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allWaybillDetails = $this->getTotalCount();
            $trashedWaybillDetails = $this->getTrashedCount();

            $query = WaybillDetail::query()->with([
                'shippingLine',
                'booking',
                'driver',
                'fleetTruck',
                'fixedExpense',
                'ratePerClient'
            ]);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('waybill_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('container_size', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('driver', function ($q) {
                            $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . request('search') . '%']);
                        })
                        ->orWhere('truck_plate_number', 'LIKE', '%' . request('search') . '%');
                });
            }

            // Filter by shipping_line_id
            if (request('shipping_line_id')) {
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            // Filter by booking_id
            if (request('booking_id')) {
                $query->where('booking_id', request('booking_id'));
            }

            // Filter by driver_id
            if (request('driver_id')) {
                $query->where('driver_id', request('driver_id'));
            }

            // Filter by helper_id (JSON array - check if array contains the helper_id)
            if (request('helper_id')) {
                $helperId = request('helper_id');
                $query->whereJsonContains('helper_id', $helperId);
            }

            // Filter by truck_plate_number
            if (request('truck_plate_number')) {
                $query->where('truck_plate_number', request('truck_plate_number'));
            }

            // Filter by container_size
            if (request('container_size')) {
                $query->where('container_size', request('container_size'));
            }

            // Filter by transaction_date
            if (request('transaction_date')) {
                $query->whereDate('transaction_date', request('transaction_date'));
            }

            // Filter by pickup_date
            if (request('pickup_date')) {
                $query->whereDate('pickup_date', request('pickup_date'));
            }

            // Filter by delivered_date
            if (request('delivered_date')) {
                $query->whereDate('delivered_date', request('delivered_date'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return WaybillDetailResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allWaybillDetails, 'trashed' => $trashedWaybillDetails]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch waybill details: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        // total_rate_per_client and total_expense are now manual inputs, no need to unset them
        
        $model = $this->model::create($data);
        
        return $this->resource::make($model->load([
            'shippingLine',
            'booking',
            'driver',
            'fleetTruck',
            'fixedExpense',
            'ratePerClient'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, int $id)
    {
        // total_rate_per_client and total_expense are now manual inputs, no need to unset them
        
        $model = $this->model::findOrFail($id);
        $model->update($data);
        
        return $this->resource::make($model->load([
            'shippingLine',
            'booking',
            'driver',
            'fleetTruck',
            'fixedExpense',
            'ratePerClient'
        ]));
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with([
            'shippingLine',
            'booking',
            'driver',
            'fleetTruck',
            'fixedExpense',
            'ratePerClient'
        ])->findOrFail($id);
        return $this->resource::make($model);
    }
}

