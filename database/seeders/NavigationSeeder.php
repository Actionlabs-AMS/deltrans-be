<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Navigation;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing navigations (use delete instead of truncate to avoid foreign key issues)
        Navigation::query()->delete();

        // Define navigation structure with parent-child relationships
        $navigationStructure = [
            // Dashboard (standalone)
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'home',
                'description' => 'Main dashboard with overview of system metrics and quick access to key features',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],

            // Profile Section
            [
                'name' => 'Profile',
                'slug' => 'profile',
                'icon' => 'user',
                'description' => 'Manage user profile',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => false,
                'children' => [],
            ],

            // Shipping Lines Section
            [
                'name' => 'Shipping Lines',
                'slug' => 'shipping-lines',
                'icon' => 'ship',
                'description' => 'Manage shipping lines and their details',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Financial Statements',
                        'slug' => 'financial-statements/:shippingLineId',
                        'icon' => 'ship',
                        'description' => 'View and manage financial statements of a specific shipping line',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Statement of Account',
                        'slug' => 'financial-statements/:shippingLineId/soa',
                        'icon' => 'ship',
                        'description' => 'Manage statement of account',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Billing Statement',
                        'slug' => 'financial-statements/:shippingLineId/billing',
                        'icon' => 'ship',
                        'description' => 'Manage billing statement',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Invoice',
                        'slug' => 'financial-statements/:shippingLineId/invoice',
                        'icon' => 'ship',
                        'description' => 'Manage invoice',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Download SOA',
                        'slug' => 'financial-statements/:shippingLineId/soa/:soaId/download',
                        'icon' => 'ship',
                        'description' => 'Download statement of account',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Email SOA to Client',
                        'slug' => 'financial-statements/:shippingLineId/soa/:soaId/email',
                        'icon' => 'ship',
                        'description' => 'Send statement of account to client',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Download Invoice',
                        'slug' => 'financial-statements/:shippingLineId/invoice/:invoiceId/download',
                        'icon' => 'ship',
                        'description' => 'Download invoice',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Email Invoice to Client',
                        'slug' => 'financial-statements/:shippingLineId/invoice/:invoiceId/email',
                        'icon' => 'ship',
                        'description' => 'Send invoice to client',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Download Billing Statement',
                        'slug' => 'financial-statements/:shippingLineId/billing/:billingId/download',
                        'icon' => 'ship',
                        'description' => 'Download billing statement',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Download Billing Statement and SOA',
                        'slug' => 'financial-statements/:shippingLineId/billing/:billingId/download-soa-and-billing',
                        'icon' => 'ship',
                        'description' => 'Download billing statement and SOA',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Email Billing Statement to Client',
                        'slug' => 'financial-statements/:shippingLineId/billing/:billingId/email',
                        'icon' => 'ship',
                        'description' => 'Send billing statement to client',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Email Billing Statement and SOA to Client',
                        'slug' => 'financial-statements/:shippingLineId/billing/:billingId/email-soa-and-billing',
                        'icon' => 'ship',
                        'description' => 'Send billing statement and SOA to client',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                ],
            ],

            // Container Yard Section
            [
                'name' => 'Container Yards',
                'slug' => 'container-yards',
                'icon' => 'map-pinned',
                'description' => 'List of container yards and their details',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],

            // Booking Management Section
            [
                'name' => 'Booking Management',
                'slug' => 'bookings',
                'icon' => 'booking',
                'description' => 'Manage bookings, containers and waybills',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Waybills',
                        'slug' => 'waybills/:bookingId',
                        'icon' => 'booking',
                        'description' => 'View and manage waybills for a specific booking',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                ],
            ],

            // Fleet Management
            [
                'name' => 'Fleet Management',
                'slug' => 'fleet-management',
                'icon' => 'truck',
                'description' => 'Manage truck, drivers and helpers',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Trucks',
                        'slug' => 'trucks',
                        'icon' => 'truck',
                        'description' => 'View and manage all registered trucks.',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Maintenance History',
                        'slug' => 'maintenance-history/:id',
                        'icon' => 'activity',
                        'description' => 'Review the maintenance history of a specific fleet truck',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Drivers',
                        'slug' => 'drivers',
                        'icon' => 'user-round',
                        'description' => 'View and manage all registered drivers.',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Waybill History',
                        'slug' => 'waybill-history/:id',
                        'icon' => 'activity',
                        'description' => 'Review the waybill history of a specific driver',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Cash Advance History',
                        'slug' => 'ca-history/:id',
                        'icon' => 'activity',
                        'description' => 'Review the cash advance history of a specific driver',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Helpers',
                        'slug' => 'helpers',
                        'icon' => 'users-round',
                        'description' => 'View and manage all registered helpers.',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Helpers Waybill History',
                        'slug' => 'helpers-waybill-history/:id',
                        'icon' => 'activity',
                        'description' => 'Review the waybill history of a specific helper',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Helpers Cash Advance History',
                        'slug' => 'helpers-ca-history/:id',
                        'icon' => 'activity',
                        'description' => 'Review the cash advance history of a specific helper',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                ],
            ],

            // Rates and Expenses Section
            [
                'name' => 'Rates and Expenses',
                'slug' => 'rates-and-expenses',
                'icon' => 'banknote',
                'description' => 'Manage fixed expenses and rate per client',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Fixed Expenses',
                        'slug' => 'fixed-expenses',
                        'icon' => 'calculator',
                        'description' => 'Manage fixed expenses related to shipping lines and container yards',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Rate per Client',
                        'slug' => 'rate-per-client',
                        'icon' => 'circle-dollar-sign',
                        'description' => 'Manage rate per client',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],

            // Budget Management Section
            [
                'name' => 'Budget Management',
                'slug' => 'budget-management',
                'icon' => 'wallet',
                'description' => 'Manage increase or decrease of budget',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],

            // User Management Section
            [
                'name' => 'User Management',
                'slug' => 'user-management',
                'icon' => 'users',
                'description' => 'Manage users, roles, permissions, and user activity tracking',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'All Users',
                        'slug' => 'users',
                        'icon' => 'user-group',
                        'description' => 'View and manage all registered users, their profiles and account status',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Roles & Permissions',
                        'slug' => 'roles',
                        'icon' => 'shield-check',
                        'description' => 'Manage user roles and permissions to control what users can access and do (authorization)',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'User Activity',
                        'slug' => 'user-activity/:userId',
                        'icon' => 'activity',
                        'description' => 'Review the detailed activity timeline, login history, and sessions for a specific user',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                ],
            ],

            // Content Management Section
            [
                'name' => 'Content Management',
                'slug' => 'content-management',
                'icon' => 'document-text',
                'description' => 'Create, edit, and organize content including posts, pages, media, and taxonomies',
                'parent_id' => null,
                'active' => false,
                'show_in_menu' => false,
                'children' => [
                    [
                        'name' => 'Pages',
                        'slug' => 'pages',
                        'icon' => 'newspaper',
                        'description' => 'Create and manage static pages like About, Contact, and Privacy Policy',
                        'active' => false,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Media Library',
                        'slug' => 'media-library',
                        'icon' => 'photo',
                        'description' => 'Upload, organize, and manage images, videos, and other media files',
                        'active' => false,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Categories',
                        'slug' => 'categories',
                        'icon' => 'tag',
                        'description' => 'Organize content into hierarchical categories for better structure',
                        'active' => false,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Tags',
                        'slug' => 'tags',
                        'icon' => 'hashtag',
                        'description' => 'Manage tags for flexible content labeling and filtering',
                        'active' => false,
                        'show_in_menu' => false,
                    ],
                ],
            ],

            // Analytics & Reports Section
            [
                'name' => 'Analytics & Reports',
                'slug' => 'analytics',
                'icon' => 'chart-bar',
                'description' => 'View detailed analytics, reports, and insights about system usage and performance',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Analytics Overview',
                        'slug' => 'analytics-dashboard',
                        'icon' => 'presentation-chart-line',
                        'description' => 'Overview of key metrics and analytics in a visual dashboard format',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Daily Operation Expenses',
                        'slug' => 'daily-operation-expenses',
                        'icon' => 'circle-dollar-sign',
                        'description' => 'Manage and view daily operation expenses',
                        'active' => true,
                        'show_in_menu' => true,
                    ],  
                    [
                        'name' => 'Detailed Expenses',
                        'slug' => 'detailed-expenses/:date',
                        'icon' => 'activity',
                        'description' => 'Display the detailed expenses of a selected date',
                        'active' => true,
                        'show_in_menu' => false,
                    ],                  
                    [
                        'name' => 'Transport Summary Report',
                        'slug' => 'transport-summary-report',
                        'icon' => 'wallet',
                        'description' => 'View transport summary expenses.',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Transport Summary',
                        'slug' => 'transport-summary/:id',
                        'icon' => 'activity',
                        'description' => 'Review the transport history of a specific fleet truck',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'User Reports',
                        'slug' => 'user-reports',
                        'icon' => 'user-circle',
                        'description' => 'Detailed reports on user registration, engagement, and behavior patterns',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Content Reports',
                        'slug' => 'content-reports',
                        'icon' => 'document-chart-bar',
                        'description' => 'Analyze content performance, views, and engagement statistics',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                    [
                        'name' => 'Activity Logs',
                        'slug' => 'activity-logs',
                        'icon' => 'clipboard-document-list',
                        'description' => 'Comprehensive system-wide audit trail of all administrative actions, module activities, and system operations across all users. Complete audit log for compliance and security monitoring',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],

            // System Settings Section
            [
                'name' => 'System Settings',
                'slug' => 'system-settings',
                'icon' => 'cog-6-tooth',
                'description' => 'Configure system preferences, security, and administrative settings',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'General Settings',
                        'slug' => 'settings',
                        'icon' => 'adjustments-horizontal',
                        'description' => 'Configure general application settings, site information, and preferences',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Navigation',
                        'slug' => 'navigation',
                        'icon' => 'list-bullet',
                        'description' => 'Customize and manage navigation menu structure and order',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Security',
                        'slug' => 'security',
                        'icon' => 'shield-exclamation',
                        'description' => 'Configure two-factor authentication (2FA) and session security settings',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Email Settings',
                        'slug' => 'email-settings',
                        'icon' => 'envelope',
                        'description' => 'Configure email server settings and notification templates',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Language',
                        'slug' => 'language',
                        'icon' => 'language',
                        'description' => 'Manage application languages, translations, and localization settings',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Backup & Restore',
                        'slug' => 'backup-restore',
                        'icon' => 'server',
                        'description' => 'Create backups, restore data, and manage system recovery options',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],
        ];

        // Create navigations recursively
        foreach ($navigationStructure as $navData) {
            $this->createNavigation($navData, null);
        }
    }

    /**
     * Recursively create navigation items and their children
     */
    private function createNavigation(array $navData, ?int $parentId): void
    {
        // Prepare navigation data
        $navigation = [
            'name' => $navData['name'],
            'slug' => $navData['slug'],
            'icon' => $navData['icon'],
            'description' => $navData['description'] ?? null,
            'parent_id' => $parentId,
            'active' => $navData['active'] ?? true,
            'show_in_menu' => $navData['show_in_menu'] ?? true,
        ];

        // Create the navigation item
        $nav = Navigation::create($navigation);

        // Create children if they exist
        if (isset($navData['children']) && is_array($navData['children'])) {
            foreach ($navData['children'] as $childData) {
                $this->createNavigation($childData, $nav->id);
            }
        }
    }
}
