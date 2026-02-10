<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * EmailService Class
 * 
 * Centralized service for sending emails using database-configured settings.
 * Automatically uses the configured mailer (SMTP, Mailgun, Postmark, SES, Microsoft, etc.)
 * from the database settings.
 */
class EmailService
{
    protected $emailHelper;
    protected $optionService;

    public function __construct(OptionService $optionService)
    {
        $this->optionService = $optionService;
        $this->emailHelper = new EmailHelper($optionService);
    }

    /**
     * Send an email using the configured mailer from database settings
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $cc CC recipients (optional)
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $body, $cc = [])
    {
        try {
            $mailer = $this->emailHelper->getMailer();
            
            // If Microsoft Graph is configured, use it
            if ($mailer === 'microsoft') {
                return $this->sendViaMicrosoftGraph($to, $subject, $body, $cc);
            }
            
            // Otherwise, use Laravel Mail with configured settings
            return $this->sendViaLaravelMail($to, $subject, $body, $cc);
            
        } catch (\Exception $e) {
            Log::error('[EmailService] Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'mailer' => $this->emailHelper->getMailer(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Send email via Microsoft Graph API
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $cc
     * @return bool
     */
    protected function sendViaMicrosoftGraph($to, $subject, $body, $cc = [])
    {
        try {
            MicrosoftGraphService::sendNotificationEmail($to, $subject, $body, $cc);
            
            Log::info('[EmailService] Email sent via Microsoft Graph', [
                'to' => $to,
                'subject' => $subject
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[EmailService] Microsoft Graph email failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send email via Laravel Mail using configured settings
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $cc
     * @return bool
     */
    protected function sendViaLaravelMail($to, $subject, $body, $cc = [])
    {
        try {
            // Get email configuration from database
            $mailConfig = $this->emailHelper->getLaravelMailConfig();
            
            // Temporarily set mail config (AppServiceProvider already sets it, but ensure it's current)
            Config::set('mail', $mailConfig);
            
            // Get from address and name
            $fromAddress = $this->emailHelper->getFromAddress();
            $fromName = $this->emailHelper->getFromName();
            
            // Send email using Laravel Mail with HTML content
            Mail::html($body, function ($message) use ($to, $subject, $fromAddress, $fromName, $cc) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
                
                // Add CC recipients if provided
                if (!empty($cc)) {
                    foreach ($cc as $ccEmail) {
                        $message->cc($ccEmail);
                    }
                }
            });
            
            Log::info('[EmailService] Email sent via Laravel Mail', [
                'to' => $to,
                'subject' => $subject,
                'mailer' => $mailConfig['default']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[EmailService] Laravel Mail email failed', [
                'to' => $to,
                'subject' => $subject,
                'mailer' => $this->emailHelper->getMailer(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if email settings are configured
     * 
     * @return bool
     */
    public function isConfigured()
    {
        return $this->emailHelper->isConfigured();
    }

    /**
     * Get the configured mailer
     * 
     * @return string
     */
    public function getMailer()
    {
        return $this->emailHelper->getMailer();
    }

    /**
     * Send an email with PDF attachment using the configured mailer
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $pdfContent PDF binary content
     * @param string $pdfFilename PDF filename for attachment
     * @param array $cc CC recipients (optional)
     * @return bool Success status
     */
    public function sendEmailWithAttachment($to, $subject, $body, $pdfContent, $pdfFilename, $cc = [])
    {
        try {
            $mailer = $this->emailHelper->getMailer();
            
            // If Microsoft Graph is configured, use it
            if ($mailer === 'microsoft') {
                return $this->sendViaMicrosoftGraphWithAttachment($to, $subject, $body, $pdfContent, $pdfFilename, $cc);
            }
            
            // Otherwise, use Laravel Mail with configured settings
            return $this->sendViaLaravelMailWithAttachment($to, $subject, $body, $pdfContent, $pdfFilename, $cc);
            
        } catch (\Exception $e) {
            Log::error('[EmailService] Failed to send email with attachment', [
                'to' => $to,
                'subject' => $subject,
                'mailer' => $this->emailHelper->getMailer(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Send email with attachment via Microsoft Graph API
     */
    protected function sendViaMicrosoftGraphWithAttachment($to, $subject, $body, $pdfContent, $pdfFilename, $cc = [])
    {
        try {
            // Convert PDF to base64 for Microsoft Graph
            $base64Content = base64_encode($pdfContent);
            $attachments = [[
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $pdfFilename,
                'contentType' => 'application/pdf',
                'contentBytes' => $base64Content
            ]];
            
            MicrosoftGraphService::sendEmailWithAttachments($to, $subject, $body, $attachments, $cc);
            
            Log::info('[EmailService] Email with attachment sent via Microsoft Graph', [
                'to' => $to,
                'subject' => $subject,
                'filename' => $pdfFilename
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[EmailService] Microsoft Graph email with attachment failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send email with attachment via Laravel Mail
     */
    protected function sendViaLaravelMailWithAttachment($to, $subject, $body, $pdfContent, $pdfFilename, $cc = [])
    {
        try {
            $mailConfig = $this->emailHelper->getLaravelMailConfig();
            Config::set('mail', $mailConfig);
            
            $fromAddress = $this->emailHelper->getFromAddress();
            $fromName = $this->emailHelper->getFromName();
            
            Mail::html($body, function ($message) use ($to, $subject, $fromAddress, $fromName, $cc, $pdfContent, $pdfFilename) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
                
                if (!empty($cc)) {
                    foreach ($cc as $ccEmail) {
                        $message->cc($ccEmail);
                    }
                }
                
                // Attach PDF
                $message->attachData($pdfContent, $pdfFilename, [
                    'mime' => 'application/pdf',
                ]);
            });
            
            Log::info('[EmailService] Email with attachment sent via Laravel Mail', [
                'to' => $to,
                'subject' => $subject,
                'filename' => $pdfFilename,
                'mailer' => $mailConfig['default']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[EmailService] Laravel Mail email with attachment failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

