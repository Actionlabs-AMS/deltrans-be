<?php

namespace App\Services;

use App\Models\Helper;
use App\Models\Driver;
use App\Models\WaybillDetail;
use App\Http\Resources\HelperResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HelperService extends BaseService
{
    public function __construct()
    {
        // Pass the HelperResource class to the parent constructor
        parent::__construct(new HelperResource(new Helper), new Helper());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allHelpers = $this->getTotalCount();
            $trashedHelpers = $this->getTrashedCount();

            $query = Helper::query();

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('last_name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('contact_number', 'LIKE', '%' . request('search') . '%');
                });
            }

            if (request()->has('is_active')) {
                $query->where('helpers.is_active', request('is_active'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return HelperResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allHelpers, 'trashed' => $trashedHelpers]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch helpers: ' . $e->getMessage());
        }
    }

    public function deactivate_helper_by_id($id)
    {
        try {
            // 1. Find the truck or throw 404
            $truck = Helper::findOrFail($id);

            // 2. Only update the is_active to 0
            $truck->update(['is_active' => 0]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch helper details: ' . $e->getMessage());
        }
    }

    public function activate_helper_by_id($id)
    {
        try {
            // 1. Find the truck or throw ModelNotFoundException (404)
            $truck = Helper::findOrFail($id);

            // 2. Only update the is_active to 1
            $truck->update(['is_active' => 1]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch helper details: ' . $e->getMessage());
        }
    }

    public function getHelperById($id)
    {
        try {
            $helper = Helper::query()
                ->addSelect([
                    'helpers.*',
                    'assigned_truck_plate_numbers' => Driver::select(DB::raw('GROUP_CONCAT(assigned_truck_plate_numbers)'))
                        ->whereRaw('JSON_CONTAINS(drivers.helpers_id, CAST(helpers.id AS JSON))')
                ])
                ->where('helpers.id', $id)
                ->firstOrFail();

            // Return as a single HelperResource instance
            return new HelperResource($helper);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \Exception('Helper not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch helper details: ' . $e->getMessage());
        }
    }

    //   public function get_waybills_by_helper_id($id, $perPage = 10)
//   {
//       try {
//           // Strict filter by driver_id first
//           $query = WaybillDetail::where('helper_id', $id);

    //           // --- Single Search Box Logic (No Relationships) ---
//           if (request('search')) {
//               $searchTerm = request('search');

    //               $query->where(function($q) use ($searchTerm) {
//                   // Search Waybill Number string
//                   $q->where('waybill_number', 'LIKE', '%' . $searchTerm . '%')
//                     // Search Truck Plate Number string directly in this table
//                     ->orWhere('truck_plate_number', 'LIKE', '%' . $searchTerm . '%');

    //                   // Search Date (Only if the search term looks like a date YYYY-MM-DD)
//                   if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
//                       $q->orWhereDate('transaction_date', $searchTerm);
//                   }
//               });
//           }

    //           // --- Ordering ---
//           if (request('order')) {
//               $query->orderBy(request('order'), request('sort') ?? 'desc');
//           } else {
//               $query->orderBy('id', 'desc');
//           }

    //           return $query->paginate($perPage)->withQueryString();

    //       } catch (\Exception $e) {
//           throw new \Exception('Failed to fetch waybills: ' . $e->getMessage());
//       }
//   }

    public function get_waybills_by_helper_id($id, $perPage = 10, $searchTerm = null, $dateFrom = null, $dateTo = null)
    {
        try {
            // 1. Strict filter by helper_id first
            $query = WaybillDetail::where('helper_id', $id);

            // 2. --- Date Range Filter (Patterned after Driver History) ---
            if ($dateFrom && $dateTo) {
                $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
            }

            // 3. --- Single Search Box Logic ---
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    // Search Waybill Number
                    $q->where('waybill_number', 'LIKE', '%' . $searchTerm . '%')
                        // Search Truck Plate Number
                        ->orWhere('truck_plate_number', 'LIKE', '%' . $searchTerm . '%');

                    // Search specific Date if search term matches YYYY-MM-DD
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
                        $q->orWhereDate('transaction_date', $searchTerm);
                    }
                });
            }

            // 4. --- Ordering ---
            // Prioritize requested sort, otherwise default to latest transaction date
            $sortColumn = request('order', 'transaction_date');
            $sortDirection = request('sort', 'desc');

            $query->orderBy($sortColumn, $sortDirection);

            // 5. Return paginated results with query strings preserved
            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch waybills: ' . $e->getMessage());
        }
    }

    public function delete_helper_by_id($id)
    {
        try {

            $model = Helper::findOrFail($id);
            return $model->delete();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch helper details: ' . $e->getMessage());
        }
    }

    public function store(array $data)
    {
        // 1. Check if ANY trashed record exists with this Name OR this Contact Number
        $trashedHelper = Helper::onlyTrashed()
            ->where(function ($query) use ($data) {
                $query->where('contact_number', $data['contact_number'])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('first_name', $data['first_name'])
                            ->where('last_name', $data['last_name']);
                    });
            })
            ->first();

        if ($trashedHelper) {
            // 2. Restore and OVERWRITE with the new data
            // This handles the case where the name changed but the number is the same
            $trashedHelper->restore();
            $trashedHelper->update($data);

            return [
                'status' => true,
                'message' => 'Helper record restored and updated successfully.',
                'data' => $trashedHelper
            ];
        }

        // 3. Create new if nothing was found in trash
        $newHelper = Helper::create($data);

        return [
            'status' => true,
            'message' => 'Helper created successfully.',
            'data' => $newHelper
        ];
    }

    public function getActiveHelpers()
    {
        return Helper::where('is_active', 1)
            ->whereNull('deleted_at')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('id', 'asc')
            ->get();
    }
}









