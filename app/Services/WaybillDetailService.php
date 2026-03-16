<?php

namespace App\Services;

use App\Http\Resources\WaybillDetailResource;
use App\Models\DieselExpense;
use App\Models\WaybillDetail;
use Illuminate\Support\Facades\DB;

class WaybillDetailService extends BaseService
{
    private array $relations = [
        'shippingLine',
        'booking',
        'driver',
        'helper',
        'fleetTruck',
        'fixedExpense',
        'truckTripExpense',
        'dieselExpense',
        'preparedByUser.getUserMetas',
    ];

    public function __construct(private TruckTripExpenseBalanceService $truckTripExpenseBalanceService)
    {
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

            $query = WaybillDetail::query()->with($this->relations);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('waybill_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('container_size', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('container_type', 'LIKE', '%' . request('search') . '%')
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

            // Filter by helper_id
            if (request('helper_id')) {
                $query->where('helper_id', request('helper_id'));
            }

            // Filter by truck_plate_number
            if (request('truck_plate_number')) {
                $query->where('truck_plate_number', request('truck_plate_number'));
            }

            // Filter by container_size
            if (request('container_size')) {
                $query->where('container_size', request('container_size'));
            }

            // Filter by container_type
            if (request('container_type')) {
                $query->where('container_type', request('container_type'));
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
        return DB::transaction(function () use ($data) {
            $data = $this->prepareDieselExpenseData($data);
            $model = $this->model::create($data);

            $this->syncTruckTripExpenseBalance(
                null,
                0.0,
                $model->truck_trip_expense_id,
                (float) $model->actual_truck_trip_expense_amount
            );

            return $this->resource::make($model->fresh()->load($this->relations));
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $model = $this->model::query()->lockForUpdate()->findOrFail($id);
            $oldTripId = $model->truck_trip_expense_id;
            $oldTripAmount = (float) $model->actual_truck_trip_expense_amount;
            $data = $this->prepareDieselExpenseData($data, $model);

            $model->update($data);

            $this->syncTruckTripExpenseBalance(
                $oldTripId,
                $oldTripAmount,
                $model->truck_trip_expense_id,
                (float) $model->actual_truck_trip_expense_amount
            );

            return $this->resource::make($model->fresh()->load($this->relations));
        });
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with($this->relations)->findOrFail($id);
        return $this->resource::make($model);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $model = $this->model::query()->lockForUpdate()->findOrFail($id);

            $this->syncTruckTripExpenseBalance(
                $model->truck_trip_expense_id,
                (float) $model->actual_truck_trip_expense_amount,
                null,
                0.0
            );

            return $model->delete();
        });
    }

    public function restore($id)
    {
        return DB::transaction(function () use ($id) {
            $model = $this->model::withTrashed()->onlyTrashed()->lockForUpdate()->findOrFail($id);

            $this->syncTruckTripExpenseBalance(
                null,
                0.0,
                $model->truck_trip_expense_id,
                (float) $model->actual_truck_trip_expense_amount
            );

            $model->restore();

            return $this->resource::make($model->fresh()->load($this->relations));
        });
    }

    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $model = $this->model::withTrashed()->onlyTrashed()->lockForUpdate()->findOrFail($id);
            $this->deleteDieselExpenseIfOrphaned($model);
            $model->forceDelete();

            return $model;
        });
    }

    public function bulkDelete($ids)
    {
        foreach ($this->model::query()->whereIn('id', $ids)->pluck('id') as $id) {
            $this->destroy($id);
        }

        return true;
    }

    public function bulkRestore($ids)
    {
        foreach ($this->model::withTrashed()->onlyTrashed()->whereIn('id', $ids)->pluck('id') as $id) {
            $this->restore($id);
        }

        return true;
    }

    public function bulkForceDelete($ids)
    {
        foreach ($this->model::withTrashed()->onlyTrashed()->whereIn('id', $ids)->pluck('id') as $id) {
            $this->forceDelete($id);
        }

        return true;
    }

    private function syncTruckTripExpenseBalance(
        ?int $oldTripId,
        float $oldTripAmount,
        ?int $newTripId,
        float $newTripAmount
    ): void {
        if ($oldTripId && $oldTripAmount > 0) {
            $this->truckTripExpenseBalanceService->incrementRemainingAmount($oldTripId, $oldTripAmount);
        }

        if ($newTripId && $newTripAmount > 0) {
            $this->truckTripExpenseBalanceService->decrementRemainingAmount($newTripId, $newTripAmount);
        }
    }

    private function prepareDieselExpenseData(array $data, ?WaybillDetail $existingWaybill = null): array
    {
        $hasDieselPayload = array_key_exists('diesel_expense_amount', $data)
            || array_key_exists('purchase_order', $data)
            || array_key_exists('diesel_expense_id', $data);

        if (!$hasDieselPayload) {
            return $data;
        }

        $amount = round((float) ($data['diesel_expense_amount'] ?? 0), 2);
        unset($data['diesel_expense_amount']);

        $purchaseOrder = array_key_exists('purchase_order', $data) ? ($data['purchase_order'] !== null ? (string) $data['purchase_order'] : null) : null;
        unset($data['purchase_order']);

        $requestedDieselExpenseId = null;
        if (array_key_exists('diesel_expense_id', $data)) {
            $requestedDieselExpenseId = $data['diesel_expense_id'] !== null ? (int) $data['diesel_expense_id'] : null;
            unset($data['diesel_expense_id']);
        }

        if ($amount <= 0) {
            if ($existingWaybill && $existingWaybill->diesel_expense_id) {
                DieselExpense::query()->whereKey($existingWaybill->diesel_expense_id)->delete();
            }

            $data['diesel_expense_id'] = null;

            return $data;
        }

        $dieselExpenseIdToUse = $requestedDieselExpenseId
            ?? ($existingWaybill?->diesel_expense_id ? (int) $existingWaybill->diesel_expense_id : null);

        if ($dieselExpenseIdToUse) {
            DieselExpense::query()
                ->whereKey($dieselExpenseIdToUse)
                ->update([
                    'amount' => $amount,
                    'purchase_order' => $purchaseOrder,
                ]);

            $data['diesel_expense_id'] = $dieselExpenseIdToUse;

            return $data;
        }

        $dieselExpense = DieselExpense::query()->create([
            'amount' => $amount,
            'purchase_order' => $purchaseOrder,
        ]);

        $data['diesel_expense_id'] = $dieselExpense->id;

        return $data;
    }

    private function deleteDieselExpenseIfOrphaned(WaybillDetail $waybillDetail): void
    {
        if (!$waybillDetail->diesel_expense_id) {
            return;
        }

        DieselExpense::query()->whereKey($waybillDetail->diesel_expense_id)->delete();
    }
}

