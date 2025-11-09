<?php

namespace App\Services;

use App\Models\Option;
use App\Http\Resources\OptionResource;
use Illuminate\Support\Facades\Cache;

class OptionService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new OptionResource(new Option()), new Option());
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
     * Get general settings only (excludes security and 2FA)
     */
    public function getGeneralSettings()
    {
        // Helper function to convert storage path to full URL
        $getLogoUrl = function($logoPath) {
            if (empty($logoPath)) {
                return '';
            }
            
            // If it's already a full URL, return as is
            if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
                return $logoPath;
            }
            
            // Convert storage path to full URL
            // storage/logos/filename.png -> http://domain.com/storage/logos/filename.png
            // Use request to get current URL with port, fallback to config
            try {
                // Try to get URL from current request (includes port automatically)
                if (app()->runningInConsole() === false && request()) {
                    // request()->root() returns the full base URL including port
                    $baseUrl = rtrim(request()->root(), '/');
                } else {
                    // Fallback to config (reads from APP_URL in .env)
                    $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
                }
            } catch (\Exception $e) {
                // If request is not available, use config
                $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
            }
            
            // Remove leading slash if present
            $cleanPath = ltrim($logoPath, '/');
            
            // Ensure path starts with storage/
            if (strpos($cleanPath, 'storage/') !== 0) {
                $cleanPath = 'storage/' . $cleanPath;
            }
            
            // Build full URL
            $fullUrl = $baseUrl . '/' . $cleanPath;
            
            return $fullUrl;
        };
        
        $settings = [
            'site' => [
                'site_name' => $this->getOption('site_name', 'CorePanel'),
                'site_description' => $this->getOption('site_description', 'Admin Panel Management System'),
                'auth_logo' => $getLogoUrl($this->getOption('auth_logo', '')),
                'sidenav_logo' => $getLogoUrl($this->getOption('sidenav_logo', '')),
            ],
            'date_time' => [
                'timezone' => $this->getOption('timezone', 'UTC'),
                'date_format' => $this->getOption('date_format', 'Y-m-d'),
                'time_format' => $this->getOption('time_format', 'H:i:s'),
            ],
            'language' => [
                'default_language' => $this->getOption('default_language', 'en'),
            ],
        ];

        return $settings;
    }

    /**
     * Update general settings only
     */
    public function updateGeneralSettings(array $settings)
    {
        $results = [];
        
        // Site settings
        if (isset($settings['site'])) {
            foreach ($settings['site'] as $key => $value) {
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Date/Time settings
        if (isset($settings['date_time'])) {
            foreach ($settings['date_time'] as $key => $value) {
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Language settings
        if (isset($settings['language'])) {
            foreach ($settings['language'] as $key => $value) {
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Clear all option caches to ensure fresh data
        Cache::forget('options.all');
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        return $results;
    }

    /**
     * Get email settings
     */
    public function getEmailSettings()
    {
        $settings = [
            'mailer' => $this->getOption('mail_mailer', 'smtp'),
            'mail_from_name' => $this->getOption('mail_from_name', 'CorePanel'),
            'mail_from_address' => $this->getOption('mail_from_address', 'noreply@example.com'),
            // SMTP settings
            'smtp' => [
                'host' => $this->getOption('mail_host', ''),
                'port' => $this->getOption('mail_port', '587'),
                'encryption' => $this->getOption('mail_encryption', 'tls'),
                'username' => $this->getOption('mail_username', ''),
                'password' => $this->getOption('mail_password', ''),
            ],
            // Mailgun settings
            'mailgun' => [
                'domain' => $this->getOption('mailgun_domain', ''),
                'secret' => $this->getOption('mailgun_secret', ''),
            ],
            // Postmark settings
            'postmark' => [
                'token' => $this->getOption('postmark_token', ''),
            ],
            // SES settings
            'ses' => [
                'key' => $this->getOption('ses_key', ''),
                'secret' => $this->getOption('ses_secret', ''),
                'region' => $this->getOption('ses_region', 'us-east-1'),
            ],
            // Microsoft Graph settings
            'microsoft' => [
                'tenant_id' => $this->getOption('microsoft_tenant_id', ''),
                'client_id' => $this->getOption('microsoft_client_id', ''),
                'client_secret' => $this->getOption('microsoft_client_secret', ''),
                'sender_email' => $this->getOption('microsoft_sender_email', ''),
            ],
        ];

        return $settings;
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings(array $settings)
    {
        $results = [];
        
        // Mailer and from settings
        if (isset($settings['mailer'])) {
            $results['mail_mailer'] = $this->setOption('mail_mailer', $settings['mailer'], 'string');
        }
        if (isset($settings['mail_from_name'])) {
            $results['mail_from_name'] = $this->setOption('mail_from_name', $settings['mail_from_name'], 'string');
        }
        if (isset($settings['mail_from_address'])) {
            $results['mail_from_address'] = $this->setOption('mail_from_address', $settings['mail_from_address'], 'string');
        }
        
        // SMTP settings
        if (isset($settings['smtp'])) {
            foreach ($settings['smtp'] as $key => $value) {
                $optionKey = 'mail_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Mailgun settings
        if (isset($settings['mailgun'])) {
            foreach ($settings['mailgun'] as $key => $value) {
                $optionKey = 'mailgun_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Postmark settings
        if (isset($settings['postmark'])) {
            foreach ($settings['postmark'] as $key => $value) {
                $optionKey = 'postmark_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // SES settings
        if (isset($settings['ses'])) {
            foreach ($settings['ses'] as $key => $value) {
                $optionKey = 'ses_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Microsoft Graph settings
        if (isset($settings['microsoft'])) {
            foreach ($settings['microsoft'] as $key => $value) {
                $optionKey = 'microsoft_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Clear all option caches
        Cache::forget('options.all');
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        return $results;
    }

    /**
     * Get security settings (2FA and Session settings)
     */
    public function getSecuritySettings()
    {
        $settings = [
            'two_factor' => [
                'enabled' => $this->getOption('two_factor_enabled', false),
                'required' => $this->getOption('two_factor_required', false),
                'backup_codes_count' => $this->getOption('two_factor_backup_codes_count', 10),
            ],
            'session' => [
                'session_enabled' => $this->getOption('session_enabled', true),
                'session_timeout' => $this->getOption('session_timeout', 30),
                'max_login_attempts' => $this->getOption('max_login_attempts', 5),
                'lockout_duration' => $this->getOption('lockout_duration', 15),
            ],
        ];

        return $settings;
    }

    /**
     * Update security settings (2FA and Session settings)
     */
    public function updateSecuritySettings(array $settings)
    {
        $results = [];
        
        // Two Factor settings - map to database keys
        if (isset($settings['two_factor'])) {
            $keyMapping = [
                'enabled' => 'two_factor_enabled',
                'required' => 'two_factor_required',
                'backup_codes_count' => 'two_factor_backup_codes_count',
            ];
            
            foreach ($settings['two_factor'] as $key => $value) {
                $dbKey = $keyMapping[$key] ?? "two_factor_{$key}";
                $type = $key === 'backup_codes_count' ? 'integer' : 'boolean';
                $results[$dbKey] = $this->setOption($dbKey, $value, $type);
            }
        }
        
        // Session settings - keys are already correct
        if (isset($settings['session'])) {
            foreach ($settings['session'] as $key => $value) {
                $type = $key === 'session_enabled' ? 'boolean' : 'integer';
                $results[$key] = $this->setOption($key, $value, $type);
            }
        }
        
        // Clear all option caches to ensure fresh data
        Cache::forget('options.all');
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        return $results;
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
            'session_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable session timeout'],
            'session_timeout' => ['value' => 30, 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            'max_login_attempts' => ['value' => 5, 'type' => 'integer', 'description' => 'Maximum login attempts before lockout'],
            'lockout_duration' => ['value' => 15, 'type' => 'integer', 'description' => 'Account lockout duration in minutes'],
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
