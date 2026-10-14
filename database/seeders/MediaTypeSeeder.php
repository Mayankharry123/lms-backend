<?php

/**
 * MediaType Seeder
 * -----------------------------------------
 * Seeds the media_types table with initial data including OOH, DOOH, and CTV types.
 *
 * @package Database\Seeders
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MediaTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('media_types')->truncate();
        Schema::enableForeignKeyConstraints();
        
        $types = [
            'ooh',
            'dooh',
            'ctv',
            'other',
        ];

        foreach ($types as $type) {
            DB::table('media_types')->insert([
                'name' => $type,
                'slug' => Str::slug($type),
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
