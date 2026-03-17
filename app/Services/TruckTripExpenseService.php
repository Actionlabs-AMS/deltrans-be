<?php

namespace App\Services;

use App\Models\TruckTripExpense;
use App\Http\Resources\TruckTripExpenseResource;

class TruckTripExpenseService extends BaseService
{
    public function __construct(private TruckTripExpenseBalanceService $truckTripExpenseBalanceService)
    {
        parent::__construct(new TruckTripExpenseResource(new TruckTripExpense), new TruckTripExpense());
    }

    public function store(array $data)
    {
        $cashOnHand = (float) ($data['cash_on_hand'] ?? 0);
        $issuedCashAmount = (float) ($data['issued_cash_amount'] ?? 0);
        $data['remaining_amount'] = $this->truckTripExpenseBalanceService
            ->initializeRemainingAmount($cashOnHand, $issuedCashAmount);

        $model = TruckTripExpense::create($data);
        $model->load('helper');
        return TruckTripExpenseResource::make($model);
    }

    public function update(array $data, int $id)
    {
        $model = TruckTripExpense::findOrFail($id);
        $model->update($data);
        $model->load('helper');
        return TruckTripExpenseResource::make($model);
    }

    public function list($perPage = 10, $trash = false)
    {
        $query = TruckTripExpense::query()->with('helper');

        if ($trash) {
            $query->onlyTrashed();
        }
        if (request('search')) {
            $query->where(function ($q) {
                $q->where('shift', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('plate_number', 'LIKE', '%' . request('search') . '%')
                    ->orWhereHas('helper', function ($q) {
                        $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                            ->orWhere('last_name', 'LIKE', '%' . request('search') . '%');
                    });
            });
        }
        if (request('plate_number')) {
            $query->where('plate_number', request('plate_number'));
        }
        if (request('helper_id')) {
            $query->where('helper_id', request('helper_id'));
        }
        // Date range: transaction_date
        if (request('transaction_date_from')) {
            $query->where('transaction_date', '>=', request('transaction_date_from'));
        }
        if (request('transaction_date_to')) {
            $query->where('transaction_date', '<=', request('transaction_date_to'));
        }
        // Date range: created_at
        if (request('created_at_from')) {
            $from = strlen(request('created_at_from')) === 10 ? request('created_at_from') . ' 00:00:00' : request('created_at_from');
            $query->where('created_at', '>=', $from);
        }
        if (request('created_at_to')) {
            $to = strlen(request('created_at_to')) === 10 ? request('created_at_to') . ' 23:59:59' : request('created_at_to');
            $query->where('created_at', '<=', $to);
        }
        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        return TruckTripExpenseResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show(int $id)
    {
        $model = $this->model::with('helper')->findOrFail($id);
        return $this->resource::make($model);
    }

    /**
     * Get the latest truck trip expense for the given plate_number and helper_id.
     * The returned record has remaining_amount kept in sync by waybill create/update/delete
     * via TruckTripExpenseBalanceService (decrement when waybill uses amount, increment on revert).
     *
     * @return \App\Http\Resources\TruckTripExpenseResource|null
     */
    public function getLatestByPlateAndHelper(string $plateNumber, int $helperId)
    {
        $model = TruckTripExpense::query()
            ->with('helper')
            ->where('plate_number', $plateNumber)
            ->where('helper_id', $helperId)
            ->orderBy('id', 'desc')
            ->first();

        return $model ? TruckTripExpenseResource::make($model) : null;
    }
}
