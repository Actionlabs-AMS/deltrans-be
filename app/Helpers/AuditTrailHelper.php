<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditTrailHelper
{
    /**
     * Log an action to the audit trail
     *
     * @param string $module
     * @param string $action
     * @param array $data
     * @param string|null $resourceId
     * @param array|null $oldData
     * @param array|null $newData
     * @return bool
     */
    public static function log(
        string $module,
        string $action,
        array $data = [],
        ?string $resourceId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): bool {
        try {
            $logData = [
                'timestamp' => now()->toISOString(),
                'module' => $module,
                'action' => $action,
                'resource_id' => $resourceId,
                'data' => $data,
                'old_data' => $oldData,
                'new_data' => $newData,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->user_email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            // Log to Laravel log file
            Log::channel('audit')->info('Audit Trail', $logData);

            // Store in database if audit table exists
            if (self::auditTableExists()) {
                self::storeInDatabase($logData);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Audit Trail Logging Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log database queries
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return bool
     */
    public static function logQuery(string $query, array $bindings, float $time): bool
    {
        try {
            $logData = [
                'timestamp' => now()->toISOString(),
                'type' => 'QUERY',
                'query' => $query,
                'bindings' => $bindings,
                'execution_time' => $time . 'ms',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
            ];

            Log::channel('audit')->info('Database Query', $logData);
            return true;
        } catch (\Exception $e) {
            Log::error('Query Logging Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if audit table exists (always false for file-based logging)
     *
     * @return bool
     */
    private static function auditTableExists(): bool
    {
        return false; // Using file-based logging instead of database
    }

    /**
     * Store audit log in database (disabled for file-based logging)
     *
     * @param array $logData
     * @return void
     */
    private static function storeInDatabase(array $logData): void
    {
        // File-based logging is handled by Log::channel('audit')
        // No database storage needed
    }

    /**
     * Get audit logs for a specific resource
     *
     * @param string $resourceId
     * @param string|null $module
     * @return \Illuminate\Support\Collection
     */
    public static function getResourceLogs(string $resourceId, ?string $module = null)
    {
        try {
            $query = DB::table('audit_trails')
                ->where('resource_id', $resourceId)
                ->orderBy('created_at', 'desc');

            if ($module) {
                $query->where('module', $module);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Get Resource Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get audit logs for a specific user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getUserLogs(int $userId, int $limit = 100)
    {
        try {
            return DB::table('audit_trails')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Get User Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get audit logs for a specific module
     *
     * @param string $module
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getModuleLogs(string $module, int $limit = 100)
    {
        try {
            return DB::table('audit_trails')
                ->where('module', $module)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Get Module Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Clean up old audit logs
     *
     * @param int $daysToKeep
     * @return int Number of records deleted
     */
    public static function cleanupOldLogs(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);
            
            $deletedCount = DB::table('audit_trails')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info("Cleaned up {$deletedCount} old audit log records");
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Cleanup Old Logs Error: ' . $e->getMessage());
            return 0;
        }
    }
}
