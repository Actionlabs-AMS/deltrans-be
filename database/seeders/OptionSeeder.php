<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Option;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            [
                'option_key' => 'site_name',
                'option_value' => 'BaseCode',
                'option_type' => 'string',
                'description' => 'Site name',
            ],
            [
                'option_key' => 'site_description',
                'option_value' => 'A generic Laravel base project with enterprise security',
                'option_type' => 'string',
                'description' => 'Site description',
            ],
            [
                'option_key' => 'site_email',
                'option_value' => 'admin@basecode.com',
                'option_type' => 'string',
                'description' => 'Site email address',
            ],
            [
                'option_key' => 'site_url',
                'option_value' => 'http://localhost:8000',
                'option_type' => 'string',
                'description' => 'Site URL',
            ],
            [
                'option_key' => 'security_enabled',
                'option_value' => 'true',
                'option_type' => 'boolean',
                'description' => 'Enable security features',
            ],
            [
                'option_key' => 'two_factor_enabled',
                'option_value' => 'true',
                'option_type' => 'boolean',
                'description' => 'Enable two-factor authentication',
            ],
            [
                'option_key' => 'audit_trail_enabled',
                'option_value' => 'true',
                'option_type' => 'boolean',
                'description' => 'Enable audit trail logging',
            ],
            [
                'option_key' => 'max_file_size',
                'option_value' => '10485760',
                'option_type' => 'integer',
                'description' => 'Maximum file upload size in bytes',
            ],
            [
                'option_key' => 'allowed_file_types',
                'option_value' => 'jpg,jpeg,png,gif,pdf,doc,docx',
                'option_type' => 'string',
                'description' => 'Allowed file types for upload',
            ],
            [
                'option_key' => 'session_lifetime',
                'option_value' => '120',
                'option_type' => 'integer',
                'description' => 'Session lifetime in minutes',
            ],
        ];

        foreach ($options as $option) {
            Option::updateOrCreate(
                ['option_key' => $option['option_key']],
                $option
            );
        }
    }
}
