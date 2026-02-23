<?php

namespace App\Services;

use App\Models\TruckTripExpense;
use App\Http\Resources\TruckTripExpenseResource;

class TruckTripExpenseService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TruckTripExpenseResource(new TruckTripExpense), new TruckTripExpense());
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
                    ->orWhereHas('helper', function ($q) {
                        $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                            ->orWhere('last_name', 'LIKE', '%' . request('search') . '%');
                    });
            });
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
}
