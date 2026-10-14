<?php

/**
 * CallStatusSeeder
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

class CallStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('call_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        DB::table('call_statuses')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Follow Up',
                'slug' => Str::slug('Follow Up'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Meeting Schedule',
                'slug' => Str::slug('Meeting Schedule'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Meeting Done',
                'slug' => Str::slug('Meeting Done'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Brief Pending',
                'slug' => Str::slug('Brief Pending'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Brief Recieved',
                'slug' => Str::slug('Brief Recieved'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Busy',
                'slug' => Str::slug('Busy'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Duplicate',
                'slug' => Str::slug('Duplicate'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Fake lead',
                'slug' => Str::slug('Fake lead'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Invalid Number',
                'slug' => Str::slug('Invalid Number'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Wrong Number',
                'slug' => Str::slug('Wrong Number'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Do Not Disturb',
                'slug' => Str::slug('Do Not Disturb'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Ringing',
                'slug' => Str::slug('Ringing'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Listen and Disconnected',
                'slug' => Str::slug('Listen and Disconnected'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Switch Off',
                'slug' => Str::slug('Switch Off'),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
