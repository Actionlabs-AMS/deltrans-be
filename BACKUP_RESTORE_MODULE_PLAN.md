# Backup and Restore Module - Implementation Plan

## 📋 Executive Summary

This document outlines a comprehensive plan for implementing a Backup and Restore module for the BaseCode (Laravel Backend) and CorePanel (Next.js Frontend) projects. The module will provide automated and manual backup capabilities for both database and file storage, with restore functionality and scheduling options.

## 🎯 Objectives

1. **Database Backup**: Full database backup with compression and encryption
2. **File Storage Backup**: Backup of storage files (local and S3)
3. **Automated Scheduling**: Cron-based scheduled backups
4. **Manual Backup**: On-demand backup creation
5. **Restore Functionality**: Safe restore with validation and rollback
6. **Backup Management**: List, download, delete, and monitor backups
7. **Security**: Encrypted backups with access control
8. **Audit Trail**: Complete logging of backup/restore operations

## 🏗️ Architecture Overview

### Backend (BaseCode - Laravel)

-   **Service Layer**: `BackupService` following `BaseService` pattern
-   **Controller**: `BackupController` for API endpoints
-   **Model**: `Backup` model for backup metadata
-   **Jobs**: Queue jobs for async backup operations
-   **Commands**: Artisan commands for scheduled backups
-   **Storage**: Local and S3 storage support

### Frontend (CorePanel - Next.js)

-   **Pages**: Backup management UI pages
-   **Components**: Backup list, restore modal, schedule form
-   **Services**: API service for backup operations
-   **DataTable**: Integration with existing DataTable component

## 📊 Database Schema

### `backups` Table

