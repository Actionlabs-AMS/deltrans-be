<?php

namespace App\Helpers;

use App\Services\OptionService;

/**
 * EmailHelper Class
 * 
 * Centralized helper class for managing email settings in the database.
 * Provides methods to save, retrieve, and format email configuration data.
 * 
 * Database Structure:
 * - mail_mailer: The selected mailer (smtp, mailgun, postmark, ses, microsoft, sendmail, log, array)
 * - mail_from_name: From name for emails
 * - mail_from_address: From email address
 * 
 * SMTP Settings:
 * - mail_host: SMTP host
 * - mail_port: SMTP port
 * - mail_encryption: Encryption type (tls, ssl, starttls, or empty)
 * - mail_username: SMTP username
 * - mail_password: SMTP password
 * 
 * Mailgun Settings:
 * - mailgun_domain: Mailgun domain
 * - mailgun_secret: Mailgun secret key
 * 
 * Postmark Settings:
 * - postmark_token: Postmark server API token
 * 
 * SES Settings:
 * - ses_key: AWS access key ID
 * - ses_secret: AWS secret access key
 * - ses_region: AWS region
 * 
 * Microsoft Graph Settings:
 * - microsoft_tenant_id: Azure AD tenant ID
 * - microsoft_client_id: Azure AD client ID
 * - microsoft_client_secret: Azure AD client secret
 * - microsoft_sender_email: Office 365 sender email address
 */
class EmailHelper
{
    protected $optionService;

    public function __construct(OptionService $optionService)
    {
        $this->optionService = $optionService;
    }

    /**
     * Get all email settings from database
     * 
     * @return array
     */
    public function getEmailSettings(): array
    {
        return [
            'mailer' => $this->optionService->getOption('mail_mailer', 'smtp'),
            'mail_from_name' => $this->optionService->getOption('mail_from_name', 'CorePanel'),
            'mail_from_address' => $this->optionService->getOption('mail_from_address', 'noreply@example.com'),
            
            // SMTP settings
            'smtp' => [
                'host' => $this->optionService->getOption('mail_host', ''),
                'port' => $this->optionService->getOption('mail_port', '587'),
                'encryption' => $this->optionService->getOption('mail_encryption', 'tls'),
                'username' => $this->optionService->getOption('mail_username', ''),
                'password' => $this->optionService->getOption('mail_password', ''),
            ],
            
            // Mailgun settings
            'mailgun' => [
                'domain' => $this->optionService->getOption('mailgun_domain', ''),
                'secret' => $this->optionService->getOption('mailgun_secret', ''),
            ],
            
            // Postmark settings
            'postmark' => [
                'token' => $this->optionService->getOption('postmark_token', ''),
            ],
            
            // SES settings
            'ses' => [
                'key' => $this->optionService->getOption('ses_key', ''),
                'secret' => $this->optionService->getOption('ses_secret', ''),
                'region' => $this->optionService->getOption('ses_region', 'us-east-1'),
            ],
            
            // Microsoft Graph settings
            'microsoft' => [
                'tenant_id' => $this->optionService->getOption('microsoft_tenant_id', ''),
                'client_id' => $this->optionService->getOption('microsoft_client_id', ''),
                'client_secret' => $this->optionService->getOption('microsoft_client_secret', ''),
                'sender_email' => $this->optionService->getOption('microsoft_sender_email', ''),
            ],
        ];
    }

