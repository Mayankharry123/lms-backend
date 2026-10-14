<?php

/**
 * BrandTypeSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BrandTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('brand_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $types = [
            'National',
            'Regional',
            'Local',
            'International',
            'Unknown' 
        ];

        foreach ($types as $type) {
            DB::table('brand_types')->insert([
                'name' => $type,
                'slug' => Str::slug($type),
                'status' => '1',
                'created_at' => Carbon::now(), // <-- [FIX 2] Ise badlein
                'updated_at' => Carbon::now()  // <-- [FIX 2] Ise badlein
            ]);
        }
    }
}