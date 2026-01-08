<?php

namespace App\Services;

use App\Models\DriverCAHistory;
use App\Http\Resources\DriverCAHistoryResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DriverCAHistoryService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new DriverCAHistoryResource(new DriverCAHistory), new DriverCAHistory());
    }

    public function getDriverHistory(int $driverId, $perPage = 10): LengthAwarePaginator
    {
        try {
            // Strict filter by driver_id first
            $query = DriverCAHistory::where('driver_id', $driverId);

            // --- Single Search Box Logic ---
            if (request('search')) {
                $searchTerm = request('search');

                $query->where(function($q) use ($searchTerm) {
                    // Search Shift or Amount
                    $q->where('shift', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('amount', 'LIKE', '%' . $searchTerm . '%');

                    // Search Date (Only if the search term looks like a date YYYY-MM-DD)
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
                        $q->orWhereDate('transaction_date', $searchTerm);
                    }
                });
            }

            // --- Dynamic Ordering ---
            if (request('order')) {
                // If 'order' is passed (e.g., from DataTable headers), apply it
                $query->orderBy(request('order'), request('sort') ?? 'desc');
            } else {
                // Default fallback ordering
                $query->orderBy('transaction_date', 'desc');
            }

            // Return paginated results with query string preserved for front-end pagination links
            return $query->paginate($perPage)->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch driver cash advance history: ' . $e->getMessage());
        }
    }

}