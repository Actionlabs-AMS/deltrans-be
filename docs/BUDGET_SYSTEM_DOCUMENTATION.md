# Budget System Documentation

## Overview

The Budget System tracks and manages budget transactions and expenses in the Deltrans backend. A central table (`budget_transactions`) records each transaction by type; linked detail tables store type-specific data. **Type 4 (Advance Expense)** uses two tables—`driver_cash_advancement_history` and `helper_cash_advancement_history`—both linked via `budget_transaction_id`; the choice of table is determined by **type** (1 = driver, 2 = helper) when inserting. Shift is stored on `budget_transactions` and can be read by joining on `budget_transaction_id`.

## Database Schema

### 1. budget_transactions

The main table that tracks all budget transactions.

| Column             | Type          | Description                                                                                                                                       |
| ------------------ | ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`               | bigIncrements | Primary key                                                                                                                                       |
| `shift`            | tinyInteger   | Shift type: `0` = morning, `1` = night                                                                                                            |
| `transaction_type` | tinyInteger   | Type of transaction:<br>`0` = add budget<br>`1` = truck_trip expense<br>`2` = parts expense<br>`3` = funds for stack run<br>`4` = advance expense |
| `description`      | text          | Description of the transaction                                                                                                                    |
| `created_at`       | timestamp     | Record creation timestamp                                                                                                                         |
| `updated_at`       | timestamp     | Record update timestamp                                                                                                                           |
| `deleted_at`       | timestamp     | Soft delete timestamp                                                                                                                             |

**Indexes:**

- `shift`
- `transaction_type`

**Note:** The `transaction_type` field references the `transaction_types` reference table for lookup values.

### 2. transaction_types (Reference Table)

Reference table for transaction type codes and their corresponding detail tables.

| Column         | Type             | Description                                               |
| -------------- | ---------------- | --------------------------------------------------------- |
| `type`         | tinyInteger (PK) | Transaction type code: `0-4`                              |
| `name`         | string           | Human-readable transaction type name                      |
| `detail_table` | string           | Name of the detail table for this transaction type        |
| `description`  | text             | Additional description of the transaction type (nullable) |
| `is_active`    | boolean          | Whether this transaction type is active                   |
| `created_at`   | timestamp        | Record creation timestamp                                 |
| `updated_at`   | timestamp        | Record update timestamp                                   |
| `deleted_at`   | timestamp        | Soft delete timestamp                                     |

**Default Values:**

- Type `0`: Add Budget → `issued_budget`
- Type `1`: Truck Trip Expense → `truck_trip_expense`
- Type `2`: Parts Expense → `parts_expense`
- Type `3`: Funds for Stack Run → `funds_for_stack_run`
- Type `4`: Advance Expense → `driver_cash_advancement_history` and `helper_cash_advancement_history` (use **type** 1 = driver, 2 = helper; fetch by joining both tables)

**Indexes:**

- `is_active`
- `name` (unique)

### 3. issued_budget

Tracks budget issuance transactions (when budget is added/issued).

| Column                  | Type            | Description                           |
| ----------------------- | --------------- | ------------------------------------- |
| `id`                    | bigIncrements   | Primary key                           |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id` |
| `date_issued`           | date            | Date when budget was issued           |
| `amount`                | decimal(15,2)   | Amount issued                         |
| `source`                | string          | Source of the budget (nullable)       |
| `created_at`            | timestamp       | Record creation timestamp             |
| `updated_at`            | timestamp       | Record update timestamp               |
| `deleted_at`            | timestamp       | Soft delete timestamp                 |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)

**Indexes:**

- `budget_transaction_id`
- `date_issued`

### 4. truck_trip_expense

Tracks expenses related to truck trips.

