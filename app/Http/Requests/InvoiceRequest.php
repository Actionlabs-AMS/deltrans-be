<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'statement_of_account_id' => 'required|exists:statement_of_accounts,id',
            'invoice_number' => 'nullable|string|max:255|unique:invoices,invoice_number',
            'date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
        ];
    }
}
