# Backup and Restore Module - Implementation Summary

## 🎯 Quick Overview

This module provides comprehensive backup and restore functionality for the BaseCode/CorePanel system, supporting both database and file storage backups with scheduling, encryption, and restore capabilities.

## 📋 Key Features

### Core Functionality

-   ✅ **Database Backup**: Full or selective table backups
-   ✅ **File Storage Backup**: Backup of storage files (local/S3)
-   ✅ **Full Backup**: Combined database + files backup
-   ✅ **Manual Backup**: On-demand backup creation
-   ✅ **Scheduled Backup**: Automated backups (daily/weekly/monthly/custom) - **No cron required!**
-   ✅ **Restore**: Safe restore with validation and rollback
-   ✅ **Compression**: Gzip/Zip compression support
-   ✅ **Encryption**: Optional AES-256 encryption
-   ✅ **Storage**: Local and S3 storage support
-   ✅ **Retention**: Configurable backup retention policy
-   ✅ **Management**: List, download, delete, monitor backups

### Security & Safety

-   ✅ **Access Control**: Permission-based access (backup.manage, backup.restore, backup.schedule)
-   ✅ **Audit Trail**: Complete logging of all operations
-   ✅ **Validation**: Pre-restore validation and integrity checks
-   ✅ **Rollback**: Automatic rollback on failed restore
-   ✅ **Pre-Restore Backup**: Auto-backup before restore

## 🏗️ Architecture

### Backend Components

```
BaseCode/
├── app/
│   ├── Models/
│   │   ├── Backup.php              # Backup metadata model
│   │   └── BackupSchedule.php      # Schedule model
│   ├── Services/
│   │   └── BackupService.php      # Main service (extends BaseService)
│   ├── Helpers/
│   │   └── BackupHelper.php       # Backup utilities
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── BackupController.php
│   │   ├── Resources/
│   │   │   └── BackupResource.php
│   │   └── Requests/
│   │       └── BackupRequest.php
│   ├── Jobs/
│   │   └── CreateBackupJob.php    # Async backup creation
│   └── Console/Commands/
│       ├── BackupRunSchedulesCommand.php
│       └── BackupCleanupCommand.php
└── database/migrations/
    ├── create_backups_table.php
    └── create_backup_schedules_table.php
```

### Frontend Components

```
CorePanel/
├── src/
│   ├── app/backup-restore/
│   │   ├── page.tsx                # Main backup list
│   │   ├── create/page.tsx        # Create backup
│   │   ├── schedules/             # Schedule management
│   │   └── [id]/page.tsx          # Backup details
│   ├── components/backup/
│   │   ├── BackupList.tsx
│   │   ├── BackupCreateForm.tsx
│   │   ├── BackupRestoreModal.tsx
│   │   └── BackupScheduleForm.tsx
│   ├── lib/services/
│   │   └── backupService.ts
│   └── types/
│       └── backup.ts
```

## 📊 Database Tables

### `backups` Table

-   Stores backup metadata
-   Tracks status (pending, in_progress, completed, failed)
-   Supports encryption and compression
-   Includes expiration dates

### `backup_schedules` Table

-   Stores schedule configurations
-   Supports daily/weekly/monthly/custom frequencies
-   Tracks last run and next run times
-   Configurable retention policies

## 🔌 API Endpoints

### Backup Management

```
GET    /api/backups                    # List backups
POST   /api/backups                    # Create backup
GET    /api/backups/{id}               # Get backup details
DELETE /api/backups/{id}               # Delete backup
GET    /api/backups/{id}/download      # Download backup
POST   /api/backups/{id}/restore       # Restore backup
GET    /api/backups/{id}/validate      # Validate backup
GET    /api/backups/stats              # Get statistics
```

### Schedule Management

```
GET    /api/backups/schedules          # List schedules
POST   /api/backups/schedules          # Create schedule
GET    /api/backups/schedules/{id}     # Get schedule
PUT    /api/backups/schedules/{id}     # Update schedule
DELETE /api/backups/schedules/{id}     # Delete schedule
POST   /api/backups/schedules/{id}/run # Run schedule manually
```

### Options

```
GET    /api/backups/options/tables      # Get database tables
GET    /api/backups/options/storage     # Get storage paths
GET    /api/backups/options/disks       # Get storage disks
```

## 🔄 Backup Types

### 1. Database Backup

-   Exports all or selected tables to SQL
-   Supports compression (gzip)
-   Optional encryption
-   Includes schema and data

### 2. Files Backup

-   Backs up storage files (local/S3)
-   Creates archive (zip/tar.gz)
-   Preserves directory structure
-   Optional encryption

### 3. Full Backup

-   Combines database + files
-   Single archive file
-   Complete system backup
-   Recommended for migrations

## ⏰ Scheduling Options

### ⚠️ Cron-Free Scheduling (Works on Any Hosting!)

**No server cron jobs required!** The system supports multiple scheduling methods:

1. **On-Request Scheduling** ⭐ (Recommended for shared hosting)

    - Checks schedules when site receives requests
    - Works on GoDaddy, shared hosting, any provider
    - Automatic execution when site is accessed

2. **Queue-Based Delayed Jobs** ⭐⭐ (Best reliability)

    - Uses Laravel's delayed jobs
    - Creates automatic chain of backups
    - Most precise timing

