# Two Ways a Booking Becomes Complete

A booking is marked **Complete** (`is_complete = true`) in one of two ways:

1. **Invoice creation** — immediate.
2. **Auto-complete after inactivity** — a 3-week countdown, then a scheduled job sets it complete.

---

## 1) Invoice creation (immediate)

- **What happens:** When a user creates an **Invoice** from a **Statement of Account (SOA)**.
- **Result:** The system immediately sets **all bookings** in that SOA to `is_complete = true`.
- No timer; completion is instant.

---

## 2) Auto-complete after inactivity (scheduled)

When an SOA is created, the system starts a **3-week countdown** for each booking in that SOA. A scheduled job checks this timer and marks the booking complete when the 3 weeks have passed with no activity.

### Timer starts / resets when SOA changes

- **SOA created**  
  For every booking in the new SOA, a timer starts: due date = **now + 3 weeks**.

- **SOA updated**
  - **New bookings added** to the SOA → a timer **starts** for those new bookings (due = now + 3 weeks).
  - **Bookings still in** the SOA → their timer **resets** (due = now + 3 weeks).
  - **Booking removed** from the SOA → its timer is **cleared** (`auto_complete_at = null`), but only if that booking is **not** in any other active SOA.

- **SOA deleted**  
  The timer is **cleared** for all bookings that were in that SOA, but only if they are **not** in any other active SOA.

### Timer extends when booking data changes

Any change to the booking or its related data **pushes the due date forward** (resets to now + 3 weeks):

- Adding or updating a **booking**.
- Adding, editing, deleting, or restoring a **Waybill**.
- Adding, editing, or deleting a **Container**.

So the 3-week inactivity period restarts whenever there is such a change.

### Completion rule

If there are **no changes** (as above) for **3 weeks in a row**, the scheduled job runs and sets `is_complete = true` for that booking.

---

## Artisan commands

| Command | What it does |
|--------|----------------|
| `php artisan schedule:list` | Shows all scheduled tasks and when they run (use this to **check the running cron**). |
| `php artisan schedule:run` | Runs the scheduler once (same as what cron runs every minute). |
| `php artisan schedule:work` | Runs the scheduler in the foreground (e.g. local dev); runs every minute until you stop it. |
| `php artisan bookings:auto-complete` | Runs the auto-complete job **once now**: marks complete all bookings whose 3-week due date has passed. |

**On the server**, cron usually runs the scheduler every minute:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The **bookings:auto-complete** command is scheduled in `app/Console/Kernel.php` to run **hourly**.

---

## Quick summary

| Way to complete | Trigger | Result |
|-----------------|--------|--------|
| **Invoice** | User creates invoice from SOA | All bookings in that SOA → `is_complete = true` right away. |
| **Auto-complete** | No activity for 3 weeks | Scheduled job sets `is_complete = true` when due date has passed. |

- Timer **starts** when a booking is first added to an SOA (or when new bookings are added on SOA update).
- Timer **resets** (due = now + 3 weeks) when the SOA is updated, or when the booking, a waybill, or a container changes.
- Timer **clears** when the booking is removed from every SOA (or the only SOA that had it is deleted).
