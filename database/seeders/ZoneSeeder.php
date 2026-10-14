<?php

/**
 * ZoneSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Zone;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Zone::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $zones = [
            'North Zone',
            'South Zone',
            'East Zone',
            'West Zone',
            'Central Zone',
        ];

        foreach ($zones as $zoneName) {
            // updateOrCreate() will check if zone already exists
            // If it doesn't exist, it will create a new one
            Zone::updateOrCreate(
                [
                    'slug' => Str::slug($zoneName) // Check existence based on slug
                ],
                [
                    'name' => $zoneName,
                    'status' => '1', // Default '1' = active (as per migration)
                ]
            );
        }
    }
}