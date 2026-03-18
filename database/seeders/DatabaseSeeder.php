<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            NavigationSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            OptionSeeder::class,
            SoaDataOptionSeeder::class,
            ShippingLineSeeder::class,
            CypaDetailsSeeder::class,
            FleetTruckSeeder::class,
            HelperSeeder::class,
            DriverSeeder::class,
            RequestSeeder::class,
            FixedExpenseSeeder::class,
            RatePerClientSeeder::class,
            BookingSeeder::class,
            TruckTripExpenseSeeder::class,
            DieselExpenseSeeder::class,
            WaybillDetailsSeeder::class,
            ContainerSeeder::class,
            StatementOfAccountSeeder::class,
            BillingStatementSeeder::class,
            InvoiceSeeder::class,
            TruckMaintenanceRecordsSeeder::class,
            DriverCAHistorySeeder::class,
            HelperCAHistorySeeder::class,
            IssuedBudgetSeeder::class,
            PartsExpenseSeeder::class,
        ]);
    }
}