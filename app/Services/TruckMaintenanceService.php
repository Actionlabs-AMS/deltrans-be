<?php

namespace App\Services;

use App\Models\TruckMaintenance;
use App\Models\FleetTruck;
use App\Http\Resources\TruckMaintenanceResource;


class TruckMaintenanceService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TruckMaintenanceResource(new TruckMaintenance), new TruckMaintenance());
    }

    public function listByTruckId(int $truckId, int $perPage, ?string $search, ?string $dateFrom = null, ?string $dateTo = null)
    {
        try {
            // 1. Retrieve the Plate Number using the provided Truck ID
            $truck = FleetTruck::find($truckId);

            if (!$truck) {
                // Handle case where the truck ID is invalid
                throw new \Exception("Truck with ID {$truckId} not found in the fleet.", 404);
            }
            
            $plateNumber = $truck->plate_number;

            // 2. Start the query using the plate number on the correct column
            // Note: The column name is 'fleet_truck_plate_number' as defined in your model
            $query = TruckMaintenance::where('fleet_truck_plate_number', $plateNumber);

            // Apply search filter (existing logic)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                      ->orWhere('article', 'like', "%{$search}%");
                });
            }

            // START: NEW DATE FILTER LOGIC
            if ($dateFrom) {
                // Query records created ON or AFTER the dateFrom
                $query->whereDate('maintenance_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                // Query records created ON or BEFORE the dateTo
                $query->whereDate('maintenance_date', '<=', $dateTo);
            }
            // END: NEW DATE FILTER LOGIC

            return $query->paginate($perPage);

        } catch (\Exception $e) {
            // Re-throw with more specific error details
            throw new \Exception('Failed to fetch truck maintenance history: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a maintenance record after verifying ownership via plate number.
     */
    public function deleteMaintenance(int $truckId, int $maintenanceId)
    {
        // 1. Find the truck
        $truck = FleetTruck::findOrFail($truckId);
        
        // 2. Find the maintenance record
        $maintenance = TruckMaintenance::findOrFail($maintenanceId);

        // 3. Business Logic: Ownership Check
        if ($maintenance->fleet_truck_plate_number !== $truck->plate_number) {
            throw new Exception("This record does not belong to the specified truck.", 403);
        }

        // 4. Action
        return $maintenance->delete();
    }

    public function updateMaintenance(int $truckId, int $maintenanceId, array $data)
    {
        // 1. Find the truck and the record
        $truck = FleetTruck::findOrFail($truckId);
        $maintenance = TruckMaintenance::findOrFail($maintenanceId);

        // 2. Ownership Check: Verify via plate number
        if ($maintenance->fleet_truck_plate_number !== $truck->plate_number) {
            throw new \Exception("This record does not belong to the specified truck.", 403);
        }

        // 3. Update the record with the new data
        $maintenance->update($data);

        return $maintenance;
    }


}

