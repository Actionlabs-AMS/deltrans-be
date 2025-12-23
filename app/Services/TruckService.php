<?php

namespace App\Services;

use App\Models\FleetTruck;
use App\Http\Resources\TruckResource;

class TruckService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TruckResource(new FleetTruck), new FleetTruck());
    }

    //todo: add filter

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $search = null, $trash = false)
    {
        try {
            $allFleetTruck = $this->getTotalCount();
            $trashedFleetTruck = $this->getTrashedCount();

            $query = FleetTruck::query();

            // Apply onlyTrashed() first if we're in trash view
            // if ($trash) {
            //     $query->onlyTrashed();
            // }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('plate_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('condition', 'LIKE', '%' . request('search') . '%');
                });
            }

            // Filter by is_active
            if (request('is_active') !== null) {
                $query->where('is_active', request('is_active'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return TruckResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allFleetTruck, 'trashed' => $trashedFleetTruck]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck list: ' . $e->getMessage());
        }
    }

    public function get_truck_by_id($id) {
        try {
            return FleetTruck::findOrFail($id);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }

    public function delete_truck_by_id($id) {
        try {

            $model = FleetTruck::findOrFail($id);
            return $model->forcedelete();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }

    public function deactivate_truck_by_id($id)
    {
         try {
             // 1. Find the truck or throw 404
            $truck = FleetTruck::findOrFail($id);                
            
            // 2. Only update the is_active to 0
            $truck->update(['is_active' => 0]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }

    public function activate_truck_by_id($id)
    {
        try {
            // 1. Find the truck or throw ModelNotFoundException (404)
            $truck = FleetTruck::findOrFail($id);                
            
            // 2. Only update the is_active to 1
            $truck->update(['is_active' => 1]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }


}

