<?php

namespace App\Services;

use App\Models\ShippingLine;
use App\Http\Resources\ShippingLineResource;

class ShippingLineService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ShippingLineResource(new ShippingLine), new ShippingLine());
    }

    //todo: add filter

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allShippingLines = $this->getTotalCount();
            $trashedShippingLines = $this->getTrashedCount();

            $query = ShippingLine::query();

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('email_address', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('contact_name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('contact_mobile', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('tin', 'LIKE', '%' . request('search') . '%');
                });
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return ShippingLineResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allShippingLines, 'trashed' => $trashedShippingLines]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch shipping lines: ' . $e->getMessage());
        }
    }
}

