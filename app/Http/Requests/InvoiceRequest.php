<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use Illuminate\Support\Facades\DB;

class InvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'statement_of_account_ids' => 'required|array|min:1',
            'statement_of_account_ids.*' => 'integer|exists:statement_of_accounts,id',
            'invoice_number' => 'nullable|string|max:255|unique:invoices,invoice_number',
            'date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
        ];
    }

    public function messages()
    {
        return [
            'statement_of_account_ids.required' => 'At least one statement of account is required.',
            'statement_of_account_ids.min' => 'At least one statement of account is required.',
            'statement_of_account_ids.*.exists' => 'One or more selected statements of account do not exist.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $soaIds = array_values(array_unique(array_map(
                'intval',
                $this->input('statement_of_account_ids', [])
            )));

            if (count($soaIds) < 1) {
                return;
            }

            $soas = StatementOfAccount::whereIn('id', $soaIds)->get(['id', 'shipping_line_id']);

            if ($soas->count() !== count($soaIds)) {
                $validator->errors()->add(
                    'statement_of_account_ids',
                    'One or more selected statements of account do not exist.'
                );
                return;
            }

            $shippingLineIds = $soas->pluck('shipping_line_id')->unique()->filter()->values();
            if ($shippingLineIds->count() > 1) {
                $validator->errors()->add(
                    'statement_of_account_ids',
                    'All selected statements of account must belong to the same shipping line.'
                );
            }

            $alreadyLinked = DB::table('invoice_statement_of_account')
                ->whereIn('statement_of_account_id', $soaIds)
                ->pluck('statement_of_account_id')
                ->unique()
                ->values()
                ->all();

            if (!empty($alreadyLinked)) {
                $validator->errors()->add(
                    'statement_of_account_ids',
                    'One or more selected statements of account are already linked to an invoice: '
                        . implode(', ', $alreadyLinked) . '.'
                );
                return;
            }

            // Type 3 rules: every SOA must have bookings with at least one waybill.
            $soasWithBookings = StatementOfAccount::whereIn('id', $soaIds)->get(['id', 'booking_ids']);
            foreach ($soasWithBookings as $soa) {
                $bookingIds = array_values(array_unique(array_map('intval', $soa->booking_ids ?? [])));
                if (empty($bookingIds)) {
                    $validator->errors()->add(
                        'statement_of_account_ids',
                        "Statement of account {$soa->id} has no bookings and cannot be invoiced."
                    );
                    continue;
                }

                $hasWaybill = WaybillDetail::whereIn('booking_id', $bookingIds)->exists();
                if (!$hasWaybill) {
                    $validator->errors()->add(
                        'statement_of_account_ids',
                        "Statement of account {$soa->id} has bookings without waybills and cannot be invoiced."
                    );
                }
            }
        });
    }
}
