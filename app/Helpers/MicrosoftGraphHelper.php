<?php

namespace App\Helpers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphHelper
{
  private static $accessToken;
  private static $graphClient;

  /**
   * Initialize the Graph client with access token
   */
  public static function initialize()
  {
    try {
      self::$accessToken = self::getAccessToken();
      Log::info('Access token obtained successfully');
      
      $isHttps = (strpos(config('app.url'), 'https://') === 0);
      self::$graphClient = new Client([
        'verify' => $isHttps ? storage_path('certs/cacert.pem') : false,
        'headers' => [
          'Authorization' => 'Bearer ' . self::$accessToken,
          'Content-Type' => 'application/json'
        ]
      ]);
      Log::info('Graph client initialized successfully');
    } catch (Exception $e) {
      Log::error('Failed to initialize Microsoft Graph: ' . $e->getMessage());
      throw new Exception('Failed to initialize Microsoft Graph: ' . $e->getMessage());
    }
  }

  /**
   * Get access token using client credentials
   */
  private static function getAccessToken()
  {
    // Get configuration from config file
    $config = config('microsoft');
    
    // Validate required configuration
    $requiredConfig = [
        'tenant_id',
        'client_id',
        'client_secret',
        'sender_email'
    ];

    foreach ($requiredConfig as $configKey) {
        if (empty($config[$configKey])) {
            Log::error("Missing required Microsoft Graph configuration: {$configKey}");
            throw new Exception("Missing required Microsoft Graph configuration: {$configKey}");
        }
    }

    $tenantId = $config['tenant_id'];
    $isHttps = (strpos(config('app.url'), 'https://') === 0);
    $verifySsl = $config['verify_ssl'] && $isHttps;
    
    $guzzle = new Client([
        'verify' => $verifySsl ? $config['cert_path'] : false
    ]);
    
    $url = str_replace('{tenant_id}', $tenantId, $config['token_url']);
    
    try {
      Log::info('Requesting access token from Microsoft Graph');
      $token = $guzzle->post($url, [
        'form_params' => [
          'client_id' => $config['client_id'],
          'client_secret' => $config['client_secret'],
          'scope' => $config['scope'],
          'grant_type' => 'client_credentials',
        ],
      ]);

      $accessToken = json_decode($token->getBody()->getContents());
      if (!isset($accessToken->access_token)) {
        Log::error('Invalid response from Microsoft: ' . json_encode($accessToken));
        throw new Exception('Failed to get access token: Invalid response from Microsoft');
      }
      Log::info('Access token obtained successfully');
      return $accessToken->access_token;
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $body = $response ? $response->getBody()->getContents() : 'No response body';
      Log::error("Microsoft Graph authentication failed: " . $body);
      throw new Exception("Microsoft Graph authentication failed: " . $body);
    } catch (\Exception $e) {
      Log::error("Failed to get access token: " . $e->getMessage());
      throw new Exception("Failed to get access token: " . $e->getMessage());
    }
  }

  /**
   * Send email using Microsoft Graph API
   * 
   * @param string $to Recipient email address
   * @param string $subject Email subject
   * @param string $body Email body (HTML)
   * @param string|array $cc Optional CC email(s)
   * @param array $attachments Optional array of attachments
   * @return bool
   */
  public static function sendEmail($to, $subject, $body, $cc = [], $attachments = [])
  {
    try {
      if (!self::$graphClient) {
        self::initialize();
      }

      $email = [
        'message' => [
          'subject' => $subject,
          'body' => [
            'contentType' => 'HTML',
            'content' => $body
          ],
          'toRecipients' => array_map(function($address) {
            return ['emailAddress' => ['address' => trim($address)]];
          }, is_array($to) ? $to : explode(',', $to)),
        ]
      ];

      // Add CC recipients if provided
      if (!empty($cc)) {
        $ccList = is_array($cc) ? $cc : explode(',', $cc);
        $ccList = array_filter(array_map('trim', $ccList));
        if (count($ccList)) {
          $email['message']['ccRecipients'] = array_map(function($address) {
            return ['emailAddress' => ['address' => $address]];
          }, $ccList);
        }
      }

      // Add attachments if any
      if (!empty($attachments)) {
        $email['message']['attachments'] = $attachments;
      }

      $config = config('microsoft');
      
      Log::info('Attempting to send email', [
        'to' => $to,
        'subject' => $subject,
        'sender' => $config['sender_email']
      ]);

      $sendMailUrl = str_replace('{sender_email}', $config['sender_email'], $config['send_mail_url']);
      $response = self::$graphClient->post($sendMailUrl, ['json' => $email]);

      $statusCode = $response->getStatusCode();
      Log::info('Email send response', ['status_code' => $statusCode]);

      return $statusCode === 202;
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $body = $response ? $response->getBody()->getContents() : 'No response body';
      Log::error('Failed to send email: ' . $body);
      throw new Exception('Failed to send email: ' . $body);
    } catch (Exception $e) {
      Log::error('Failed to send email: ' . $e->getMessage());
      throw new Exception('Failed to send email: ' . $e->getMessage());
    }
  }

  /**
   * Send notification email for PathCast system
   */
  public static function sendNotificationEmail($to, $subject, $body, $cc = [])
  {
    return self::sendEmail($to, $subject, $body, $cc);
  }

  /**
   * Send user registration confirmation email
   */
  public static function sendUserRegistrationEmail($to, $userName, $verificationLink)
  {
    $subject = "Welcome to PathCast - Please Verify Your Email";
    $body = "<h2>Welcome to PathCast!</h2>"
          . "<p>Hello {$userName},</p>"
          . "<p>Thank you for registering with PathCast. Please click the link below to verify your email address:</p>"
          . "<p><a href='{$verificationLink}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email Address</a></p>"
          . "<p>If the button doesn't work, you can copy and paste this link into your browser:</p>"
          . "<p>{$verificationLink}</p>"
          . "<p>Best regards,<br>The PathCast Team</p>";
    
    return self::sendEmail($to, $subject, $body);
  }

  /**
   * Send password reset email
   */
  public static function sendPasswordResetEmail($to, $userName, $resetLink)
  {
    $subject = "PathCast - Password Reset Request";
    $body = "<h2>Password Reset Request</h2>"
          . "<p>Hello {$userName},</p>"
          . "<p>You have requested to reset your password for your PathCast account. Click the link below to reset your password:</p>"
          . "<p><a href='{$resetLink}' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>"
          . "<p>If the button doesn't work, you can copy and paste this link into your browser:</p>"
          . "<p>{$resetLink}</p>"
          . "<p>This link will expire in 1 hour for security reasons.</p>"
          . "<p>If you didn't request this password reset, please ignore this email.</p>"
          . "<p>Best regards,<br>The PathCast Team</p>";
    
    return self::sendEmail($to, $subject, $body);
  }

  /**
   * Send two-factor authentication code email
   */
  public static function sendTwoFactorCodeEmail($to, $userName, $code)
  {
    $subject = "PathCast - Two-Factor Authentication Code";
    $body = "<h2>Two-Factor Authentication Code</h2>"
          . "<p>Hello {$userName},</p>"
          . "<p>Your two-factor authentication code is:</p>"
          . "<h1 style='color: #007bff; font-size: 32px; text-align: center; letter-spacing: 5px;'>{$code}</h1>"
          . "<p>This code will expire in 10 minutes.</p>"
          . "<p>If you didn't request this code, please contact support immediately.</p>"
          . "<p>Best regards,<br>The PathCast Team</p>";
    
    return self::sendEmail($to, $subject, $body);
  }
}
