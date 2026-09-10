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
        return $this->sendEmailWithFileAttachment(
            $to,
            $subject,
            $body,
            $pdfContent,
            $pdfFilename,
            'application/pdf',
            $cc
        );
    }

    /**
     * Send an email with one or more file attachments using the configured mailer.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $fileContent Single file binary content (optional if $files provided)
     * @param string|null $filename Single filename (optional if $files provided)
     * @param string $mimeType MIME type for single file
     * @param array $cc CC recipients (optional)
     * @param array<int, array{content:string,name:string,mime:string}> $files Optional multi-file list
     * @return bool Success status
     */
    public function sendEmailWithFileAttachment(
        $to,
        $subject,
        $body,
        $fileContent = null,
        $filename = null,
        $mimeType = 'application/octet-stream',
        $cc = [],
        array $files = []
    ) {
        try {
            if (empty($files) && $fileContent !== null && $filename !== null) {
                $files = [[
                    'content' => $fileContent,
                    'name' => $filename,
                    'mime' => $mimeType ?: 'application/octet-stream',
                ]];
            }

            $mailer = $this->emailHelper->getMailer();

            if ($mailer === 'microsoft') {
                return $this->sendViaMicrosoftGraphWithFileAttachments($to, $subject, $body, $files, $cc);
            }

            return $this->sendViaLaravelMailWithFileAttachments($to, $subject, $body, $files, $cc);
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
     * Send email with file attachments via Microsoft Graph API.
     *
     * @param array<int, array{content:string,name:string,mime:string}> $files
     */
    protected function sendViaMicrosoftGraphWithFileAttachments($to, $subject, $body, array $files, $cc = [])
    {
        try {
            $attachments = [];
            foreach ($files as $file) {
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => $file['name'],
                    'contentType' => $file['mime'] ?? 'application/octet-stream',
                    'contentBytes' => base64_encode($file['content']),
                ];
            }

            MicrosoftGraphService::sendEmailWithAttachments($to, $subject, $body, $attachments, $cc);

            Log::info('[EmailService] Email with attachment sent via Microsoft Graph', [
                'to' => $to,
                'subject' => $subject,
                'filenames' => array_column($files, 'name'),
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
     * Send email with file attachments via Laravel Mail.
     *
     * @param array<int, array{content:string,name:string,mime:string}> $files
     */
    protected function sendViaLaravelMailWithFileAttachments($to, $subject, $body, array $files, $cc = [])
    {
        try {
            $mailConfig = $this->emailHelper->getLaravelMailConfig();
            Config::set('mail', $mailConfig);

            $fromAddress = $this->emailHelper->getFromAddress();
            $fromName = $this->emailHelper->getFromName();

            Mail::html($body, function ($message) use ($to, $subject, $fromAddress, $fromName, $cc, $files) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);

                if (!empty($cc)) {
                    foreach ($cc as $ccEmail) {
                        $message->cc($ccEmail);
                    }
                }

                foreach ($files as $file) {
                    $message->attachData($file['content'], $file['name'], [
                        'mime' => $file['mime'] ?? 'application/octet-stream',
                    ]);
                }
            });

            Log::info('[EmailService] Email with attachment sent via Laravel Mail', [
                'to' => $to,
                'subject' => $subject,
                'filenames' => array_column($files, 'name'),
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

