<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordEmail;
use App\Mail\VerifyEmail;
use App\Helpers\PasswordHelper;

class UserService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new UserResource(new User), new User());
  }
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    $allUsers = $this->getTotalCount();
    $trashedUsers = $this->getTrashedCount();

    return UserResource::collection(User::query()
    ->with('role') // Eager load role relationship
    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
    // Exclude super admin role (role_id = 1) if needed, or remove this filter to show all users
    ->when(request('search'), function ($query) {
      $search = request('search');
      return $query->where(function ($q) use ($search) {
        $q->where('users.user_login', 'LIKE', '%' . $search . '%')
          ->orWhere('users.user_email', 'LIKE', '%' . $search . '%')
          ->orWhere('roles.name', 'LIKE', '%' . $search . '%');
      });
    })
    ->when(request()->has('role_name') && request('role_name') !== '', function ($query) {
      // Strict filter: exact match on role name
      return $query->where('roles.name', request('role_name'));
    })
    ->when(request()->has('user_status') && request('user_status') !== '', function ($query) {
      // Strict filter: exact match on user status (handles 0, 1, 2 correctly)
      // Note: Using request()->has() to check existence, not truthiness, so '0' is handled correctly
      return $query->where('users.user_status', request('user_status'));
    })
    ->when(request('order'), function ($query) {
      $order = request('order');
      $sort = request('sort', 'asc');
      
      // Handle ordering by role name
      if ($order === 'role_name') {
        return $query->orderBy('roles.name', $sort);
      }
      
      // Handle other fields
      return $query->orderBy('users.' . $order, $sort);
    })
    ->when(!request('order'), function ($query) {
      return $query->orderBy('users.id', 'desc');
    })
    ->when($trash, function ($query) {
      return $query->onlyTrashed();
    })
    ->select('users.*') // Important to avoid column conflicts
    ->paginate($perPage)->withQueryString()
    )->additional(['meta' => ['all' => $allUsers, 'trashed' => $trashedUsers]]);
  }

  /**
  * Store a newly created resource in storage.
  */
  public function storeWithMeta(array $data, array $metaData)
  {
    $user = parent::store($data); // Call the parent method
    if(count($metaData))
      $user->saveUserMeta($metaData);

    $user_key = $user->user_activation_key;
    $this->sendVerifyEmail($user, $user_key);

    return new UserResource($user);
  }

  /**
  * Update the specified resource in storage.
  */
  public function updateWithMeta(array $data, array $metaData, User $user)
  {
    $user->update($data);
    if(count($metaData))
      $user->saveUserMeta($metaData);

    $this->sendForgotPasswordEmail($user);

    return new UserResource($user);
  }

  /**
  * Bulk restore a soft-deleted user.
  */
  public function bulkChangePassword($ids) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $this->genTempPassword($user);
      }
    }
  }

  public function genTempPassword(User $user) 
	{
		if($user) {
			$salt = $user->user_salt;
			$new_password = PasswordHelper::generateSalt();
			$password = PasswordHelper::generatePassword($salt, $new_password);

			$user->update(['user_pass' => $password]);

			$this->sendForgotPasswordEmail($user, $new_password);
		}
	}

  /**
  * Bulk change user password.
  */
  public function bulkChangeRole($ids, $role) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $this->changeRole($user, $role);
      }
    }
  }

  public function changeRole(User $user, $role) 
  {
    if(isset($role)) {
      // Update role_id directly on users table
      $user->update(['role_id' => $role]);
    }
  }

  /**
  * Send verify email.
  */
  public function sendVerifyEmail($user, $user_key)
  {
    $options = array(
      'verify_url'   => env('ADMIN_APP_URL')."/login/activate/".$user_key,
      'password'   => request('user_pass')
    );

    Mail::to($user->user_email)->send(new VerifyEmail($user, $options));
  }

  /**
  * Send temporary password.
  */
  public function sendForgotPasswordEmail($user, $new_password = '') 
  {
    $user_pass = ($new_password) ? $new_password : request('user_pass');
    $options = array(
      'login_url' => env('ADMIN_APP_URL')."/login",
      'new_password' => $user_pass
    );

    if($user_pass)
      Mail::to($user->user_email)->send(new ForgotPasswordEmail($user, $options));
  }
}