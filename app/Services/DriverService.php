<?php

namespace App\Services;

use App\Models\Driver;
use App\Http\Resources\DriverResource;

class DriverService extends BaseService
{
  public function __construct()
  {
      // Pass the DriverResource class to the parent constructor
      parent::__construct(new DriverResource(new Driver), new Driver());
  }
  
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allDrivers = $this->getTotalCount();
      $trashedDrivers = $this->getTrashedCount();

      $query = Driver::query();
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where(function($q) {
          $q->where('first_name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('last_name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('contact_number', 'LIKE', '%' . request('search') . '%');
        });
      }

      // Filter by is_active
      if (request('is_active') !== null) {
        $query->where('is_active', request('is_active'));
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
      } else {
        $query->orderBy('id', 'desc');
      }

      return DriverResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allDrivers, 'trashed' => $trashedDrivers]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch drivers: ' . $e->getMessage());
    }
  }
}

