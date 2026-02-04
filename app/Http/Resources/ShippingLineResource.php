<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SoaDataOption;
use App\Models\RatePerClient;

class ShippingLineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get shipping lines template data with name and description
        $shippingLinesTemplate = [];
        if (!empty($this->shipping_lines_template)) {
            $options = SoaDataOption::whereIn('id', $this->shipping_lines_template)
                ->get(['id', 'name', 'description']);
            $shippingLinesTemplate = $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'description' => $option->description,
                ];
            })->toArray();
        }

        // Get transaction information template data with name and description
        $transactionInformationTemplate = [];
        if (!empty($this->transaction_information_template)) {
            $options = SoaDataOption::whereIn('id', $this->transaction_information_template)
                ->get(['id', 'name', 'description']);
            $transactionInformationTemplate = $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'description' => $option->description,
                ];
            })->toArray();
        }

        // Get tax_percent from rate_per_clients based on shipping_line_id
        $taxPercent = null;
        $ratePerClient = RatePerClient::where('shipping_line_id', $this->id)
            ->where('is_active', 1)
            ->whereNotNull('tax_percent')
            ->first();
        if ($ratePerClient) {
            $taxPercent = $ratePerClient->tax_percent;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email_address' => $this->email_address,
            'address' => $this->address,
            'contact_name' => $this->contact_name,
            'contact_mobile' => $this->contact_mobile,
            'landlines' => $this->landlines ?? [],
            'shipping_lines_template' => $shippingLinesTemplate,
            'transaction_information_template' => $transactionInformationTemplate,
            'fax_no' => $this->fax_no,
            'tin' => $this->tin,
            'tax_percent' => $taxPercent,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

