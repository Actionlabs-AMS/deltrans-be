<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Navigation;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing navigations (use delete instead of truncate to avoid foreign key issues)
        Navigation::query()->delete();

        $navigations = [
            // Dashboard
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'cil-speedometer',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
            
            // User Management
            [
                'name' => 'User Management',
                'slug' => 'user-management',
                'icon' => 'cil-people',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Users',
                'slug' => 'users',
                'icon' => 'cil-user',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Roles',
                'slug' => 'roles',
                'icon' => 'cil-shield-alt',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            
            // Content Management
            [
                'name' => 'Content Management',
                'slug' => 'content-management',
                'icon' => 'cil-folder',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Media Library',
                'slug' => 'media-library',
                'icon' => 'cil-image',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Categories',
                'slug' => 'categories',
                'icon' => 'cil-tags',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'icon' => 'cil-tag',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            
            // System Settings
            [
                'name' => 'System Settings',
                'slug' => 'system-settings',
                'icon' => 'cil-settings',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Navigation',
                'slug' => 'navigation',
                'icon' => 'cil-list',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Security Dashboard',
                'slug' => 'security',
                'icon' => 'cil-shield',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
                'show_in_menu' => true,
            ],
        ];

        // Create parent navigations first
        $parentNavigations = [];
        foreach ($navigations as $navigation) {
            if ($navigation['parent_id'] === null && in_array($navigation['slug'], ['dashboard', 'user-management', 'content-management', 'system-settings'])) {
                $nav = Navigation::create($navigation);
                $parentNavigations[$navigation['slug']] = $nav->id;
            }
        }

        // Create child navigations
        foreach ($navigations as $navigation) {
            if ($navigation['parent_id'] === null && !in_array($navigation['slug'], ['dashboard', 'user-management', 'content-management', 'system-settings'])) {
                $parentId = null;
                
                // Determine parent based on slug
                if (in_array($navigation['slug'], ['users', 'roles'])) {
                    $parentId = $parentNavigations['user-management'] ?? null;
                } elseif (in_array($navigation['slug'], ['media-library', 'categories', 'tags'])) {
                    $parentId = $parentNavigations['content-management'] ?? null;
                } elseif (in_array($navigation['slug'], ['navigation', 'security'])) {
                    $parentId = $parentNavigations['system-settings'] ?? null;
                }
                
                $navigation['parent_id'] = $parentId;
                Navigation::create($navigation);
            }
        }
    }
}
