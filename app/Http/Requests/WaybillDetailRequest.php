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
            'booking_id' => 'required|integer|exists:bookings,id',
            'driver_id' => 'required|integer|exists:drivers,id',
            'helper_id' => 'nullable|array',
            'helper_id.*' => 'integer|exists:helpers,id',
            'container_size' => 'required|string|max:255',
            'truck_plate_number' => 'required|string|exists:fleet_trucks,plate_number',
            'fixed_expense_id' => 'required|integer|exists:fixed_expenses,id',
            'rate_per_client_id' => 'nullable|integer|exists:rate_per_clients,id',
            'pickup_date' => 'required|date',
            'delivered_date' => 'required|date|after_or_equal:pickup_date',
            'post_expense_amount' => 'nullable|numeric|min:0',
            'total_rate_per_client' => 'nullable|numeric|min:0',
            'total_expense' => 'nullable|numeric|min:0',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['waybill_number'] = 'sometimes|required|string|max:255|unique:waybill_details,waybill_number,' . $this->route('id');
            $rules['transaction_date'] = 'sometimes|required|date';
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['booking_id'] = 'sometimes|required|integer|exists:bookings,id';
            $rules['driver_id'] = 'sometimes|required|integer|exists:drivers,id';
            $rules['helper_id'] = 'sometimes|nullable|array';
            $rules['helper_id.*'] = 'integer|exists:helpers,id';
            $rules['container_size'] = 'sometimes|required|string|max:255';
            $rules['truck_plate_number'] = 'sometimes|required|string|exists:fleet_trucks,plate_number';
            $rules['fixed_expense_id'] = 'sometimes|required|integer|exists:fixed_expenses,id';
            $rules['rate_per_client_id'] = 'sometimes|nullable|integer|exists:rate_per_clients,id';
            $rules['pickup_date'] = 'sometimes|required|date';
            $rules['delivered_date'] = 'sometimes|required|date|after_or_equal:pickup_date';
            $rules['total_rate_per_client'] = 'sometimes|nullable|numeric|min:0';
            $rules['total_expense'] = 'sometimes|nullable|numeric|min:0';
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
            'booking_id.required' => 'The booking is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'driver_id.required' => 'The driver is required.',
            'driver_id.exists' => 'The selected driver does not exist.',
            'helper_id.array' => 'The helper ID must be an array.',
            'helper_id.*.integer' => 'Each helper ID must be an integer.',
            'helper_id.*.exists' => 'One or more selected helpers do not exist.',
            'container_size.required' => 'The container size is required.',
            'container_size.string' => 'The container size must be a string.',
            'container_size.max' => 'The container size may not be greater than 255 characters.',
            'truck_plate_number.required' => 'The truck plate number is required.',
            'truck_plate_number.exists' => 'The selected truck plate number does not exist.',
            'fixed_expense_id.required' => 'The fixed expense is required.',
            'fixed_expense_id.exists' => 'The selected fixed expense does not exist.',
            'rate_per_client_id.exists' => 'The selected rate per client does not exist.',
            'total_rate_per_client.numeric' => 'The total rate per client must be a valid number.',
            'total_rate_per_client.min' => 'The total rate per client must be at least 0.',
            'total_expense.numeric' => 'The total expense must be a valid number.',
            'total_expense.min' => 'The total expense must be at least 0.',
            'pickup_date.required' => 'The pickup date is required.',
            'pickup_date.date' => 'The pickup date must be a valid date.',
            'delivered_date.required' => 'The delivered date is required.',
            'delivered_date.date' => 'The delivered date must be a valid date.',
            'delivered_date.after_or_equal' => 'The delivered date must be after or equal to the pickup date.',
            'post_expense_amount.numeric' => 'The post expense amount must be a valid number.',
            'post_expense_amount.min' => 'The post expense amount must be at least 0.',
        ];
    }
}

