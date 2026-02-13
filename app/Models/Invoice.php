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
        'quantity',
        'unit_price',
        'item_description',
        'vatable_sales',
        'zero_rated_sales',
        'vat_exempt_sales',
        'vat',
        'total_sales',
        'less_vat',
        'net_of_vat',
        'discount',
        'discount_id',
        'less_withdrawing_tax',
        'total_amount',
    ];

    protected $casts = [
        'statement_of_account_id' => 'integer',
        'date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'vatable_sales' => 'decimal:2',
        'zero_rated_sales' => 'decimal:2',
        'vat_exempt_sales' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'less_vat' => 'decimal:2',
        'net_of_vat' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_id' => 'integer',
        'less_withdrawing_tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
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
