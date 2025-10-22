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

		// Fetch the permissions for the role, including navigation and permission details
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
		foreach ($rolePermissions as $rolePermission) {
			$parent_navigation_id = $rolePermission->parent_id;
			$navigation_id = $rolePermission->navigation_id;
			$permission_id = $rolePermission->permission_id;
			$allowed = $rolePermission->allowed;
			$permission_name = $rolePermission->permission_name;

			// If parent_id is null (top-level item without children), store directly under navigation_id
			if ($parent_navigation_id === null) {
				// If the navigation doesn't exist in our structure, create it
				if (!isset($permissions_data[$navigation_id])) {
					$permissions_data[$navigation_id] = [];
				}

				// If the navigation under itself doesn't exist, create it
				if (!isset($permissions_data[$navigation_id][$navigation_id])) {
					$permissions_data[$navigation_id][$navigation_id] = [];
				}

				// Add the permission under this navigation
				$permissions_data[$navigation_id][$navigation_id][$permission_name] = (bool) $allowed;
			} else {
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
		}

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

			$permissions = $this->permissions[$navigation->id] ?? [];

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
