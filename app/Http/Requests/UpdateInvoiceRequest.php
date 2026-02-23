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
            'discount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
        ];
    }
}
