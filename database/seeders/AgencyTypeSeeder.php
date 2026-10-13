<?php

/**
 * AgencyTypeSeeder
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

class AgencyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks (important)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('agency_type')->truncate(); // Clear table

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();

        $types = ['Online', 'Offline', 'Both'];

        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'name'       => $type,
                'slug'       => Str::slug($type),
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('agency_type')->insert($data);
    }
}
