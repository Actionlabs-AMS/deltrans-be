<?php

namespace App\Services;

use App\Models\Option;
use App\Http\Resources\OptionResource;
use Illuminate\Support\Facades\Cache;

class OptionService extends BaseService
{
    public function __construct()
    {
        parent::__construct(OptionResource::class, Option::class);
    }

    /**
     * Get all options with caching
     */
    public function getAllOptions()
    {
        return Cache::remember('options.all', 3600, function () {
            return Option::all()->mapWithKeys(function ($option) {
                return [$option->option_key => $option->value];
            });
        });
    }

    /**
     * Get option by key with caching
     */
    public function getOption($key, $default = null)
    {
        return Cache::remember("option.{$key}", 3600, function () use ($key, $default) {
            return Option::get($key, $default);
        });
    }

    /**
     * Set option by key and clear cache
     */
    public function setOption($key, $value, $type = 'string', $description = null)
    {
        $option = Option::set($key, $value, $type, $description);
        
        // Clear related caches
        Cache::forget("option.{$key}");
        Cache::forget('options.all');
        
        return $this->resource::make($option);
    }

    /**
     * Update multiple options at once
     */
    public function updateMultiple(array $options)
    {
        $results = [];
        
        foreach ($options as $key => $data) {
            $value = $data['value'] ?? $data;
            $type = $data['type'] ?? 'string';
            $description = $data['description'] ?? null;
            
            $results[$key] = $this->setOption($key, $value, $type, $description);
        }
        
        return $results;
    }

    /**
     * Get options by category/group
     */
    public function getOptionsByGroup($group)
    {
        return Cache::remember("options.group.{$group}", 3600, function () use ($group) {
            return Option::where('option_key', 'like', "{$group}_%")
                ->get()
                ->mapWithKeys(function ($option) {
                    return [$option->option_key => $option->value];
                });
        });
    }

    /**
     * Delete option by key
     */
    public function deleteOption($key)
    {
        $option = Option::where('option_key', $key)->first();
        
        if ($option) {
            $option->delete();
            
            // Clear related caches
            Cache::forget("option.{$key}");
            Cache::forget('options.all');
            
            return true;
        }
        
        return false;
    }

    /**
     * Get system settings (2FA, security, etc.)
     */
    public function getSystemSettings()
    {
        $settings = [
            'two_factor' => [
                'enabled' => $this->getOption('two_factor_enabled', false),
                'required' => $this->getOption('two_factor_required', false),
                'backup_codes_count' => $this->getOption('two_factor_backup_codes_count', 10),
            ],
            'security' => [
                'session_timeout' => $this->getOption('session_timeout', 30),
                'max_login_attempts' => $this->getOption('max_login_attempts', 5),
                'password_min_length' => $this->getOption('password_min_length', 8),
            ],
            'general' => [
                'site_name' => $this->getOption('site_name', 'CorePanel'),
                'site_description' => $this->getOption('site_description', 'Admin Panel'),
                'timezone' => $this->getOption('timezone', 'UTC'),
            ],
        ];

        return $settings;
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(array $settings)
    {
        $results = [];
        
        foreach ($settings as $category => $options) {
            foreach ($options as $key => $value) {
                $optionKey = "{$category}_{$key}";
                $results[$optionKey] = $this->setOption($optionKey, $value, $this->getOptionType($value));
            }
        }
        
        return $results;
    }

    /**
     * Determine option type based on value
     */
    private function getOptionType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value) || is_object($value)) {
            return 'json';
        }
        
        return 'string';
    }

    /**
     * Initialize default options
     */
    public function initializeDefaultOptions()
    {
        $defaultOptions = [
            // Two-Factor Authentication
            'two_factor_enabled' => ['value' => false, 'type' => 'boolean', 'description' => 'Enable two-factor authentication'],
            'two_factor_required' => ['value' => false, 'type' => 'boolean', 'description' => 'Require two-factor authentication for all users'],
            'two_factor_backup_codes_count' => ['value' => 10, 'type' => 'integer', 'description' => 'Number of backup codes to generate'],
            
            // Security Settings
            'session_timeout' => ['value' => 30, 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            'max_login_attempts' => ['value' => 5, 'type' => 'integer', 'description' => 'Maximum login attempts before lockout'],
            'password_min_length' => ['value' => 8, 'type' => 'integer', 'description' => 'Minimum password length'],
            
            // General Settings
            'site_name' => ['value' => 'CorePanel', 'type' => 'string', 'description' => 'Site name'],
            'site_description' => ['value' => 'Admin Panel', 'type' => 'string', 'description' => 'Site description'],
            'timezone' => ['value' => 'UTC', 'type' => 'string', 'description' => 'Default timezone'],
        ];

        foreach ($defaultOptions as $key => $data) {
            if (!Option::where('option_key', $key)->exists()) {
                Option::set($key, $data['value'], $data['type'], $data['description']);
            }
        }
    }
}
