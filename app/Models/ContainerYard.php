<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContainerYard extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cypa_details';

    protected $fillable = [
        'name',
        'address',
        'contact_name',
        'contact_mobile',
        'landlines', // Ensure this is fillable
        'type',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        // FIX: Cast 'landlines' to an array
        'landlines' => 'array',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}