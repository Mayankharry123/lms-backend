<?php

/**
 * FinanceStatus Seeder
 * -----------------------------------------
 * Seeds the finance_statuses table with Approved and Denied statuses.
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

class FinanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('finance_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            'Approved',
            'Denied',
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

        DB::table('finance_statuses')->insert($rows);
    }
}
