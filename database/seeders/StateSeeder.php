<?php

/**
 * StateSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use App\Services\GeographyDumpImporter;
use Illuminate\Database\Seeder;
use RuntimeException;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $table = 'states';
        $sourceFile = base_path('sql/states.sql');
        $importer = new GeographyDumpImporter();
        $count = $importer->importTableFromFile($table, $sourceFile, true);

        if ($count < 1) {
            throw new RuntimeException(sprintf(
                'No geography rows were imported for table "%s" from %s.',
                $table,
                $sourceFile
            ));
        }
    }
}
