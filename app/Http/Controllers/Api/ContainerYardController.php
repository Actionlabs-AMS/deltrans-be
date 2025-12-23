<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\ContainerYardRequest; // Assumed new Request class                
use App\Services\ContainerYardService;      // Assumed new Service class                
use App\Services\MessageService;
use App\Http\Resources\ContainerYardResource;

/**
 * @OA\Tag(
 * name="Container Yard Management",                
 * description="API Endpoints for managing container yards"                
 * )
 * @OA\Schema(
 * schema="ContainerYard",                
 * title="Container Yard Model",                
 * description="A container yard resource",                
 * @OA\Property(property="id", type="integer", example=1),                
 * @OA\Property(property="name", type="string", example="North Yard"),                
 * @OA\Property(property="address", type="string", example="123 Port St."),                
 * @OA\Property(property="contact_name", type="string", example="John Doe"),                
 * @OA\Property(property="contact_mobile", type="string", example="09171234567"),                
 * @OA\Property(property="landlines", type="array", @OA\Items(type="string"), example="['02-8123-4567','02-8123-4568']"),
 * @OA\Property(property="location_type", type="string", example="Container Yard/Port"),                
 * @OA\Property(property="is_active", type="integer", example=1, description="1=Active, 0=Inactive"),                
 * @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),                
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")                
 * )
 * @OA\Schema(
 * schema="ContainerYardInput",                
 * title="Container Yard Input",                
 * description="Data required to create or update a container yard",                
 * required={"name", "address", "type", "is_active"},                
 * @OA\Property(property="name", type="string", example="North Yard"),                
 * @OA\Property(property="address", type="string", example="123 Port St."),                
 * @OA\Property(property="contact_name", type="string", example="John Doe"),                
 * @OA\Property(property="contact_mobile", type="string", example="09171234567"),                
 * @OA\Property(property="landlines", type="array",@OA\Items(type="string"), example="['02-8123-4567','02-8123-4568']"),      
 * @OA\Property(property="location_type", type="string", example="Container Yard/Port"),                
 * @OA\Property(property="is_active", type="integer", example=1),                
 * )
 */
class ContainerYardController extends BaseController
{
    // Updated constructor to use ContainerYardService                
    public function __construct(ContainerYardService $cyService, MessageService $messageService)
    {
        parent::__construct($cyService, $messageService);
    }

    /**
     * @OA\Get(
     * path="/api/container-yards/yard-list",                
     * operationId="getContainerYardsList",                
     * tags={"Container Yard Management"},                
     * summary="Get list of container yards",                
     * description="Returns a paginated list of container yards with search filtering.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Page number",
     * required=false,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Items per page",
     * required=false,
     * @OA\Schema(type="integer", example=10)
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Search term for filtering by Name, Address, Contact Name, or Location Type.",
     * required=false,
     * @OA\Schema(type="string", example="North Yard")
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/ContainerYard")                
     * )
     * ),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function index()
    {
        //return parent::index();
        //Pass all relevant query parameters explicitly to the service
        $request = request();
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        // ... get other filters

        return $this->service->list($perPage, $search);
    }

    /**
     * @OA\Post(
     * path="/api/container-yards/add-yard",                
     * operationId="storeContainerYard",                
     * tags={"Container Yard Management"},                
     * summary="Create a new container yard",                
     * description="Creates a new container yard resource in storage.",                
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Container yard data to store",                
     * @OA\JsonContent(ref="#/components/schemas/ContainerYardInput")                
     * ),
     * @OA\Response(
     * response=201,
     * description="Container yard created successfully",                
     * @OA\JsonContent(ref="#/components/schemas/ContainerYard")                
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function store(ContainerYardRequest $request)
    {
        try {
            //NOTE landline is array, format should be ["02-1111-1111","02-1111-1111"]
            $data = $request->validated(); // Use validated data                
            $containerYard = $this->service->store($data);
            return response($containerYard, 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An unexpected server error occurred.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/container-yards/get-yard-by-id/{id}",                
     * operationId="getContainerYardById",                
     * tags={"Container Yard Management"},                
     * summary="Get a specific container yard",                
     * description="Returns a single container yard resource by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the container yard to return",                
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(ref="#/components/schemas/ContainerYard")                
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function show($id)
    {
        try {
            $containerYard = $this->service->get_yard_by_id($id);
            return response($containerYard, 200); // Usually 200 OK for GET                
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Container yard id not found.',
            ], 404);
        }
    }

    /**
     * @OA\Put(
     * path="/api/container-yards/update-yard/{id}",                
     * operationId="updateContainerYard",                
     * tags={"Container Yard Management"},                
     * summary="Update an existing container yard",                
     * description="Updates an existing container yard resource by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the container yard to update",                
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Container yard data to update (uses ContainerYardRequest for validation)",                
     * @OA\JsonContent(ref="#/components/schemas/ContainerYardInput")                
     * ),
     * @OA\Response(
     * response=200,
     * description="Container yard updated successfully",                
     * @OA\JsonContent(ref="#/components/schemas/ContainerYard")                
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function update(ContainerYardRequest $request, string $id)
    {
        try {
            $data = $request->validated(); // Use validated data                
            $containerYard = $this->service->update($data, $id);
            return response($containerYard, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Container yard id not found.',
            ], 404);
        }
    }

    /**
     * @OA\Patch(
     * path="/api/container-yards/deactivate-yard/{id}",                
     * operationId="deactivateContainerYard",                
     * tags={"Container Yard Management"},                
     * summary="Deactivate a container yard (Logical Delete)",                
     * description="Updates the container yard is_active to 0 (inactive) by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the container yard to deactivate",                
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,                
     * description="Container yard successfully deactivated",                
     * @OA\JsonContent(
     * @OA\Property(property="status_code", type="integer", example=200),
     * @OA\Property(property="message", type="string", example="Container yard deactivated successfully.")                
     * )
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function destroy($id)
    {
        try {
            $this->service->deactivate_yard_by_id($id);

            return response()->json([
                'status_code' => 200,
                'message' => 'Container yard deactivated successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Container yard id not found.',
            ], 404);
        }
    }

    /**
     * @OA\Patch(
     * path="/api/container-yards/activate-yard/{id}",                
     * operationId="activateContainerYard",                
     * tags={"Container Yard Management"},                
     * summary="Activate a container yard (Restore)",                
     * description="Updates the container yard is_active to 1 (active) by its ID.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the container yard to activate",                
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,                
     * description="Container yard successfully activated",                
     * @OA\JsonContent(
     * @OA\Property(property="status_code", type="integer", example=200),
     * @OA\Property(property="message", type="string", example="Container yard activated successfully.")                
     * )
     * ),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function restore($id)
    {
        try {
            $this->service->activate_yard_by_id($id);

            return response()->json([
                'status_code' => 200,
                'message' => 'Container yard activated successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Container yard id not found.',
            ], 404);
        }
    }

    /**
     * @OA\Get(
     * path="/api/container-yards/search",                
     * operationId="searchContainerYardByName",                
     * tags={"Container Yard Management"},                
     * summary="Search container yards by name",                
     * description="Returns a list of container yards matching the partial name provided.",                
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="Partial or full name of the container yard",                
     * required=true,                
     * @OA\Schema(type="string", example="North")                
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/ContainerYard")                
     * )
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),                
     * @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->query('name');

            if (!$searchTerm) {
                return response()->json(['message' => 'Name search term is required.'], 400);
            }

            $yards = $this->service->search_yard_by_name($searchTerm);

            //return response($yards, 200);

            return ContainerYardResource::collection($yards);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Container yard name not found.',
            ], 404);
        }

    }

}