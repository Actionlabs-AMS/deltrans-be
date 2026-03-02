# Booking Completion: Two Types of Triggers

This document explains how bookings get marked **Complete** (`is_complete = true`). There are **two distinct triggers**: one when an invoice is created (immediate), and one after a **3-week** auto-complete window (scheduled).

---

## 1. Trigger 1: Invoice creation (immediate)

### When it happens

When a user **generates/creates an invoice** from a Statement of Account (SOA), the system immediately marks all bookings in that SOA as complete.

### What the system does

- After the invoice is successfully created, the system reads the SOA’s **booking ID(s)**.
- All those bookings are updated to **Complete = Yes** (`is_complete = true`).
- No timer is involved; completion is immediate.

### Why it matters

- Bookings that have been invoiced are treated as **billing-processed**.
- Dashboards and reports that use completion status stay in sync.
- Users do not need to mark those bookings complete by hand.

### Scope / notes

- One SOA can include **multiple bookings**; **all** of them are marked complete when the invoice is created.
- This only runs in the **invoice generation flow** (create invoice from SOA).
- Setting `is_complete = true` here is **one-way**: the system does not automatically set bookings back to incomplete if the invoice is later changed or removed.

---

## 2. Trigger 2: Auto-complete after 3 weeks (scheduled)

A booking can also be marked complete **automatically 3 weeks** after a “due” time, if no invoice was created in the meantime. This uses a **timer** stored on the booking: `auto_complete_at`.

### The 3-week window

- The delay is **3 weeks** (21 days), defined by `WEEKS_UNTIL_AUTO_COMPLETE = 3` in the observers and in the `AutoCompleteBookings` command.
- The timer is stored as a **timestamp** on the booking: `auto_complete_at`. When that date/time has passed, the scheduled command can set the booking to complete.

### How the timer starts (first time)

The timer **starts** when a booking is first tied to an SOA:

- **SOA created:** When a Statement of Account is **created** and includes one or more booking IDs, the system sets `auto_complete_at = now() + 3 weeks` for each of those bookings (only if they are not already complete).
- So: “3 weeks from the first SOA that includes this booking” is when the auto-complete is due, unless something resets or clears the timer.

### How the timer resets (pushed forward)

The **same 3-week window** is applied again from “now” when any of the following happens (so the due date is pushed 3 weeks forward):

1. **SOA updated**  
   When an SOA is **updated**, the timer is reset for **all bookings currently in that SOA** (again to `now() + 3 weeks`). So any edit to the SOA that contains the booking pushes the auto-complete date out.

2. **Booking updated**  
   When the **booking** itself is updated and the change is “meaningful” (any field except `auto_complete_at`, `is_complete`, `updated_at`, `created_at`, `deleted_at`), the timer is reset to `now() + 3 weeks`. So ongoing changes to the booking keep pushing the due date out.

3. **Waybill detail saved/deleted/restored**  
   When a waybill detail linked to the booking is saved, deleted, or restored, the booking’s timer (if it exists) is reset to `now() + 3 weeks`.

4. **Container saved/deleted**  
   When a container linked to the booking is saved or deleted, the booking’s timer (if it exists) is reset to `now() + 3 weeks`.

In all these cases, the system only updates bookings that are **not** already complete and that **already have** an `auto_complete_at` set; it does not start a new timer for bookings that never had one.

### How the timer is cleared (stopped)

The timer is **removed** (`auto_complete_at` set to `null`) when the booking no longer has any SOA pointing to it:

- **Booking removed from an SOA:** When an SOA is **updated** and a booking is **removed** from its `booking_ids`, the system checks whether **any other** (non-deleted) SOA still references that booking. If **none** do, it sets `auto_complete_at = null` for that booking (only if the booking is not complete). So the auto-complete timer is cleared when the booking is no longer on any SOA.

- **SOA deleted:** When an SOA is **deleted**, for each booking that was in that SOA, the system checks whether any other SOA still references it. If **none** do, it sets `auto_complete_at = null` for that booking (only if not complete). So deleting the only SOA that contained the booking clears the timer.

If the booking is still referenced by at least one other SOA, the timer is **not** cleared; it can still run or be reset by other SOA/booking/waybill/container events.

### When auto-complete actually runs (3 weeks “due”)

- A **scheduled command** runs periodically: `php artisan bookings:auto-complete` (configured in `app/Console/Kernel.php` to run **hourly**).
- The command finds bookings where:
  - `is_complete = false`,
  - `auto_complete_at` is not null,
  - `auto_complete_at <= now()` (the 3-week due time has passed).
- For those bookings it sets:
  - `is_complete = true`,
  - `auto_complete_at = null`.

So in practice: the timer **starts** when the booking is first put on an SOA (or when the timer is set), can be **reset** many times by SOA/booking/waybill/container activity, and when it is **not** reset for 3 weeks and the command runs, the booking is marked complete.

---

## Summary table

| Trigger              | When it runs                          | Effect                                      |
|----------------------|----------------------------------------|---------------------------------------------|
| **Invoice creation** | User creates an invoice from an SOA   | All bookings in that SOA → `is_complete = true` (immediate). |
| **Auto-complete**    | Scheduled command, after 3 weeks due | Bookings with `auto_complete_at <= now()` → `is_complete = true`, `auto_complete_at = null`. |

- **Timer starts:** When an SOA that includes the booking is **created** (or when an update sets/resets the timer).
- **Timer resets (3 weeks from now):** SOA updated (with that booking), booking meaningfully updated, waybill detail or container saved/deleted/restored.
- **Timer cleared:** Booking removed from every SOA (or the only SOA containing it is deleted), so no SOA references it.

---

## Scope / notes (both triggers)

- One SOA can include **multiple bookings**; invoice creation marks **all** of them complete; the 3-week timer is **per booking**.
- Completion is only set to `true`; the system does not automatically revert to incomplete if an invoice is changed or removed, or if an SOA is later edited.
- The 3-week value is defined in: `StatementOfAccountObserver`, `BookingObserver`, `WaybillDetailObserver`, `ContainerObserver`, and `AutoCompleteBookings` command.
