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
		'is_super_admin'
	];

    /**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
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

		// For Super Admin, check if there are explicit permissions in database
		// If explicit permissions exist, use them; otherwise grant all permissions
		$hasExplicitPermissions = $this->rolePermissions()->exists();
		
		if ($this->is_super_admin && !$hasExplicitPermissions) {
			// Super Admin with no explicit permissions - grant all permissions automatically
			\Log::info('Role::getPermissionsFormatted - Super Admin with no explicit permissions - granting all', [
				'role_id' => $this->id,
				'role_name' => $this->name,
			]);
			// Get all navigations
			$navigations = Navigation::all();
			
			// Get all permissions
			$permissions = Permission::all();
			$permissionNames = $permissions->pluck('name')->toArray();

			// Grant all permissions to all navigations
			// Use sima-api style: parent_id ?? 0 (default to 0 for root items)
			// IMPORTANT: Only store navigations under their actual parent, not under themselves
			foreach ($navigations as $navigation) {
				// Get the parent ID - if null (root item), use 0
				$parent_navigation_id = $navigation->parent_id ?? 0; // Default to 0 if parent_id is null (matching sima-api)
				$navigation_id = $navigation->id;

				// Skip if navigation is trying to store under itself (shouldn't happen, but safety check)
				if ($parent_navigation_id == $navigation_id && $navigation->parent_id !== null) {
					continue;
				}

				// If the parent navigation doesn't exist in our structure, create it
				if (!isset($permissions_data[$parent_navigation_id])) {
					$permissions_data[$parent_navigation_id] = [];
				}

				// If the navigation under this parent doesn't exist, create it
				if (!isset($permissions_data[$parent_navigation_id][$navigation_id])) {
					$permissions_data[$parent_navigation_id][$navigation_id] = [];
				}

				// Add all permissions under this navigation
				foreach ($permissionNames as $permission_name) {
					$permissions_data[$parent_navigation_id][$navigation_id][$permission_name] = true;
				}
			}

			\Log::info('Role::getPermissionsFormatted - Super Admin permissions generated', [
				'role_id' => $this->id,
				'permissions_structure_keys' => array_keys($permissions_data),
				'total_navigations' => count($permissions_data),
			]);
			
			return $permissions_data;
		}
		
		// For Super Admin with explicit permissions OR non-super admin roles, fetch from role_permissions table
		if ($this->is_super_admin) {
			\Log::info('Role::getPermissionsFormatted - Super Admin with explicit permissions - using database', [
				'role_id' => $this->id,
				'role_name' => $this->name,
			]);
		}

		// For non-super admin roles, fetch from role_permissions table
		\Log::info('Role::getPermissionsFormatted - Non-Super Admin - Fetching from role_permissions', [
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

	private function childRoutes($navigations = null, $parentId = null) 
	{
		$childRoutes = [];
		foreach ($navigations as $navigation) {

			$permissions = $this->permissions[$parentId][$navigation->id] ?? [];

			if(!empty($permissions) && !empty($permissions['can_view'])) {
				$routes = [
					'id' => $navigation->id,
					'path' => '/' . $navigation->slug,
					'name' => $navigation->name,
					'side_nav' => $navigation->show_in_menu ? 'true' : 'false',
					'icon' => $navigation->icon ?? '',
					'children' => ($navigation['children']) ? $this->childRoutes($navigation['children'], $navigation->id) : []
				];

				$childRoutes[] = $routes;
			}

			if(!empty($permissions) && !empty($permissions['can_edit'])) {
				$routes = [
					'path' => '/' . $navigation->slug . '/:id',
					'name' => 'Edit ' . $navigation->name,
					'side_nav' => 'false',
					'icon' => $navigation->icon ?? '',
				];

				$childRoutes[] = $routes;
			}

			if(!empty($permissions) && !empty($permissions['can_create'])) {
				$routes = [
					'path' => '/' . $navigation->slug . '/create',
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

			if(!empty($permissions)) {
				$route = [
					'id' => $navigation->id,
					'path' => '/' . $navigation->slug,
					'name' => $navigation->name,
					'side_nav' => $navigation->show_in_menu ? 'true' : 'false',
					'icon' => $navigation->icon ?? '',
					'children' => ($navigation['children']) ? $this->childRoutes($navigation['children'], $navigation->id) : []
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
