<?php

/**
 * PrioritySeeder
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

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('priorities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Fetch call_status IDs in insertion order
        $callStatuses = DB::table('call_statuses')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        DB::table('priorities')->insert([
            [
                'uuid' => Str::uuid(),
                'name' => 'High',
                'slug' => Str::slug('High'),
                'call_status' => json_encode([
                    $callStatuses[3], // ID 4
                    $callStatuses[4], // ID 5
                ]),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'uuid' => Str::uuid(),
                'name' => 'Medium',
                'slug' => Str::slug('Medium'),
                'call_status' => json_encode([
                    $callStatuses[0], // ID 1
                    $callStatuses[1], // ID 2
                    $callStatuses[2], // ID 3
                ]),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'uuid' => Str::uuid(),
                'name' => 'Low',
                'slug' => Str::slug('Low'),
                'call_status' => json_encode(array_slice($callStatuses, 5, 9)),
                // IDs 6 to 14
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'uuid' => Str::uuid(),
                'name' => 'Other',
                'slug' => Str::slug('Other'),
                'call_status' => null,
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
