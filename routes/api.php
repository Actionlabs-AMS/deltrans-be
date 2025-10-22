<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\SecurityDashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\NavigationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/user/me', [UserController::class, 'getUser']);
	Route::post('/logout', [AuthController::class, 'logout']);
	
	/*
	|--------------------------------------------------------------------------
	| Options Management Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('options')->group(function () {
		// media date folder
		Route::get('/dates', [MediaController::class, 'dateFolder']);
		// categories-related routes
		Route::get('/categories', [CategoryController::class, 'getCategories']);  // Retrieve all categories for dropdown
		Route::get('/categories/{id}', [CategoryController::class, 'getSubCategories']);  // Retrieve subcategories for a specific category		
		Route::get('/tags', [TagController::class, 'getTags']);  // Retrieve all tags for dropdown
		// navigation-related routes
		Route::get('/navigations', [NavigationController::class, 'getNavigations']);  // Retrieve all categories for dropdown
		Route::get('/navigations/{id}', [NavigationController::class, 'getSubNavigations']);  // Retrieve subcategories for a specific category		
		Route::get('/routes', [NavigationController::class, 'getRoutes']);  // Retrieve all routes
		Route::get('/roles', [RoleController::class, 'getRoles']);  // Retrieve all roles
	});

	/*
	|--------------------------------------------------------------------------
	| User Management Routes
	|--------------------------------------------------------------------------
	|
	| All Users
	| Roles & Permissions
	| Shortcut Link to Create User
	|
	*/
	Route::prefix('user-management')->group(function () {
		Route::prefix('users')->group(function () {
			// Standard CRUD operations
			Route::get('/', [UserController::class, 'index']);  // Retrieve all users
			Route::get('/{id}', [UserController::class, 'show']);  // Retrieve a user
			Route::post('/', [UserController::class, 'store']);  // Create a new user
			Route::put('/{id}', [UserController::class, 'update']);  // Update an existing user
			Route::delete('/{id}', [UserController::class, 'destroy']);  // Delete a user
			
			// Bulk operations
			Route::post('/bulk/delete', [UserController::class, 'bulkDelete']);  // Bulk delete users
			Route::post('/bulk/restore', [UserController::class, 'bulkRestore']);  // Bulk restore users
			Route::post('/bulk/force-delete', [UserController::class, 'bulkForceDelete']);  // Bulk permanently delete users
			Route::post('/bulk/role', [UserController::class, 'bulkChangeRole']);
		});

		// Custom route for archived (trashed) users with a distinct prefix
		Route::prefix('archived/users')->group(function () {
			Route::get('/', [UserController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [UserController::class, 'restore']);
			Route::delete('/{id}', [UserController::class, 'forceDelete']);
		});

		Route::prefix('roles')->group(function () {
			// Standard CRUD operations
			Route::get('/', [RoleController::class, 'index']);  // Retrieve all roles
			Route::get('/{id}', [RoleController::class, 'show']);  // Retrieve a role
			Route::post('/', [RoleController::class, 'store']);  // Create a new role
			Route::put('/{id}', [RoleController::class, 'update']);  // Update an existing role
			Route::delete('/{id}', [RoleController::class, 'destroy']);  // Delete a role
			
			// Bulk operations
			Route::post('/bulk/delete', [RoleController::class, 'bulkDelete']);  // Bulk delete roles
			Route::post('/bulk/restore', [RoleController::class, 'bulkRestore']);  // Bulk restore roles
			Route::post('/bulk/force-delete', [RoleController::class, 'bulkForceDelete']);  // Bulk permanently delete roles
		});

		// Custom route for archived (trashed) roles with a distinct prefix
		Route::prefix('archived/roles')->group(function () {
			Route::get('/', [RoleController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [RoleController::class, 'restore']);
			Route::delete('/{id}', [RoleController::class, 'forceDelete']);
		});
	});

	/*
	|--------------------------------------------------------------------------
	| Content Management Routes
	|--------------------------------------------------------------------------
	|
	| Media Library
	| Categories
	| Tags
	|
	*/
	Route::prefix('content-management')->group(function () {

		Route::apiResource('/media-library', MediaController::class);
		Route::post('/media-library/bulk/delete', [MediaController::class, 'bulkDelete']);	

		Route::prefix('categories')->group(function () {
			// Standard CRUD operations
			Route::get('/', [CategoryController::class, 'index']);  // Retrieve all categories
			Route::get('/{id}', [CategoryController::class, 'show']);  // Retrieve a single category
			Route::post('/', [CategoryController::class, 'store']);  // Create a new category
			Route::put('/{id}', [CategoryController::class, 'update']);  // Update an existing category
			Route::delete('/{id}', [CategoryController::class, 'destroy']);  // Delete a category
			
			// Bulk operations
			Route::post('/bulk/delete', [CategoryController::class, 'bulkDelete']);  // Bulk delete categories
			Route::post('/bulk/restore', [CategoryController::class, 'bulkRestore']);  // Bulk restore categories
			Route::post('/bulk/force-delete', [CategoryController::class, 'bulkForceDelete']);  // Bulk permanently delete categories
		});

		// Additional category management routes
		Route::prefix('archived/categories')->group(function () {
			Route::get('/', [CategoryController::class, 'getTrashed']); // Retrieve soft-deleted categories
			Route::patch('/restore/{id}', [CategoryController::class, 'restore']); // Restore a soft-deleted category
			Route::delete('/{id}', [CategoryController::class, 'forceDelete']); // Permanently delete a soft-deleted category
		});

		Route::prefix('tags')->group(function () {
			// Standard CRUD operations
			Route::get('/', [TagController::class, 'index']);  // Retrieve all tags
			Route::get('/{id}', [TagController::class, 'show']);  // Retrieve a single tag
			Route::post('/', [TagController::class, 'store']);  // Create a new tag
			Route::put('/{id}', [TagController::class, 'update']);  // Update an existing tag
			Route::delete('/{id}', [TagController::class, 'destroy']);  // Delete a tag
			
			// Bulk operations
			Route::post('/bulk/delete', [TagController::class, 'bulkDelete']);  // Bulk delete tags
			Route::post('/bulk/restore', [TagController::class, 'bulkRestore']);  // Bulk restore tags
			Route::post('/bulk/force-delete', [TagController::class, 'bulkForceDelete']);  // Bulk permanently delete tags
		});

		// Custom route for archived (trashed) tags with a distinct prefix
		Route::prefix('archived/tags')->group(function () {
			Route::get('/', [TagController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [TagController::class, 'restore']);
			Route::delete('/{id}', [TagController::class, 'forceDelete']);
		});
	});

	/*
	|--------------------------------------------------------------------------
	| System Settings Routes
	|--------------------------------------------------------------------------
	|
	| Navigations
	|
	*/
	Route::prefix('system-settings')->group(function () {
		Route::prefix('navigation')->group(function () {
			// Standard CRUD operations
			Route::get('/', [NavigationController::class, 'index']);  // Retrieve all navigation
			Route::get('/{id}', [NavigationController::class, 'show']);  // Retrieve a navigation
			Route::post('/', [NavigationController::class, 'store']);  // Create a new navigation
			Route::put('/{id}', [NavigationController::class, 'update']);  // Update an existing navigation
			Route::delete('/{id}', [NavigationController::class, 'destroy']);  // Delete a navigation
			
			// Bulk operations
			Route::post('/bulk/delete', [NavigationController::class, 'bulkDelete']);  // Bulk delete navigations
			Route::post('/bulk/restore', [NavigationController::class, 'bulkRestore']);  // Bulk restore navigations
			Route::post('/bulk/force-delete', [NavigationController::class, 'bulkForceDelete']);  // Bulk permanently delete navigations
		});

		// Custom route for archived (trashed) navigations with a distinct prefix
		Route::prefix('archived/navigation')->group(function () {
			Route::get('/', [NavigationController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [NavigationController::class, 'restore']);
			Route::delete('/{id}', [NavigationController::class, 'forceDelete']);
		});
	});

	// PROFILE ROUTES
	Route::post('/profile', [UserController::class, 'updateProfile']);
	
	// SECURITY DASHBOARD ROUTES
	Route::prefix('security')->group(function () {
		Route::get('/metrics', [SecurityDashboardController::class, 'getMetrics']);
		Route::post('/scan', [SecurityDashboardController::class, 'runSecurityScan']);
		Route::get('/events', [SecurityDashboardController::class, 'getSecurityEvents']);
		Route::get('/blocked-ips', [SecurityDashboardController::class, 'getBlockedIPs']);
		Route::post('/unblock-ip', [SecurityDashboardController::class, 'unblockIP']);
		Route::get('/config', [SecurityDashboardController::class, 'getSecurityConfig']);
		Route::post('/config', [SecurityDashboardController::class, 'updateSecurityConfig']);
	});
});

Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:auth');
Route::post('/validate', [AuthController::class, 'activateUser'])->middleware('throttle:auth');
Route::post('/generate-password', [AuthController::class, 'genTempPassword'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Microsoft Graph Test Route (for testing integration)
Route::get('/test-microsoft-graph', function () {
    try {
        $result = \App\Services\MicrosoftGraphService::sendNotificationEmail(
            'test@example.com',
            'BaseCode Microsoft Graph Test',
            '<h1>Microsoft Graph Integration Test</h1><p>This is a test email from BaseCode using Microsoft Graph API.</p><p>If you receive this email, the integration is working correctly!</p>'
        );
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Test email sent successfully via Microsoft Graph' : 'Failed to send test email'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Microsoft Graph test failed'
        ], 500);
    }
});

// Two-Factor Authentication Routes
Route::prefix('2fa')->group(function () {
    Route::post('/send-code', [TwoFactorAuthController::class, 'sendCode'])->middleware('throttle:auth');
    Route::post('/verify-code', [TwoFactorAuthController::class, 'verifyCode'])->middleware('throttle:auth');
    Route::post('/is-required', [TwoFactorAuthController::class, 'isRequired'])->middleware('throttle:auth');
    
    // Protected 2FA management routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/enable', [TwoFactorAuthController::class, 'enable']);
        Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
        Route::get('/status', [TwoFactorAuthController::class, 'status']);
        Route::post('/generate-backup-codes', [TwoFactorAuthController::class, 'generateBackupCodes']);
    });
});
