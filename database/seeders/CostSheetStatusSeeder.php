<?php

/**
 * CostSheetStatus Seeder
 * -----------------------------------------
 * Seeds the cost_sheet_statuses table with Submitted and Pending statuses.
 * Existing rows are updated so brief foreign keys are not removed.
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
        $statuses = [
            'Submitted',
            'Pending',
        ];

        $now = Carbon::now();

        foreach ($statuses as $name) {
            $slug = Str::slug($name);
            $existing = DB::table('cost_sheet_statuses')->where('slug', $slug)->first();

            if ($existing) {
                DB::table('cost_sheet_statuses')->where('id', $existing->id)->update([
                    'name' => $name,
                    'status' => '1',
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('cost_sheet_statuses')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
