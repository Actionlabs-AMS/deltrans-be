<?php

namespace App\Services;

use App\Models\BillingStatement;
use App\Models\DieselExpense;
use App\Models\Invoice;
use App\Models\PartsExpense;
use App\Models\ShippingLine;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardService
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {
    }

    /**
     * Get the combined dashboard payload.
     */
    public function getDashboard(array $filters = []): array
    {
        ['start' => $start, 'end' => $end] = $this->resolveDateRange($filters);

        return [
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'kpis' => $this->getKpis($filters),
            'sales_overview' => $this->getSalesOverview($filters),
            'overdue_payments' => $this->getOverduePayments(),
        ];
    }

    /**
     * Get dashboard KPIs for the selected range.
     */
    public function getKpis(array $filters = []): array
    {
        ['start' => $start, 'end' => $end] = $this->resolveDateRange($filters);

        $waybillQuery = WaybillDetail::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);

        $completedWaybillQuery = (clone $waybillQuery)
            ->whereHas('booking', function ($query) {
                $query->where('is_complete', true);
            });

        return [
            'shipping_line_count' => ShippingLine::count(),
            'waybills_completed' => (clone $completedWaybillQuery)->count(),
            'waybills_total' => (clone $waybillQuery)->count(),
            'sales' => $this->getSalesTotalAmountDue($start, $end),
            'waybill_expenses' => $this->getWaybillExpensesTotal($start, $end),
            'parts_expense' => $this->getPartsExpenseTotal($start, $end),
            'diesel_expense' => $this->getDieselExpenseTotal($start, $end),
        ];
    }

    /**
     * Get the sales overview payload for the selected range.
     */
    public function getSalesOverview(array $filters = []): array
    {
        ['start' => $start, 'end' => $end] = $this->resolveDateRange($filters);

        $months = $this->getMonthKeys($start, $end);
        $incomeByMonth = array_fill_keys($months, 0.0);
        $waybillExpensesByMonth = array_fill_keys($months, 0.0);

        foreach ($this->getSalesTotalAmountDueByMonth($start, $end) as $month => $amount) {
            if (array_key_exists($month, $incomeByMonth)) {
                $incomeByMonth[$month] = round((float) $amount, 2);
            }
        }

        foreach ($this->getWaybillExpensesByMonth($start, $end) as $month => $amount) {
            if (array_key_exists($month, $waybillExpensesByMonth)) {
                $waybillExpensesByMonth[$month] = round((float) $amount, 2);
            }
        }

        return [
            'months' => array_keys($incomeByMonth),
            'income' => array_values($incomeByMonth),
            'waybill_expenses' => array_values($waybillExpensesByMonth),
        ];
    }

    /**
     * Get overdue payments grouped by billing statement.
     */
    public function getOverduePayments(): array
    {
        $statements = BillingStatement::query()
            ->where('is_paid', false)
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->with(['statementOfAccount.shippingLine'])
            ->orderBy('due_date')
            ->get();

        $amountsBySoa = $this->getStatementOfAccountAmounts(
            $statements->pluck('statement_of_account_id')->all()
        );

        return $statements->map(function (BillingStatement $statement) use ($amountsBySoa) {
            $statementOfAccountId = (int) $statement->statement_of_account_id;

            return [
                'shipping_line_name' => $statement->statementOfAccount?->shippingLine?->name ?? '',
                'transaction_no' => $statement->billing_statement_no,
                'overdue_payment_date' => optional($statement->due_date)->toDateString(),
                'overdue_payment_amount' => round((float) ($amountsBySoa[$statementOfAccountId] ?? 0), 2),
                'billing_statement_id' => $statement->id,
                'statement_of_account_id' => $statementOfAccountId,
            ];
        })->toArray();
    }

    /**
     * Get the enhanced stats payload used by widgets (KPIs only, no legacy counts).
     */
    public function getEnhancedStats(array $filters = []): array
    {
        return $this->getKpis($filters);
    }

    /**
     * Resolve the requested date range.
     *
     * Supported filters:
     * - date
     * - date_from / date_to
     * - year
     */
    private function resolveDateRange(array $filters): array
    {
        if (!empty($filters['date'])) {
            $date = Carbon::parse($filters['date'])->startOfDay();

            return [
                'start' => $date->copy(),
                'end' => $date->copy(),
            ];
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $start = Carbon::parse($filters['date_from'] ?? $filters['date_to'])->startOfDay();
            $end = Carbon::parse($filters['date_to'] ?? $filters['date_from'])->endOfDay();

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }

            return [
                'start' => $start,
                'end' => $end,
            ];
        }

        if (!empty($filters['year'])) {
            $year = (int) $filters['year'];
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
            ];
        }

        return [
            'start' => Carbon::now()->startOfMonth(),
            'end' => Carbon::now()->endOfMonth(),
        ];
    }

    /**
     * Build the list of year-month keys for the chart.
     *
     * @return array<int, string>
     */
    private function getMonthKeys(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create(
            $start->copy()->startOfMonth(),
            '1 month',
            $end->copy()->startOfMonth()
        );

        $months = [];

        foreach ($period as $date) {
            $months[] = $date->format('Y-m');
        }

        return $months;
    }

    /**
     * Sales total = sum of "total amount due" for all invoices in range (same logic as invoice PDF).
     */
    private function getSalesTotalAmountDue(Carbon $start, Carbon $end): float
    {
        return round(array_sum($this->getSalesTotalAmountDueByMonth($start, $end)), 2);
    }

    /**
     * Sales (total amount due) by month from invoice date. Uses InvoiceService::getComputedTotals per invoice.
     *
     * @return array<string, float>
     */
    private function getSalesTotalAmountDueByMonth(Carbon $start, Carbon $end): array
    {
        $invoices = Invoice::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'statement_of_account_id', 'date', 'discount']);

        $incomeByMonth = [];

        foreach ($invoices as $invoice) {
            $totals = $this->invoiceService->getComputedTotals(
                (int) $invoice->statement_of_account_id,
                (float) ($invoice->discount ?? 0)
            );
            $monthKey = $invoice->date ? $invoice->date->format('Y-m') : Carbon::now()->format('Y-m');
            $incomeByMonth[$monthKey] = ($incomeByMonth[$monthKey] ?? 0) + (float) ($totals['total_amount'] ?? 0);
        }

        return $incomeByMonth;
    }

    /**
     * Waybill expenses = sum over waybills in range of (total_expense + actual_truck_trip_expense_amount) + diesel.
     */
    private function getWaybillExpensesTotal(Carbon $start, Carbon $end): float
    {
        $waybillPart = (float) WaybillDetail::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(total_expense), 0) + COALESCE(SUM(actual_truck_trip_expense_amount), 0) as total')
            ->value('total');

        return round($waybillPart + $this->getDieselExpenseTotal($start, $end), 2);
    }

    /**
     * Waybill expenses by month (transaction_date). total_expense + actual_truck_trip_expense_amount per month + diesel per month.
     *
     * @return array<string, float>
     */
    private function getWaybillExpensesByMonth(Carbon $start, Carbon $end): array
    {
        $waybillPart = WaybillDetail::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, COALESCE(SUM(total_expense), 0) + COALESCE(SUM(actual_truck_trip_expense_amount), 0) as total_amount")
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->toArray();

        $dieselByMonth = DieselExpense::query()
            ->join('waybill_details', 'waybill_details.diesel_expense_id', '=', 'diesel_expenses.id')
            ->whereNull('waybill_details.deleted_at')
            ->whereBetween('waybill_details.transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(waybill_details.transaction_date, '%Y-%m') as month_key, COALESCE(SUM(diesel_expenses.amount), 0) as total_amount")
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->toArray();

        $totals = [];
        foreach (array_unique(array_merge(array_keys($waybillPart), array_keys($dieselByMonth))) as $monthKey) {
            $totals[$monthKey] = (float) ($waybillPart[$monthKey] ?? 0) + (float) ($dieselByMonth[$monthKey] ?? 0);
        }

        return $totals;
    }

    /**
     * Parts expense total = sum(quantity * amount_per_item) in range.
     */
    private function getPartsExpenseTotal(Carbon $start, Carbon $end): float
    {
        return round((float) PartsExpense::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(quantity * amount_per_item), 0) as total_amount')
            ->value('total_amount'), 2);
    }

    /**
     * Get the total diesel expense for the selected range (diesel_expenses.amount via waybill_details.transaction_date).
     */
    private function getDieselExpenseTotal(Carbon $start, Carbon $end): float
    {
        return round((float) DieselExpense::query()
            ->join('waybill_details', 'waybill_details.diesel_expense_id', '=', 'diesel_expenses.id')
            ->whereNull('waybill_details.deleted_at')
            ->whereBetween('waybill_details.transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('diesel_expenses.amount'), 2);
    }

    /**
     * Resolve SOA totals using booking_ids and waybill totals.
     *
     * @param array<int, int|string|null> $statementOfAccountIds
     * @return array<int, float>
     */
    private function getStatementOfAccountAmounts(array $statementOfAccountIds): array
    {
        $statementOfAccountIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => $id !== null ? (int) $id : null,
            $statementOfAccountIds
        ))));

        if ($statementOfAccountIds === []) {
            return [];
        }

        $statementOfAccounts = StatementOfAccount::query()
            ->whereIn('id', $statementOfAccountIds)
            ->get(['id', 'booking_ids']);

        $bookingToSoaMap = [];
        $amountsBySoa = [];

        foreach ($statementOfAccounts as $statementOfAccount) {
            $soaId = (int) $statementOfAccount->id;
            $amountsBySoa[$soaId] = 0.0;

            foreach ($statementOfAccount->booking_ids ?? [] as $bookingId) {
                $bookingId = (int) $bookingId;

                if ($bookingId <= 0) {
                    continue;
                }

                $bookingToSoaMap[$bookingId] ??= [];
                $bookingToSoaMap[$bookingId][] = $soaId;
            }
        }

        if ($bookingToSoaMap === []) {
            return $amountsBySoa;
        }

        $bookingTotals = WaybillDetail::query()
            ->whereIn('booking_id', array_keys($bookingToSoaMap))
            ->selectRaw('booking_id, COALESCE(SUM(total_rate_per_client), 0) as total_amount')
            ->groupBy('booking_id')
            ->pluck('total_amount', 'booking_id');

        foreach ($bookingToSoaMap as $bookingId => $soaIds) {
            $bookingAmount = (float) ($bookingTotals[$bookingId] ?? 0);

            foreach ($soaIds as $soaId) {
                $amountsBySoa[$soaId] += $bookingAmount;
            }
        }

        return $amountsBySoa;
    }
}