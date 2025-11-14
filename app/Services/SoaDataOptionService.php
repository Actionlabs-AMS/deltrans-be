<?php

namespace App\Services;

use App\Models\SoaDataOption;
use App\Http\Resources\SoaDataOptionResource;

class SoaDataOptionService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new SoaDataOptionResource(new SoaDataOption), new SoaDataOption());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allOptions = $this->getTotalCount();
            $trashedOptions = $this->getTrashedCount();

            $query = SoaDataOption::query();

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('description', 'LIKE', '%' . request('search') . '%');
                });
            }

            // Filter by parent_id if provided
            if (request()->has('parent_id')) {
                $parentId = request('parent_id');
                if ($parentId === 'null' || $parentId === null) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'asc');
            }

            return SoaDataOptionResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allOptions, 'trashed' => $trashedOptions]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch SOA data options: ' . $e->getMessage());
        }
    }

    /**
     * Get parent options (where parent_id is null).
     */
    public function getParents()
    {
        return SoaDataOptionResource::collection(
            SoaDataOption::whereNull('parent_id')->orderBy('id', 'asc')->get()
        );
    }

    /**
     * Get children options for a specific parent.
     */
    public function getChildren($parentId)
    {
        return SoaDataOptionResource::collection(
            SoaDataOption::where('parent_id', $parentId)->orderBy('id', 'asc')->get()
        );
    }
}

