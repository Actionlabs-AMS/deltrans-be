# Booking Completion on Invoice Creation

## Purpose

This document explains a system behavior: **when an invoice is created from a Statement of Account (SOA), the related booking(s) are automatically marked as Complete**.

## When it happens

This happens **after the user generates/creates an invoice** and selects a specific **SOA**.

## What the system does

Once the invoice is successfully created:

- The system looks at the SOA and checks which **booking ID(s)** are included in it.
- All of those booking records are updated to:
  - **Complete = Yes** (`is_complete = true`)

## Why this matters

This ensures that:

- Bookings included in an invoice are treated as **already processed for billing**.
- Dashboards and summaries that rely on booking completion status immediately reflect the latest state.
- Users do not need to manually update booking completion after invoicing.

## Scope / Notes

- One SOA can include **multiple bookings**. If so, **all** bookings listed in that SOA are marked complete when the invoice is created.
- This behavior only triggers when an invoice is created **from an SOA** (the invoice generation flow).
- This update sets bookings to Complete; it does not automatically revert bookings if an invoice is later changed or removed.

