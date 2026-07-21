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
}
