# Budget System

## Overview

Budget-related data lives in **one table per category** (issued budget, truck trip expense, parts, stack run, driver/helper cash advance). There is no central `budget_transactions` table. Each table has a **shift** column (string, nullable, e.g. `"Day"`, `"Night"`, `"1st"`, `"2nd"`).

**Note:** The `budget_history` migration exists but is **disabled** (table creation commented out); it is not in use.

**Date columns:** All budget tables use **`transaction_date`** (including `issued_budget`; formerly `date_issued`).

---

## Budget Tables

| Table | Purpose |
|--------|---------|
| `issued_budget` | Budget added/issued |
| `truck_trip_expense` | Truck trip expenses (helper-linked) |
| `parts_expense` | Parts & maintenance (fleet_truck-linked) |
| `funds_for_stack_run` | Stack run funds |
| `driver_cash_advancement_history` | Driver cash advances |
| `helper_cash_advancement_history` | Helper cash advances |

---

## Table Schemas (from migrations)

| Table | Date column | Other columns |
|-------|-------------|----------------|
| `issued_budget` | `transaction_date` | shift, amount, source |
| `truck_trip_expense` | `transaction_date` | shift, helper_id, cash_on_hand, issued_cash_amount |
| `parts_expense` | `transaction_date` | shift, plate_number, receipt_no, quantity, article, amount_per_item |
| `funds_for_stack_run` | `transaction_date` | shift, remarks, amount |
| `driver_cash_advancement_history` | `transaction_date` | amount, shift, driver_id |
| `helper_cash_advancement_history` | `transaction_date` | amount, shift, helper_id |

All tables: `id` (bigint PK), `timestamps`, `softDeletes`. Amounts are `decimal(15,2)`. Indexes include the date column, `shift`, and FKs where present (see migrations for full index names).

---

## Shift

- **Type:** `string`, nullable  
- **Allowed values:** `Rule::in(['Day', 'Night', '1st', '2nd'])`  
- Stored on every budget-related table. Used in cash advance create/update validation.

---

## Cash Advance – API & Implementation

### Models
- **DriverCAHistory** → table `driver_cash_advancement_history`; `belongsTo` Driver.
- **HelperCAHistory** → table `helper_cash_advancement_history`; `belongsTo` Helper.

### API Endpoints

| Method | Route | Controller | Description |
|--------|--------|------------|-------------|
| GET | `/api/drivers/get-cash-advance/{id}` | DriverCAHistoryController@getCashAdvances | Paginated driver cash advance history by driver ID |
| GET | `/api/helpers/get-cash-advance/{id}` | HelperCAHistoryController@getHelperCashAdvances | Paginated helper cash advance history by helper ID |

**Query parameters (both endpoints):**
- `per_page` (optional, default 10) – Pagination size (1–100).
- `search` (optional) – Filter by shift, amount, or date (YYYY-MM-DD).
- `filter_type` (optional, driver only) – `weekly` (default) or `monthly`; defines range from `reference_date`.
- `reference_date` (optional, driver only) – Anchor date (YYYY-MM-DD). With `weekly`: Monday–Sunday; with `monthly`: first–last day of month.

**Response:** Paginated collection of cash advance records (via DriverCAHistoryResource / HelperCAHistoryResource), with `status_code` and `message` in `additional`.

**Resource fields (driver):** `id`, `amount`, `transaction_date`, `transaction_date_formatted`, `shift`, `driver_id`, `driver_name` (when loaded), `created_at`, `updated_at`, `deleted_at`.

**Resource fields (helper):** Same shape with `helper_id` and `helper_name` instead of driver.

### Services
- **DriverCAHistoryService** – `getDriverHistory($driverId, $perPage, $search, $dateFrom, $dateTo)` with date range and search.
- **HelperCAHistoryService** – `getHelperHistory($helperId, $perPage, $searchTerm, $dateFrom, $dateTo)` with date range and search.

### Validation (create/update)
- **Driver:** `driver_id` (required, exists:drivers), `amount` (required, numeric, 0–999999.99), `transaction_date` (required, date, before_or_equal:today), `shift` (required, in: Day, Night, 1st, 2nd).
- **Helper:** Same for `helper_id` (exists:helpers), `amount`, `transaction_date`, `shift`.

*(A standalone POST/PUT cash advance route is currently removed; the above validation is defined in DriverCAHistoryRequest / HelperCAHistoryRequest for future use.)*

---

## Shift budget balance (remaining COH per shift)

Recorded closing balance per **`transaction_date` + `shift`**, stored in **`shift_budget_balances`**. Source rows remain in the six budget tables; balances are aggregated and chained across shifts.

**Shift order (carryover):** `Day` → `Night` on the same calendar day, then next calendar `Day` (e.g. May 27 Night → May 28 Day). Rows labeled `1st` / `2nd` use the same slots as `Day` / `Night` for carryover only.

**Computation (per shift):**

- `issued_budget` = sum of `issued_budget.amount` for that date + shift  
- `carried_from_previous` = previous shift’s `remaining_coh` (0 if none)  
- `total_budget` = `issued_budget` + `carried_from_previous`  
- `total_expense` = sum of truck trip `issued_cash_amount`, parts `quantity × amount_per_item`, stack run, driver/helper cash advances (same rules as budget summary)  
- `remaining_coh` = `total_budget` − `total_expense` (= `cash_on_hand` in API responses)

Balances are recalculated automatically when any of the six source tables change (create/update/delete/restore), cascading forward to later shifts. Use **POST** recalculate for backfill.

### API

| Method | Route | Description |
|--------|--------|-------------|
| GET | `/api/budget/shift-balances` | Paginated list (`transaction_date`, `transaction_date_from`/`_to`, `shift`) |
| GET | `/api/budget/shift-balances/show` | Query: `transaction_date`, `shift` — refresh and return one balance |
| POST | `/api/budget/shift-balances/recalculate` | Body: `transaction_date` + `shift`, or `recalculate_all: true` |

---

## Budget Summary API

**GET** `/api/budget/summary` – Consolidated list from all six budget tables plus totals.

**Query parameters:**
- `shift` – `Day`, `Night`, or `All` (default)
- `transaction_date_from`, `transaction_date_to` – Transaction date range
- `created_at_from`, `created_at_to` – Created-at range
- `per_page` – Pagination size (default 10)

**Response:** Each row has a `type` and shared fields; missing data is `null`.
- `type`: `"Budget"` (issued_budget), `"Truck Expense"` (truck_trip_expense), `"Parts Expense"` (parts_expense), `"Other Expense"` (funds_for_stack_run), `"Driver Cash Advance"`, `"Helper Cash Advance"`
- `total_budget` – Sum of income (Budget) in filtered data
- `total_expense` – Sum of expenses in filtered data
- `cash_on_hand` – Total Budget − Total Expense for the filtered set

---

## Reference

- **Relationships:** Driver → driver_cash_advancement_history; Helper → helper_cash_advancement_history, truck_trip_expense; Fleet truck (plate_number) → parts_expense.
- **Migrations:** Run with `php artisan migrate` (or `migrate:refresh`). Budget tables: `issued_budget`, `truck_trip_expense`, `parts_expense`, `funds_for_stack_run`, `driver_cash_advancement_history`, `helper_cash_advancement_history`, `shift_budget_balances`. The `budget_history` migration is present but disabled (no table created).
- **Conventions:** Amounts use `decimal(15,2)`; all budget tables use soft deletes and include `shift` where applicable. **Date columns:** All tables use `transaction_date`.
