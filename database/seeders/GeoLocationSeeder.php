<?php

/**
 * GeoLocationSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backward-compatible wrapper that imports all geography tables.
 * New code should prefer the table-specific seeders.
 */
class GeoLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            SubregionSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
        ]);
    }
}
