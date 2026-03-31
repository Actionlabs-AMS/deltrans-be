<?php

namespace App\Services;

use App\Models\DriverCAHistory;
use App\Models\HelperCAHistory;
use App\Http\Resources\CashAdvanceResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CashAdvanceService
{
    /**
     * List cash advance records. type: 0 or null = both tables, 1 = driver only, 2 = helper only.
     */
    public function list(
        ?int $type,
        int $perPage = 10,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $createdAtFrom = null,
        ?string $createdAtTo = null
    ): LengthAwarePaginator {
        if ($type === 1) {
            return $this->listDriver($perPage, $search, $dateFrom, $dateTo, $createdAtFrom, $createdAtTo);
        }
        if ($type === 2) {
            return $this->listHelper($perPage, $search, $dateFrom, $dateTo, $createdAtFrom, $createdAtTo);
        }
        return $this->listBoth($perPage, $search, $dateFrom, $dateTo, $createdAtFrom, $createdAtTo);
    }

    protected function listDriver(
        int $perPage,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $createdAtFrom,
        ?string $createdAtTo
    ): LengthAwarePaginator {
        $query = DriverCAHistory::query()->with('driver');
        $this->applyFilters($query, $search, $dateFrom, $dateTo, 'transaction_date', $createdAtFrom, $createdAtTo);
        $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        return $query->paginate($perPage)->withQueryString();
    }

    protected function listHelper(
        int $perPage,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $createdAtFrom,
        ?string $createdAtTo
    ): LengthAwarePaginator {
        $query = HelperCAHistory::query()->with('helper');
        $this->applyFilters($query, $search, $dateFrom, $dateTo, 'transaction_date', $createdAtFrom, $createdAtTo);
        $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        return $query->paginate($perPage)->withQueryString();
    }

    protected function listBoth(
        int $perPage,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $createdAtFrom,
        ?string $createdAtTo
    ): LengthAwarePaginator {
        $driverQuery = DriverCAHistory::query()->with('driver');
        $this->applyFilters($driverQuery, $search, $dateFrom, $dateTo, 'transaction_date', $createdAtFrom, $createdAtTo);
        $helperQuery = HelperCAHistory::query()->with('helper');
        $this->applyFilters($helperQuery, $search, $dateFrom, $dateTo, 'transaction_date', $createdAtFrom, $createdAtTo);

        $driverItems = $driverQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        $helperItems = $helperQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();

        $merged = $driverItems->concat($helperItems)->sort(function ($a, $b) {
            $da = $a->transaction_date?->format('Y-m-d') ?? '';
            $db = $b->transaction_date?->format('Y-m-d') ?? '';
            $dCmp = strcmp($db, $da);
            if ($dCmp !== 0) {
                return $dCmp;
            }
            $ca = $a->created_at?->format('Y-m-d H:i:s') ?? '';
            $cb = $b->created_at?->format('Y-m-d H:i:s') ?? '';
            $cCmp = strcmp($cb, $ca);
            if ($cCmp !== 0) {
                return $cCmp;
            }

            return $b->id <=> $a->id;
        })->values();

        $total = $merged->count();
        $page = (int) request('page', 1);
        $slice = $merged->slice(($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    protected function applyFilters(
        $query,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
        string $dateColumn,
        ?string $createdAtFrom = null,
        ?string $createdAtTo = null
    ): void {
        if ($dateFrom) {
            $query->where($dateColumn, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where($dateColumn, '<=', $dateTo);
        }
        if ($createdAtFrom) {
            $from = strlen($createdAtFrom) === 10 ? $createdAtFrom . ' 00:00:00' : $createdAtFrom;
            $query->where('created_at', '>=', $from);
        }
        if ($createdAtTo) {
            $to = strlen($createdAtTo) === 10 ? $createdAtTo . ' 23:59:59' : $createdAtTo;
            $query->where('created_at', '<=', $to);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('shift', 'LIKE', '%' . $search . '%')
                    ->orWhereRaw('CAST(amount AS CHAR) LIKE ?', ['%' . $search . '%']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                    $q->orWhereDate('transaction_date', $search);
                }
            });
        }
    }

    public function show(int $type, int $id): array
    {
        if ($type === 1) {
            $model = DriverCAHistory::with('driver')->findOrFail($id);
        } else {
            $model = HelperCAHistory::with('helper')->findOrFail($id);
        }
        return (new CashAdvanceResource($model))->toArray(request());
    }

    public function store(array $data): array
    {
        $type = (int) $data['type'];
        $requestorId = (int) $data['requestor_id'];
        unset($data['type'], $data['requestor_id']);

        if ($type === 1) {
            $data['driver_id'] = $requestorId;
            $model = DriverCAHistory::create($data);
            $model->load('driver');
        } else {
            $data['helper_id'] = $requestorId;
            $model = HelperCAHistory::create($data);
            $model->load('helper');
        }
        return (new CashAdvanceResource($model))->toArray(request());
    }

    public function update(array $data, int $id): array
    {
        $type = (int) $data['type'];
        unset($data['type']);
        if ($type === 1) {
            $model = DriverCAHistory::findOrFail($id);
            $this->ensureOnePerShiftPerDate($model->driver_id, null, $model->transaction_date, $model->shift, $data, $id, 1);
            $model->update(array_intersect_key($data, array_flip(['amount', 'transaction_date', 'shift'])));
            $model->load('driver');
        } else {
            $model = HelperCAHistory::findOrFail($id);
            $this->ensureOnePerShiftPerDate(null, $model->helper_id, $model->transaction_date, $model->shift, $data, $id, 2);
            $model->update(array_intersect_key($data, array_flip(['amount', 'transaction_date', 'shift'])));
            $model->load('helper');
        }
        return (new CashAdvanceResource($model))->toArray(request());
    }

    /**
     * Ensure only one cash advance per (transaction_date, shift) per driver or helper.
     * For update: exclude record with $excludeId.
     */
    protected function ensureOnePerShiftPerDate(
        ?int $driverId,
        ?int $helperId,
        $currentDate,
        ?string $currentShift,
        array $updateData,
        int $excludeId,
        int $type
    ): void {
        $transactionDate = isset($updateData['transaction_date'])
            ? \Carbon\Carbon::parse($updateData['transaction_date'])->format('Y-m-d')
            : (\Carbon\Carbon::parse($currentDate)->format('Y-m-d'));
        $shift = $updateData['shift'] ?? $currentShift;
        if ($type === 1 && $driverId !== null) {
            $exists = DriverCAHistory::where('driver_id', $driverId)
                ->where('transaction_date', $transactionDate)
                ->where('shift', $shift)
                ->where('id', '!=', $excludeId)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'transaction_date' => ['This driver already has a cash advance for this shift on this date. Only one request per shift per transaction date is allowed.'],
                ]);
            }
        }
        if ($type === 2 && $helperId !== null) {
            $exists = HelperCAHistory::where('helper_id', $helperId)
                ->where('transaction_date', $transactionDate)
                ->where('shift', $shift)
                ->where('id', '!=', $excludeId)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'transaction_date' => ['This helper already has a cash advance for this shift on this date. Only one request per shift per transaction date is allowed.'],
                ]);
            }
        }
    }

    public function destroy(int $type, int $id): void
    {
        if ($type === 1) {
            $model = DriverCAHistory::findOrFail($id);
        } else {
            $model = HelperCAHistory::findOrFail($id);
        }
        $model->delete();
    }
}
