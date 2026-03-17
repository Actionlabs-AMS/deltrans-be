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
| `sales_overview` | object | See [Sales overview](#12-sales-overview) below |
| `overdue_payments` | array | See [Overdue payments](#13-overdue-payments) below |

**How we pull/compute:**

- **Date range:** From `resolveDateRange($filters)`:
  - `date` → single day (start = end = that day).
  - `date_from` / `date_to` → custom range (order corrected if swapped).
  - `year` → Jan 1 – Dec 31 of that year.
  - No params → current month (start of month → end of month).
- **KPIs:** `getKpis($filters)` — see below.
- **Sales overview:** `getSalesOverview($filters)` — see below.
- **Overdue payments:** `getOverduePayments()` — no date filter; see below.

---

### 1.2 KPIs (`data.kpis`)

**Data returned:**

| Field | Type | Description |
|-------|------|-------------|
| `shipping_line_count` | integer | Total count of shipping lines (not filtered by date range) |
| `waybills_completed` | integer | Waybill details in range where related `booking.is_complete = true` |
| `waybills_total` | integer | All waybill details in range |
| `sales` | number | Total "total amount due" in range (same logic as invoice PDF) |
| `waybill_expenses` | number | total_expense + actual_truck_trip_expense_amount + diesel_expense.amount for waybills in range |
| `parts_expense` | number | Sum of `quantity * amount_per_item` in parts_expense in range |
| `diesel_expense` | number | Sum of diesel_expenses.amount for waybills in range |

**How we pull/compute:**

| KPI | Pull/Computation |
|-----|-------------------|
| **shipping_line_count** | **shipping_lines** table **count** (no date filter). |
| **waybills_completed** | **waybill_details** where `transaction_date` in range + `whereHas('booking', is_complete = true)` → **count**. |
| **waybills_total** | **waybill_details** where `transaction_date` in range → **count**. |
| **sales** | **invoices** where `date` in range → for each invoice, `InvoiceService::getComputedTotals(statement_of_account_id, discount)` (same as invoice PDF); sum **total_amount**. |
| **waybill_expenses** | **waybill_details** in range: `SUM(total_expense) + SUM(actual_truck_trip_expense_amount)` + **diesel_expense** total (see below). |
| **parts_expense** | **parts_expense** where `transaction_date` in range → `SUM(quantity * amount_per_item)`. |
| **diesel_expense** | **diesel_expenses** joined to **waybill_details** on `diesel_expense_id`, `waybill_details.deleted_at IS NULL`, `transaction_date` in range → **SUM(diesel_expenses.amount)**. |

---

### 1.3 Sales overview (`data.sales_overview`)

**Data returned:**

| Field | Type | Description |
|-------|------|-------------|
| `months` | array of strings | Month keys `"Y-m"` (e.g. `["2025-01","2025-02"]`) |
| `income` | array of numbers | Sales (total amount due) per month from invoice date (same order as `months`) |
| `waybill_expenses` | array of numbers | Waybill expenses per month from waybill transaction_date (same order as `months`) |

**How we pull/compute:**

- **months:** `CarbonPeriod` from range start-of-month to end-of-month, step 1 month → format `Y-m`.
- **income:** Same as **sales** but grouped by month: for each **invoice** in range, `InvoiceService::getComputedTotals(soa_id, discount)` → **total_amount**; group by `Y-m` of **invoice.date** → fill `income` array.
- **waybill_expenses:** Per-month from **waybill_details** `transaction_date`: `SUM(total_expense) + SUM(actual_truck_trip_expense_amount)` by `Y-m`, plus **diesel_expenses** by month (join to waybill_details, same date filter); merge by month key; fill array (months with no data = 0).

---

### 1.4 Overdue payments (`data.overdue_payments`)

**Data returned:** Array of objects:

| Field | Type | Description |
|-------|------|-------------|
| `shipping_line_name` | string | From SOA → shipping_line |
| `transaction_no` | string | Billing statement number |
| `overdue_payment_date` | string (date) | Due date |
| `overdue_payment_amount` | number | SOA amount (computed) |
| `billing_statement_id` | integer | PK |
| `statement_of_account_id` | integer | FK |

**How we pull/compute:**

- **Source:** **billing_statements** where `is_paid = false` and `due_date < today`, ordered by `due_date`, with `statementOfAccount.shippingLine`.
- **Amount:** For each statement’s `statement_of_account_id`, amount = sum of **waybill_details** `total_rate_per_client` for bookings in that SOA’s `booking_ids` (same `getStatementOfAccountAmounts` logic as sales).

---

### 1.5 Stats (widgets) — `GET /api/dashboard/stats`

**Data returned:** Single object with the same KPIs as above:

- `shipping_line_count`, `waybills_completed`, `waybills_total`, `sales`, `waybill_expenses`, `parts_expense`, `diesel_expense`

**How we pull/compute:** Same as [KPIs](#12-kpis) (uses same date filters).

---

## 2. Other dashboard endpoints (same data, different shape)

- **`GET /api/dashboard/kpis`** — Returns `date_from`, `date_to`, `kpis` (same KPIs and computation).
- **`GET /api/dashboard/sales-overview`** — Returns `sales_overview` only (same computation).
- **`GET /api/dashboard/overdue-payments`** — Returns `overdue_payments` only (same computation).

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
