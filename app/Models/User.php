<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserMeta;
use App\Traits\EncryptsAttributes;

class User extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, SoftDeletes, EncryptsAttributes;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_login',
		'user_email',
		'user_pass',
		'user_salt',
		'user_status',
		'user_activation_key',
		'remember_token',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'user_pass',
		'user_salt',
		'user_activation_key',
		'remember_token',
	];

	/**
	 * Initialize the model.
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		
		// Set encrypted attributes for this model
		$this->encrypted = [
			'user_activation_key',
		];
	}

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
	protected $table = 'users';

	public function saveUserMeta($metaData) 
	{
		foreach ($metaData as $key => $data) {
			UserMeta::updateOrCreate(
			[
				'user_id' => $this->id,
				'meta_key' => $key
			],
			[
				'meta_value' => $data,
			]);
		}
	}

	/**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
		'user_details',
		'user_role',
	];

	public function getUserMetas()
	{   
		return $this->hasMany('App\Models\UserMeta', 'user_id', 'id');
	}

	public function getUserRole($role_id)
	{   
		return Role::find($role_id);
	}

	/****************************************
	*           ATTRIBUTES PARTS            *
	****************************************/
	public function getUserDetailsAttribute()
	{
		return $this->getUserMetas()->pluck('meta_value', 'meta_key')->toArray();
	}

	public function getUserRoleAttribute()
	{
		$user_role = json_decode($this->user_details['user_role'] ?? 'null');
		return $user_role && ($role = $this->getUserRole($user_role->id)) ? $role : null;
	}

	/**
	 * Get a specific meta value for the user
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getMeta($key)
	{
		$meta = $this->getUserMetas()->where('meta_key', $key)->first();
		return $meta ? $meta->meta_value : null;
	}
}
