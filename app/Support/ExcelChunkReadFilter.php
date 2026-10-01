<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Reads the header row plus one Excel window at a time so large imports
 * do not load the full worksheet into memory.
 */
class ExcelChunkReadFilter implements IReadFilter
{
    private int $startRow = 1;
    private int $endRow = 1;

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = max(1, $startRow);
        $this->endRow = $this->startRow + max(1, $chunkSize) - 1;
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row === 1) {
            return true;
        }

        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
