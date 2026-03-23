# API Data Returned & How We Pull/Compute It

This document summarizes the **data returned** by key Deltrans API endpoints and **how that data is pulled or computed** (database queries and business logic).

---

## 1. Dashboard API

**Base path:** `GET /api/dashboard` (and sub-routes).  
**Auth:** `auth:sanctum`.  
**Query params (optional):** `date`, `date_from`, `date_to`, `year`.

### 1.1 Combined dashboard — `GET /api/dashboard`

**Data returned:**

| Field | Type | Description |
|-------|------|-------------|
| `date_from` | string (date) | Start of applied date range |
| `date_to` | string (date) | End of applied date range |
| `kpis` | object | See [KPIs](#11-kpis) below |
| `sales_overview` | array | See [Sales overview](#12-sales-overview) below |
| `overdue_count` | integer | Count of overdue items (same filters as overdue_payments) |
| `overdue_payments` | array | See [Overdue payments](#13-overdue-payments) below |
| `diesel_by_plate` | array | Diesel totals grouped by `waybill_details.truck_plate_number` (same date range as KPI diesel; see [Diesel by plate](#16-diesel-by-plate-warehouse)) |
| `receivables_by_entity` | object | `total` + `entities[]` with `shipping_line_name` and `amount` (invoice totals in range; same underlying amounts as `kpis.sales` when summed) |
| `budget_category_totals` | object | Issued budget + expense buckets from budget tables for the same date range (see [Budget category totals](#17-budget-category-totals--daily-chart)) |
| `daily_budget_chart` | array | One `{ date, income, expense }` per day in range — see [Budget category totals](#17-budget-category-totals--daily-chart) |

**How we pull/compute:**

- **Date range:** From `resolveDateRange($filters)`:
  - `date` → single day (start = end = that day).
  - `date_from` / `date_to` → custom range (order corrected if swapped).
  - `year` → Jan 1 – Dec 31 of that year.
  - No params → current month (start of month → end of month).
- **KPIs:** `getKpis($filters)` — see below.
- **Sales overview:** `getSalesOverview($filters)` — see below.
- **Overdue count:** `count(getOverduePayments($filters))`.
- **Overdue payments:** `getOverduePayments($filters)` — uses filter end date as "as of" for overdue; see below.
- **Warehouse widgets:** `diesel_by_plate`, `receivables_by_entity`, `budget_category_totals`, `daily_budget_chart` — see sections 1.6–1.7.

---

### 1.2 KPIs (`data.kpis`)

**Data returned:**

| Field | Type | Description |
|-------|------|-------------|
| `shipping_line_count` | integer | Total count of shipping lines (not filtered by date range) |
| `waybills_completed` | integer | Waybill details in range where related `booking.is_complete = true` |
| `waybills_total` | integer | All waybill details in range |
| `waybills_remaining` | integer | `waybills_total - waybills_completed` (not below zero) |
| `sales` | number | Total "total amount due" in range (same logic as invoice PDF) |
| `waybill_expenses` | number | total_expense + actual_truck_trip_expense_amount + diesel_expense.amount for waybills in range |
| `parts_expense` | number | Sum of `quantity * amount_per_item` in parts_expense in range |
| `diesel_expense` | number | Sum of diesel_expenses.amount for waybills in range |
| `overdue_count` | integer | Count of overdue items (billing due_date or SOA+waybill no_of_days before filter end; same as `data.overdue_payments` length) |

**How we pull/compute:**

| KPI | Pull/Computation |
|-----|-------------------|
| **shipping_line_count** | **shipping_lines** table **count** (no date filter). |
| **waybills_completed** | **waybill_details** where `transaction_date` in range + `whereHas('booking', is_complete = true)` → **count**. |
| **waybills_total** | **waybill_details** where `transaction_date` in range → **count**. |
| **waybills_remaining** | **waybills_total** − **waybills_completed** (floored at 0). |
| **sales** | **invoices** where `date` in range → for each invoice, `InvoiceService::getComputedTotals(statement_of_account_id, discount)` (same as invoice PDF); sum **total_amount**. |
| **waybill_expenses** | **waybill_details** in range: `SUM(total_expense) + SUM(actual_truck_trip_expense_amount)` + **diesel_expense** total (see below). |
| **parts_expense** | **parts_expense** where `transaction_date` in range → `SUM(quantity * amount_per_item)`. |
| **diesel_expense** | **diesel_expenses** joined to **waybill_details** on `diesel_expense_id`, `waybill_details.deleted_at IS NULL`, `transaction_date` in range → **SUM(diesel_expenses.amount)**. |
| **overdue_count** | **count** of `getOverduePayments($filters)` (billing statements with `due_date` &lt; filter end, plus SOAs without billing where soa.created_at + waybill no_of_days &lt; filter end). |

---

### 1.3 Sales overview (`data.sales_overview`)

**Data returned:** An **array of objects**, one per month in the filter range (chronological), each with:

| Field | Type | Description |
|-------|------|-------------|
| `month` | string | Month key `Y-m` (e.g. `2025-08`) |
| `income` | number | Sales (total amount due) for that month from **invoice.date** (`InvoiceService::getComputedTotals`) |
| `waybill_expenses` | number | Waybill expenses for that month from **waybill_details.transaction_date** + diesel tied to those waybills |

**How we pull/compute:**

- **Month list:** `CarbonPeriod` from range start-of-month to end-of-month, step 1 month → format `Y-m`; one row per month.
- **income:** For each **invoice** in range, `InvoiceService::getComputedTotals(soa_id, discount)` → **total_amount**; group by `Y-m` of **invoice.date**.
- **waybill_expenses:** Per month from **waybill_details** `transaction_date`: `SUM(total_expense) + SUM(actual_truck_trip_expense_amount)` by `Y-m`, plus **diesel_expenses** by month (join to waybill_details); merge by month key (months with no data = 0).

`GET /api/dashboard/sales-overview` returns `{ "sales_overview": [ ... ] }` with the same row shape.

---

### 1.4 Overdue payments (`data.overdue_count`, `data.overdue_payments`)

**Data returned:** `overdue_count` (integer) and an array of objects:

| Field | Type | Description |
|-------|------|-------------|
| `shipping_line_name` | string | From SOA → shipping_line |
| `transaction_no` | string | Billing statement number or SOA `dli_sa_number` (or `SOA-{id}` when no billing) |
| `overdue_payment_date` | string (date) | Due date (from billing or SOA created_at + waybill no_of_days) |
| `overdue_payment_amount` | number | SOA amount (computed) |
| `billing_statement_id` | integer \| null | Billing statement PK, or null for SOAs without billing |
| `statement_of_account_id` | integer | SOA FK |
| `due_date` | string (date) | Same as `overdue_payment_date` (Figma-friendly alias) |
| `soa_number` | string | SOA `dli_sa_number` when present; for billing rows may fall back to billing statement number |

**How we pull/compute:**

- **"As of" date:** End of the dashboard date range from filters (same as `date_to` / year end / single date).
- **Part 1 — SOAs with billing_statements:** **billing_statements** where `is_paid = false`, `due_date` is not null, and `due_date < as_of_date`, ordered by `due_date`, with `statementOfAccount.shippingLine`. Each row uses the billing statement’s due date and SOA amount.
- **Part 2 — SOAs with no billing_statements:** **statement_of_accounts** that have no **billing_statements** (via `whereDoesntHave('billingStatements')`). For each such SOA, get waybills via SOA → Booking → **waybill_details** (using `booking_ids`). For each waybill, due date = `statement_of_account.created_at + waybill_details.no_of_days` (in days). If that due date is **before** the as_of date, the SOA is overdue. One row per such SOA: `transaction_no` = SOA `dli_sa_number` or `SOA-{id}`, `overdue_payment_date` = earliest such due date among its waybills, `billing_statement_id` = null.
- **Amount:** For each row’s `statement_of_account_id`, amount = sum of **waybill_details** `total_rate_per_client` for bookings in that SOA’s `booking_ids` (same `getStatementOfAccountAmounts` logic as sales).
- **overdue_count:** Number of items in the `overdue_payments` array.

---

### 1.5 Stats (widgets) — `GET /api/dashboard/stats`

**Data returned:** Single object with the same KPIs as above:

- `shipping_line_count`, `waybills_completed`, `waybills_total`, `waybills_remaining`, `sales`, `waybill_expenses`, `parts_expense`, `diesel_expense`, `overdue_count`

**How we pull/compute:** Same as [KPIs](#12-kpis) (uses same date filters).

---

### 1.6 Diesel by plate (warehouse) — `data.diesel_by_plate`

**Data returned:** Array of `{ truck_plate_number, amount }` where `amount` is the sum of `diesel_expenses.amount` for waybills whose `transaction_date` is in the dashboard range. Plates empty or null are grouped as `Unassigned`.

**How we pull/compute:** `diesel_expenses` joined to `waybill_details` on `diesel_expense_id`, filtered by `waybill_details.transaction_date`, grouped by normalized plate.

---

### 1.7 Budget category totals & daily chart — `data.budget_category_totals`, `data.daily_budget_chart`

**`budget_category_totals`** (all use **`transaction_date`** between `date_from` and `date_to`):

| Key | Source |
|-----|--------|
| `issued_budget` | `issued_budget.amount` |
| `truck_trip_budget` | `truck_trip_expense.issued_cash_amount` |
| `parts` | `parts_expense`: `quantity × amount_per_item` |
| `others` | `funds_for_stack_run.amount` |
| `driver_cash_advance` | `driver_cash_advancement_history.amount` |
| `helper_cash_advance` | `helper_cash_advancement_history.amount` |

**`daily_budget_chart`:** Array of objects, one per calendar day (in order), each with **`date`** (`Y-m-d`), **`income`** (sum of invoice `total_amount` that day via `invoices.date`), and **`expense`** (sum of the five expense categories that day via each row’s **`transaction_date`**).

**Budget summary list:** `GET /api/budget/summary` also returns `category_totals` for the filtered rows. When `transaction_date_from` and `transaction_date_to` are both set, it also returns `daily_budget_chart` for that range. **`date_from` / `date_to`** query params are accepted as aliases for `transaction_date_from` / `transaction_date_to` so the same range as the dashboard can be used.

---

## 2. Other dashboard endpoints (same data, different shape)

- **`GET /api/dashboard/kpis`** — Returns `date_from`, `date_to`, `kpis` (same KPIs and computation).
- **`GET /api/dashboard/sales-overview`** — Returns `sales_overview` only (same computation).
- **`GET /api/dashboard/overdue-payments`** — Accepts same date params; returns `overdue_count` and `overdue_payments` (same computation).

---

## 3. Tables and models used for dashboard

| Table / Model | Usage |
|---------------|--------|
| **waybill_details** | Transaction date filter, waybill counts, waybill expenses (`total_expense`, `actual_truck_trip_expense_amount`), diesel link (`diesel_expense_id`). |
| **bookings** | `is_complete` for “completed” waybills. |
| **invoices** | Date range; sales = InvoiceService getComputedTotals (total amount due) per invoice. |
| **statement_of_accounts** | Used by InvoiceService for total amount due; also `booking_ids` for overdue amount. |
| **billing_statements** | Overdue list; SOA and shipping line. |
| **shipping_lines** | Total count for `shipping_line_count` (no date filter); name on overdue rows. |
| **parts_expense** | `quantity * amount_per_item` by `transaction_date`. |
| **diesel_expenses** | Joined via `waybill_details.diesel_expense_id`; sum `amount` by waybill `transaction_date`. |

---

## 4. Other APIs (high level)

- **CRUD-style endpoints** (e.g. users, waybills, drivers): Return data from **API Resources** (e.g. `WaybillDetailResource`). Data is **pulled** from the corresponding Eloquent model(s), often with `with()` for relations; **no extra aggregation** beyond what’s in the DB and resource.
- **Waybill details** expose `diesel_expense_id`, `actual_truck_trip_expense_amount`, and when loaded `diesel_expense_amount` from `dieselExpense.amount`; create/update can accept `diesel_expense_amount` and create/update a **diesel_expenses** row and set `diesel_expense_id`.
- **Reports / budget / SOA:** Typically use dedicated services that query the same tables (waybills, SOA, invoices, etc.) and return aggregated or formatted data; logic is in the corresponding service class.

For full request/response shapes, see the generated OpenAPI spec (e.g. `storage/api-docs/api-docs.json` or Swagger UI).
