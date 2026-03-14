<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class MessageService 
{
  public function responseError(): JsonResponse
  {
    return response()->json([
      'message' => 'An error has occurred, please reload the page or try again later. Please contact the administrator if error has re-occured.',
      'status' => false,
      'status_code' => 422,
    ], 422);
    
  }
}