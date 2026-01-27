<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
  use HasFactory, SoftDeletes;
  /**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'active',
	];

    /**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'active' => 'boolean',
		'is_super_admin' => 'boolean',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
	];

  /**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'roles';

	// Define the relationship to RolePermission (many-to-many through role_permissions)
	public function rolePermissions()
	{
		return $this->hasMany(RolePermission::class);
	}

	// Method to get the permissions in the required format
	public function getPermissionsFormatted()
	{
		// Initialize the array to store the structured permissions
		$permissions_data = [];

		// Fetch permissions from role_permissions table
		\Log::info('Role::getPermissionsFormatted - Fetching from role_permissions', [
			'role_id' => $this->id,
			'role_name' => $this->name,
		]);
		$rolePermissions = $this->rolePermissions()
			->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
			->join('navigations', 'role_permissions.navigation_id', '=', 'navigations.id') // Join to get parent navigation data
			->select(
				'role_permissions.navigation_id',
				'role_permissions.permission_id',
				'role_permissions.allowed',
				'permissions.name as permission_name',
				'navigations.parent_id'  // Get the parent navigation ID
			)
			->get();

		// Loop through the permissions and group them by navigation structure
		// Use sima-api style: parent_id ?? 0 (default to 0 for root items)
		foreach ($rolePermissions as $rolePermission) {
			$parent_navigation_id = $rolePermission->parent_id ?? 0; // Default to 0 if parent_id is null (matching sima-api)
			$navigation_id = $rolePermission->navigation_id;
			$permission_id = $rolePermission->permission_id;
			$allowed = $rolePermission->allowed;
			$permission_name = $rolePermission->permission_name;

			// If the parent navigation doesn't exist in our structure, create it
			if (!isset($permissions_data[$parent_navigation_id])) {
				$permissions_data[$parent_navigation_id] = [];
			}

			// If the navigation under this parent doesn't exist, create it
			if (!isset($permissions_data[$parent_navigation_id][$navigation_id])) {
				$permissions_data[$parent_navigation_id][$navigation_id] = [];
			}

			// Add the permission under this navigation
			$permissions_data[$parent_navigation_id][$navigation_id][$permission_name] = (bool) $allowed;
		}

		\Log::info('Role::getPermissionsFormatted - Permissions fetched', [
			'role_id' => $this->id,
			'permissions_count' => count($permissions_data),
			'permissions_structure_keys' => array_keys($permissions_data),
		]);

		return $permissions_data;
	}

	private function getNavigations($parentId=null) 
	{
		$navigations = Navigation::when(!$parentId, function ($query) {
			return $query->whereNull('parent_id');
		})
		->when($parentId, function ($query) use ($parentId) {
				return $query->where('parent_id', $parentId);
		})
		->get();
	
		// Initialize an empty array
		$navigationById = [];

		// Loop through the navigations and set the `id` as the array key
		foreach ($navigations as $navigation) {
				$navigationById[$navigation->id] = $navigation;
				$navigationById[$navigation->id]['children'] = $this->getNavigations($navigation->id);
		}	

		return $navigationById;
	}

	private function childRoutes($navigations = null, $parentId = null, $parentPath = '') 
	{
		$childRoutes = [];
		foreach ($navigations as $navigation) {

			$permissions = $this->permissions[$parentId][$navigation->id] ?? [];

			// Construct the full path by prefixing with parent path
			$fullPath = $parentPath . '/' . $navigation->slug;

			if(!empty($permissions) && !empty($permissions['can_view'])) {
				$routes = [
					'id' => $navigation->id,
					'path' => $fullPath,
					'name' => $navigation->name,
					'side_nav' => $navigation->show_in_menu ? 'true' : 'false',
					'icon' => $navigation->icon ?? '',
					'children' => ($navigation['children']) ? $this->childRoutes($navigation['children'], $navigation->id, $fullPath) : []
				];

				$childRoutes[] = $routes;
			}

			if(!empty($permissions) && !empty($permissions['can_edit'])) {
				$routes = [
					'path' => $fullPath . '/:id',
					'name' => 'Edit ' . $navigation->name,
					'side_nav' => 'false',
					'icon' => $navigation->icon ?? '',
				];

				$childRoutes[] = $routes;
			}

			if(!empty($permissions) && !empty($permissions['can_create'])) {
				$routes = [
					'path' => $fullPath . '/create',
					'name' => 'Create ' . $navigation->name,
					'side_nav' => 'false',
					'icon' => $navigation->icon ?? '',
				];

				$childRoutes[] = $routes;
			}
		}

		return $childRoutes;
	}

	private function generateRoutes()
	{
		$routes = [];
		$navigations = $this->getNavigations(); // Start from root

		foreach ($navigations as $navigation) {
			// Root navigations have parent_id = null, so use 0 as parent key (matching sima-api style)
			$parentKey = 0; // Root items use 0 as parent key
			$permissions = $this->permissions[$parentKey][$navigation->id] ?? [];

		// Generate children routes first to check if any children have permissions
		$hasChildren = !empty($navigation['children']);
		$parentPath = '/' . $navigation->slug;
		$childrenRoutes = $hasChildren ? $this->childRoutes($navigation['children'], $navigation->id, $parentPath) : [];

		// Check if parent has any visible children based on navigation's show_in_menu property
		// A child is visible if it has show_in_menu === true
		$hasVisibleChildren = false;
		if ($hasChildren && !empty($navigation['children'])) {
			foreach ($navigation['children'] as $childNavigation) {
				// Check if this child navigation has show_in_menu === true
				if ($childNavigation->show_in_menu === true) {
					$hasVisibleChildren = true;
					break;
				}
			}
		}

		// Generate create/edit routes for parent if permissions exist
		// Only generate parent create/edit routes when:
		// 1. Parent has no children (standalone route), OR
		// 2. Parent has children but ALL children are not visible (all have show_in_menu === false)
		// This means: do NOT generate parent create/edit if parent has any visible children
		if (!empty($permissions) && !$hasVisibleChildren) {
				// Generate create route if can_create permission exists
				if (!empty($permissions['can_create'])) {
					$childrenRoutes[] = [
						'path' => $parentPath . '/create',
						'name' => 'Create ' . $navigation->name,
						'side_nav' => 'false',
						'icon' => $navigation->icon ?? '',
					];
				}

				// Generate edit route if can_edit permission exists
				if (!empty($permissions['can_edit'])) {
					$childrenRoutes[] = [
						'path' => $parentPath . '/:id',
						'name' => 'Edit ' . $navigation->name,
						'side_nav' => 'false',
						'icon' => $navigation->icon ?? '',
					];
				}
			}

			// Include parent navigation based on these rules:
			// 1. If it has children: include if it has direct permissions OR childrenRoutes is not empty (has children with permissions)
			// 2. If it has no children (standalone route): include if it has direct permissions
			$shouldInclude = false;
			if ($hasChildren) {
				// Parent navigation: show if it has direct permissions OR has children with permissions
				$shouldInclude = !empty($permissions) || !empty($childrenRoutes);
			} else {
				// Standalone route: show if it has direct permissions
				$shouldInclude = !empty($permissions);
			}

			if($shouldInclude) {
				$route = [
					'id' => $navigation->id,
					'path' => $parentPath,
					'name' => $navigation->name,
					'side_nav' => $navigation->show_in_menu ? 'true' : 'false',
					'icon' => $navigation->icon ?? '',
					'children' => $childrenRoutes
				];

				$routes[] = $route;
			}
		}

		return $routes;
	}

	public function getUserRoutes() 
	{
		if($this->permissions) {
			return $this->generateRoutes();
		}
		return [];
	}

	 /**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
		'permissions'
	]; 

	/****************************************
	*           ATTRIBUTES PARTS            *
	****************************************/
	public function getPermissionsAttribute()
	{
		return $this->getPermissionsFormatted();
	} 
}
