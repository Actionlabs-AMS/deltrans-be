<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MessageService;

/**
 * --- DEFINE TOP-LEVEL INFO ONCE ---
 * @OA\Info(                
 * title="Deltrans API",                
 * version="1.0.0",                
 * description="A comprehensive Laravel API with authentication, role management, and security features",
 * @OA\Contact(
 *    email="admin@deltrans.com"
 *  )            
 * )              
 * 
 * * --- DEFINE GLOBAL RESPONSES ONCE ---                
 *
* @OA\Response(
 * response="BadRequest",
 * description="Bad request, often due to validation errors or malformed input",
 * @OA\JsonContent(
 * @OA\Property(property="success", type="boolean", example=false),
 * @OA\Property(property="message", type="string", example="The given data was invalid."),
 * @OA\Property(property="errors", type="object", example={"license_plate": {"The license plate field is required."}})
 * )
 * )
 * @OA\Response(
 * response="NotFound",
 * description="Resource not found",
 * @OA\JsonContent(
 * @OA\Property(property="success", type="boolean", example=false),
 * @OA\Property(property="message", type="string", example="Resource not found.")
 * )
 * )
 * @OA\Response(
 * response="GeneralError",
 * description="General Server Error",
 * @OA\JsonContent(
 * @OA\Property(property="success", type="boolean", example=false),
 * @OA\Property(property="message", type="string", example="An error occurred.")
 * )
 * )              
 */

class BaseController extends Controller
{
  protected $service;
  protected $messageService;

  // Inject the common services into the BaseController constructor
  public function __construct($service, MessageService $messageService)
  {
    $this->service = $service;
    $this->messageService = $messageService;
  }

  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $items = $this->service->list();
      return $items;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle showing resources
  public function show($id)
  {
    try {
      $item = $this->service->show($id);
      return $item;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle destroying resources
  public function destroy($id)
  {
    try {
      $this->service->destroy($id);
      return response(['message' => 'Resource has been moved to trash.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk deleting resources
  public function bulkDelete(Request $request)
  {
    try {
      $this->service->bulkDelete($request->ids);
      return response(['message' => 'Resources have been deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle trashed resources
  public function getTrashed() 
  {
    try {
      $items = $this->service->list(10, true);
      return $items;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle restoring resources
  public function restore($id)
  {
    try {
      $item = $this->service->restore($id);
      return response([
        'message' => 'Resource has been restored.',
        'resource' => $item
      ], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk restoring resources
  public function bulkRestore(Request $request)
  {
    try {
      $this->service->bulkRestore($request->ids);
      return response(['message' => 'Resources have been restored.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle force deleting resources
  public function forceDelete($id)
  {
    try {
      $this->service->forceDelete($id);
      return response(['message' => 'Resource has been permanently deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk force deleting resources
  public function bulkForceDelete(Request $request)
  {
    try {
      $this->service->bulkForceDelete($request->ids);
      return response(['message' => 'Resources have been permanently deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
