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
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserActivityController;
use App\Http\Controllers\Api\ShippingLineController;
use App\Http\Controllers\Api\SoaDataOptionController;
use App\Http\Controllers\Api\HelperController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\TruckController;
use App\Http\Controllers\Api\ContainerYardController;
use App\Http\Controllers\Api\SoaAndBillingController;
use App\Http\Controllers\Api\SoaBillingCheckerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\TruckMaintenanceController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ContainerController;
use App\Http\Controllers\Api\RatePerClientController;
use App\Http\Controllers\Api\FixedExpenseController;
use App\Http\Controllers\Api\WaybillDetailController;
use App\Http\Controllers\Api\DriverCAHistoryController;
use App\Http\Controllers\Api\HelperCAHistoryController;
use App\Http\Controllers\Api\IssuedBudgetController;
use App\Http\Controllers\Api\TruckTripExpenseController;
use App\Http\Controllers\Api\PartsExpenseController;
use App\Http\Controllers\Api\FundsForStackRunController;
use App\Http\Controllers\Api\CashAdvanceController;
use App\Http\Controllers\Api\BudgetSummaryController;
use App\Http\Controllers\Api\ReportsController;

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

// Public route for general settings (site name, logos) - needed for login page
// MUST be defined BEFORE auth middleware group to avoid being caught by /{key} catch-all route
Route::get('/system-settings/settings/general', [SettingsController::class, 'getGeneralSettings']);

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/user/me', [UserController::class, 'getUser']);
	Route::post('/logout', [AuthController::class, 'logout']);

	// Dashboard Routes
	Route::prefix('dashboard')->group(function () {
		Route::get('/stats', [DashboardController::class, 'getStats']);
	});

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
		// SOA data options-related routes
		Route::get('/soa-data-options/parents', [SoaDataOptionController::class, 'getParents']);  // Retrieve all parent SOA data options
		Route::get('/soa-data-options/parents/{parentId}/children', [SoaDataOptionController::class, 'getChildren']);  // Retrieve children for a specific parent
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
			Route::post('/bulk/password', [UserController::class, 'bulkChangePassword']);  // Bulk change password

			// Import/Export operations
			Route::post('/import', [UserController::class, 'import']);  // Import users from CSV
		});

		// Custom route for archived (trashed) users with a distinct prefix
		Route::prefix('archived/users')->group(function () {
			Route::get('/', [UserController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [UserController::class, 'restore']);
			Route::delete('/{id}', [UserController::class, 'forceDelete']);
		});

		// User Activity Routes
		Route::prefix('user-activity')->group(function () {
			Route::get('/{userId}', [UserActivityController::class, 'getUserActivities']);
			Route::get('/{userId}/login-history', [UserActivityController::class, 'getLoginHistory']);
			Route::get('/{userId}/sessions', [UserActivityController::class, 'getActiveSessions']);
			Route::post('/{userId}/sessions/{tokenId}/revoke', [UserActivityController::class, 'revokeSession']);
			Route::get('/{userId}/timeline', [UserActivityController::class, 'getUserTimeline']);
			Route::get('/{userId}/statistics', [UserActivityController::class, 'getActivityStatistics']);
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
	| Shipping Line Management Routes
	|--------------------------------------------------------------------------
	|
	| Shipping Lines CRUD Operations
	|
	*/
	Route::prefix('shipping-lines')->group(function () {
		// Standard CRUD operations
		Route::get('/', [ShippingLineController::class, 'index']);  // Retrieve all shipping lines
		Route::get('/{id}', [ShippingLineController::class, 'show']);  // Retrieve a single shipping line
		Route::post('/', [ShippingLineController::class, 'store']);  // Create a new shipping line
		Route::put('/{id}', [ShippingLineController::class, 'update']);  // Update an existing shipping line
		Route::delete('/{id}', [ShippingLineController::class, 'destroy']);  // Delete a shipping line

		// Bulk operations
		Route::post('/bulk/delete', [ShippingLineController::class, 'bulkDelete']);  // Bulk delete shipping lines
		Route::post('/bulk/restore', [ShippingLineController::class, 'bulkRestore']);  // Bulk restore shipping lines
		Route::post('/bulk/force-delete', [ShippingLineController::class, 'bulkForceDelete']);  // Bulk permanently delete shipping lines
	});

	// Custom route for archived (trashed) shipping lines with a distinct prefix
	Route::prefix('archived/shipping-lines')->group(function () {
		Route::get('/', [ShippingLineController::class, 'getTrashed']); // Retrieve soft-deleted shipping lines
		Route::patch('/restore/{id}', [ShippingLineController::class, 'restore']); // Restore a soft-deleted shipping line
		Route::delete('/{id}', [ShippingLineController::class, 'forceDelete']); // Permanently delete a soft-deleted shipping line
	});

	/*
	|--------------------------------------------------------------------------
	| SOA Data Option Management Routes
	|--------------------------------------------------------------------------
	|
	| SOA Data Options CRUD Operations
	|
	*/
	Route::prefix('soa-data-options')->group(function () {
		// Standard CRUD operations
		Route::get('/', [SoaDataOptionController::class, 'index']);  // Retrieve all SOA data options
		Route::get('/{id}', [SoaDataOptionController::class, 'show']);  // Retrieve a single SOA data option
		Route::post('/', [SoaDataOptionController::class, 'store']);  // Create a new SOA data option
		Route::put('/{id}', [SoaDataOptionController::class, 'update']);  // Update an existing SOA data option
		Route::delete('/{id}', [SoaDataOptionController::class, 'destroy']);  // Delete a SOA data option

		// Bulk operations
		Route::post('/bulk/delete', [SoaDataOptionController::class, 'bulkDelete']);  // Bulk delete SOA data options
		Route::post('/bulk/restore', [SoaDataOptionController::class, 'bulkRestore']);  // Bulk restore SOA data options
		Route::post('/bulk/force-delete', [SoaDataOptionController::class, 'bulkForceDelete']);  // Bulk permanently delete SOA data options
	});

	// Custom route for archived (trashed) SOA data options with a distinct prefix
	Route::prefix('archived/soa-data-options')->group(function () {
		Route::get('/', [SoaDataOptionController::class, 'getTrashed']); // Retrieve soft-deleted SOA data options
		Route::patch('/restore/{id}', [SoaDataOptionController::class, 'restore']); // Restore a soft-deleted SOA data option
		Route::delete('/{id}', [SoaDataOptionController::class, 'forceDelete']); // Permanently delete a soft-deleted SOA data option
	});

	/*
	|--------------------------------------------------------------------------
	| Statement of Accounts Management Routes
	|--------------------------------------------------------------------------
	|
	| Statement of Accounts CRUD Operations
	|
	*/
	Route::prefix('soa')->group(function () {
		// Generate SOA
		Route::post('/generate', [SoaAndBillingController::class, 'generate']);  // Generate a new statement of account

		// Download PDFs (must be before /{id} route)
		Route::get('/{id}/download', [SoaAndBillingController::class, 'download']);  // Download SOA PDF only
		Route::get('/{id}/download-billing-and-soa', [SoaAndBillingController::class, 'downloadBillingAndSoa']);  // Download 2-page PDF (Billing then SOA), {id} = SOA ID
		Route::get('/{id}/invoice/download', [InvoiceController::class, 'downloadBySoaId']);  // Download Invoice PDF for this SOA, {id} = SOA ID

		// Send email with PDF attachment
		Route::post('/{id}/send-email', [SoaAndBillingController::class, 'sendSoaEmail']);  // Send SOA PDF via email
		Route::post('/{id}/send-billing-and-soa-email', [SoaAndBillingController::class, 'sendBillingAndSoaEmail']);  // Send combined Billing + SOA PDF via email, {id} = SOA ID

		// Line items by booking ID(s) (transaction table data, same as SOA PDF)
		Route::get('/line-items', [SoaAndBillingController::class, 'lineItems']);  // Get line items by booking_ids[] and shipping_line_id (query)

		// Standard CRUD operations
		Route::get('/', [SoaAndBillingController::class, 'index']);  // Retrieve all statement of accounts
		Route::get('/{id}', [SoaAndBillingController::class, 'show']);  // Retrieve a single statement of account
		Route::put('/{id}', [SoaAndBillingController::class, 'update']);  // Update a statement of account
	});

	/*
	|--------------------------------------------------------------------------
	| Billing Statement Management Routes
	|--------------------------------------------------------------------------
	|
	| Billing Statement CRUD Operations
	|
	*/
	Route::prefix('billing-statements')->group(function () {
		// Generate Billing Statement
		Route::post('/generate', [SoaAndBillingController::class, 'billingStatementsGenerate']);  // Generate a new billing statement

		// Download PDF (must be before /{id} route)
		Route::get('/{id}/download', [SoaAndBillingController::class, 'billingStatementsDownload']);  // Download Billing Statement PDF

		// Send email with PDF attachment
		Route::post('/{id}/send-email', [SoaAndBillingController::class, 'sendBillingStatementEmail']);  // Send Billing Statement PDF via email

		// Standard CRUD operations
		Route::get('/', [SoaAndBillingController::class, 'billingStatementsIndex']);  // Retrieve all billing statements
		Route::get('/{id}', [SoaAndBillingController::class, 'billingStatementsShow']);  // Retrieve a single billing statement
		Route::put('/{id}', [SoaAndBillingController::class, 'billingStatementsUpdate']);  // Update a billing statement
	});

	/*
	|--------------------------------------------------------------------------
	| Combined SOA + Billing (generate both in one request; download 2-page PDF)
	|--------------------------------------------------------------------------
	*/
	Route::prefix('soa-and-billing')->group(function () {
		Route::post('/generate', [SoaAndBillingController::class, 'generateSoaAndBilling']);  // Generate SOA + Billing in one request
		Route::post('/attachments', [SoaAndBillingController::class, 'storeAttachments']);  // Upload temp images for PDF attachments (returns token)
	});

	/*
	|--------------------------------------------------------------------------
	| SOA / Billing / Invoice Booking Validator (check valid vs invalid bookings by type)
	|--------------------------------------------------------------------------
	*/
	Route::prefix('soa-billing-check')->group(function () {
		Route::post('/validate', [SoaBillingCheckerController::class, 'validateBookings']);  // Validate bookings for type 1=SOA, 2=Billing, 3=Invoice
	});

	/*
	|--------------------------------------------------------------------------
	| Invoice Management Routes
	|--------------------------------------------------------------------------
	|
	| Invoice CRUD Operations
	|
	*/
	Route::prefix('invoices')->group(function () {
		// Generate Invoice
		Route::post('/generate', [InvoiceController::class, 'generate']);  // Generate a new invoice

		// Download PDF (must be before /{id} route)
		Route::get('/{id}/download', [InvoiceController::class, 'download']);  // Download Invoice PDF

		// Send email with PDF attachment
		Route::post('/{id}/send-email', [InvoiceController::class, 'sendEmail']);  // Send Invoice PDF via email

		// Upload temp attachments for PDF
		Route::post('/attachments', [InvoiceController::class, 'storeAttachments']);  // Upload temp images for PDF attachments

		// Standard CRUD operations
		Route::get('/', [InvoiceController::class, 'index']);  // Retrieve all invoices
		Route::get('/{id}', [InvoiceController::class, 'show']);  // Retrieve a single invoice
		Route::put('/{id}', [InvoiceController::class, 'update']);  // Update an invoice
	});

	/*
	|--------------------------------------------------------------------------
	| Helper Management Routes
	|--------------------------------------------------------------------------
	|
	| Helpers CRUD Operations
	|
	*/
	Route::prefix('helpers')->group(function () {
		// Standard CRUD operations
		Route::get('/helpers-paginated', [HelperController::class, 'getHelperListPaginated']);
		Route::get('/active-list', [HelperController::class, 'getActiveHelperList']);
		Route::get('/', [HelperController::class, 'index']);  // Retrieve all helpers
		Route::get('/{id}', [HelperController::class, 'show']);  // Retrieve a single helper
		Route::post('/', [HelperController::class, 'store']);  // Create a new helper
		Route::put('/{id}', [HelperController::class, 'update']);  // Update an existing helper
		Route::delete('/{id}', [HelperController::class, 'destroy']);  // Delete a helper
		Route::patch('/deactivate/{id}', [HelperController::class, 'deactivate']);  // Deactivate a helper
		Route::patch('/activate/{id}', [HelperController::class, 'activate']);  // Activate a helper
		Route::get('/details/{id}', [HelperController::class, 'getHelperDetails']);  // Retrieve a single helper



		// Bulk operations
		Route::post('/bulk/delete', [HelperController::class, 'bulkDelete']);  // Bulk delete helpers
		Route::post('/bulk/restore', [HelperController::class, 'bulkRestore']);  // Bulk restore helpers
		Route::post('/bulk/force-delete', [HelperController::class, 'bulkForceDelete']);  // Bulk permanently delete helpers

		//For waybill
		Route::get('/get-waybill/{id}', [HelperController::class, 'getWaybillByHelperId']);

		//For Drive CA History
		Route::get('/get-cash-advance/{id}', [HelperCAHistoryController::class, 'getHelperCashAdvances']);
	});

	// Custom route for archived (trashed) helpers with a distinct prefix
	Route::prefix('archived/helpers')->group(function () {
		Route::get('/', [HelperController::class, 'getTrashed']); // Retrieve soft-deleted helpers
		Route::patch('/restore/{id}', [HelperController::class, 'restore']); // Restore a soft-deleted helper
		Route::delete('/{id}', [HelperController::class, 'forceDelete']); // Permanently delete a soft-deleted helper
	});

	/*
	|--------------------------------------------------------------------------
	| Stack Run Management Routes
	|--------------------------------------------------------------------------
	|
	| Stack Runs CRUD Operations
	|
	*/
	Route::prefix('bookings')->group(function () {
		// Standard CRUD operations
		Route::get('/', [BookingController::class, 'index']);  // Retrieve all bookings
		Route::get('/by-shipping-line/{shipping_line_id}', [BookingController::class, 'byShippingLine']);  // Bookings by shipping line + optional expected_date range
		Route::post('/', [BookingController::class, 'store']);  // Create a new booking
		Route::get('/{id}/remaining-container', [BookingController::class, 'remainingContainer']);  // Remaining container breakdown for a booking
		Route::get('/{id}', [BookingController::class, 'show']);  // Retrieve a single booking
		Route::put('/{id}', [BookingController::class, 'update']);  // Update a booking
		Route::delete('/{id}', [BookingController::class, 'destroy']);  // Soft delete a booking

		// Bulk operations
		Route::post('/bulk/delete', [BookingController::class, 'bulkDelete']);  // Bulk delete bookings
		Route::post('/bulk/restore', [BookingController::class, 'bulkRestore']);  // Bulk restore bookings
		Route::post('/bulk/force-delete', [BookingController::class, 'bulkForceDelete']);  // Bulk permanently delete bookings
	});

	/*
	|--------------------------------------------------------------------------
	| Archived Bookings Routes
	|--------------------------------------------------------------------------
	|
	| Trash/Archive Management for Bookings
	|
	*/
	Route::prefix('archived/bookings')->group(function () {
		Route::get('/', [BookingController::class, 'getTrashed']);  // Get trashed bookings
		Route::patch('/restore/{id}', [BookingController::class, 'restore']);  // Restore a trashed booking
		Route::delete('/{id}', [BookingController::class, 'forceDelete']);  // Permanently delete a booking
	});

	/*
	|--------------------------------------------------------------------------
	| Container Management Routes
	|--------------------------------------------------------------------------
	|
	| Container CRUD Operations
	|
	*/
	Route::prefix('containers')->group(function () {
		Route::get('/', [ContainerController::class, 'getContainers']);  // Get containers by booking_id and optionally waybill_number
		Route::get('/{id}', [ContainerController::class, 'show']);  // Get a specific container by ID
	});

	Route::prefix('bookings')->group(function () {
		// Container management routes
		Route::post('/{bookingId}/containers', [ContainerController::class, 'addContainer']);  // Add a container to a booking
		Route::put('/{bookingId}/containers/{containerId}', [ContainerController::class, 'updateContainer']);  // Update a container
		Route::delete('/{bookingId}/containers/{containerId}', [ContainerController::class, 'deleteContainer']);  // Delete a container
	});

	/*
	|--------------------------------------------------------------------------
	| Rate Per Client Management Routes
	|--------------------------------------------------------------------------
	|
	| Rate Per Clients CRUD Operations
	|
	*/
	Route::prefix('rate-per-clients')->group(function () {
		// Standard CRUD operations
		Route::get('/', [RatePerClientController::class, 'index']);  // Retrieve all rate per clients
		Route::get('/{id}', [RatePerClientController::class, 'show']);  // Retrieve a single rate per client
		Route::post('/', [RatePerClientController::class, 'store']);  // Create a new rate per client
		Route::put('/{id}', [RatePerClientController::class, 'update']);  // Update an existing rate per client
		Route::delete('/{id}', [RatePerClientController::class, 'destroy']);  // Delete a rate per client

		// Bulk operations
		Route::post('/bulk/delete', [RatePerClientController::class, 'bulkDelete']);  // Bulk delete rate per clients
		Route::post('/bulk/restore', [RatePerClientController::class, 'bulkRestore']);  // Bulk restore rate per clients
		Route::post('/bulk/force-delete', [RatePerClientController::class, 'bulkForceDelete']);  // Bulk permanently delete rate per clients
	});

	// Custom route for archived (trashed) rate per clients with a distinct prefix
	Route::prefix('archived/rate-per-clients')->group(function () {
		Route::get('/', [RatePerClientController::class, 'getTrashed']); // Retrieve soft-deleted rate per clients
		Route::patch('/restore/{id}', [RatePerClientController::class, 'restore']); // Restore a soft-deleted rate per client
		Route::delete('/{id}', [RatePerClientController::class, 'forceDelete']); // Permanently delete a soft-deleted rate per client
	});

	/*
	|--------------------------------------------------------------------------
	| Fixed Expense Management Routes
	|--------------------------------------------------------------------------
	|
	| Fixed Expenses CRUD Operations
	|
	*/
	Route::prefix('fixed-expenses')->group(function () {
		// Standard CRUD operations
		Route::get('/', [FixedExpenseController::class, 'index']);  // Retrieve all fixed expenses
		Route::get('/{id}', [FixedExpenseController::class, 'show']);  // Retrieve a single fixed expense
		Route::post('/', [FixedExpenseController::class, 'store']);  // Create a new fixed expense
		Route::put('/{id}', [FixedExpenseController::class, 'update']);  // Update an existing fixed expense
		Route::delete('/{id}', [FixedExpenseController::class, 'destroy']);  // Delete a fixed expense

		// Bulk operations
		Route::post('/bulk/delete', [FixedExpenseController::class, 'bulkDelete']);  // Bulk delete fixed expenses
		Route::post('/bulk/restore', [FixedExpenseController::class, 'bulkRestore']);  // Bulk restore fixed expenses
		Route::post('/bulk/force-delete', [FixedExpenseController::class, 'bulkForceDelete']);  // Bulk permanently delete fixed expenses
	});

	// Custom route for archived (trashed) fixed expenses with a distinct prefix
	Route::prefix('archived/fixed-expenses')->group(function () {
		Route::get('/', [FixedExpenseController::class, 'getTrashed']); // Retrieve soft-deleted fixed expenses
		Route::patch('/restore/{id}', [FixedExpenseController::class, 'restore']); // Restore a soft-deleted fixed expense
		Route::delete('/{id}', [FixedExpenseController::class, 'forceDelete']); // Permanently delete a soft-deleted fixed expense
	});

	/*
	|--------------------------------------------------------------------------
	| Waybill Detail Management Routes
	|--------------------------------------------------------------------------
	|
	| Waybill Details CRUD Operations
	|
	*/
	Route::prefix('waybill-details')->group(function () {
		// Standard CRUD operations
		Route::get('/', [WaybillDetailController::class, 'index']);  // Retrieve all waybill details
		Route::get('/{id}', [WaybillDetailController::class, 'show']);  // Retrieve a single waybill detail
		Route::post('/', [WaybillDetailController::class, 'store']);  // Create a new waybill detail
		Route::put('/{id}', [WaybillDetailController::class, 'update']);  // Update an existing waybill detail
		Route::delete('/{id}', [WaybillDetailController::class, 'destroy']);  // Delete a waybill detail

		// Bulk operations
		Route::post('/bulk/delete', [WaybillDetailController::class, 'bulkDelete']);  // Bulk delete waybill details
		Route::post('/bulk/restore', [WaybillDetailController::class, 'bulkRestore']);  // Bulk restore waybill details
		Route::post('/bulk/force-delete', [WaybillDetailController::class, 'bulkForceDelete']);  // Bulk permanently delete waybill details
	});

	// Custom route for archived (trashed) waybill details with a distinct prefix
	Route::prefix('archived/waybill-details')->group(function () {
		Route::get('/', [WaybillDetailController::class, 'getTrashed']); // Retrieve soft-deleted waybill details
		Route::patch('/restore/{id}', [WaybillDetailController::class, 'restore']); // Restore a soft-deleted waybill detail
		Route::delete('/{id}', [WaybillDetailController::class, 'forceDelete']); // Permanently delete a soft-deleted waybill detail
	});

	/*
	|--------------------------------------------------------------------------
	| Driver Management Routes
	|--------------------------------------------------------------------------
	|
	| Drivers CRUD Operations
	|
	*/
	Route::prefix('drivers')->group(function () {
		// Standard CRUD operations
		Route::get('/', [DriverController::class, 'index']);  // Retrieve all drivers
		Route::get('/{id}', [DriverController::class, 'show']);  // Retrieve a single driver
		Route::post('/', [DriverController::class, 'store']);  // Create a new driver
		Route::put('/{id}', [DriverController::class, 'update']);  // Update an existing driver
		Route::delete('/{id}', [DriverController::class, 'destroy']);  // Delete a driver
		Route::patch('/deactivate/{id}', [DriverController::class, 'deactivate']);  // Deactivate a driver
		Route::patch('/activate/{id}', [DriverController::class, 'activate']);  // Activate a driver

		// Bulk operations
		Route::post('/bulk/delete', [DriverController::class, 'bulkDelete']);  // Bulk delete drivers
		Route::post('/bulk/restore', [DriverController::class, 'bulkRestore']);  // Bulk restore drivers
		Route::post('/bulk/force-delete', [DriverController::class, 'bulkForceDelete']);  // Bulk permanently delete drivers

		//For waybill
		Route::get('/get-waybill/{id}', [DriverController::class, 'getWaybillByDriverId']);

		//For Drive CA History
		Route::get('/get-cash-advance/{id}', [DriverCAHistoryController::class, 'getCashAdvances']);
	});

	// Custom route for archived (trashed) drivers with a distinct prefix
	Route::prefix('archived/drivers')->group(function () {
		Route::get('/', [DriverController::class, 'getTrashed']); // Retrieve soft-deleted drivers
		Route::patch('/restore/{id}', [DriverController::class, 'restore']); // Restore a soft-deleted driver
		Route::delete('/{id}', [DriverController::class, 'forceDelete']); // Permanently delete a soft-deleted driver
	});

	/*
	|--------------------------------------------------------------------------
	| Budget Management Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('budget')->group(function () {
		Route::get('/summary', [BudgetSummaryController::class, 'index']);
		Route::get('/issued-budget', [IssuedBudgetController::class, 'index']);
		Route::get('/issued-budget/{id}', [IssuedBudgetController::class, 'show']);
		Route::post('/issued-budget', [IssuedBudgetController::class, 'store']);
		Route::patch('/issued-budget/{id}', [IssuedBudgetController::class, 'update']);
		Route::delete('/issued-budget/{id}', [IssuedBudgetController::class, 'destroy']);

		Route::get('/truck-trip-expense', [TruckTripExpenseController::class, 'index']);
		Route::get('/truck-trip-expense/{id}', [TruckTripExpenseController::class, 'show']);
		Route::post('/truck-trip-expense', [TruckTripExpenseController::class, 'store']);
		Route::patch('/truck-trip-expense/{id}', [TruckTripExpenseController::class, 'update']);
		Route::delete('/truck-trip-expense/{id}', [TruckTripExpenseController::class, 'destroy']);

		Route::get('/parts-expense', [PartsExpenseController::class, 'index']);
		Route::get('/parts-expense/{id}', [PartsExpenseController::class, 'show']);
		Route::post('/parts-expense', [PartsExpenseController::class, 'store']);
		Route::patch('/parts-expense/{id}', [PartsExpenseController::class, 'update']);
		Route::delete('/parts-expense/{id}', [PartsExpenseController::class, 'destroy']);

		Route::get('/funds-for-stack-run', [FundsForStackRunController::class, 'index']);
		Route::get('/funds-for-stack-run/{id}', [FundsForStackRunController::class, 'show']);
		Route::post('/funds-for-stack-run', [FundsForStackRunController::class, 'store']);
		Route::patch('/funds-for-stack-run/{id}', [FundsForStackRunController::class, 'update']);
		Route::delete('/funds-for-stack-run/{id}', [FundsForStackRunController::class, 'destroy']);
	});

	/*
	|--------------------------------------------------------------------------
	| Cash Advance API (Unified: type 1=driver, 2=helper)
	| GET: type 0 or null = both tables; POST/PATCH/DELETE: type required
	|--------------------------------------------------------------------------
	*/
	Route::prefix('cash-advances')->group(function () {
		Route::get('/', [CashAdvanceController::class, 'index']);
		Route::get('/{id}', [CashAdvanceController::class, 'show']);
		Route::post('/', [CashAdvanceController::class, 'store']);
		Route::patch('/{id}', [CashAdvanceController::class, 'update']);
		Route::delete('/{id}', [CashAdvanceController::class, 'destroy']);
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

		// Settings Management Routes (moved under system-settings)
		Route::prefix('settings')->group(function () {
			Route::get('/', [SettingsController::class, 'index']);
			Route::post('/', [SettingsController::class, 'update']);
			Route::post('/initialize', [SettingsController::class, 'initialize']);

			// General Settings (excludes security and 2FA) - Must be before /{key} route
			// Note: GET /general is public (defined outside auth middleware) for login/2FA pages
			// POST requires authentication (inside auth middleware)
			Route::post('/general', [SettingsController::class, 'updateGeneralSettings']);

			// Email Settings - Must be before /{key} route
			Route::get('/email', [SettingsController::class, 'getEmailSettings']);
			Route::post('/email', [SettingsController::class, 'updateEmailSettings']);

			// Security Settings (2FA and Session) - Must be before /{key} route
			Route::get('/security', [SettingsController::class, 'getSecuritySettings']);
			Route::post('/security', [SettingsController::class, 'updateSecuritySettings']);

			// Individual option routes (must be after specific routes)
			Route::get('/{key}', [SettingsController::class, 'show']);
			Route::put('/{key}', [SettingsController::class, 'updateOption']);

			// 2FA Settings
			Route::prefix('two-factor')->group(function () {
				Route::get('/status', [SettingsController::class, 'getTwoFactorStatus']);
				Route::post('/enable', [SettingsController::class, 'enableTwoFactor']);
				Route::post('/disable', [SettingsController::class, 'disableTwoFactor']);
				Route::post('/backup-codes', [SettingsController::class, 'generateBackupCodes']);
			});
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

	/*
	|--------------------------------------------------------------------------
	| Backup and Restore Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('backups')->group(function () {
		// Options (most specific routes first)
		Route::get('/options/tables', [\App\Http\Controllers\Api\BackupController::class, 'getTables']);
		Route::get('/options/disks', [\App\Http\Controllers\Api\BackupController::class, 'getDisks']);

		// Schedule Management (before /{id} routes)
		Route::get('/schedules', [\App\Http\Controllers\Api\BackupController::class, 'schedules']);
		Route::post('/schedules', [\App\Http\Controllers\Api\BackupController::class, 'createSchedule']);
		Route::get('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'getSchedule']);
		Route::put('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'updateSchedule']);
		Route::delete('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'deleteSchedule']);
		Route::post('/schedules/{id}/run', [\App\Http\Controllers\Api\BackupController::class, 'runSchedule']);

		// Backup Management
		Route::get('/', [\App\Http\Controllers\Api\BackupController::class, 'index']);
		Route::post('/', [\App\Http\Controllers\Api\BackupController::class, 'store']);
		Route::get('/stats', [\App\Http\Controllers\Api\BackupController::class, 'stats']);
		Route::get('/{id}', [\App\Http\Controllers\Api\BackupController::class, 'show']);
		Route::delete('/{id}', [\App\Http\Controllers\Api\BackupController::class, 'destroy']);
		Route::get('/{id}/download', [\App\Http\Controllers\Api\BackupController::class, 'download']);
		Route::post('/{id}/restore', [\App\Http\Controllers\Api\BackupController::class, 'restoreBackup']);
		Route::get('/{id}/validate', [\App\Http\Controllers\Api\BackupController::class, 'validateBackup']);
	});

	/*
	|--------------------------------------------------------------------------
	| Fleet Truck Management Routes
	|--------------------------------------------------------------------------
	|
	| Fleet Truck CRUD Operations
	|
	*/
	Route::prefix('trucks')->group(function () {
		// Standard CRUD operations
		Route::get('/truck-list', [TruckController::class, 'index']);  // Retrieve all truck list
		Route::get('/get-truck-by-id/{id}', [TruckController::class, 'show']);  // Get a specific truck
		Route::post('/add-truck', [TruckController::class, 'store']);  // Create a new truck
		Route::put('/update-truck/{id}', [TruckController::class, 'update']);  // Update an existing truck details
		Route::patch('/deactivate-truck/{id}', [TruckController::class, 'deactivate']);  // Deactivate a truck
		Route::patch('/activate-truck/{id}', [TruckController::class, 'restore']);  // Activate a truck
		Route::delete('/truck-list/{id}', [TruckController::class, 'destroy']);
		Route::get('/active-list', [TruckController::class, 'getActiveTruckList']);

		// Bulk operations
		Route::post('/bulk/delete', [TruckController::class, 'bulkDelete']);  // Bulk delete shipping lines
		Route::post('/bulk/restore', [TruckController::class, 'bulkRestore']);  // Bulk restore shipping lines
		Route::post('/bulk/force-delete', [TruckController::class, 'bulkForceDelete']);  // Bulk permanently delete shipping lines

		// Route for maintenance history
		Route::post('/truck-maintenance', [TruckMaintenanceController::class, 'store']);
		Route::get('/truck-maintenance/{id}', [TruckMaintenanceController::class, 'show']);
		Route::get('{truckId}/maintenance-history', [TruckMaintenanceController::class, 'getMaintenanceHistory']);
		Route::delete('{truckId}/maintenance-history/{id}', [TruckMaintenanceController::class, 'destroy']);
		Route::put('/truck-maintenance/{id}', [TruckMaintenanceController::class, 'update']);


	});

	// // Custom route for archived (trashed) shipping lines with a distinct prefix
	// Route::prefix('archived/trucks')->group(function () {
	// 	Route::get('/', [TruckController::class, 'getTrashed']); // Retrieve soft-deleted shipping lines
	// 	Route::patch('/restore/{id}', [TruckController::class, 'restore']); // Restore a soft-deleted shipping line
	// 	Route::delete('/{id}', [TruckController::class, 'forceDelete']); // Permanently delete a soft-deleted shipping line
	// });

	/*
	|--------------------------------------------------------------------------
	| Container Yard Management Routes
	|--------------------------------------------------------------------------
	|
	| Container Yard CRUD Operations
	|
	*/
	Route::prefix('container-yards')->group(function () {
		// Standard CRUD operations
		Route::get('/yard-list', [ContainerYardController::class, 'index']);  // Retrieve all container yard list
		Route::get('get-yard-by-id/{id}', [ContainerYardController::class, 'show']);  // Get a specific container yard
		Route::post('/add-yard', [ContainerYardController::class, 'store']);  // Create a new container yard
		Route::put('/update-yard/{id}', [ContainerYardController::class, 'update']);  // Update an existing container yard details
		Route::patch('/deactivate-yard/{id}', [ContainerYardController::class, 'destroy']);  // Deactivate a container yard
		Route::patch('/activate-yard/{id}', [ContainerYardController::class, 'restore']);  // Activate a container yard
		Route::get('/search', [ContainerYardController::class, 'search'])->name('container-yards.search');
		// // Bulk operations
		// Route::post('/bulk/delete', [TruckController::class, 'bulkDelete']);  // Bulk delete shipping lines
		// Route::post('/bulk/restore', [TruckController::class, 'bulkRestore']);  // Bulk restore shipping lines
		// Route::post('/bulk/force-delete', [TruckController::class, 'bulkForceDelete']);  // Bulk permanently delete shipping lines
	});

	// // Custom route for archived (trashed) shipping lines with a distinct prefix
	// Route::prefix('archived/trucks')->group(function () {
	// 	Route::get('/', [TruckController::class, 'getTrashed']); // Retrieve soft-deleted shipping lines
	// 	Route::patch('/restore/{id}', [TruckController::class, 'restore']); // Restore a soft-deleted shipping line
	// 	Route::delete('/{id}', [TruckController::class, 'forceDelete']); // Permanently delete a soft-deleted shipping line
	// });

		/*
	|--------------------------------------------------------------------------
	| Report Management Routes
	|--------------------------------------------------------------------------
	|
	| Report Operations
	|
	*/
	Route::prefix('reports')->group(function () {
		// Standard CRUD operations
		Route::get('/summary', [ReportsController::class, 'index']);  // Retrieve all reports data
		Route::get('/issued-budget', [ReportsController::class, 'getIssuedBudget']); 
		Route::get('/truck-trip-expense', [ReportsController::class, 'getTruckExpense']);  
		Route::get('/parts-expense', [ReportsController::class, 'getPartsExpense']);  
		Route::get('/cash-advances', [ReportsController::class, 'getReportCashAdvances']);  

	});

});

// Webhook endpoint (no auth required, but token protected)
Route::post('/backups/webhook/trigger', [\App\Http\Controllers\Api\BackupController::class, 'webhookTrigger']);

Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:auth');
Route::post('/validate', [AuthController::class, 'activateUser'])->middleware('throttle:auth');
// Legacy alias kept for backward compatibility
Route::post('/generate-password', [AuthController::class, 'genTempPassword'])->middleware('throttle:auth');
// New explicit forgot-password endpoint used by the frontend
Route::post('/auth/forgot-password', [AuthController::class, 'genTempPassword'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/enable-2fa-setup', [AuthController::class, 'enable2FASetup'])->middleware('throttle:login');

// Microsoft Graph Test Route (for testing integration)
Route::get('/test-microsoft-graph', function () {
	try {
		$result = \App\Services\MicrosoftGraphService::sendNotificationEmail(
			'test@example.com',
			'Deltrans Microsoft Graph Test',
			'<h1>Microsoft Graph Integration Test</h1><p>This is a test email from Deltrans using Microsoft Graph API.</p><p>If you receive this email, the integration is working correctly!</p>'
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

// Test SMTP Email Configuration Route
Route::post('/test-smtp-email', function (\Illuminate\Http\Request $request) {
	try {
		$email = $request->input('email', 'test@example.com');

		// Get current mail configuration
		$mailConfig = [
			'default' => config('mail.default'),
			'from' => config('mail.from'),
			'smtp_host' => config('mail.mailers.smtp.host'),
			'smtp_port' => config('mail.mailers.smtp.port'),
			'smtp_encryption' => config('mail.mailers.smtp.encryption'),
			'smtp_username' => config('mail.mailers.smtp.username') ? '***configured***' : 'not set',
		];

		// Try to send a test email
		\Illuminate\Support\Facades\Mail::raw('This is a test email from Deltrans SMTP configuration. If you receive this, your SMTP settings are working correctly!', function ($message) use ($email) {
			$message->to($email)
				->subject('Deltrans SMTP Test Email');
		});

		return response()->json([
			'success' => true,
			'message' => 'Test email sent successfully to ' . $email,
			'mail_config' => $mailConfig,
		]);
	} catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
		return response()->json([
			'success' => false,
			'message' => 'Failed to send test email via SMTP',
			'error' => $e->getMessage(),
			'mail_config' => $mailConfig ?? [],
		], 500);
	} catch (\Exception $e) {
		return response()->json([
			'success' => false,
			'message' => 'Unexpected error',
			'error' => $e->getMessage(),
			'mail_config' => $mailConfig ?? [],
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

// (merged into system-settings/settings above)
