<?php

namespace App\Services;

use App\Models\FixedExpense;
use App\Http\Resources\FixedExpenseResource;

class FixedExpenseService extends BaseService
{
    public function __construct()
    {
        // Pass the FixedExpenseResource class to the parent constructor
        parent::__construct(new FixedExpenseResource(new FixedExpense), new FixedExpense());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        // Remove total_expenses from data if provided
        // This field is auto-calculated
        unset($data['total_expenses']);

        // Create the model - total_expenses will be auto-calculated in the model boot method
        $model = $this->model::create($data);

        return $this->resource::make($model->fresh());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, $id)
    {
        // Remove total_expenses from data if provided
        // This field is auto-calculated
        unset($data['total_expenses']);

        $model = $this->model::findOrFail($id);
        $model->update($data);
        
        // total_expenses will be auto-calculated in the model boot method
        return $this->resource::make($model->fresh()->load(['shippingLine', 'cypaFrom', 'cypaTo']));
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allFixedExpenses = $this->getTotalCount();
            $trashedFixedExpenses = $this->getTrashedCount();

            $query = FixedExpense::query()->with(['shippingLine', 'cypaFrom', 'cypaTo']);

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

            // Filter by shipping_line_id
            if (request('shipping_line_id')) {
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            // Filter by cypa_id_from
            if (request('cypa_id_from')) {
                $query->where('cypa_id_from', request('cypa_id_from'));
            }

            // Filter by cypa_id_to
            if (request('cypa_id_to')) {
                $query->where('cypa_id_to', request('cypa_id_to'));
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

            return FixedExpenseResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allFixedExpenses, 'trashed' => $trashedFixedExpenses]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch fixed expenses: ' . $e->getMessage());
        }
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with(['shippingLine', 'cypaFrom', 'cypaTo'])
            ->findOrFail($id);
        return $this->resource::make($model);
    }
}

