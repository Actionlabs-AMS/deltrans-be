<?php

namespace App\Services;

use App\Models\TruckMaintenance;
use App\Http\Resources\TruckMaintenanceResource;

class TruckService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TruckMaintenanceResource(new TruckMaintenance), new TruckMaintenance());
    }

    //todo: add filter

    public function listByTruckId(int $truckId, int $perPage, ?string $search)
    {
        try {
            
            $query = TruckMaintenanceRecord::where('truck_id', $truckId);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%");
                });
            }

            return $query->paginate($perPage);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck maintenance history: ' . $e->getMessage());
        }
    }
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
                        ->orWhere('condition', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('status', 'LIKE', '%' . request('search') . '%');
                });
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
            
            // 2. Only update the status to 0
            $truck->update(['status' => 0]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }

    public function activate_truck_by_id($id)
    {
        try {
            // 1. Find the truck or throw ModelNotFoundException (404)
            $truck = FleetTruck::findOrFail($id);                
            
            // 2. Only update the status to 1
            $truck->update(['status' => 1]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch truck details: ' . $e->getMessage());
        }
    }


}