```sql
CREATE TABLE `backups` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('database', 'files', 'full') NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `storage_disk` VARCHAR(50) DEFAULT 'local',
    `storage_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT UNSIGNED NULL,
    `file_path` VARCHAR(500) NULL,
    `encrypted` BOOLEAN DEFAULT FALSE,
    `compression` ENUM('none', 'gzip', 'zip') DEFAULT 'gzip',
    `tables_included` TEXT NULL, -- JSON array for selective backups
    `files_included` TEXT NULL, -- JSON array for file paths
    `metadata` TEXT NULL, -- JSON for additional info
    `error_message` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `completed_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,

    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `backup_schedules` Table

```sql
CREATE TABLE `backup_schedules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('database', 'files', 'full') NOT NULL,
    `frequency` ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    `cron_expression` VARCHAR(100) NULL,
    `time` TIME NULL, -- For daily/weekly/monthly
    `day_of_week` TINYINT NULL, -- 0-6 for weekly (0=Sunday)
    `day_of_month` TINYINT NULL, -- 1-31 for monthly
    `retention_days` INT UNSIGNED DEFAULT 30,
    `storage_disk` VARCHAR(50) DEFAULT 'local',
    `encrypted` BOOLEAN DEFAULT FALSE,
    `compression` ENUM('none', 'gzip', 'zip') DEFAULT 'gzip',
    `tables_included` TEXT NULL, -- JSON array
    `files_included` TEXT NULL, -- JSON array
    `active` BOOLEAN DEFAULT TRUE,
    `last_run_at` TIMESTAMP NULL,
    `next_run_at` TIMESTAMP NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_active` (`active`),
    INDEX `idx_next_run_at` (`next_run_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🔧 Backend Implementation

### 1. Model: `Backup.php`

**Location**: `app/Models/Backup.php`

**Features**:

-   Soft deletes
-   Cast JSON fields
-   Relationships (user, schedule)
-   Accessors for file size formatting
-   Scopes for filtering

**Key Methods**:

-   `scopeCompleted()`, `scopeFailed()`, `scopeByType()`
-   `getFileSizeHumanAttribute()` - Format file size
-   `isExpired()` - Check if backup expired
-   `canRestore()` - Check if backup can be restored

### 2. Model: `BackupSchedule.php`

**Location**: `app/Models/BackupSchedule.php`

**Features**:

-   Cron expression generation
-   Next run calculation
-   Schedule validation

**Key Methods**:

-   `getCronExpression()` - Generate cron from frequency
-   `calculateNextRun()` - Calculate next execution time
-   `shouldRun()` - Check if schedule should run now

### 3. Service: `BackupService.php`

**Location**: `app/Services/BackupService.php`

**Extends**: `BaseService`

**Key Methods**:

```php
// Backup Operations
public function createBackup(array $data): Backup
public function createDatabaseBackup(array $options = []): Backup
public function createFilesBackup(array $options = []): Backup
public function createFullBackup(array $options = []): Backup

// Restore Operations
public function restoreBackup(int $backupId, array $options = []): bool
public function validateBackup(int $backupId): array
public function previewBackup(int $backupId): array

// Management
public function listBackups(array $filters = []): Collection
public function deleteBackup(int $backupId): bool
public function downloadBackup(int $backupId): StreamedResponse
public function getBackupStats(): array

// Scheduling
public function createSchedule(array $data): BackupSchedule
public function updateSchedule(int $scheduleId, array $data): BackupSchedule
public function deleteSchedule(int $scheduleId): bool
public function runScheduledBackups(): void
```

**Helper Methods**:

-   `generateBackupName()` - Generate unique backup filename
-   `compressBackup()` - Compress backup file
-   `encryptBackup()` - Encrypt backup file
-   `storeBackup()` - Store backup to disk/S3
-   `validateDatabaseBackup()` - Validate SQL backup integrity
-   `extractBackup()` - Extract compressed backup

### 4. Helper: `BackupHelper.php`

**Location**: `app/Helpers/BackupHelper.php`

**Key Methods**:

```php
// Database Operations
public static function exportDatabase(array $tables = []): string
public static function importDatabase(string $sqlFile): bool
public static function getTableList(): array
public static function getDatabaseSize(): int

// File Operations
public static function backupStorageFiles(array $paths = []): string
public static function restoreStorageFiles(string $backupPath, array $paths = []): bool
public static function getStorageSize(): int
public static function listStorageFiles(string $path = ''): array

// Compression
public static function compressGzip(string $filePath): string
public static function decompressGzip(string $filePath): string
public static function compressZip(array $files, string $outputPath): string
public static function extractZip(string $filePath, string $extractTo): bool

// Encryption
public static function encryptFile(string $filePath, string $key = null): string
public static function decryptFile(string $filePath, string $key = null): string

// Validation
public static function validateBackupFile(string $filePath): array
public static function checkDiskSpace(int $requiredSize): bool
```

### 5. Controller: `BackupController.php`

**Location**: `app/Http/Controllers/Api/BackupController.php`

**Routes**:

```php
// Backup Management
GET    /api/backups                    - List backups
POST   /api/backups                    - Create manual backup
GET    /api/backups/{id}               - Get backup details
DELETE /api/backups/{id}               - Delete backup
GET    /api/backups/{id}/download       - Download backup
POST   /api/backups/{id}/restore       - Restore backup
GET    /api/backups/{id}/validate      - Validate backup
GET    /api/backups/stats               - Get backup statistics

// Schedule Management
GET    /api/backups/schedules           - List schedules
POST   /api/backups/schedules           - Create schedule
GET    /api/backups/schedules/{id}      - Get schedule details
PUT    /api/backups/schedules/{id}      - Update schedule
DELETE /api/backups/schedules/{id}      - Delete schedule
POST   /api/backups/schedules/{id}/run  - Run schedule manually

// Options
GET    /api/backups/options/tables       - Get database tables list
GET    /api/backups/options/storage     - Get storage paths
GET    /api/backups/options/disks       - Get available storage disks
```

### 6. Job: `CreateBackupJob.php`

**Location**: `app/Jobs/CreateBackupJob.php`

**Purpose**: Handle async backup creation to prevent timeout

**Features**:

-   Progress tracking
-   Error handling
-   Notification on completion
-   Timeout protection

### 7. Command: `BackupRunSchedulesCommand.php` (Optional)

**Location**: `app/Console/Commands/BackupRunSchedulesCommand.php`

**Purpose**: Run scheduled backups (optional - only if cron is available)

**Usage**: `php artisan backup:run-schedules`

**Note**: This command is **optional**. The system uses on-request scheduling by default, which works without cron. This command can be used if cron is available for more precise timing.

**Schedule** (if cron is available): Add to `app/Console/Kernel.php`:

```php
$schedule->command('backup:run-schedules')->everyMinute();
```

**Alternative**: Use `CheckBackupSchedulesMiddleware` for on-request scheduling (no cron needed).

### 8. Command: `BackupCleanupCommand.php`

**Location**: `app/Console/Commands/BackupCleanupCommand.php`

**Purpose**: Clean up expired backups

**Usage**: `php artisan backup:cleanup`

**Schedule**: Daily at 2 AM

```php
$schedule->command('backup:cleanup')->dailyAt('02:00');
```

### 9. Resource: `BackupResource.php`

**Location**: `app/Http/Resources/BackupResource.php`

**Purpose**: Format backup data for API responses

### 10. Request: `BackupRequest.php`

**Location**: `app/Http/Requests/BackupRequest.php`

**Purpose**: Validate backup creation/update requests

## 🎨 Frontend Implementation

### 1. Pages Structure

```
src/app/
├── backup-restore/
│   ├── page.tsx                    # Main backup list page
│   ├── create/
│   │   └── page.tsx                # Create backup page
│   ├── schedules/
│   │   ├── page.tsx                # Schedule list page
│   │   ├── create/
│   │   │   └── page.tsx             # Create schedule page
│   │   └── [id]/
│   │       └── page.tsx             # Edit schedule page
│   └── [id]/
│       └── page.tsx                 # Backup details page
```

### 2. Components

**Location**: `src/components/backup/`

```
backup/
├── BackupList.tsx                   # Main backup list with DataTable
├── BackupCreateForm.tsx            # Create backup form
├── BackupRestoreModal.tsx          # Restore confirmation modal
├── BackupScheduleForm.tsx          # Schedule creation/editing form
├── BackupStats.tsx                 # Statistics dashboard
├── BackupProgress.tsx               # Progress indicator for running backups
└── BackupFilters.tsx                # Filter component
```

### 3. Services

**Location**: `src/lib/services/backupService.ts`

```typescript
// Backup Operations
export const getBackups = (params?: BackupListParams): Promise<BackupListResponse>
export const getBackup = (id: number): Promise<Backup>
export const createBackup = (data: CreateBackupData): Promise<Backup>
export const deleteBackup = (id: number): Promise<void>
export const downloadBackup = (id: number): Promise<Blob>
export const restoreBackup = (id: number, options?: RestoreOptions): Promise<void>
export const validateBackup = (id: number): Promise<ValidationResult>
export const getBackupStats = (): Promise<BackupStats>

// Schedule Operations
export const getSchedules = (): Promise<BackupSchedule[]>
export const getSchedule = (id: number): Promise<BackupSchedule>
export const createSchedule = (data: CreateScheduleData): Promise<BackupSchedule>
export const updateSchedule = (id: number, data: UpdateScheduleData): Promise<BackupSchedule>
export const deleteSchedule = (id: number): Promise<void>
export const runSchedule = (id: number): Promise<void>

// Options
export const getBackupTables = (): Promise<string[]>
export const getStoragePaths = (): Promise<string[]>
export const getStorageDisks = (): Promise<StorageDisk[]>
```

### 4. Types

**Location**: `src/types/backup.ts`

```typescript
export interface Backup {
    id: number;
    name: string;
    type: "database" | "files" | "full";
    status: "pending" | "in_progress" | "completed" | "failed" | "cancelled";
    storage_disk: string;
    storage_path: string;
    file_size: number;
    file_path: string | null;
    encrypted: boolean;
    compression: "none" | "gzip" | "zip";
    tables_included: string[] | null;
    files_included: string[] | null;
    metadata: Record<string, any> | null;
    error_message: string | null;
    created_by: number | null;
    completed_at: string | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface BackupSchedule {
    id: number;
    name: string;
    type: "database" | "files" | "full";
    frequency: "daily" | "weekly" | "monthly" | "custom";
    cron_expression: string | null;
    time: string | null;
    day_of_week: number | null;
    day_of_month: number | null;
    retention_days: number;
    storage_disk: string;
    encrypted: boolean;
    compression: "none" | "gzip" | "zip";
    tables_included: string[] | null;
    files_included: string[] | null;
    active: boolean;
    last_run_at: string | null;
    next_run_at: string | null;
    created_by: number | null;
    created_at: string;
    updated_at: string;
}
```

## 🔐 Security Considerations

### 1. Access Control

-   **Permission**: `backup.manage`, `backup.restore`, `backup.schedule`
-   **Role-based**: Only admins can restore backups
-   **Audit Trail**: Log all backup/restore operations

### 2. Encryption

-   **Backup Encryption**: Optional AES-256 encryption
-   **Key Management**: Use Laravel's encryption key
-   **Secure Storage**: Encrypted backups stored securely

### 3. Validation

-   **Backup Integrity**: Validate before restore
-   **File Verification**: Checksum validation
-   **Database Validation**: SQL syntax validation

### 4. Safety Measures

-   **Restore Confirmation**: Require explicit confirmation
-   **Backup Before Restore**: Auto-backup before restore
-   **Rollback Capability**: Ability to rollback failed restore
-   **Dry Run**: Preview restore without executing

### 5. Cron-Free Scheduling

-   **On-Request Scheduling**: Works on any hosting without cron
-   **Queue-Based Jobs**: Uses Laravel delayed jobs
-   **External Webhooks**: Free webhook services for guaranteed execution
-   **Manual Trigger**: Always available for immediate backups
-   **Auto-Detection**: Automatically selects best method based on environment

## 📅 Scheduling System

### ⚠️ Important: Cron-Free Scheduling

**This implementation supports multiple scheduling methods that work WITHOUT server cron jobs**, making it compatible with shared hosting (GoDaddy, etc.) and environments without cron access.

See **[BACKUP_SCHEDULING_ALTERNATIVES.md](./BACKUP_SCHEDULING_ALTERNATIVES.md)** for detailed implementation of:

-   **On-Request Scheduling**: Checks schedules on API requests (works on any hosting)
-   **Queue-Based Delayed Jobs**: Uses Laravel's delayed jobs (most reliable)
-   **External Webhook Services**: Free/paid services trigger backups (guaranteed execution)
-   **Manual Trigger**: Always available for immediate backups

### Frequency Options

1. **Daily**: Run at specified time every day
2. **Weekly**: Run on specific day of week at specified time
3. **Monthly**: Run on specific day of month at specified time
4. **Custom**: Use cron expression for advanced scheduling

### Cron Expression Examples

-   Daily at 2 AM: `0 2 * * *`
-   Weekly (Monday) at 3 AM: `0 3 * * 1`
-   Monthly (1st) at 4 AM: `0 4 1 * *`
-   Every 6 hours: `0 */6 * * *`

### Scheduling Methods (No Cron Required)

#### Method 1: On-Request Scheduling (Recommended for Shared Hosting)

-   Checks schedules when the application receives requests
-   Works on any hosting provider
-   No server configuration needed
-   Automatic execution when site is accessed

#### Method 2: Queue-Based Delayed Jobs (Best Reliability)

-   Uses Laravel's delayed jobs to schedule next backup
-   Creates automatic chain of scheduled backups
-   Most precise timing
-   Requires queue worker (can be triggered on-request)

#### Method 3: External Webhook Services (Guaranteed Execution)

-   Uses free services (EasyCron, cron-job.org) to trigger backups
-   Works even when site is inactive
-   No server configuration needed
-   Free tier available

#### Method 4: Manual Trigger (Always Available)

-   Admin can manually trigger any schedule
-   Available regardless of other methods
-   Useful for testing and immediate backups

## 💾 Storage Strategy

### Local Storage

-   **Path**: `storage/app/backups/`
-   **Structure**: `{type}/{year}/{month}/{filename}`
-   **Example**: `storage/app/backups/database/2025/01/backup_20250115_020000.sql.gz`

### S3 Storage

-   **Bucket**: Configured S3 bucket
-   **Path**: `backups/{type}/{year}/{month}/`
-   **Lifecycle**: Auto-delete after retention period

### Retention Policy

-   **Default**: 30 days
-   **Configurable**: Per backup or schedule
-   **Auto-cleanup**: Scheduled cleanup job

## 🔄 Restore Process

### 1. Pre-Restore Validation

-   Check backup file integrity
-   Verify database compatibility
-   Check disk space
-   Validate file permissions

### 2. Pre-Restore Backup

-   Create automatic backup before restore
-   Store in temporary location
-   Use for rollback if restore fails

### 3. Restore Execution

-   **Database Restore**:

    -   Disable foreign key checks
    -   Drop existing tables (optional)
    -   Import SQL file
    -   Re-enable foreign key checks
    -   Verify data integrity

-   **Files Restore**:
    -   Backup current files
    -   Extract backup archive
    -   Copy files to storage
    -   Verify file integrity

### 4. Post-Restore

-   Verify restore success
-   Run integrity checks
-   Clear application cache
-   Log restore operation
-   Send notification

### 5. Rollback (if failed)

-   Restore pre-restore backup
-   Revert file changes
-   Log rollback operation
-   Notify administrators

## 📊 Monitoring & Reporting

### Statistics Dashboard

-   Total backups count
-   Total storage used
-   Last backup time
-   Success/failure rates
-   Storage disk usage
-   Upcoming scheduled backups

### Notifications

-   **Email**: On backup completion/failure
-   **In-app**: Toast notifications
-   **Logs**: Audit trail entries

### Health Checks

-   Disk space monitoring
-   Backup file integrity
-   Schedule execution status
-   Storage connectivity

## 🧪 Testing Strategy

### Unit Tests

-   Backup creation logic
-   Compression/decompression
-   Encryption/decryption
-   Validation functions
-   Schedule calculation

### Integration Tests

-   Full backup/restore cycle
-   Schedule execution
-   S3 storage operations
-   Database operations

### Feature Tests

-   API endpoints
-   Permission checks
-   Error handling
-   Concurrent operations

## 📝 Implementation Phases

### Phase 1: Core Backend (Week 1-2)

-   [ ] Database migrations
-   [ ] Models (Backup, BackupSchedule)
-   [ ] BackupHelper class
-   [ ] Basic BackupService
-   [ ] BackupController with CRUD
-   [ ] Unit tests

### Phase 2: Backup Operations (Week 3-4)

-   [ ] Database backup implementation
-   [ ] Files backup implementation
-   [ ] Compression support
-   [ ] Encryption support
-   [ ] Storage integration (local + S3)
-   [ ] CreateBackupJob
-   [ ] Integration tests

### Phase 3: Restore Operations (Week 5)

-   [ ] Restore validation
-   [ ] Database restore
-   [ ] Files restore
-   [ ] Rollback mechanism
-   [ ] Safety checks
-   [ ] Feature tests

### Phase 4: Scheduling (Week 6)

-   [ ] BackupSchedule model
-   [ ] Schedule CRUD
-   [ ] Cron expression generation
-   [ ] **On-Request Scheduling** (CheckBackupSchedulesMiddleware)
-   [ ] **Queue-Based Scheduling** (Delayed jobs)
-   [ ] **Webhook Endpoint** (for external services)
-   [ ] BackupRunSchedulesCommand (optional, if cron available)
-   [ ] Schedule execution logic
-   [ ] Auto-detection of scheduling method
-   [ ] Tests

### Phase 5: Frontend - Basic UI (Week 7-8)

-   [ ] Backup list page with DataTable
-   [ ] Create backup form
-   [ ] Backup details page
-   [ ] Download functionality
-   [ ] Delete functionality
-   [ ] API integration

### Phase 6: Frontend - Advanced Features (Week 9)

-   [ ] Restore modal
-   [ ] Schedule management pages
-   [ ] Statistics dashboard
-   [ ] Progress indicators
-   [ ] Notifications
-   [ ] Error handling

### Phase 7: Polish & Optimization (Week 10)

-   [ ] Performance optimization
-   [ ] UI/UX improvements
-   [ ] Documentation
-   [ ] Security audit
-   [ ] Load testing
-   [ ] Bug fixes

## 🚀 Deployment Checklist

### Backend

-   [ ] Run migrations
-   [ ] Configure storage disks
-   [ ] **Configure scheduling method** (on-request, queue, or webhook)
-   [ ] **Set up webhook token** (if using webhook method)
-   [ ] **Register CheckBackupSchedulesMiddleware** (if using on-request)
-   [ ] Set up cron jobs (optional - only if available)
-   [ ] Configure permissions
-   [ ] Set encryption keys
-   [ ] Test backup/restore
-   [ ] Monitor disk space

### Frontend

-   [ ] Build production bundle
-   [ ] Configure API endpoints
-   [ ] Test all features
-   [ ] Verify permissions
-   [ ] Check responsive design

### Infrastructure

-   [ ] S3 bucket setup
-   [ ] Backup storage allocation
-   [ ] Monitoring setup
-   [ ] Alert configuration
-   [ ] Documentation

## 📚 API Documentation

### Swagger/OpenAPI

-   Document all endpoints
-   Include request/response examples
-   Add authentication requirements
-   Document error responses

## 🔍 Future Enhancements

1. **Incremental Backups**: Only backup changed data
2. **Backup Verification**: Automated integrity checks
3. **Multi-Cloud Support**: Support for multiple cloud providers
4. **Backup Replication**: Copy backups to multiple locations
5. **Backup Encryption Keys**: Per-backup encryption keys
6. **Backup Compression Levels**: Configurable compression
7. **Backup Notifications**: Multiple notification channels
8. **Backup Analytics**: Advanced reporting and analytics
9. **Backup Search**: Search backups by content/metadata
10. **Backup Comparison**: Compare backup contents

## 📖 Documentation Requirements

1. **User Guide**: How to create/restore backups
2. **Admin Guide**: Configuration and scheduling
3. **API Documentation**: Complete API reference
4. **Troubleshooting**: Common issues and solutions
5. **Security Guide**: Best practices for backup security

## ✅ Success Criteria

1. ✅ Can create database backups
2. ✅ Can create file backups
3. ✅ Can create full backups
4. ✅ Can restore from backups
5. ✅ Can schedule automated backups
6. ✅ Can manage backup retention
7. ✅ Can download backups
8. ✅ Can encrypt backups
9. ✅ Can compress backups
10. ✅ Can monitor backup status
11. ✅ Can view backup statistics
12. ✅ Can handle errors gracefully
13. ✅ Can audit all operations
14. ✅ Can secure backup access

---

**Document Version**: 1.0  
**Last Updated**: January 2025  
**Author**: Development Team  
**Status**: Planning Phase
