<?php

namespace App\Services;

use App\Models\BillingStatement;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\MediaLibrary;
use App\Models\PartsExpense;
use App\Models\StatementOfAccount;
use App\Models\Tag;
use App\Models\TruckTripExpense;
use App\Models\User;
use App\Models\WaybillDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardService
{
    /**
     * Get legacy dashboard statistics.
     */
    public function getStats(): array
    {
        return $this->getLegacyStats();
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
            'shipping_lines' => (clone $waybillQuery)
                ->distinct()
                ->count('shipping_line_id'),
            'waybills' => (clone $completedWaybillQuery)->count(),
            'waybills_total' => (clone $waybillQuery)->count(),
            'sales' => $this->getInvoiceBasedSalesTotal($start, $end),
            'expenses' => $this->getExpensesTotal($start, $end),
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
        $expenseByMonth = array_fill_keys($months, 0.0);

        foreach ($this->getInvoiceIncomeByMonth($start, $end) as $month => $amount) {
            if (array_key_exists($month, $incomeByMonth)) {
                $incomeByMonth[$month] = round((float) $amount, 2);
            }
        }

        foreach ($this->getExpenseTotalsByMonth($start, $end) as $month => $amount) {
            if (array_key_exists($month, $expenseByMonth)) {
                $expenseByMonth[$month] = round((float) $amount, 2);
            }
        }

        return [
            'months' => array_keys($incomeByMonth),
            'income' => array_values($incomeByMonth),
            'expenses' => array_values($expenseByMonth),
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
     * Get the enhanced stats payload used by widgets.
     */
    public function getEnhancedStats(array $filters = []): array
    {
        return array_merge(
            $this->getLegacyStats(),
            $this->getKpis($filters)
        );
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
     * Get the legacy stats used by the existing dashboard widgets.
     */
    private function getLegacyStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_media' => MediaLibrary::count(),
            'total_categories' => Category::count(),
            'total_tags' => Tag::count(),
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
     * Get invoice-based sales total for the selected range.
     */
    private function getInvoiceBasedSalesTotal(Carbon $start, Carbon $end): float
    {
        return round(array_sum($this->getInvoiceIncomeByMonth($start, $end)), 2);
    }

    /**
     * Get invoice-based income grouped by month.
     *
     * @return array<string, float>
     */
    private function getInvoiceIncomeByMonth(Carbon $start, Carbon $end): array
    {
        $invoiceSummaries = Invoice::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('statement_of_account_id, MIN(date) as invoice_date')
            ->groupBy('statement_of_account_id')
            ->get();

        $amountsBySoa = $this->getStatementOfAccountAmounts(
            $invoiceSummaries->pluck('statement_of_account_id')->all()
        );

        $incomeByMonth = [];

        foreach ($invoiceSummaries as $invoiceSummary) {
            $monthKey = Carbon::parse($invoiceSummary->invoice_date)->format('Y-m');
            $soaId = (int) $invoiceSummary->statement_of_account_id;

            $incomeByMonth[$monthKey] = ($incomeByMonth[$monthKey] ?? 0)
                + (float) ($amountsBySoa[$soaId] ?? 0);
        }

        return $incomeByMonth;
    }

    /**
     * Get the total expenses for the selected range.
     */
    private function getExpensesTotal(Carbon $start, Carbon $end): float
    {
        $waybillExpenses = (float) WaybillDetail::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_expense');

        $truckTripExpenses = (float) TruckTripExpense::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('issued_cash_amount');

        $partsExpenses = (float) PartsExpense::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(quantity * amount_per_item), 0) as total_amount')
            ->value('total_amount');

        return round($waybillExpenses + $truckTripExpenses + $partsExpenses, 2);
    }

    /**
     * Get monthly expense totals for the selected range.
     *
     * @return array<string, float>
     */
    private function getExpenseTotalsByMonth(Carbon $start, Carbon $end): array
    {
        $totals = [];

        $waybillExpenses = WaybillDetail::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, COALESCE(SUM(total_expense), 0) as total_amount")
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->toArray();

        $truckTripExpenses = TruckTripExpense::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, COALESCE(SUM(issued_cash_amount), 0) as total_amount")
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->toArray();

        $partsExpenses = PartsExpense::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, COALESCE(SUM(quantity * amount_per_item), 0) as total_amount")
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->toArray();

        foreach ([$waybillExpenses, $truckTripExpenses, $partsExpenses] as $sourceTotals) {
            foreach ($sourceTotals as $monthKey => $amount) {
                $totals[$monthKey] = ($totals[$monthKey] ?? 0) + (float) $amount;
            }
        }

        return $totals;
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