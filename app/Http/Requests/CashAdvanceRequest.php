<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // GET (index): type optional (0, 1, 2 or null)
        // GET (show): type required (1 or 2) because IDs can overlap across tables
        if ($this->isMethod('GET') && !$this->route('id')) {
            return [
                'type' => 'nullable|integer|in:0,1,2',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'transaction_date_from' => 'nullable|date',
                'transaction_date_to' => 'nullable|date',
                'created_at_from' => 'nullable|date',
                'created_at_to' => 'nullable|date',
            ];
        }
        if ($this->isMethod('GET') && $this->route('id')) {
            return [
                'type' => 'required|integer|in:1,2',
            ];
        }

        // POST: type required (1 or 2), requestor_id = driver id when type=1, helper id when type=2
        if ($this->isMethod('POST')) {
            $rules = [
                'type' => 'required|integer|in:1,2',
                'requestor_id' => 'required|integer|min:1',
                'amount' => 'required|numeric|min:0|max:999999.99',
                'transaction_date' => 'required|date|before_or_equal:today',
                'shift' => ['required', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            ];
            return $rules;
        }

        // PATCH: type required (1 or 2)
        if ($this->isMethod('PATCH')) {
            return [
                'type' => 'required|integer|in:1,2',
                'amount' => 'sometimes|required|numeric|min:0|max:999999.99',
                'transaction_date' => 'sometimes|required|date|before_or_equal:today',
                'shift' => ['sometimes', 'required', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            ];
        }

        // DELETE: type required (1 or 2)
        if ($this->isMethod('DELETE')) {
            return [
                'type' => 'required|integer|in:1,2',
            ];
        }

        return [];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type') && is_string($this->type)) {
            $this->merge(['type' => (int) $this->type]);
        }
        // Allow type in query for PATCH (e.g. ?type=1)
        if (($this->isMethod('PATCH') || $this->isMethod('PUT')) && $this->query('type') && !$this->has('type')) {
            $this->merge(['type' => (int) $this->query('type')]);
        }

        // Backward-compatible: accept driver_id/helper_id instead of requestor_id on POST
        if ($this->isMethod('POST') && !$this->has('requestor_id')) {
            $type = $this->has('type') ? (int) $this->type : null;
            if ($type === 1 && $this->has('driver_id')) {
                $this->merge(['requestor_id' => (int) $this->driver_id]);
            }
            if ($type === 2 && $this->has('helper_id')) {
                $this->merge(['requestor_id' => (int) $this->helper_id]);
            }
        }

        // Allow type in query for GET show and DELETE (e.g. /cash-advances/{id}?type=1)
        if (($this->isMethod('GET') || $this->isMethod('DELETE')) && $this->query('type') && !$this->has('type')) {
            $this->merge(['type' => (int) $this->query('type')]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->isMethod('POST') || !$this->has('type') || !$this->has('requestor_id')) {
                return;
            }
            $type = (int) $this->type;
            $requestorId = (int) $this->requestor_id;
            if ($type === 1 && !\App\Models\Driver::where('id', $requestorId)->exists()) {
                $validator->errors()->add('requestor_id', 'The selected requestor_id does not exist in drivers.');
            }
            if ($type === 2 && !\App\Models\Helper::where('id', $requestorId)->exists()) {
                $validator->errors()->add('requestor_id', 'The selected requestor_id does not exist in helpers.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_date.before_or_equal' => 'You cannot record a cash advance for a future date.',
            'type.required' => 'The type field is required (1 = driver, 2 = helper).',
            'type.in' => 'Type must be 1 (driver) or 2 (helper).',
        ];
    }
}
