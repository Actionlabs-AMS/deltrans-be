<?php

namespace App\Services;

use App\Models\RatePerClient;
use App\Http\Resources\RatePerClientResource;

class RatePerClientService extends BaseService
{
    public function __construct()
    {
        // Pass the RatePerClientResource class to the parent constructor
        parent::__construct(new RatePerClientResource(new RatePerClient), new RatePerClient());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allRatePerClients = $this->getTotalCount();
            $trashedRatePerClients = $this->getTrashedCount();

            $query = RatePerClient::query()->with(['shippingLine', 'cypa']);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('container_size', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('requirements', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('remarks', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypa', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Filter by is_active
            if (request('is_active') !== null) {
                $query->where('is_active', request('is_active'));
            }

            // Filter by shipping_line_id
            if (request('shipping_line_id')) {
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            // Filter by cypa_id
            if (request('cypa_id') !== null) {
                $query->where('cypa_id', request('cypa_id'));
            }

            // Filter by container_size
            if (request('container_size')) {
                $query->where('container_size', request('container_size'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return RatePerClientResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allRatePerClients, 'trashed' => $trashedRatePerClients]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch rate per clients: ' . $e->getMessage());
        }
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with(['shippingLine', 'cypa'])
            ->findOrFail($id);
        return $this->resource::make($model);
    }
}

