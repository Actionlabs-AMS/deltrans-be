<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API endpoints for dashboard statistics"
 * )
 */
class DashboardController extends BaseController
{
  public function __construct(DashboardService $dashboardService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($dashboardService, $messageService);
  }

  /**
   * Get dashboard statistics
   * 
   * @OA\Get(
   *     path="/api/dashboard/stats",
   *     summary="Get dashboard statistics",
   *     tags={"Dashboard"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Dashboard statistics retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="total_users", type="integer", example=150),
   *                 @OA\Property(property="total_media", type="integer", example=234),
   *                 @OA\Property(property="total_categories", type="integer", example=45),
   *                 @OA\Property(property="total_tags", type="integer", example=128)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getStats(): JsonResponse
  {
    try {
      $stats = $this->service->getStats();

      return response()->json([
        'success' => true,
        'data' => $stats,
      ], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}