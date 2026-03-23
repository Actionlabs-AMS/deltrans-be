<?php

namespace App\Services;

use App\Http\Resources\DieselExpenseResource;
use App\Models\DieselExpense;

class DieselExpenseService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new DieselExpenseResource(new DieselExpense()), new DieselExpense());
    }

    public function list($perPage = 10, $trash = false)
    {
        $query = DieselExpense::query()
            ->with(['waybillDetail:id,diesel_expense_id,waybill_number,truck_plate_number,transaction_date']);

        if (request()->filled('transaction_date_from')) {
            $from = request('transaction_date_from');
            $query->whereHas('waybillDetail', function ($waybillQuery) use ($from) {
                $waybillQuery->whereDate('transaction_date', '>=', $from);
            });
        }

        if (request()->filled('transaction_date_to')) {
            $to = request('transaction_date_to');
            $query->whereHas('waybillDetail', function ($waybillQuery) use ($to) {
                $waybillQuery->whereDate('transaction_date', '<=', $to);
            });
        }

        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(amount AS CHAR) LIKE ?', ['%' . $search . '%'])
                    ->orWhereHas('waybillDetail', function ($waybillQuery) use ($search) {
                        $waybillQuery->where('waybill_number', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        if (request()->filled('amount')) {
            $query->where('amount', request('amount'));
        }

        if (request()->filled('amount_min')) {
            $query->where('amount', '>=', request('amount_min'));
        }

        if (request()->filled('amount_max')) {
            $query->where('amount', '<=', request('amount_max'));
        }

        if (request()->filled('waybill_number')) {
            $query->whereHas('waybillDetail', function ($waybillQuery) {
                $waybillQuery->where('waybill_number', 'LIKE', '%' . request('waybill_number') . '%');
            });
        }

        if (request()->filled('created_at_from')) {
            $from = strlen(request('created_at_from')) === 10
                ? request('created_at_from') . ' 00:00:00'
                : request('created_at_from');

            $query->where('created_at', '>=', $from);
        }

        if (request()->filled('created_at_to')) {
            $to = strlen(request('created_at_to')) === 10
                ? request('created_at_to') . ' 23:59:59'
                : request('created_at_to');

            $query->where('created_at', '<=', $to);
        }

        $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        return DieselExpenseResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show(int $id)
    {
        $model = $this->model::with(['waybillDetail:id,diesel_expense_id,waybill_number,truck_plate_number,transaction_date'])
            ->findOrFail($id);

        return $this->resource::make($model);
    }
}