| Column                  | Type            | Description                           |
| ----------------------- | --------------- | ------------------------------------- |
| `id`                    | bigIncrements   | Primary key                           |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id` |
| `helper_id`             | bigInteger (FK) | Reference to `helpers.id` (nullable)  |
| `cash_on_hand`          | decimal(15,2)   | Current money available               |
| `issued_cash_amount`    | decimal(15,2)   | Amount of cash issued                 |
| `date_issued`           | date            | Date when cash was issued             |
| `created_at`            | timestamp       | Record creation timestamp             |
| `updated_at`            | timestamp       | Record update timestamp               |
| `deleted_at`            | timestamp       | Soft delete timestamp                 |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)
- `helper_id` → `helpers.id` (SET NULL ON DELETE)

**Indexes:**

- `budget_transaction_id`
- `helper_id`
- `date_issued`

### 5. parts_expense

Tracks expenses for vehicle parts and maintenance.

| Column                  | Type            | Description                                         |
| ----------------------- | --------------- | --------------------------------------------------- |
| `id`                    | bigIncrements   | Primary key                                         |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id`               |
| `plate_number`          | string (FK)     | Reference to `fleet_trucks.plate_number` (nullable) |
| `receipt_no`            | string          | Receipt number (nullable)                           |
| `quantity`              | integer         | Quantity of items purchased                         |
| `article`               | string          | Description of the article/part (nullable)          |
| `amount_per_item`       | decimal(15,2)   | Cost per item                                       |
| `date`                  | date            | Date of purchase                                    |
| `created_at`            | timestamp       | Record creation timestamp                           |
| `updated_at`            | timestamp       | Record update timestamp                             |
| `deleted_at`            | timestamp       | Soft delete timestamp                               |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)
- `plate_number` → `fleet_trucks.plate_number` (SET NULL ON DELETE)

**Indexes:**

- `budget_transaction_id`
- `plate_number`
- `date`
- `receipt_no`

**Calculated Fields:**

- Total amount = `quantity * amount_per_item`

### 6. funds_for_stack_run

Tracks funds allocated for stack run operations.

| Column                  | Type            | Description                                 |
| ----------------------- | --------------- | ------------------------------------------- |
| `id`                    | bigIncrements   | Primary key                                 |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id`       |
| `remarks`               | text            | Remarks or notes about the funds (nullable) |
| `amount`                | decimal(15,2)   | Total amount of funds                       |
| `date`                  | date            | Date of the transaction                     |
| `created_at`            | timestamp       | Record creation timestamp                   |
| `updated_at`            | timestamp       | Record update timestamp                     |
| `deleted_at`            | timestamp       | Soft delete timestamp                       |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)

**Indexes:**

- `budget_transaction_id`
- `date`

### 7. driver_cash_advancement_history (Type 4 – driver)

Cash advance records for **drivers**. Each row links to a budget transaction (type 4). Shift is also on `budget_transactions`; this table can store a label (e.g. "Day"/"Night") for display.

| Column                  | Type            | Description                                    |
| ----------------------- | --------------- | ---------------------------------------------- |
| `id`                    | bigIncrements   | Primary key                                    |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id`          |
| `amount`                | decimal(15,2)   | Cash advance amount                            |
| `transaction_date`      | date            | Date of the advance                            |
| `shift`                 | string          | Shift label (e.g. Day/Night) – nullable        |
| `driver_id`             | bigInteger (FK) | Reference to `drivers.id`                      |
| `created_at`            | timestamp       | Record creation timestamp                      |
| `updated_at`            | timestamp       | Record update timestamp                        |
| `deleted_at`            | timestamp       | Soft delete timestamp                          |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)
- `driver_id` → `drivers.id` (CASCADE DELETE)

**Indexes:** `budget_transaction_id`, `transaction_date`, `driver_id`, `shift`

### 8. helper_cash_advancement_history (Type 4 – helper)

Cash advance records for **helpers**. Same structure as driver table, with `helper_id` instead of `driver_id`.

| Column                  | Type            | Description                                    |
| ----------------------- | --------------- | ---------------------------------------------- |
| `id`                    | bigIncrements   | Primary key                                    |
| `budget_transaction_id` | bigInteger (FK) | Reference to `budget_transactions.id`          |
| `amount`                | decimal(15,2)   | Cash advance amount                            |
| `transaction_date`      | date            | Date of the advance                            |
| `shift`                 | string          | Shift label (e.g. Day/Night) – nullable        |
| `helper_id`             | bigInteger (FK) | Reference to `helpers.id`                      |
| `created_at`            | timestamp       | Record creation timestamp                      |
| `updated_at`            | timestamp       | Record update timestamp                        |
| `deleted_at`            | timestamp       | Soft delete timestamp                          |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)
- `helper_id` → `helpers.id` (CASCADE DELETE)

**Indexes:** `budget_transaction_id`, `transaction_date`, `helper_id`, `shift`

## Transaction Types

### Type 0: Add Budget

