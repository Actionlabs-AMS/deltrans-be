<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'date',
        'discount',
        'discount_id',
    ];

    protected $appends = [
        'statement_of_account_ids',
    ];

    protected $casts = [
        'date' => 'date',
        'discount' => 'decimal:2',
        'discount_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Statements of account linked to this invoice (many-to-many).
     */
    public function statementOfAccounts()
    {
        return $this->belongsToMany(
            StatementOfAccount::class,
            'invoice_statement_of_account',
            'invoice_id',
            'statement_of_account_id'
        )->withTimestamps();
    }

    /**
     * Array of linked SOA IDs (included in API responses via $appends).
     *
     * @return array<int>
     */
    public function getStatementOfAccountIdsAttribute(): array
    {
        if ($this->relationLoaded('statementOfAccounts')) {
            return $this->statementOfAccounts
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (!$this->exists) {
            return [];
        }

        return $this->statementOfAccounts()
            ->pluck('statement_of_accounts.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * First linked SOA (for shipping line / email recipient).
     */
    public function primaryStatementOfAccount(): ?StatementOfAccount
    {
        if ($this->relationLoaded('statementOfAccounts')) {
            return $this->statementOfAccounts->first();
        }

        return $this->statementOfAccounts()->with('shippingLine')->first();
    }

    /**
     * Shipping line via the first linked statement of account.
     */
    public function getShippingLineAttribute(): ?ShippingLine
    {
        return $this->primaryStatementOfAccount()?->shippingLine;
    }

    /**
     * True when the invoice has at least one linked billing statement
     * and every linked billing statement is paid.
     */
    public function isPaid(): bool
    {
        $soaIds = $this->statement_of_account_ids;
        if ($soaIds === []) {
            return false;
        }

        $statuses = BillingStatement::query()
            ->whereIn('statement_of_account_id', $soaIds)
            ->pluck('is_paid');

        return $statuses->isNotEmpty() && $statuses->every(fn ($paid) => (bool) $paid);
    }

    /**
     * Paid status keyed by invoice id, using one billing query for the set.
     *
     * @param  iterable<Invoice>  $invoices
     * @return array<int, bool>
     */
    public static function paidStatusMap(iterable $invoices): array
    {
        $invoices = collect($invoices);
        $soaIds = $invoices
            ->flatMap(fn (Invoice $invoice) => $invoice->statement_of_account_ids)
            ->unique()
            ->values()
            ->all();

        $soaIdsWithBilling = [];
        $unpaidSoaIds = [];

        if ($soaIds !== []) {
            $rows = BillingStatement::query()
                ->whereIn('statement_of_account_id', $soaIds)
                ->get(['statement_of_account_id', 'is_paid']);

            foreach ($rows as $row) {
                $soaIdsWithBilling[(int) $row->statement_of_account_id] = true;
                if (!$row->is_paid) {
                    $unpaidSoaIds[(int) $row->statement_of_account_id] = true;
                }
            }
        }

        $map = [];
        foreach ($invoices as $invoice) {
            $hasBilling = false;
            $hasUnpaid = false;
            foreach ($invoice->statement_of_account_ids as $soaId) {
                $soaId = (int) $soaId;
                if (isset($soaIdsWithBilling[$soaId])) {
                    $hasBilling = true;
                }
                if (isset($unpaidSoaIds[$soaId])) {
                    $hasUnpaid = true;
                }
            }
            $map[(int) $invoice->id] = $hasBilling && !$hasUnpaid;
        }

        return $map;
    }
}
