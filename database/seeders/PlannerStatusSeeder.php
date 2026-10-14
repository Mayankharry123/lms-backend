<?php

/**
 * PlannerStatusSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PlannerStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('planner_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('planner_statuses')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Plan Submitted',
                'slug' => Str::slug('Plan Submitted'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Plan Reviewed',
                'slug' => Str::slug('Plan Reviewed'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Plan Approved',
                'slug' => Str::slug('Plan Approved'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
