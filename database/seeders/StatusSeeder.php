<?php

/**
 * StatusSeeder
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

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $callStatuses = DB::table('call_statuses')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        DB::table('statuses')->insert([
            [
                'uuid' => Str::uuid(),
                'name' => 'Interested',
                'slug' => Str::slug('Interested'),
                'call_status' => json_encode([$callStatuses[0]]),    // ID 1
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Meeting Schedule',
                'slug' => Str::slug('Meeting Schedule'),
                'call_status' => json_encode([$callStatuses[1]]),    // ID 2
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Meeting Done',
                'slug' => Str::slug('Meeting Done'),
                'call_status' => json_encode([$callStatuses[2]]),    // ID 3
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Brief Pending',
                'slug' => Str::slug('Brief Pending'),
                'call_status' => json_encode([$callStatuses[3]]),    // ID 4
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Brief Recieved',
                'slug' => Str::slug('Brief Recieved'),
                'call_status' => json_encode([$callStatuses[4]]),   
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Pending',
                'slug' => Str::slug('Pending'),
                'call_status' => json_encode(array_slice($callStatuses, 5, 9)), 
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
