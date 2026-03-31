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

    // public function getDriverHistory(int $driverId, $perPage = 10): LengthAwarePaginator
    // {
    //     try {
    //         // Strict filter by driver_id first
    //         $query = DriverCAHistory::where('driver_id', $driverId);

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
    //         throw new \Exception('Failed to fetch driver cash advance history: ' . $e->getMessage());
    //     }
    // }

    // public function getDriverHistory($driverId, $perPage = 10, $search = null, $dateFrom = null, $dateTo = null)
    // {
    //     try {
    //         $query = DriverCAHistory::where('driver_id', $driverId);

    //         // Apply Date Range Filter
    //         if ($dateFrom && $dateTo) {
    //             $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
    //         }

    //         // Apply Search Filter (Shift or general keyword)
    //         if ($search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('shift', 'LIKE', "%{$search}%")
    //                 ->orWhere('amount', 'LIKE', "%{$search}%");
                    
    //                 // If search term looks like a date, check transaction_date directly
    //                 if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
    //                     $q->orWhereDate('transaction_date', $search);
    //                 }
    //             });
    //         }

    //         // Always show newest first
    //         return $query->with(['driver'])
    //                     ->orderBy('transaction_date', 'desc')
    //                     ->orderBy('id', 'desc')
    //                     ->paginate($perPage)
    //                     ->withQueryString();

    //     } catch (\Exception $e) {
    //         throw new \Exception('Failed to fetch Cash Advance history: ' . $e->getMessage());
    //     }
    // }

    public function getDriverHistory($driverId, $perPage = 10, $search = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $query = DriverCAHistory::where('driver_id', $driverId);

            // Apply Date Range Filter
            if ($dateFrom && $dateTo) {
                $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
            }

            // Apply Search Filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('shift', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%");
                    
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                        $q->orWhereDate('transaction_date', $search);
                    }
                });
            }

            // 🌟 APPLY SORTING QUERY
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                // Default sorting when no header is clicked
                $query->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc');
            }

            return $query->with(['driver'])
                        ->paginate($perPage)
                        ->withQueryString();

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch Cash Advance history: ' . $e->getMessage());
        }
    }

    

}