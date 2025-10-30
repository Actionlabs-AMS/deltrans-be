<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\OptionService;
use App\Services\MessageService;
use App\Services\TwoFactorAuthService;

/**
 * @OA\Tag(
 *     name="Settings Management",
 *     description="API endpoints for application settings management"
 * )
 */
class SettingsController extends BaseController
{
    protected $optionService;
    protected $twoFactorService;

    public function __construct(OptionService $optionService, MessageService $messageService, TwoFactorAuthService $twoFactorService)
    {
        $this->optionService = $optionService;
        $this->messageService = $messageService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Get all system settings
     * 
     * @OA\Get(
     *     path="/api/settings",
     *     summary="Get all system settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $settings = $this->optionService->getSystemSettings();
            
            return response([
                'success' => true,
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve settings');
        }
    }

    /**
     * Update system settings
     * 
     * @OA\Post(
     *     path="/api/settings",
     *     summary="Update system settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="two_factor", type="object"),
     *             @OA\Property(property="security", type="object"),
     *             @OA\Property(property="general", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Settings updated successfully")
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'two_factor' => 'sometimes|array',
                'two_factor.enabled' => 'sometimes|boolean',
                'two_factor.required' => 'sometimes|boolean',
                'two_factor.backup_codes_count' => 'sometimes|integer|min:5|max:20',
                'security' => 'sometimes|array',
                'security.session_timeout' => 'sometimes|integer|min:5|max:480',
                'security.max_login_attempts' => 'sometimes|integer|min:3|max:10',
                'security.password_min_length' => 'sometimes|integer|min:6|max:32',
                'general' => 'sometimes|array',
                'general.site_name' => 'sometimes|string|max:255',
                'general.site_description' => 'sometimes|string|max:500',
                'general.timezone' => 'sometimes|string|max:50',
            ]);

            $this->optionService->updateSystemSettings($validated);

            return response([
                'success' => true,
                'message' => 'Settings updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update settings');
        }
    }

    /**
     * Get specific option by key
     * 
     * @OA\Get(
     *     path="/api/settings/{key}",
     *     summary="Get specific option by key",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Option key",
     *         @OA\Schema(type="string", example="two_factor_enabled")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option retrieved successfully"
     *     )
     * )
     */
    public function show($key)
    {
        try {
            $value = $this->optionService->getOption($key);
            
            return response([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve option');
        }
    }

    /**
     * Update specific option by key
     * 
     * @OA\Put(
     *     path="/api/settings/{key}",
     *     summary="Update specific option by key",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Option key",
     *         @OA\Schema(type="string", example="two_factor_enabled")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="true"),
     *             @OA\Property(property="type", type="string", example="boolean"),
     *             @OA\Property(property="description", type="string", example="Enable 2FA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option updated successfully"
     *     )
     * )
     */
    public function updateOption(Request $request, $key)
    {
        try {
            $validated = $request->validate([
                'value' => 'required',
                'type' => 'sometimes|string|in:string,boolean,integer,float,json',
                'description' => 'sometimes|string|max:500'
            ]);

            $option = $this->optionService->setOption(
                $key,
                $validated['value'],
                $validated['type'] ?? 'string',
                $validated['description'] ?? null
            );

            return response([
                'success' => true,
                'message' => 'Option updated successfully',
                'data' => $option
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update option');
        }
    }

    /**
     * Get 2FA settings for current user
     * 
     * @OA\Get(
     *     path="/api/settings/two-factor/status",
     *     summary="Get 2FA status for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA status retrieved successfully"
     *     )
     * )
     */
    public function getTwoFactorStatus()
    {
        try {
            $user = auth()->user();
            $status = $this->twoFactorService->getStatus($user->id);
            
            return response([
                'success' => true,
                'data' => $status
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve 2FA status');
        }
    }

    /**
     * Enable 2FA for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/enable",
     *     summary="Enable 2FA for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA enabled successfully"
     *     )
     * )
     */
    public function enableTwoFactor()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->enable($user->id);
            
            return response([
                'success' => true,
                'message' => '2FA enabled successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to enable 2FA');
        }
    }

    /**
     * Disable 2FA for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/disable",
     *     summary="Disable 2FA for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA disabled successfully"
     *     )
     * )
     */
    public function disableTwoFactor()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->disable($user->id);
            
            return response([
                'success' => true,
                'message' => '2FA disabled successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to disable 2FA');
        }
    }

    /**
     * Generate backup codes for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/backup-codes",
     *     summary="Generate backup codes for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Backup codes generated successfully"
     *     )
     * )
     */
    public function generateBackupCodes()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->generateBackupCodes($user->id);
            
            return response([
                'success' => true,
                'message' => 'Backup codes generated successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to generate backup codes');
        }
    }

    /**
     * Initialize default options (admin only)
     * 
     * @OA\Post(
     *     path="/api/settings/initialize",
     *     summary="Initialize default options",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Default options initialized successfully"
     *     )
     * )
     */
    public function initialize()
    {
        try {
            $this->optionService->initializeDefaultOptions();
            
            return response([
                'success' => true,
                'message' => 'Default options initialized successfully'
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to initialize default options');
        }
    }
}
