<?php

namespace App\Services;

use App\Models\ContainerYard; // Assumed renamed from FleetTruck
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use App\Http\Resources\ContainerYardResource;

class ContainerYardService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ContainerYardResource(new ContainerYard), new ContainerYard());
    }
    /**
     * Get list of container yards.
     *
     * @return Collection
     */
    public function index(): Collection
    {
        // Simple retrieval. For pagination, you might use ->paginate()
        return ContainerYard::all();
    }

    /**
     * Retrieve all resources with paginate.
     */
    /**
     * Get list of container yards with search, sort, and pagination.
     * Updated for cypa_details table.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
    
            $allContainerYards = $this->getTotalCount();
            $trashedContainerYards = $this->getTrashedCount();

            // FIX: Use the ContainerYard model
            $query = ContainerYard::query();

            // Note: Since we are using status-based logical deactivation (0/1) 
            // rather than Laravel Soft Deletes, $trash handling should be adjusted 
            // to check status if necessary. Assuming status check is done elsewhere for now.

            // FIX: Apply search conditions based on new columns
            if (request('search')) {
                // $query->where(function ($q) {                
                //     $searchTerm = '%' . request('search') . '%';
                //     $q->where('name', 'LIKE', $searchTerm)
                //       ->orWhere('address', 'LIKE', $searchTerm)                
                //       ->orWhere('contact_name', 'LIKE', $searchTerm)
                //       ->orWhere('location_type', 'LIKE', $searchTerm);
                // }); 

                $query->where(function ($q) {                
                    //$searchTerm = '%' . request('search') . '%';
                    $q->where('name', 'LIKE', '%' . request('search') . '%')
                      ->orWhere('address', 'LIKE', '%' . request('search') . '%')                
                      ->orWhere('contact_name', 'LIKE', '%' . request('search') . '%')
                      ->orWhere('location_type', 'LIKE', '%' . request('search') . '%');
                }); 

            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'asc');
            }

            // FIX: Use ContainerYardResource instead of TruckResource
            return ContainerYardResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional([
                'meta' => [
                    'all' => $allContainerYards,                
                    'trashed' => $trashedContainerYards                
                ]
            ]);

        } catch (\Exception $e) {
            // FIX: Updated error message
            throw new \Exception('Failed to fetch container yard list: ' . $e->getMessage());
        }
    }

    // Function done in BaseController and BaseService
    // /**
    //  * Store a newly created container yard.
    //  *
    //  * @param array $data Data from validated ContainerYardRequest
    //  * @return ContainerYard
    //  */
    // public function store(array $data): ContainerYard
    // {
    //     // Note: Because of casting in the Model, Laravel automatically 
    //     // converts the 'landlines' array into a JSON string here.
    //     return ContainerYard::create($data);
    // }

    /**
     * Retrieve a container yard by its ID.
     * * @param int $id
     * @return ContainerYard
     * @throws ModelNotFoundException
     */
    public function get_yard_by_id(int $id): ContainerYard
    {
        // findOrFail throws ModelNotFoundException if ID not found
        try {
            return ContainerYard::findOrFail($id);

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch container yard details: ' . $e->getMessage());
        }
    }

    // Function done in BaseController and BaseService
    // /**
    //  * Update an existing container yard.
    //  *
    //  * @param array $data Data from validated ContainerYardRequest
    //  * @param int $id
    //  * @return ContainerYard
    //  * @throws ModelNotFoundException
    //  */
    // public function update(array $data, int $id): ContainerYard
    // {
    //     $yard = $this->get_yard_by_id($id);
        
    //     // Again, 'landlines' array is automatically handled by Model casting
    //     $yard->update($data);

    //     return $yard;
    // }

    /**
     * Deactivate a container yard (Status to 0).
     *
     * @param int $id
     * @throws ModelNotFoundException
     */
    public function deactivate_yard_by_id(int $id): void
    {
        try {
            $yard = $this->get_yard_by_id($id);
            $yard->update(['status' => 0]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch container yard details: ' . $e->getMessage());
        }
    }

    /**
     * Activate a container yard (Status to 1).
     * * @param int $id
     * @throws ModelNotFoundException
     */
    public function activate_yard_by_id(int $id): void
    {
        try {
            $yard = $this->get_yard_by_id($id);
            $yard->update(['status' => 1]); 

        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch container yard details: ' . $e->getMessage());
        }

    }

    /**
     * Search for container yards by name.
     *
     * @param string $name The search term for the yard name.
     * @return Collection
     */
    public function search_yard_by_name(string $name): Collection
    {
        try {
            // Using 'LIKE' to allow partial matching (e.g., searching "North" finds "North Yard")
            return ContainerYard::where('name', 'LIKE', '%' . $name . '%')
            ->orderBy('name', 'asc') // Optional: Order alphabetically
            ->get();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch container yard details: ' . $e->getMessage());
        }
    }
}