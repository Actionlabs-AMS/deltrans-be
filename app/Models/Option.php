<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_key',
        'option_value',
        'option_type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $table = 'options';

    /**
     * Get option value with type casting
     */
    public function getValueAttribute()
    {
        switch ($this->option_type) {
            case 'boolean':
                return filter_var($this->option_value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->option_value;
            case 'float':
                return (float) $this->option_value;
            case 'json':
                return json_decode($this->option_value, true);
            default:
                return $this->option_value;
        }
    }

    /**
     * Set option value with type casting
     */
    public function setValueAttribute($value)
    {
        switch ($this->option_type) {
            case 'boolean':
                $this->option_value = $value ? 'true' : 'false';
                break;
            case 'json':
                $this->option_value = json_encode($value);
                break;
            default:
                $this->option_value = (string) $value;
        }
    }

    /**
     * Get option by key
     */
    public static function get($key, $default = null)
    {
        $option = self::where('option_key', $key)->first();
        return $option ? $option->value : $default;
    }

    /**
     * Set option by key
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        return self::updateOrCreate(
            ['option_key' => $key],
            [
                'option_value' => $value,
                'option_type' => $type,
                'description' => $description,
            ]
        );
    }
}
