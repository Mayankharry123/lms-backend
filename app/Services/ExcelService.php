<?php

/**
 * ExcelService
 * -----------------------------------------
 * Shared Excel read/write helper used by master import/template APIs.
 * Wraps PhpSpreadsheet so controllers and domain services stay thin.
 *
 * @package App\Services
 */

namespace App\Services;

use App\Support\ExcelChunkReadFilter;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ExcelService
{
    /**
     * Read all rows from the first worksheet of an .xlsx/.xls file.
     *
     * @param string $filePath
     * @return array<int, array<int, mixed>> Zero-indexed rows and columns
     * @throws DomainException
     */
    public function read(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new DomainException('Unable to read the Excel file. Please upload a valid .xlsx or .xls file.');
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DomainException('Unable to read the Excel file. Please upload a valid .xlsx or .xls file.');
        }

        return is_array($rows) ? $rows : [];
    }

    /**
     * Return the highest row number in the first worksheet without loading cell data.
     *
     * @param string $filePath
     * @return int
     * @throws DomainException
     */
    public function getTotalRows(string $filePath): int
    {
        $this->assertReadableExcel($filePath);

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $info = $reader->listWorksheetInfo($filePath);
            $totalRows = (int) ($info[0]['totalRows'] ?? 0);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DomainException('Unable to read the Excel file. Please upload a valid .xlsx or .xls file.');
        }

        return max(0, $totalRows);
    }

    /**
     * Read the header row plus one window of data rows.
     *
     * @param string $filePath
     * @param ExcelChunkReadFilter $filter
     * @param int $startRow Excel row number of the first data row in this window (2+)
     * @param int $chunkSize
     * @return array{headers: array<int, mixed>, rows: array<int, array{row: int, cells: array<int, mixed>}>}
     * @throws DomainException
     */
    public function readChunk(string $filePath, ExcelChunkReadFilter $filter, int $startRow, int $chunkSize): array
    {
        $this->assertReadableExcel($filePath);

        try {
            $filter->setRows($startRow, $chunkSize);
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $reader->setReadFilter($filter);

            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestColumn = $sheet->getHighestDataColumn() ?: 'A';
            $endRow = $startRow + $chunkSize - 1;

            $headerCells = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, false)[0] ?? [];

            $rows = [];
            for ($excelRow = $startRow; $excelRow <= $endRow; $excelRow++) {
                $cells = $sheet->rangeToArray(
                    'A' . $excelRow . ':' . $highestColumn . $excelRow,
                    null,
                    true,
                    true,
                    false
                )[0] ?? [];

                $rows[] = [
                    'row' => $excelRow,
                    'cells' => is_array($cells) ? $cells : [],
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $reader);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DomainException('Unable to read the Excel file. Please upload a valid .xlsx or .xls file.');
        }

        return [
            'headers' => is_array($headerCells) ? $headerCells : [],
            'rows' => $rows,
        ];
    }

    /**
     * @param string $filePath
     * @return void
     * @throws DomainException
     */
    private function assertReadableExcel(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new DomainException('Unable to read the Excel file. Please upload a valid .xlsx or .xls file.');
        }
    }

    /**
     * Build an .xlsx file as a binary string.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param string $sheetTitle
     * @return string
     * @throws DomainException
     */
    public function write(array $headers, array $rows, string $sheetTitle = 'Sheet1'): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
            $sheet->fromArray(array_values($headers), null, 'A1');

            if (!empty($rows)) {
                $sheet->fromArray(array_values($rows), null, 'A2');
            }

            $columnCount = max(count($headers), 1);
            $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

            $headerRange = 'A1:' . $lastColumn . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('D9EAF7');
            $sheet->freezePane('A2');

            for ($column = 1; $column <= $columnCount; $column++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($tempPath === false) {
                throw new DomainException('Unable to generate the Excel file.');
            }

            $writer->save($tempPath);
            $content = file_get_contents($tempPath);
            @unlink($tempPath);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer);

            if ($content === false) {
                throw new DomainException('Unable to generate the Excel file.');
            }

            return $content;
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DomainException('Unable to generate the Excel file.');
        }
    }

    /**
     * Write an .xlsx file to disk.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param string $filePath
     * @param string $sheetTitle
     * @return void
     * @throws DomainException
     */
    public function writeToFile(array $headers, array $rows, string $filePath, string $sheetTitle = 'Sheet1'): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Unable to create the Excel export directory.');
        }

        $content = $this->write($headers, $rows, $sheetTitle);
        if (file_put_contents($filePath, $content) === false) {
            throw new DomainException('Unable to save the Excel file.');
        }
    }
}
