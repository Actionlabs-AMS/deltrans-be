<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $invoiceId = $this->route('id');

        return [
            'invoice_number' => 'nullable|string|max:255|unique:invoices,invoice_number,' . $invoiceId,
            'date' => 'nullable|date',
            'quantity' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'item_description' => 'nullable|string',
            'vatable_sales' => 'nullable|numeric|min:0',
            'zero_rated_sales' => 'nullable|numeric|min:0',
            'vat_exempt_sales' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'total_sales' => 'nullable|numeric|min:0',
            'less_vat' => 'nullable|numeric|min:0',
            'net_of_vat' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
            'less_withdrawing_tax' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ];
    }
}
