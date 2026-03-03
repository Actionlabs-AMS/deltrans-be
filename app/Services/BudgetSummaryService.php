<?php

namespace App\Services;

use App\Models\IssuedBudget;
use App\Models\TruckTripExpense;
use App\Models\PartsExpense;
use App\Models\FundsForStackRun;
use App\Models\DriverCAHistory;
use App\Models\HelperCAHistory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Log;

class BudgetSummaryService
{
    private const TYPE_BUDGET = 'Issued Budget';
    private const TYPE_TRUCK_EXPENSE = 'Truck Expense';
    private const TYPE_PARTS_EXPENSE = 'Parts Expense';
    private const TYPE_OTHER_EXPENSE = 'Other Expense';
    private const TYPE_DRIVER_CASH_ADVANCE = 'Driver Cash Advance';
    private const TYPE_HELPER_CASH_ADVANCE = 'Helper Cash Advance';

    /**
     * Build base query with common filters (transaction_date, created_at, shift).
     */
    private function applyFilters($query, string $transactionDateColumn, ?string $shift): void
    {
        if (request('transaction_date_from')) {
            $query->where($transactionDateColumn, '>=', request('transaction_date_from'));
        }
        if (request('transaction_date_to')) {
            $query->where($transactionDateColumn, '<=', request('transaction_date_to'));
        }
        if (request('created_at_from')) {
            $from = strlen(request('created_at_from')) === 10 ? request('created_at_from') . ' 00:00:00' : request('created_at_from');
            $query->where('created_at', '>=', $from);
        }
        if (request('created_at_to')) {
            $to = strlen(request('created_at_to')) === 10 ? request('created_at_to') . ' 23:59:59' : request('created_at_to');
            $query->where('created_at', '<=', $to);
        }
        if ($shift && $shift !== 'All') {
            $query->where('shift', $shift);
        }
    }

    /**
     * Collect all rows from the 6 tables, map to unified shape, sort, and compute totals.
     */
    public function list(int $perPage = 10, ?string $shift = 'All', ?string $type): array
    {
        $all = collect();

        // Issued Budget (income) -> type "Budget"
        $issuedQuery = IssuedBudget::query();
        $this->applyFilters($issuedQuery, 'transaction_date', $shift);
        foreach ($issuedQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_BUDGET,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => (float) $row->amount,
                'description' => $row->source,
                'source_table' => 'issued_budget',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Truck Trip Expense
        $truckQuery = TruckTripExpense::query()->with('helper');
        $this->applyFilters($truckQuery, 'transaction_date', $shift);
        foreach ($truckQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $desc = $row->helper ? trim($row->helper->first_name . ' ' . $row->helper->last_name) : null;
            $amount = (float) $row->issued_cash_amount;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_TRUCK_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => -$amount,
                'description' => $desc,
                'source_table' => 'truck_trip_expense',
                'cash_on_hand' => (float) $row->cash_on_hand,
                'issued_cash_amount' => $amount,
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Parts Expense
        $partsQuery = PartsExpense::query();
        $this->applyFilters($partsQuery, 'transaction_date', $shift);
        foreach ($partsQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount_per_item * (int) $row->quantity;
            $desc = $row->article ?: $row->receipt_no;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_PARTS_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => -$amount,
                'description' => $desc,
                'source_table' => 'parts_expense',
                'plate_number' => $row->plate_number,
                'receipt_no' => $row->receipt_no,
                'quantity' => $row->quantity,
                'article' => $row->article,
                'amount_per_item' => (float) $row->amount_per_item,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Funds For Stack Run
        $fundsQuery = FundsForStackRun::query();
        $this->applyFilters($fundsQuery, 'transaction_date', $shift);
        foreach ($fundsQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_OTHER_EXPENSE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => -$amount,
                'description' => $row->remarks,
                'source_table' => 'funds_for_stack_run',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Driver Cash Advance
        $driverCAQuery = DriverCAHistory::query()->with('driver');
        $this->applyFilters($driverCAQuery, 'transaction_date', $shift);
        foreach ($driverCAQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->driver ? trim($row->driver->first_name . ' ' . $row->driver->last_name) : null;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_DRIVER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => -$amount,
                'description' => $desc,
                'source_table' => 'driver_cash_advancement_history',
                'driver_id' => $row->driver_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Helper Cash Advance
        $helperCAQuery = HelperCAHistory::query()->with('helper');
        $this->applyFilters($helperCAQuery, 'transaction_date', $shift);
        foreach ($helperCAQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
            $amount = (float) $row->amount;
            $desc = $row->helper ? trim($row->helper->first_name . ' ' . $row->helper->last_name) : null;
            $all->push([
                'id' => $row->id,
                'type' => self::TYPE_HELPER_CASH_ADVANCE,
                'transaction_date' => $row->transaction_date?->format('Y-m-d'),
                'shift' => $row->shift,
                'amount' => -$amount,
                'description' => $desc,
                'source_table' => 'helper_cash_advancement_history',
                'helper_id' => $row->helper_id,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Sort by transaction_date desc, then id desc
        if ($type && $type !== 'All') {
           $all = $all->filter(fn($row) => $row['type'] === $type);
        }

        $sorted = $all->sortByDesc(function ($item) {
            return $item['transaction_date'] . ' ' . str_pad((string) $item['id'], 10, '0', STR_PAD_LEFT);
        })->values();

        $total = $sorted->count();
        $totalBudget = $sorted->where('type', self::TYPE_BUDGET)->sum('amount');
        $totalExpense = $sorted->whereIn('type', [
            self::TYPE_TRUCK_EXPENSE,
            self::TYPE_PARTS_EXPENSE,
            self::TYPE_OTHER_EXPENSE,
            self::TYPE_DRIVER_CASH_ADVANCE,
            self::TYPE_HELPER_CASH_ADVANCE,
        ])->sum(fn ($item) => abs((float) $item['amount']));
        $cashOnHand = $totalBudget - $totalExpense;

        $page = (int) request('page', 1);
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'data' => $paginator->items(),
            'total_budget' => round($totalBudget, 2),
            'total_expense' => round($totalExpense, 2),
            'cash_on_hand' => round($cashOnHand, 2),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
