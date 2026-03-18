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
    // public function list($perPage = 10, $search = null, $trash = false)
    // {
    //     try {
    //         $allFleetTruck = $this->getTotalCount();
    //         $trashedFleetTruck = $this->getTrashedCount();

    //         $query = FleetTruck::query();

    //         // Apply onlyTrashed() first if we're in trash view
    //         // if ($trash) {
    //         //     $query->onlyTrashed();
    //         // }

    //         // Then apply search conditions
    //         if (request('search')) {
    //             $query->where(function ($q) {
    //                 $q->where('plate_number', 'LIKE', '%' . request('search') . '%')
    //                     ->orWhere('condition', 'LIKE', '%' . request('search') . '%');
    //             });
    //         }

    //         // Filter by is_active
    //         if (request('is_active') !== null) {
    //             $query->where('is_active', request('is_active'));
    //         }

    //         // Apply ordering
    //         if (request('order')) {
    //             $query->orderBy(request('order'), request('sort') ?? 'asc');
    //         } else {
    //             $query->orderBy('id', 'desc');
    //         }

    //         return TruckResource::collection(
    //             $query->paginate($perPage)->withQueryString()
    //         )->additional(['meta' => ['all' => $allFleetTruck, 'trashed' => $trashedFleetTruck]]);
    //     } catch (\Exception $e) {
    //         throw new \Exception('Failed to fetch truck list: ' . $e->getMessage());
    //     }
    // }

    public function list($perPage = 10, $search = null, $trash = false)
    {
        try {
            $allFleetTruck = $this->getTotalCount();
            $trashedFleetTruck = $this->getTrashedCount();

            $query = FleetTruck::query();

            // 1. Apply Trash Filter
            if ($trash) {
                $query->onlyTrashed();
            }

            // 2. Apply Text Search
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('plate_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('condition', 'LIKE', '%' . request('search') . '%');
                });
            }

            // 3. 🌟 NEW: Filter by is_active
            // We use has() to ensure the key exists, then check the value
            if (request()->has('is_active') && request('is_active') !== '') {
                $query->where('is_active', request('is_active'));
            }

            // 4. Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return TruckResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional([
                'meta' => [
                    'all' => $allFleetTruck, 
                    'trashed' => $trashedFleetTruck
                ]
            ]);
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
            return $model->delete();

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

    public function store(array $data)
    {
        // 1. Check if a record with this plate exists in the trash
        $trashedTruck = FleetTruck::onlyTrashed()
            ->where('plate_number', $data['plate_number'])
            ->first();

        if ($trashedTruck) {
            // 2. Restore and update the existing record
            $trashedTruck->restore();
            $trashedTruck->update([
                'condition' => $data['condition'],
                'is_active' => 1, // Reset to active
            ]);

            return [
                'status' => true,
                'message' => 'Truck record restored successfully.',
                'data' => $trashedTruck
            ];
        }

        // 3. If no trash found, create a new record
        $newTruck = FleetTruck::create($data);

        return [
            'status' => true,
            'message' => 'Truck created successfully.',
            'data' => $newTruck
        ];
    }

    public function getActiveTrucks()
    {
        return FleetTruck::where('is_active', 1)
            ->whereNull('deleted_at') 
            ->select('id', 'plate_number', 'condition')
            ->orderBy('plate_number', 'asc')
            ->get();
    }
}

