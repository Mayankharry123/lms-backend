<?php

/**
 * CostSheetStatus Seeder
 * -----------------------------------------
 * Seeds the cost_sheet_statuses table with Submitted and Pending statuses.
 *
 * @package Database\Seeders
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CostSheetStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cost_sheet_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            'Submitted',
            'Pending',
        ];

        $now = Carbon::now();
        $rows = [];

        foreach ($statuses as $name) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('cost_sheet_statuses')->insert($rows);
    }
}
