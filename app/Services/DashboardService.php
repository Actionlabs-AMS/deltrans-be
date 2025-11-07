<?php

namespace App\Services;

use App\Models\User;
use App\Models\MediaLibrary;
use App\Models\Category;
use App\Models\Tag;

class DashboardService
{
  /**
   * Get dashboard statistics
   * 
   * @return array
   */
  public function getStats(): array
  {
    try {
      return [
        'total_users' => User::count(),
        'total_media' => MediaLibrary::count(),
        'total_categories' => Category::count(),
        'total_tags' => Tag::count(),
      ];
    } catch (\Exception $e) {
      throw new \Exception('Failed to retrieve dashboard statistics: ' . $e->getMessage());
    }
  }
}