    /**
     * Save email settings to database
     * 
     * @param array $settings Email settings array
     * @return array Results of save operations
     */
    public function saveEmailSettings(array $settings): array
    {
        $results = [];
        
        // Save mailer and from settings
        if (isset($settings['mailer'])) {
            $results['mail_mailer'] = $this->optionService->setOption('mail_mailer', $settings['mailer'], 'string');
        }
        
        if (isset($settings['mail_from_name'])) {
            $results['mail_from_name'] = $this->optionService->setOption('mail_from_name', $settings['mail_from_name'], 'string');
        }
        
        if (isset($settings['mail_from_address'])) {
            $results['mail_from_address'] = $this->optionService->setOption('mail_from_address', $settings['mail_from_address'], 'string');
        }
        
        // Save SMTP settings
        if (isset($settings['smtp']) && is_array($settings['smtp'])) {
            $smtpMapping = [
                'host' => 'mail_host',
                'port' => 'mail_port',
                'encryption' => 'mail_encryption',
                'username' => 'mail_username',
                'password' => 'mail_password',
            ];
            
            foreach ($smtpMapping as $key => $optionKey) {
                if (isset($settings['smtp'][$key])) {
                    $results[$optionKey] = $this->optionService->setOption($optionKey, $settings['smtp'][$key], 'string');
                }
            }
        }
        
        // Save Mailgun settings
        if (isset($settings['mailgun']) && is_array($settings['mailgun'])) {
            $mailgunMapping = [
                'domain' => 'mailgun_domain',
                'secret' => 'mailgun_secret',
            ];
            
            foreach ($mailgunMapping as $key => $optionKey) {
                if (isset($settings['mailgun'][$key])) {
                    $results[$optionKey] = $this->optionService->setOption($optionKey, $settings['mailgun'][$key], 'string');
                }
            }
        }
        
        // Save Postmark settings
        if (isset($settings['postmark']) && is_array($settings['postmark'])) {
            $postmarkMapping = [
                'token' => 'postmark_token',
            ];
            
            foreach ($postmarkMapping as $key => $optionKey) {
                if (isset($settings['postmark'][$key])) {
                    $results[$optionKey] = $this->optionService->setOption($optionKey, $settings['postmark'][$key], 'string');
                }
            }
        }
        
        // Save SES settings
        if (isset($settings['ses']) && is_array($settings['ses'])) {
            $sesMapping = [
                'key' => 'ses_key',
                'secret' => 'ses_secret',
                'region' => 'ses_region',
            ];
            
            foreach ($sesMapping as $key => $optionKey) {
                if (isset($settings['ses'][$key])) {
                    $results[$optionKey] = $this->optionService->setOption($optionKey, $settings['ses'][$key], 'string');
                }
            }
        }
        
        // Save Microsoft Graph settings
        if (isset($settings['microsoft']) && is_array($settings['microsoft'])) {
            $microsoftMapping = [
                'tenant_id' => 'microsoft_tenant_id',
                'client_id' => 'microsoft_client_id',
                'client_secret' => 'microsoft_client_secret',
                'sender_email' => 'microsoft_sender_email',
            ];
            
            foreach ($microsoftMapping as $key => $optionKey) {
                if (isset($settings['microsoft'][$key])) {
                    $results[$optionKey] = $this->optionService->setOption($optionKey, $settings['microsoft'][$key], 'string');
                }
            }
        }
        
        return $results;
    }

    /**
     * Get email configuration for Laravel Mail config
     * Primary: Database options, Fallback: Environment variables
     * 
     * @return array Laravel Mail configuration array
     */
    public function getLaravelMailConfig(): array
    {
        // Helper function to get option with env fallback
        $getConfig = function ($optionKey, $envKey, $default = null) {
            try {
                $dbValue = $this->optionService->getOption($optionKey, null);
                if ($dbValue !== null && $dbValue !== '') {
                    return $dbValue;
                }
            } catch (\Exception $e) {
                // If database query fails, fallback to env
            }
            return env($envKey, $default);
        };

        // Get mailer (default mailer)
        // Note: "microsoft" is not a Laravel mailer - it's handled via MicrosoftGraphService
        // If microsoft is selected, use SMTP as fallback for Laravel Mail
        $mailer = $getConfig('mail_mailer', 'MAIL_MAILER', 'smtp');
        
        // Auto-convert sendmail to SMTP on Windows (sendmail doesn't work on Windows)
        if ($mailer === 'sendmail' && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $mailer = 'smtp'; // Use SMTP on Windows instead of sendmail
        }
        
        if ($mailer === 'microsoft') {
            $mailer = 'smtp'; // Use SMTP for Laravel Mail, Microsoft Graph handled separately
        }

        // Get from address and name
        $fromAddress = $getConfig('mail_from_address', 'MAIL_FROM_ADDRESS', 'hello@example.com');
        $fromName = $getConfig('mail_from_name', 'MAIL_FROM_NAME', 'Example');

        // Build mailers configuration
        $mailers = [
            'smtp' => [
                'transport' => 'smtp',
                'url' => env('MAIL_URL'),
                'host' => $getConfig('mail_host', 'MAIL_HOST', 'smtp.mailgun.org'),
                'port' => $getConfig('mail_port', 'MAIL_PORT', 587),
                'encryption' => $getConfig('mail_encryption', 'MAIL_ENCRYPTION', 'tls'),
                'username' => $getConfig('mail_username', 'MAIL_USERNAME'),
                'password' => $getConfig('mail_password', 'MAIL_PASSWORD'),
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            'ses' => [
                'transport' => 'ses',
            ],
            'postmark' => [
                'transport' => 'postmark',
            ],
            'mailgun' => [
                'transport' => 'mailgun',
            ],
            'sendmail' => [
                'transport' => 'sendmail',
                'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
            ],
            'log' => [
                'transport' => 'log',
                'channel' => env('MAIL_LOG_CHANNEL'),
            ],
            'array' => [
                'transport' => 'array',
            ],
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'smtp',
                    'log',
                ],
            ],
            'roundrobin' => [
                'transport' => 'roundrobin',
                'mailers' => [
                    'ses',
                    'postmark',
                ],
            ],
        ];

