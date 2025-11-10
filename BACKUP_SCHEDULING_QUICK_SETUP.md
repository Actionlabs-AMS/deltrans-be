# Backup Scheduling - Quick Setup Guide

## 🚀 Quick Start (Choose Your Method)

### For Shared Hosting (GoDaddy, etc.) - Recommended ⭐

**Method**: On-Request Scheduling

1. **Register Middleware** (one-time setup):
```php
// app/Http/Kernel.php
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\CheckBackupSchedulesMiddleware::class,
];
```

2. **Done!** Schedules will automatically check when your site receives requests.

**That's it!** No cron jobs needed. Works on any hosting.

---

### For VPS/Cloud (AWS, DigitalOcean) - Best Reliability ⭐⭐

**Method**: Queue-Based Delayed Jobs

1. **Configure Queue**:
```env
QUEUE_CONNECTION=database
```

2. **Run Queue Worker** (or use on-request processing):
```bash
php artisan queue:work --queue=backups
```

3. **Schedules auto-chain** - Each backup schedules the next one automatically.

---

### For Guaranteed Execution - Even When Site is Inactive 🌐

**Method**: External Webhook Service

1. **Set Webhook Token**:
```env
BACKUP_WEBHOOK_TOKEN=your-secure-random-token-here
```

2. **Set up Free Service** (EasyCron example):
   - Go to https://www.easycron.com
   - Create account (free tier: 1 cron job)
   - Add new cron job:
     - **URL**: `https://yourdomain.com/api/backups/webhook/trigger`
     - **Method**: POST
     - **Headers**: `X-Webhook-Token: your-secure-random-token-here`
     - **Schedule**: Every 15 minutes
   - Save and activate

3. **Done!** Backups will trigger even if site is inactive.

---

## 📋 Configuration

### Environment Variables

```env
# Scheduling Method (auto, on_request, queue, webhook, manual)
BACKUP_SCHEDULING_METHOD=auto

# For webhook method
BACKUP_WEBHOOK_TOKEN=your-secure-random-token-here

# For queue method
QUEUE_CONNECTION=database

# Schedule check cooldown (minutes) - for on-request method
BACKUP_SCHEDULE_CHECK_COOLDOWN=5
```

### Auto-Detection

If `BACKUP_SCHEDULING_METHOD=auto`, the system will:
1. Use **queue** if queue is configured
2. Use **webhook** if webhook token is set
3. Fall back to **on-request** (always works)

---

## ✅ Verification

### Check Scheduling Status

```bash
# Via API
GET /api/backups/schedules/status

# Response:
{
  "scheduling_method": "on_request",
  "last_check": "2025-01-15T10:30:00Z",
  "active_schedules": 3,
  "due_schedules": 0,
  "queue_connection": "sync",
  "webhook_configured": false
}
```

### Test Schedule

1. Create a test schedule (daily, 1 minute from now)
2. Wait for next request to your site
3. Check backup was created

---

## 🎯 Recommended Setup by Hosting Type

| Hosting Type | Primary Method | Fallback |
|-------------|---------------|----------|
| **Shared Hosting** (GoDaddy) | On-Request | Manual Trigger |
| **VPS/Cloud** | Queue-Based | On-Request |
| **Serverless** | Webhook | Manual Trigger |
| **Dedicated Server** | Queue-Based + Cron | On-Request |

---

## 🔧 Troubleshooting

### Schedules Not Running?

1. **Check Method**:
   ```bash
   GET /api/backups/schedules/status
   ```

2. **On-Request Method**:
   - Ensure middleware is registered
   - Make sure site receives regular traffic
   - Check `last_check` timestamp

3. **Queue Method**:
   - Ensure queue worker is running
   - Check `failed_jobs` table
   - Verify queue connection

4. **Webhook Method**:
   - Verify webhook token matches
   - Check external service logs
   - Test webhook endpoint manually

### Manual Trigger (Always Works)

If schedules aren't running automatically, you can always trigger manually:

```bash
# Via API
POST /api/backups/schedules/{id}/run

# Or via UI
# Click "Run Now" button on schedule
```

---

## 📚 Full Documentation

- **Detailed Alternatives**: [BACKUP_SCHEDULING_ALTERNATIVES.md](./BACKUP_SCHEDULING_ALTERNATIVES.md)
- **Full Implementation Plan**: [BACKUP_RESTORE_MODULE_PLAN.md](./BACKUP_RESTORE_MODULE_PLAN.md)
- **Implementation Summary**: [BACKUP_RESTORE_IMPLEMENTATION_SUMMARY.md](./BACKUP_RESTORE_IMPLEMENTATION_SUMMARY.md)

---

**Remember**: You don't need cron jobs! The system works on any hosting. 🎉

