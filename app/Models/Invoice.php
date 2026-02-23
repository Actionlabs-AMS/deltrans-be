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
        'statement_of_account_id',
        'invoice_number',
        'date',
        'discount',
        'discount_id',
    ];

    protected $casts = [
        'statement_of_account_id' => 'integer',
        'date' => 'date',
        'discount' => 'decimal:2',
        'discount_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the statement of account associated with the invoice.
     */
    public function statementOfAccount()
    {
        return $this->belongsTo(StatementOfAccount::class, 'statement_of_account_id');
    }

    /**
     * Get the shipping line via the statement of account.
     */
    public function shippingLine()
    {
        return $this->hasOneThrough(
            ShippingLine::class,
            StatementOfAccount::class,
            'id',
            'id',
            'statement_of_account_id',
            'shipping_line_id'
        );
    }
}
