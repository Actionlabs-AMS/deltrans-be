<?php

namespace App\Services;

use App\Models\Helper;
use App\Http\Resources\HelperResource;

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
        $query->where(function($q) {
          $q->where('first_name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('last_name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('contact_number', 'LIKE', '%' . request('search') . '%');
        });
      }

      // Filter by active status
      if (request('active_status') !== null) {
        $query->where('active_status', request('active_status'));
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
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
}

