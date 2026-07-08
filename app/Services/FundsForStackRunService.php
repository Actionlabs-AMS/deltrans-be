<?php

namespace App\Services;

use App\Models\FundsForStackRun;
use App\Http\Resources\FundsForStackRunResource;

class FundsForStackRunService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new FundsForStackRunResource(new FundsForStackRun), new FundsForStackRun());
    }

    public function list($perPage = 10, $trash = false)
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = FundsForStackRun::query();

        if ($trash) {
            $query->onlyTrashed();
        }
        if (request('search')) {
            $query->where(function ($q) {
                $q->where('remarks', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('shift', 'LIKE', '%' . request('search') . '%')
                    ->orWhereRaw('CAST(amount AS CHAR) LIKE ?', ['%' . request('search') . '%']);
            });
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
        $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        return FundsForStackRunResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $all, 'trashed' => $trashed]]);
    }

    public function show(int $id)
    {
        $model = $this->model::findOrFail($id);
        return $this->resource::make($model);
    }
}