        return [
            'default' => $mailer,
            'mailers' => $mailers,
            'from' => [
                'address' => $fromAddress,
                'name' => $fromName,
            ],
            'markdown' => [
                'theme' => 'default',
                'paths' => [
                    resource_path('views/vendor/mail'),
                ],
            ],
        ];
    }

    /**
     * Get the selected mailer
     * Auto-converts sendmail to SMTP on Windows
     * 
     * @return string
     */
    public function getMailer(): string
    {
        $mailer = $this->optionService->getOption('mail_mailer', 'smtp');
        
        // Auto-convert sendmail to SMTP on Windows (sendmail doesn't work on Windows)
        if ($mailer === 'sendmail' && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'smtp'; // Use SMTP on Windows instead of sendmail
        }
        
        return $mailer;
    }

    /**
     * Get from address
     * 
     * @return string
     */
    public function getFromAddress(): string
    {
        return $this->optionService->getOption('mail_from_address', 'noreply@example.com');
    }

    /**
     * Get from name
     * 
     * @return string
     */
    public function getFromName(): string
    {
        return $this->optionService->getOption('mail_from_name', 'CorePanel');
    }

    /**
     * Get SMTP settings
     * 
     * @return array
     */
    public function getSmtpSettings(): array
    {
        return [
            'host' => $this->optionService->getOption('mail_host', ''),
            'port' => $this->optionService->getOption('mail_port', '587'),
            'encryption' => $this->optionService->getOption('mail_encryption', 'tls'),
            'username' => $this->optionService->getOption('mail_username', ''),
            'password' => $this->optionService->getOption('mail_password', ''),
        ];
    }

    /**
     * Get Mailgun settings
     * 
     * @return array
     */
    public function getMailgunSettings(): array
    {
        return [
            'domain' => $this->optionService->getOption('mailgun_domain', ''),
            'secret' => $this->optionService->getOption('mailgun_secret', ''),
        ];
    }

    /**
     * Get Postmark settings
     * 
     * @return array
     */
    public function getPostmarkSettings(): array
    {
        return [
            'token' => $this->optionService->getOption('postmark_token', ''),
        ];
    }

    /**
     * Get SES settings
     * 
     * @return array
     */
    public function getSesSettings(): array
    {
        return [
            'key' => $this->optionService->getOption('ses_key', ''),
            'secret' => $this->optionService->getOption('ses_secret', ''),
            'region' => $this->optionService->getOption('ses_region', 'us-east-1'),
        ];
    }

    /**
     * Get Microsoft Graph settings
     * 
     * @return array
     */
    public function getMicrosoftSettings(): array
    {
        return [
            'tenant_id' => $this->optionService->getOption('microsoft_tenant_id', ''),
            'client_id' => $this->optionService->getOption('microsoft_client_id', ''),
            'client_secret' => $this->optionService->getOption('microsoft_client_secret', ''),
            'sender_email' => $this->optionService->getOption('microsoft_sender_email', ''),
        ];
    }

    /**
     * Check if email settings are configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        $mailer = $this->getMailer();
        $fromAddress = $this->getFromAddress();
        
        if (empty($fromAddress)) {
            return false;
        }
        
        // Check if selected mailer has required settings
        switch ($mailer) {
            case 'smtp':
                $smtp = $this->getSmtpSettings();
                return !empty($smtp['host']) && !empty($smtp['username']);
                
            case 'mailgun':
                $mailgun = $this->getMailgunSettings();
                return !empty($mailgun['domain']) && !empty($mailgun['secret']);
                
            case 'postmark':
                $postmark = $this->getPostmarkSettings();
                return !empty($postmark['token']);
                
            case 'ses':
                $ses = $this->getSesSettings();
                return !empty($ses['key']) && !empty($ses['secret']);
                
            case 'microsoft':
                $microsoft = $this->getMicrosoftSettings();
                return !empty($microsoft['tenant_id']) && 
                       !empty($microsoft['client_id']) && 
                       !empty($microsoft['client_secret']) && 
                       !empty($microsoft['sender_email']);
                
            case 'sendmail':
            case 'log':
            case 'array':
                // These mailers don't need additional configuration
                return true;
                
            default:
                return false;
        }
    }
}