- **Table:** `issued_budget`
- **Purpose:** Records when budget is added or issued to the system
- **Key Fields:** `date_issued`, `amount`, `source`

### Type 1: Truck Trip Expense

- **Table:** `truck_trip_expense`
- **Purpose:** Tracks expenses related to truck trips, including helper assignments and cash management
- **Key Fields:** `helper_id`, `cash_on_hand`, `issued_cash_amount`, `date_issued`

### Type 2: Parts Expense

- **Table:** `parts_expense`
- **Purpose:** Records expenses for vehicle parts and maintenance items
- **Key Fields:** `plate_number`, `receipt_no`, `quantity`, `article`, `amount_per_item`, `date`

### Type 3: Funds for Stack Run

- **Table:** `funds_for_stack_run`
- **Purpose:** Tracks funds allocated for stack run operations
- **Key Fields:** `remarks`, `amount`, `date`

### Type 4: Advance Expense

- **Tables:** `driver_cash_advancement_history` (when **type** = 1), `helper_cash_advancement_history` (when **type** = 2)
- **Purpose:** Tracks cash advances given to drivers or helpers. One budget transaction (type 4) links to one row in either the driver or helper table.
- **Insert:** Use **type** to choose table: **1** = driver → insert into `driver_cash_advancement_history` with `driver_id`; **2** = helper → insert into `helper_cash_advancement_history` with `helper_id`. Always create a `budget_transactions` row first (type 4, shift), then insert the detail row with `budget_transaction_id`.
- **Fetch:** For “all type 4” data, query both tables and join to `budget_transactions` on `budget_transaction_id` (shift and description live on `budget_transactions`).
- **Key Fields:** `budget_transaction_id`, `amount`, `transaction_date`, `shift`; plus `driver_id` or `helper_id` depending on type.

## Shift Types

- **0** = Morning shift
- **1** = Night shift

## Cash Advance Type (for Type 4 – insert/fetch)

- **1** = Driver → use `driver_cash_advancement_history`; require `driver_id`
- **2** = Helper → use `helper_cash_advancement_history`; require `helper_id`

## Relationships

```
budget_transactions (1) ──→ (N) issued_budget
budget_transactions (1) ──→ (N) truck_trip_expense
budget_transactions (1) ──→ (N) parts_expense
budget_transactions (1) ──→ (N) funds_for_stack_run
budget_transactions (1) ──→ (N) driver_cash_advancement_history   (type 4, type=1)
budget_transactions (1) ──→ (N) helper_cash_advancement_history     (type 4, type=2)

drivers (1) ──→ (N) driver_cash_advancement_history
helpers (1) ──→ (N) helper_cash_advancement_history
helpers (1) ──→ (N) truck_trip_expense
fleet_trucks (1) ──→ (N) parts_expense
```

## Usage Guidelines

### Creating a Budget Transaction

1. **Create the main transaction record** in `budget_transactions`:
    - Set `shift` (0 for morning, 1 for night)
    - Set `transaction_type` (0-4 based on expense type)
    - Add `description`

2. **Create the corresponding detail record** in the appropriate table:
    - Type 0 → `issued_budget`
    - Type 1 → `truck_trip_expense`
    - Type 2 → `parts_expense`
    - Type 3 → `funds_for_stack_run`
    - Type 4 → `driver_cash_advancement_history` (if driver, **type** = 1) or `helper_cash_advancement_history` (if helper, **type** = 2). Use **POST /api/cash-advances** with `type`, `driver_id` or `helper_id`, `amount`, `transaction_date`, and optional `shift`, `description`.

### Querying Budget Data

When querying budget transactions, always join with the appropriate detail table based on `transaction_type`:

```php
// Example: Get all truck trip expenses
$transactions = BudgetTransaction::where('transaction_type', 1)
    ->with('truckTripExpense')
    ->get();
```

### Data Integrity

- All detail tables have foreign key constraints to `budget_transactions`
- When a `budget_transaction` is deleted, all related records in detail tables are cascade deleted
- Soft deletes are enabled on all tables for data recovery purposes

### Cash Advance API (Type 4)

