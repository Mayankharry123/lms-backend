<?php

/**
 * OperationStatus Seeder
 * -----------------------------------------
 * Seeds the operation_statuses table with Campaign Live and Campaign Pending.
 *
 * @package Database\Seeders
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('operation_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            'Live',
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

        DB::table('operation_statuses')->insert($rows);
    }
}
