<?php

/**
 * BriefStatusSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BriefStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('brief_statuses')->truncate();
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $statuses = [
            [ 'name' => 'Not Interested', 'slug' => 'not-interested', 'priority_id' => 3, 'status' => '1', 'percentage' => 0.00 ], // Low
            [ 'name' => 'Submission',     'slug' => 'submission',      'priority_id' => 2, 'status' => '1', 'percentage' => 25.00 ], // Medium
            [ 'name' => 'Negotiation',    'slug' => 'negotiation',     'priority_id' => 2, 'status' => '1', 'percentage' => 50.00 ], // Medium
            [ 'name' => 'Approve',        'slug' => 'approve',         'priority_id' => 1, 'status' => '1', 'percentage' => 75.00 ], // High
            [ 'name' => 'Closed',         'slug' => 'closed',          'priority_id' => 1, 'status' => '1', 'percentage' => 100.00 ], // High
        ];

        foreach ($statuses as $status) {
            DB::table('brief_statuses')->updateOrInsert(
                ['name' => $status['name']], // unique condition
                [
                    'uuid'         => Str::uuid(),
                    'slug'         => $status['slug'],
                    'priority_id'  => $status['priority_id'],
                    'percentage'   => $status['percentage'],
                    'status'       => $status['status'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );
        }
    }
}
