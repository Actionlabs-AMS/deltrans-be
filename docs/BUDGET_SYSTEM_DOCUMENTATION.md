# Budget System Documentation

## Overview

The Budget System is designed to track and manage various types of budget transactions and expenses within the Deltrans backend application. The system consists of a main transaction table (`budget_transactions`) and several specialized tables for different expense types.

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
- Type `4`: Advance Expense → `advance_expense`

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

### 7. advance_expense

Tracks cash advances given to drivers or helpers.

| Column                     | Type            | Description                                                                                                                                                                                                           |
| -------------------------- | --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                       | bigIncrements   | Primary key                                                                                                                                                                                                           |
| `budget_transaction_id`    | bigInteger (FK) | Reference to `budget_transactions.id`                                                                                                                                                                                 |
| `employee_type`            | tinyInteger     | Type of employee:<br>`1` = driver<br>`2` = helper                                                                                                                                                                     |
| `driver_helper_id`         | bigInteger      | ID of the driver or helper based on `employee_type` (nullable)<br>**Note:** This references either `drivers.id` or `helpers.id` depending on `employee_type`. Foreign key constraint is handled at application level. |
| `cash_advance_given_today` | decimal(15,2)   | Amount of cash advance given                                                                                                                                                                                          |
| `date_issued`              | date            | Date when advance was issued                                                                                                                                                                                          |
| `created_at`               | timestamp       | Record creation timestamp                                                                                                                                                                                             |
| `updated_at`               | timestamp       | Record update timestamp                                                                                                                                                                                               |
| `deleted_at`               | timestamp       | Soft delete timestamp                                                                                                                                                                                                 |

**Foreign Keys:**

- `budget_transaction_id` → `budget_transactions.id` (CASCADE DELETE)
- `driver_helper_id` → Referenced at application level (either `drivers.id` or `helpers.id`)

**Indexes:**

- `budget_transaction_id`
- `employee_type`
- `driver_helper_id`
- `date_issued`

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

- **Table:** `advance_expense`
- **Purpose:** Tracks cash advances given to drivers or helpers
- **Key Fields:** `employee_type`, `driver_helper_id`, `cash_advance_given_today`, `date_issued`

## Shift Types

- **0** = Morning shift
- **1** = Night shift

## Employee Types (for Advance Expense)

- **1** = Driver
- **2** = Helper

## Relationships

```
budget_transactions (1) ──→ (N) issued_budget
budget_transactions (1) ──→ (N) truck_trip_expense
budget_transactions (1) ──→ (N) parts_expense
budget_transactions (1) ──→ (N) funds_for_stack_run
budget_transactions (1) ──→ (N) advance_expense

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
    - Type 4 → `advance_expense`

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

6. `2026_01_27_131104_create_advance_expense_table.php`
    - Creates the `advance_expense` table for transaction type 4 (Advance Expense)
    - Sets up foreign keys and indexes

7. `2026_01_27_130858_create_transaction_types_table.php`
    - Creates the `transaction_types` reference table
    - Populates default transaction type data (types 0-4)

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

- The `advance_expense.driver_helper_id` field can reference either `drivers.id` or `helpers.id` depending on `employee_type`. This is handled at the application level since MySQL doesn't support conditional foreign keys.
- All monetary values use `decimal(15,2)` for precision.
- All tables use soft deletes for data recovery and audit purposes.
- Timestamps (`created_at`, `updated_at`) are automatically managed by Laravel.
- The `transaction_types` table serves as a reference/lookup table and is populated with default values during migration. You can add more transaction types in the future if needed.
