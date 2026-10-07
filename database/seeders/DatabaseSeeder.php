<?php

/**
 * Database Seeder
 * -----------------------------------------
 * Seeds the database with initial data for testing and development.
 *
 * @package Database\Seeders
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            RegionSeeder::class,
            SubregionSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            UserSeeder::class,
            LaratrustDummySeeder::class,
            PermissionSeeder::class,
            LeadSourceSeeder::class,
            AgencyTypeSeeder::class,
            BrandTypeSeeder::class,
            ZoneSeeder::class,
            CallStatusSeeder::class,
            PrioritySeeder::class,
            StatusSeeder::class,
            BriefStatusSeeder::class,
            OperationStatusSeeder::class,
            FinanceStatusSeeder::class,
            CostSheetStatusSeeder::class,
            VoucherTypeSeeder::class,
        ]);
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
