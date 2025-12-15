<?php

namespace App\Services;

use App\Models\StatementOfAccount;
use App\Http\Resources\StatementOfAccountResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StatementOfAccountService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new StatementOfAccountResource(new StatementOfAccount), new StatementOfAccount());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allSoa = $this->getTotalCount();
            $trashedSoa = $this->getTrashedCount();

            $query = StatementOfAccount::query();

            if ($trash) {
                $query->onlyTrashed();
            }

            // Apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('dli_sa_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($query) {
                            $query->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return StatementOfAccountResource::collection(
                $query->with('shippingLine')->paginate($perPage)->withQueryString()
            )->additional([
                        'meta' => [
                            'all' => $allSoa,
                            'trashed' => $trashedSoa
                        ]
                    ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch statement of accounts: ' . $e->getMessage());
        }
    }

    /**
     * Generate a new statement of account.
     *
     * @param array $data
     * @return StatementOfAccountResource
     */
    public function generate(array $data)
    {
        try {
            // Validate shipping line exists
            $shippingLine = \App\Models\ShippingLine::findOrFail($data['shipping_line_id']);

            // Create the statement of account
            $soa = StatementOfAccount::create($data);

            // Load the relationship
            $soa->load('shippingLine');

            return StatementOfAccountResource::make($soa);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Shipping line not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate statement of account: ' . $e->getMessage());
        }
    }
}