- **Endpoint:** `POST /api/cash-advances`
- **Body:** `type` (required, 1 = driver, 2 = helper), `driver_id` (required when type = 1), `helper_id` (required when type = 2), `amount`, `transaction_date`, and optionally `shift` (0 = morning, 1 = night), `description`
- **Behaviour:** Creates a `budget_transactions` row (transaction_type = 4, shift from request), then inserts one row into `driver_cash_advancement_history` or `helper_cash_advancement_history` with the new `budget_transaction_id`.
- **Fetch driver advances:** Use existing driver cash advance endpoints (e.g. by driver ID). **Fetch helper advances:** Use existing helper cash advance endpoints. **Fetch all type 4:** Query both history tables and join to `budget_transactions` on `budget_transaction_id`.

## Migration Information

**Migration Files (in execution order):**

1. `2025_11_06_140926_create_budget_transactions_table.php`
    - Creates the `budget_transactions` table with the correct structure
    - Includes `shift` and `transaction_type` columns
    - Sets up indexes

2. `2026_01_27_131058_create_issued_budget_table.php`
    - Creates the `issued_budget` table for transaction type 0 (Add Budget)
    - Sets up foreign keys and indexes

3. `2026_01_27_131100_create_truck_trip_expense_table.php`
    - Creates the `truck_trip_expense` table for transaction type 1 (Truck Trip Expense)
    - Sets up foreign keys to `budget_transactions` and `helpers`
    - Sets up indexes

4. `2026_01_27_131101_create_parts_expense_table.php`
    - Creates the `parts_expense` table for transaction type 2 (Parts Expense)
    - Sets up foreign keys to `budget_transactions` and `fleet_trucks`
    - Sets up indexes

5. `2026_01_27_131102_create_funds_for_stack_run_table.php`
    - Creates the `funds_for_stack_run` table for transaction type 3 (Funds for Stack Run)
    - Sets up foreign keys and indexes

6. `2025_11_06_140928_create_driver_cash_advancement_history_table.php`
    - Creates `driver_cash_advancement_history` with `budget_transaction_id` (FK to `budget_transactions`), used for type 4 when **type** = 1 (driver)

7. `2025_11_06_140930_create_helper_cash_advancement_history_table.php`
    - Creates `helper_cash_advancement_history` with `budget_transaction_id` (FK to `budget_transactions`), used for type 4 when **type** = 2 (helper)

8. `2026_01_27_130858_create_transaction_types_table.php`
    - Creates the `transaction_types` reference table
    - Populates default transaction type data (types 0-4); type 4 is later updated to reference driver/helper cash advance tables

9. `2026_02_20_000001_drop_advance_expense_and_update_transaction_type_4.php`
    - Drops `advance_expense` table if it exists (legacy)
    - Updates `transaction_types` for type 4: `detail_table` = `driver_cash_advancement_history`, description notes that type 4 uses both driver and helper tables based on type 1/2

## Usage Examples

### Querying Transaction Types

```php
// Get all active transaction types
$types = DB::table('transaction_types')
    ->where('is_active', true)
    ->orderBy('type')
    ->get();

// Get transaction type by code
$type = DB::table('transaction_types')
    ->where('type', 1)
    ->first();

// Get detail table name for a transaction type
$detailTable = DB::table('transaction_types')
    ->where('type', $transactionType)
    ->value('detail_table');
```

### Using Transaction Types in Budget Transactions

```php
// When creating a budget transaction, reference the transaction_types table
$budgetTransaction = BudgetTransaction::create([
    'shift' => 0, // morning
    'transaction_type' => 1, // Truck Trip Expense
    'description' => 'Trip expense for helper assignment',
]);

// Get transaction type details
$transactionType = DB::table('transaction_types')
    ->where('type', $budgetTransaction->transaction_type)
    ->first();
```

## Notes

- **Type 4 (Advance Expense):** No single `advance_expense` table. Use `driver_cash_advancement_history` for drivers (**type** = 1) and `helper_cash_advancement_history` for helpers (**type** = 2). Both have `budget_transaction_id`; shift and description live on `budget_transactions`. When fetching “all advance expense” data, join both history tables to `budget_transactions`.
- All monetary values use `decimal(15,2)` for precision.
- All tables use soft deletes for data recovery and audit purposes.
- Timestamps (`created_at`, `updated_at`) are automatically managed by Laravel.
- The `transaction_types` table serves as a reference/lookup table. For type 4, `detail_table` is set to `driver_cash_advancement_history`; application logic uses **type** 1 or 2 to choose the correct table for insert and to join both tables when querying.