3. **External Webhook Services** 🌐 (Guaranteed execution)

    - Free services (EasyCron, cron-job.org)
    - Works even when site is inactive
    - No server configuration needed

4. **Manual Trigger** (Always available)
    - Admin can trigger any schedule immediately
    - Available regardless of other methods

See **[BACKUP_SCHEDULING_ALTERNATIVES.md](./BACKUP_SCHEDULING_ALTERNATIVES.md)** for detailed implementation.

### Frequency Types

1. **Daily**: Run at specified time every day
2. **Weekly**: Run on specific day at specified time
3. **Monthly**: Run on specific day of month
4. **Custom**: Cron expression for advanced scheduling

### Example Schedules

-   Daily at 2 AM: `0 2 * * *`
-   Weekly (Monday) at 3 AM: `0 3 * * 1`
-   Monthly (1st) at 4 AM: `0 4 1 * *`
-   Every 6 hours: `0 */6 * * *`

## 🔐 Security Features

### Access Control

-   **Permission**: `backup.manage` - Create/manage backups
-   **Permission**: `backup.restore` - Restore backups (admin only)
-   **Permission**: `backup.schedule` - Manage schedules

### Encryption

-   Optional AES-256 encryption
-   Uses Laravel's encryption key
-   Encrypted backups stored securely

### Validation

-   Backup integrity checks
-   File checksum validation
-   SQL syntax validation
-   Pre-restore safety checks

## 💾 Storage Strategy

### Local Storage

-   Path: `storage/app/backups/`
-   Structure: `{type}/{year}/{month}/{filename}`
-   Example: `backups/database/2025/01/backup_20250115_020000.sql.gz`

### S3 Storage

-   Bucket: Configured S3 bucket
-   Path: `backups/{type}/{year}/{month}/`
-   Lifecycle: Auto-delete after retention

### Retention

-   Default: 30 days
-   Configurable per backup/schedule
-   Auto-cleanup via scheduled job

## 🔄 Restore Process

### Steps

1. **Validation**: Check backup integrity and compatibility
2. **Pre-Backup**: Create automatic backup before restore
3. **Execution**: Restore database/files
4. **Verification**: Verify restore success
5. **Rollback**: If failed, restore pre-restore backup

### Safety Features

-   Requires explicit confirmation
-   Creates backup before restore
-   Validates before execution
-   Supports dry-run preview
-   Automatic rollback on failure

## 📈 Implementation Timeline

### Phase 1: Core Backend (Week 1-2)

-   Database migrations
-   Models and basic service
-   CRUD operations
-   Unit tests

### Phase 2: Backup Operations (Week 3-4)

-   Database backup implementation
-   Files backup implementation
-   Compression/encryption
-   Storage integration
-   Integration tests

### Phase 3: Restore Operations (Week 5)

-   Restore validation
-   Database/files restore
-   Rollback mechanism
-   Feature tests

### Phase 4: Scheduling (Week 6)

-   Schedule model and CRUD
-   Cron expression generation
-   Schedule execution
-   Tests

### Phase 5: Frontend - Basic UI (Week 7-8)

-   Backup list page
-   Create backup form
-   Backup details
-   API integration

### Phase 6: Frontend - Advanced (Week 9)

-   Restore modal
-   Schedule management
-   Statistics dashboard
-   Notifications

### Phase 7: Polish (Week 10)

-   Performance optimization
-   UI/UX improvements
-   Documentation
-   Security audit
-   Bug fixes

## ✅ Success Criteria

-   [x] Can create database backups
-   [x] Can create file backups
-   [x] Can create full backups
-   [x] Can restore from backups
-   [x] Can schedule automated backups
-   [x] Can manage backup retention
-   [x] Can download backups
-   [x] Can encrypt backups
-   [x] Can compress backups
-   [x] Can monitor backup status
-   [x] Can view backup statistics
-   [x] Can handle errors gracefully
-   [x] Can audit all operations
-   [x] Can secure backup access

## 🚀 Quick Start (After Implementation)

### Create Manual Backup

```php
// Via API
POST /api/backups
{
    "name": "Manual Backup",
    "type": "full",
    "compression": "gzip",
    "encrypted": true
}
```

### Create Schedule

```php
// Via API
POST /api/backups/schedules
{
    "name": "Daily Backup",
    "type": "database",
    "frequency": "daily",
    "time": "02:00",
    "retention_days": 30
}
```

### Restore Backup

```php
// Via API
POST /api/backups/{id}/restore
{
    "confirm": true,
    "create_backup": true
}
```

## 📝 Notes

-   Follows existing BaseService pattern
-   Integrates with S3Helper for cloud storage
-   Uses existing DataTable component in frontend
-   Follows audit trail patterns
-   Respects permission system
-   Compatible with encryption/anonymization features

## 🔗 Related Documentation

-   Full Implementation Plan: `BACKUP_RESTORE_MODULE_PLAN.md`
-   BaseCode README: `README.md`
-   CorePanel README: `../CorePanel/README.md`

---

**Status**: Planning Complete - Ready for Implementation  
**Estimated Duration**: 10 weeks  
**Priority**: High  
**Complexity**: Medium-High
