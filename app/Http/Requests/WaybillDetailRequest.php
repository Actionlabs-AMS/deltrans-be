<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaybillDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'waybill_number' => 'required|string|max:255|unique:waybill_details,waybill_number',
            'transaction_date' => 'required|date',
            'shipping_line_id' => 'required|integer|exists:shipping_lines,id',
            'stack_run_id' => 'required|integer|exists:stack_runs,id',
            'driver_id' => 'required|integer|exists:drivers,id',
            'helper_id' => 'required|integer|exists:helpers,id',
            'truck_plate_number' => 'required|string|exists:fleet_trucks,plate_number',
            'fixed_expense_id' => 'required|integer|exists:fixed_expenses,id',
            'rate_per_client_id' => 'required|integer|exists:rate_per_clients,id',
            'extra_money' => 'nullable|numeric|min:0',
            'pickup_date' => 'required|date',
            'delivered_date' => 'required|date|after_or_equal:pickup_date',
            'post_expense_amount' => 'nullable|numeric|min:0',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['waybill_number'] = 'sometimes|required|string|max:255|unique:waybill_details,waybill_number,' . $this->route('id');
            $rules['transaction_date'] = 'sometimes|required|date';
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['stack_run_id'] = 'sometimes|required|integer|exists:stack_runs,id';
            $rules['driver_id'] = 'sometimes|required|integer|exists:drivers,id';
            $rules['helper_id'] = 'sometimes|required|integer|exists:helpers,id';
            $rules['truck_plate_number'] = 'sometimes|required|string|exists:fleet_trucks,plate_number';
            $rules['fixed_expense_id'] = 'sometimes|required|integer|exists:fixed_expenses,id';
            $rules['rate_per_client_id'] = 'sometimes|required|integer|exists:rate_per_clients,id';
            $rules['pickup_date'] = 'sometimes|required|date';
            $rules['delivered_date'] = 'sometimes|required|date|after_or_equal:pickup_date';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'waybill_number.required' => 'The waybill number is required.',
            'waybill_number.unique' => 'The waybill number has already been taken.',
            'transaction_date.required' => 'The transaction date is required.',
            'transaction_date.date' => 'The transaction date must be a valid date.',
            'shipping_line_id.required' => 'The shipping line is required.',
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'stack_run_id.required' => 'The stack run is required.',
            'stack_run_id.exists' => 'The selected stack run does not exist.',
            'driver_id.required' => 'The driver is required.',
            'driver_id.exists' => 'The selected driver does not exist.',
            'helper_id.required' => 'The helper is required.',
            'helper_id.exists' => 'The selected helper does not exist.',
            'truck_plate_number.required' => 'The truck plate number is required.',
            'truck_plate_number.exists' => 'The selected truck plate number does not exist.',
            'fixed_expense_id.required' => 'The fixed expense is required.',
            'fixed_expense_id.exists' => 'The selected fixed expense does not exist.',
            'rate_per_client_id.required' => 'The rate per client is required.',
            'rate_per_client_id.exists' => 'The selected rate per client does not exist.',
            'pickup_date.required' => 'The pickup date is required.',
            'pickup_date.date' => 'The pickup date must be a valid date.',
            'delivered_date.required' => 'The delivered date is required.',
            'delivered_date.date' => 'The delivered date must be a valid date.',
            'extra_money.numeric' => 'The extra money must be a valid number.',
            'extra_money.min' => 'The extra money must be at least 0.',
            'pickup_date.date' => 'The pickup date must be a valid date.',
            'delivered_date.date' => 'The delivered date must be a valid date.',
            'delivered_date.after_or_equal' => 'The delivered date must be after or equal to the pickup date.',
            'post_expense_amount.numeric' => 'The post expense amount must be a valid number.',
            'post_expense_amount.min' => 'The post expense amount must be at least 0.',
        ];
    }
}

