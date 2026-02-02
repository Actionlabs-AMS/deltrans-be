<?php

namespace App\Services;

use App\Models\HelperCAHistory;
use App\Http\Resources\HelperCAHistoryResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HelperCAHistoryService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new HelperCAHistoryResource(new HelperCAHistory), new HelperCAHistory());
    }

    // public function getHelperHistory(int $helperId, $perPage = 10): LengthAwarePaginator
    // {
    //     try {
    //         // Strict filter by driver_id first
    //         $query = HelperCAHistory::where('helper_id', $helperId);

    //         // --- Single Search Box Logic ---
    //         if (request('search')) {
    //             $searchTerm = request('search');

    //             $query->where(function($q) use ($searchTerm) {
    //                 // Search Shift or Amount
    //                 $q->where('shift', 'LIKE', '%' . $searchTerm . '%')
    //                   ->orWhere('amount', 'LIKE', '%' . $searchTerm . '%');

    //                 // Search Date (Only if the search term looks like a date YYYY-MM-DD)
    //                 if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
    //                     $q->orWhereDate('transaction_date', $searchTerm);
    //                 }
    //             });
    //         }

    //         // --- Dynamic Ordering ---
    //         if (request('order')) {
    //             // If 'order' is passed (e.g., from DataTable headers), apply it
    //             $query->orderBy(request('order'), request('sort') ?? 'desc');
    //         } else {
    //             // Default fallback ordering
    //             $query->orderBy('transaction_date', 'desc');
    //         }

    //         // Return paginated results with query string preserved for front-end pagination links
    //         return $query->paginate($perPage)->withQueryString();

    //     } catch (\Exception $e) {
    //         throw new \Exception('Failed to fetch helper cash advance history: ' . $e->getMessage());
    //     }
    // }
    public function getHelperHistory($id, $perPage = 10, $searchTerm = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $query = HelperCAHistory::where('helper_id', $id);

            // Date Range Filter
            if ($dateFrom && $dateTo) {
                $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
            }

            // Search Logic
            if ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('amount', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('shift', 'LIKE', '%' . $searchTerm . '%');
                    
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
                        $q->orWhereDate('transaction_date', $searchTerm);
                    }
                });
            }

            return $query->orderBy('transaction_date', 'desc')
                        ->paginate($perPage)
                        ->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch history: ' . $e->getMessage());
        }
    }

}