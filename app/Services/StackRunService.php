<?php

namespace App\Services;

use App\Models\StackRun;
use App\Http\Resources\StackRunResource;

class StackRunService extends BaseService
{
    public function __construct()
    {
        // Pass the StackRunResource class to the parent constructor
        parent::__construct(new StackRunResource(new StackRun), new StackRun());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        // Remove is_complete and total_amount from data if provided
        // These fields are fillable but not allowed in this API (other APIs will update them)
        unset($data['is_complete'], $data['total_amount']);

        // Create the model - defaults will be applied from model $attributes
        $model = $this->model::create($data);

        return $this->resource::make($model->fresh());
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with(['shippingLine', 'cypaFrom', 'cypaTo', 'containers'])
            ->findOrFail($id);

        // Count waybills for this stack run
        $actualNoOfWaybill = \Illuminate\Support\Facades\DB::table('waybill_details')
            ->where('stack_run_id', $id)
            ->whereNull('deleted_at')
            ->count();

        // Add the count to the model as an attribute
        $model->actual_no_of_waybill = $actualNoOfWaybill;

        return $this->resource::make($model);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, $id)
    {
        // Remove is_complete and total_amount from data if provided
        // These fields are fillable but not allowed in this API (other APIs will update them)
        unset($data['is_complete'], $data['total_amount']);

        $model = $this->model::findOrFail($id);
        $model->update($data);
        return $this->resource::make($model->fresh()->load(['shippingLine', 'cypaFrom', 'cypaTo', 'containers']));
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allStackRuns = $this->getTotalCount();
            $trashedStackRuns = $this->getTrashedCount();

            $query = StackRun::query()->with(['shippingLine', 'cypaFrom', 'cypaTo']);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('container_size', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaFrom', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaTo', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return StackRunResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allStackRuns, 'trashed' => $trashedStackRuns]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch stack runs: ' . $e->getMessage());
        }
    }
}


