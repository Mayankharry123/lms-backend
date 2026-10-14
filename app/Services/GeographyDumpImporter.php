<?php

/**
 * GeographyDumpImporter
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class GeographyDumpImporter
{
    /**
     * Tables in dependency order for inserts.
     *
     * @var array<int, string>
     */
    private array $insertOrder = ['regions', 'subregions', 'countries', 'states', 'cities'];

    /**
     * Tables in reverse dependency order for truncation.
     *
     * @var array<int, string>
     */
    private array $truncateOrder = ['cities', 'states', 'countries', 'subregions', 'regions'];

    /**
     * GeographyDumpImporter constructor.
     */
    public function __construct()
    {
    }

    /**
     * Import all geography tables in dependency order.
     *
     * @param bool $truncate
     * @return array<string, int>
     */
    public function importAll(bool $truncate = true): array
    {
        $this->ensureUtf8mb4Connection();

        if ($truncate) {
            $this->truncateTables($this->truncateOrder);
        }

        $imported = [];

        foreach ($this->insertOrder as $table) {
            $imported[$table] = $this->importTableFromFile($table, $this->sourcePathForTable($table), false);
        }

        return $imported;
    }

    /**
     * Import specified geography tables in dependency order.
     *
     * @param array $tables
     * @param bool $truncate
     * @return array<string, int>
     */
    public function importTables(array $tables, bool $truncate = true): array
    {
        $this->ensureUtf8mb4Connection();

        $tables = $this->normalizeTables($tables);

        if ($truncate) {
            $this->truncateTables(array_intersect($this->truncateOrder, $tables));
        }

        $imported = [];

        foreach ($this->insertOrder as $table) {
            if (! in_array($table, $tables, true)) {
                continue;
            }

            $imported[$table] = $this->importTableFromFile($table, $this->sourcePathForTable($table), false);
        }

        return $imported;
    }

    /**
     * Import a single geography table.
     *
     * @param string $table
     * @param bool $truncate
     * @return int
     */
    public function importTable(string $table, bool $truncate = true): int
    {
        $this->ensureUtf8mb4Connection();

        $table = $this->normalizeTable($table);

        if ($truncate) {
            $this->truncateTables([$table]);
        }

        return $this->importTableFromFile($table, $this->sourcePathForTable($table), false);
    }

    /**
     * Get list of available geography table names.
     *
     * @return array<int, string>
     */
    public function availableTables(): array
    {
        return $this->insertOrder;
    }

    /**
     * Validate and normalize a list of geography tables.
     *
     * @param array $tables
     * @return array<int, string>
     */
    private function normalizeTables(array $tables): array
    {
        $normalized = [];

        foreach ($tables as $table) {
            $normalized[] = $this->normalizeTable((string) $table);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Validate and normalize a single table name.
     *
     * @param string $table
     * @return string
     * @throws RuntimeException
     */
    private function normalizeTable(string $table): string
    {
        $table = trim($table);

        if (! in_array($table, $this->insertOrder, true)) {
            throw new RuntimeException("Unsupported geography table: {$table}");
        }

        return $table;
    }

    /**
     * Ensure database PDO connection uses utf8mb4 encoding.
     *
     * @return void
     */
    private function ensureUtf8mb4Connection(): void
    {
        DB::connection()->getPdo()->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    /**
     * Truncate specified tables safely with foreign key checks temporarily disabled.
     *
     * @param array $tables
     * @return void
     */
    private function truncateTables(array $tables): void
    {
        if ($tables === []) {
            return;
        }

        $originalForeignKeyChecks = (int) DB::selectOne('SELECT @@FOREIGN_KEY_CHECKS AS value')->value;
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=' . $originalForeignKeyChecks . ';');
        }
    }

    /**
     * Import table records from an SQL source file.
     *
     * @param string $table
     * @param string $sourcePath
     * @param bool $truncate
     * @return int
     * @throws RuntimeException
     */
    public function importTableFromFile(string $table, string $sourcePath, bool $truncate = false): int
    {
        $table = $this->normalizeTable($table);

        $statements = $this->statementsForFile($table, $sourcePath);

        if ($statements === []) {
            throw new RuntimeException(sprintf(
                'No INSERT statements were found for geography table "%s" in %s.',
                $table,
                $sourcePath
            ));
        }

        if ($truncate) {
            $this->truncateTables([$table]);
        }

        $count = 0;

        foreach ($statements as $statement) {
            DB::unprepared($statement);
            $count++;
        }

        return $count;
    }

    /**
     * Read and parse SQL insert statements from source file.
     *
     * @param string $table
     * @param string $sourcePath
     * @return array<int, string>
     * @throws RuntimeException
     */
    private function statementsForFile(string $table, string $sourcePath): array
    {
        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read geography SQL file: {$sourcePath}");
        }

        if ($contents === '') {
            throw new RuntimeException("Geography SQL file is empty: {$sourcePath}");
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        return $this->parseInsertStatements($contents, $table);
    }

    /**
     * Resolve the SQL source file path for a table.
     *
     * @param string $table
     * @return string
     * @throws RuntimeException
     */
    private function sourcePathForTable(string $table): string
    {
        $tableFile = base_path('sql/' . $table . '.sql');

        if (is_file($tableFile)) {
            return $tableFile;
        }

        throw new RuntimeException(sprintf(
            'Unable to find a geography SQL source for table "%s". Looked for %s.',
            $table,
            $tableFile
        ));
    }

    /**
     * Parse raw SQL file contents into individual INSERT statement strings.
     *
     * @param string $contents
     * @param string $table
     * @return array<int, string>
     * @throws RuntimeException
     */
    private function parseInsertStatements(string $contents, string $table): array
    {
        $statements = [];
        $lines = preg_split('/\R/', $contents) ?: [];
        $headerPattern = sprintf(
            '/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+(?:`[^`]+`\.)?`?%s`?\b/i',
            preg_quote($table, '/')
        );

        $currentStatement = [];
        $collecting = false;

        foreach ($lines as $line) {
            if (! $collecting) {
                if (! preg_match($headerPattern, $line)) {
                    continue;
                }

                $collecting = true;
                $currentStatement = [$line];

                if (str_ends_with(rtrim($line), ';')) {
                    $statements[] = implode("\n", $currentStatement);
                    $collecting = false;
                    $currentStatement = [];
                }

                continue;
            }

            $currentStatement[] = $line;

            if (! str_ends_with(rtrim($line), ';')) {
                continue;
            }

            $statements[] = implode("\n", $currentStatement);
            $collecting = false;
            $currentStatement = [];
        }

        if ($collecting && $currentStatement !== []) {
            throw new RuntimeException(sprintf(
                'Geography SQL ended while reading an INSERT statement for table "%s".',
                $table
            ));
        }

        return $statements;
    }
}
