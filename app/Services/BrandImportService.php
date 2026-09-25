<?php

namespace App\Services;

use App\Contracts\Repositories\BrandImportRepositoryInterface;
use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Contracts\Repositories\BrandTypeRepositoryInterface;
use App\Contracts\Repositories\CityRepositoryInterface;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Contracts\Repositories\IndustryRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Models\Brand;
use App\Models\BrandImport;
use App\Support\ExcelChunkReadFilter;
use DomainException;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BrandImportService
{
    public const CHUNK_SIZE = 500;

    public const IMPORT_ROOT = 'writable/uploads/brands';

    public const EXPORT_ROOT = 'writable/exports/brands';

    /**
     * @var array<int, string>
     */
    private const STAGES = ['pending', 'processing', 'completed', 'failed'];

    public function __construct(
        protected BrandRepositoryInterface $brandRepository,
        protected BrandTypeRepositoryInterface $brandTypeRepository,
        protected IndustryRepositoryInterface $industryRepository,
        protected CountryRepositoryInterface $countryRepository,
        protected StateRepositoryInterface $stateRepository,
        protected CityRepositoryInterface $cityRepository,
        protected ZoneRepositoryInterface $zoneRepository,
        protected BrandImportRepositoryInterface $brandImportRepository,
        protected ExcelService $excelService,
        protected BrandService $brandService
    ) {
    }

    /**
     * Save the uploaded Excel file under writable/, process it in chunks, then archive it.
     *
     * @param UploadedFile $file
     * @param int|null $createdBy
     * @return array<string, mixed>
     * @throws DomainException
     */
    public function import(UploadedFile $file, ?int $createdBy = null): array
    {
        $this->assertValidExcelUpload($file);
        $this->ensureImportDirectories();

        $originalFilename = $this->sanitizeOriginalFilename((string) $file->getClientOriginalName());
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $storedFilename = sprintf(
            'brand_import_%s_%s.%s',
            date('Ymd_His'),
            str_replace('.', '', uniqid('', true)),
            $extension
        );

        $pendingPath = $this->stagePath('pending', $storedFilename);
        if (!$file->move(dirname($pendingPath), $storedFilename) && !is_file($pendingPath)) {
            throw new DomainException('Unable to store the uploaded Excel file.');
        }

        $import = $this->brandImportRepository->create([
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'stored_path' => $this->relativePath('pending', $storedFilename),
            'status' => BrandImport::STATUS_PENDING,
            'created_by' => $createdBy,
            'failed_details' => [],
        ]);

        $processingPath = $this->stagePath('processing', $storedFilename);
        $currentPath = $pendingPath;

        try {
            $currentPath = $this->moveImportFile($pendingPath, $processingPath);
            $import = $this->brandImportRepository->update($import, [
                'status' => BrandImport::STATUS_PROCESSING,
                'stored_path' => $this->relativePath('processing', $storedFilename),
            ]);

            $summary = $this->processStoredFile($currentPath, $import, $createdBy);
            $failedExport = $this->storeFailedRecordsExcel($summary['failed_details']);

            $completedPath = $this->stagePath('completed', $storedFilename);
            $currentPath = $this->moveImportFile($currentPath, $completedPath);

            $import = $this->brandImportRepository->update($import, [
                'status' => BrandImport::STATUS_COMPLETED,
                'stored_path' => $this->relativePath('completed', $storedFilename),
                'total_records' => $summary['total_records'],
                'processed_records' => $summary['processed_records'],
                'created_records' => $summary['created_records'],
                'updated_records' => $summary['updated_records'],
                'failed_records' => $summary['failed_records'],
                'current_chunk' => $summary['current_chunk'],
                'failed_details' => $summary['failed_details'],
                'error_message' => null,
            ]);

            return $this->toApiSummary($import, BrandImport::STATUS_COMPLETED, $failedExport['url']);
        } catch (Throwable $e) {
            Log::error('Brand Excel import failed', [
                'stored_filename' => $storedFilename,
                'exception' => $e,
            ]);

            try {
                if (is_file($currentPath)) {
                    $failedPath = $this->stagePath('failed', $storedFilename);
                    $currentPath = $this->moveImportFile($currentPath, $failedPath);
                }
            } catch (Throwable $moveException) {
                Log::error('Unable to move failed brand import file', [
                    'stored_filename' => $storedFilename,
                    'exception' => $moveException,
                ]);
            }

            $import = $this->brandImportRepository->update($import, [
                'status' => BrandImport::STATUS_FAILED,
                'stored_path' => is_file($this->stagePath('failed', $storedFilename))
                    ? $this->relativePath('failed', $storedFilename)
                    : $import->stored_path,
                'error_message' => $e->getMessage(),
            ]);

            if ($e instanceof DomainException) {
                throw $e;
            }

            throw new DomainException('Brand Excel import failed.');
        }
    }

    /**
     * @param string $filePath
     * @param BrandImport $import
     * @param int|null $createdBy
     * @return array<string, mixed>
     * @throws DomainException
     */
    private function processStoredFile(string $filePath, BrandImport $import, ?int $createdBy): array
    {
        $totalRows = $this->excelService->getTotalRows($filePath);
        if ($totalRows < 2) {
            throw new DomainException('The Excel file is empty. Please use the brand import template.');
        }

        $headerChunk = $this->excelService->readChunk($filePath, new ExcelChunkReadFilter(), 2, 1);
        $columnMap = $this->mapImportColumns($headerChunk['headers'] ?? []);

        $totalRecords = 0;
        $processedRecords = 0;
        $createdRecords = 0;
        $updatedRecords = 0;
        $failedRecords = 0;
        $failedDetails = [];
        $currentChunk = 0;
        $knownBrands = [];

        $lastDataRow = $totalRows;
        $chunkSize = self::CHUNK_SIZE;

        for ($startRow = 2; $startRow <= $lastDataRow; $startRow += $chunkSize) {
            $currentChunk++;
            $chunk = $this->excelService->readChunk($filePath, new ExcelChunkReadFilter(), $startRow, $chunkSize);

            $parsedRows = [];
            foreach ($chunk['rows'] as $row) {
                $values = $this->extractRowValues($row['cells'] ?? [], $columnMap);
                if ($this->isEmptyImportRow($values)) {
                    continue;
                }

                $totalRecords++;
                $parsedRows[] = [
                    'row' => (int) $row['row'],
                    'values' => $values,
                ];
            }

            if ($parsedRows === []) {
                $this->brandImportRepository->update($import, [
                    'current_chunk' => $currentChunk,
                    'total_records' => $totalRecords,
                    'processed_records' => $processedRecords,
                ]);
                continue;
            }

            $lookups = $this->loadImportLookups($parsedRows);
            foreach ($lookups['existing_brands'] as $nameKey => $brand) {
                if (!isset($knownBrands[$nameKey])) {
                    $knownBrands[$nameKey] = $brand;
                }
            }

            $pendingByName = [];
            foreach ($parsedRows as $parsedRow) {
                $processedRecords++;
                $values = $parsedRow['values'];
                $validated = $this->validateImportRow($values, $lookups);

                if ($validated['reason'] !== null) {
                    $failedRecords++;
                    $failedDetails[] = [
                        'row' => $parsedRow['row'],
                        'brand_name' => $values['name'],
                        'reason' => $validated['reason'],
                        'values' => $values,
                    ];
                    continue;
                }

                $nameKey = mb_strtolower($validated['payload']['name']);
                $existing = $knownBrands[$nameKey] ?? null;
                $pendingByName[$nameKey] = [
                    'action' => $existing ? 'update' : 'insert',
                    'payload' => $validated['payload'],
                    'existing_id' => $existing->id ?? null,
                    'row' => $parsedRow['row'],
                    'values' => $values,
                ];
            }

            $inserts = [];
            $updates = [];
            $insertNameKeys = [];
            $now = now();

            foreach ($pendingByName as $nameKey => $item) {
                if ($item['action'] === 'update') {
                    $updates[] = $this->buildUpdateRow($item['existing_id'], $item['payload'], $now);
                    continue;
                }

                $inserts[] = $this->buildInsertRow($item['payload'], $createdBy, $now);
                $insertNameKeys[] = $nameKey;
            }

            try {
                DB::beginTransaction();

                if ($inserts !== []) {
                    $this->brandRepository->insertBatch($inserts);
                    $created = $this->brandRepository->findBySlugs(
                        array_map(static fn ($row) => $row['slug'], $inserts)
                    );

                    $slugUpdates = [];
                    $createdIds = [];
                    foreach ($created as $brand) {
                        $nameKey = mb_strtolower(trim((string) $brand->name));
                        $knownBrands[$nameKey] = $brand;
                        $createdIds[] = (int) $brand->id;
                        $slugUpdates[] = [
                            'id' => $brand->id,
                            'slug' => $this->uniqueSlugForBrand($brand->name, (int) $brand->id),
                        ];
                    }

                    if ($slugUpdates !== []) {
                        $this->brandRepository->updateBatch($slugUpdates);
                    }
                    if ($createdIds !== []) {
                        $this->brandRepository->attachAgencyToBrands(
                            $createdIds,
                            $this->brandService->getDirectAgencyId()
                        );
                    }

                    $createdRecords += count($createdIds);
                }

                if ($updates !== []) {
                    $this->brandRepository->updateBatch($updates);
                    $updatedRecords += count($updates);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error('Brand import chunk failed', [
                    'chunk' => $currentChunk,
                    'exception' => $e,
                ]);

                foreach ($pendingByName as $item) {
                    $failedRecords++;
                    $failedDetails[] = [
                        'row' => $item['row'],
                        'brand_name' => $item['values']['name'] ?? '',
                        'reason' => 'Database/import validation error: ' . $e->getMessage(),
                        'values' => $item['values'] ?? [],
                    ];
                }

                $this->brandImportRepository->update($import, [
                    'current_chunk' => $currentChunk,
                    'total_records' => $totalRecords,
                    'processed_records' => $processedRecords,
                    'created_records' => $createdRecords,
                    'updated_records' => $updatedRecords,
                    'failed_records' => $failedRecords,
                    'failed_details' => $failedDetails,
                    'error_message' => $e->getMessage(),
                ]);

                continue;
            }

            $this->brandImportRepository->update($import, [
                'current_chunk' => $currentChunk,
                'total_records' => $totalRecords,
                'processed_records' => $processedRecords,
                'created_records' => $createdRecords,
                'updated_records' => $updatedRecords,
                'failed_records' => $failedRecords,
                'failed_details' => $failedDetails,
            ]);
        }

        return [
            'total_records' => $totalRecords,
            'processed_records' => $processedRecords,
            'created_records' => $createdRecords,
            'updated_records' => $updatedRecords,
            'failed_records' => $failedRecords,
            'current_chunk' => $currentChunk,
            'failed_details' => $failedDetails,
        ];
    }

    /**
     * @param BrandImport $import
     * @param string $status
     * @param string|null $failedFileUrl
     * @return array<string, mixed>
     */
    private function toApiSummary(BrandImport $import, string $status, ?string $failedFileUrl = null): array
    {
        $failedDetails = $import->failed_details ?? [];
        $successCount = (int) $import->created_records + (int) $import->updated_records;
        $failedCount = (int) $import->failed_records;

        $failedRecordsForApi = array_map(static function ($detail) {
            return [
                'row' => $detail['row'] ?? null,
                'brand_name' => $detail['brand_name'] ?? '',
                'reason' => $detail['reason'] ?? '',
            ];
        }, is_array($failedDetails) ? $failedDetails : []);

        return [
            'total_count' => (int) $import->total_records,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_file_url' => $failedFileUrl,
            'total_records' => (int) $import->total_records,
            'processed_records' => (int) $import->processed_records,
            'created_records' => (int) $import->created_records,
            'updated_records' => (int) $import->updated_records,
            'failed_records' => $failedRecordsForApi,
            'failed_record_count' => $failedCount,
            'status' => $status,
            'current_chunk' => (int) $import->current_chunk,
            'original_filename' => $import->original_filename,
            'stored_filename' => $import->stored_filename,
            'failed_details' => $failedRecordsForApi,
            'total_rows' => (int) $import->total_records,
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
        ];
    }

    /**
     * @param UploadedFile $file
     * @return void
     * @throws DomainException
     */
    private function assertValidExcelUpload(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new DomainException('The file must be a valid Excel file (.xlsx or .xls).');
        }
    }

    private function ensureImportDirectories(): void
    {
        foreach (self::STAGES as $stage) {
            $directory = $this->stageDirectory($stage);
            if (is_dir($directory)) {
                continue;
            }

            if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new DomainException('Unable to create the brand import directory.');
            }
        }

        $exportDirectory = base_path(self::EXPORT_ROOT);
        if (!is_dir($exportDirectory) && !@mkdir($exportDirectory, 0775, true) && !is_dir($exportDirectory)) {
            throw new DomainException('Unable to create the brand export directory.');
        }
    }

    private function stageDirectory(string $stage): string
    {
        return base_path(self::IMPORT_ROOT . DIRECTORY_SEPARATOR . $stage);
    }

    private function stagePath(string $stage, string $filename): string
    {
        return $this->stageDirectory($stage) . DIRECTORY_SEPARATOR . $filename;
    }

    private function relativePath(string $stage, string $filename): string
    {
        return self::IMPORT_ROOT . '/' . $stage . '/' . $filename;
    }

    private function moveImportFile(string $from, string $to): string
    {
        if ($from === $to) {
            return $to;
        }

        $directory = dirname($to);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Unable to move the import file.');
        }

        if (!@rename($from, $to)) {
            if (!@copy($from, $to)) {
                throw new DomainException('Unable to move the import file.');
            }
            @unlink($from);
        }

        return $to;
    }

    private function sanitizeOriginalFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $basename = preg_replace('/[^A-Za-z0-9._-]/', '_', $basename) ?? 'brands.xlsx';

        return mb_substr($basename !== '' ? $basename : 'brands.xlsx', 0, 255);
    }

    /**
     * @param array<int, array<string, mixed>> $failedDetails
     * @return array{path: string|null, url: string|null}
     */
    private function storeFailedRecordsExcel(array $failedDetails): array
    {
        if ($failedDetails === []) {
            return ['path' => null, 'url' => null];
        }

        $filename = sprintf(
            'brand_import_failed_%s_%s.xlsx',
            date('Ymd_His'),
            str_replace('.', '', uniqid('', true))
        );
        $absolutePath = base_path(self::EXPORT_ROOT . DIRECTORY_SEPARATOR . $filename);

        $headers = array_merge(BrandService::IMPORT_HEADERS, ['error_reason']);
        $rows = [];
        foreach ($failedDetails as $detail) {
            $values = is_array($detail['values'] ?? null) ? $detail['values'] : [];
            $row = [];
            foreach (BrandService::IMPORT_HEADERS as $header) {
                $row[] = $values[$header] ?? '';
            }
            $row[] = (string) ($detail['reason'] ?? 'Database/import validation error');
            $rows[] = $row;
        }

        $this->excelService->writeToFile($headers, $rows, $absolutePath, 'Failed Records');

        $publicDirectory = base_path('public/exports/brands');
        if (!is_dir($publicDirectory) && !@mkdir($publicDirectory, 0775, true) && !is_dir($publicDirectory)) {
            throw new DomainException('Unable to create the public brand export directory.');
        }

        $publicPath = $publicDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!@copy($absolutePath, $publicPath)) {
            throw new DomainException('Unable to publish the failed records Excel file.');
        }

        return [
            'path' => self::EXPORT_ROOT . '/' . $filename,
            'url' => $this->publicFailedFileUrl($filename),
        ];
    }

    private function publicFailedFileUrl(string $filename): string
    {
        $baseUrl = rtrim((string) env('APP_URL', ''), '/');
        $port = (string) ($_SERVER['SERVER_PORT'] ?? '');

        // php -S sits on :8000; Vite is :5173. Never emit a relative or frontend URL
        // because the browser then requests /exports/... from the UI origin.
        if ($baseUrl === '' || str_contains($baseUrl, ':5173')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $listenPort = ($port !== '' && $port !== '80' && $port !== '443') ? $port : '8000';
            $baseUrl = $scheme . '://localhost:' . $listenPort;
        }

        return $baseUrl . '/exports/brands/' . rawurlencode($filename);
    }

    /**
     * Stream a generated failed-records Excel file.
     *
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws DomainException
     */
    public function downloadFailedRecordsFile(string $token): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $token = basename(str_replace(['..', '\\'], ['', '/'], $token));
        $token = (string) preg_replace('/\.xlsx$/i', '', $token);

        if (!preg_match('/^brand_import_failed_[A-Za-z0-9_-]+$/', $token)) {
            throw new DomainException('Failed records file not found.');
        }

        $filename = $token . '.xlsx';
        $candidates = [
            base_path(self::EXPORT_ROOT . DIRECTORY_SEPARATOR . $filename),
            base_path('public/exports/brands/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        throw new DomainException('Failed records file not found.');
    }

    /**
     * @param array<int, mixed> $headerRow
     * @return array<string, int>
     * @throws DomainException
     */
    private function mapImportColumns(array $headerRow): array
    {
        $columnMap = [];
        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader($header);
            if ($normalized === '') {
                continue;
            }
            $columnMap[$normalized] = (int) $index;
        }

        $missing = [];
        foreach (BrandService::IMPORT_HEADERS as $header) {
            if (!array_key_exists($header, $columnMap)) {
                $missing[] = $header;
            }
        }

        if ($missing !== []) {
            throw new DomainException(
                'Invalid Excel template. Missing column(s): ' . implode(', ', $missing) . '.'
            );
        }

        return $columnMap;
    }

    private function normalizeHeader($header): string
    {
        $value = strtolower(trim($this->cellToString($header)));
        $value = preg_replace('/[\s\-]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $columnMap
     * @return array<string, string>
     */
    private function extractRowValues(array $row, array $columnMap): array
    {
        $values = [];
        foreach (BrandService::IMPORT_HEADERS as $header) {
            $index = $columnMap[$header];
            $values[$header] = $this->cellToString($row[$index] ?? null);
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @return bool
     */
    private function isEmptyImportRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellToString($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value) || is_int($value)) {
            if (is_float($value) && floor($value) == $value) {
                return (string) (int) $value;
            }

            return trim((string) $value);
        }

        return trim((string) $value);
    }

    /**
     * @param array<int, array{row: int, values: array<string, string>}> $parsedRows
     * @return array<string, mixed>
     */
    private function loadImportLookups(array $parsedRows): array
    {
        $collect = static function (array $parsedRows, string $column): array {
            $names = [];
            foreach ($parsedRows as $parsedRow) {
                $name = $parsedRow['values'][$column] ?? '';
                if ($name !== '') {
                    $names[] = $name;
                }
            }

            return $names;
        };

        $existingBrands = [];
        foreach ($this->brandRepository->findByNames($collect($parsedRows, 'name')) as $brand) {
            $existingBrands[mb_strtolower(trim((string) $brand->name))] = $brand;
        }

        $states = [];
        foreach ($this->stateRepository->findByNames($collect($parsedRows, 'state')) as $state) {
            $states[mb_strtolower(trim((string) $state->name))][] = $state;
        }

        $cities = [];
        foreach ($this->cityRepository->findByNames($collect($parsedRows, 'city')) as $city) {
            $cities[mb_strtolower(trim((string) $city->name))][] = $city;
        }

        return [
            'brand_types' => $this->indexByName(
                $this->brandTypeRepository->findByNames($collect($parsedRows, 'brand_type'))
            ),
            'industries' => $this->indexByName(
                $this->industryRepository->findByNames($collect($parsedRows, 'industry'))
            ),
            'countries' => $this->indexByName(
                $this->countryRepository->findByNames($collect($parsedRows, 'country'))
            ),
            'states' => $states,
            'cities' => $cities,
            'zones' => $this->indexByName(
                $this->zoneRepository->findByNames($collect($parsedRows, 'zone'))
            ),
            'existing_brands' => $existingBrands,
        ];
    }

    /**
     * @param iterable<int, object> $records
     * @return array<string, object>
     */
    private function indexByName(iterable $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $key = mb_strtolower(trim((string) ($record->name ?? '')));
            if ($key !== '' && !isset($map[$key])) {
                $map[$key] = $record;
            }
        }

        return $map;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, mixed> $lookups
     * @return array{reason: string|null, payload: array<string, mixed>}
     */
    private function validateImportRow(array $values, array $lookups): array
    {
        $name = $values['name'];
        if ($name === '') {
            return $this->failedImportRow('Brand name is required.');
        }
        if (mb_strlen($name) > 255) {
            return $this->failedImportRow('Brand name may not be greater than 255 characters.');
        }

        $brandType = $this->resolveRequiredMaster($values['brand_type'], $lookups['brand_types'], 'Brand Type');
        if (is_string($brandType)) {
            return $this->failedImportRow($brandType);
        }

        $industry = $this->resolveRequiredMaster($values['industry'], $lookups['industries'], 'Industry');
        if (is_string($industry)) {
            return $this->failedImportRow($industry);
        }

        $country = $this->resolveRequiredMaster($values['country'], $lookups['countries'], 'Country');
        if (is_string($country)) {
            return $this->failedImportRow($country);
        }

        $state = $this->resolveState($values['state'], $country, $lookups['states']);
        if (is_string($state)) {
            return $this->failedImportRow($state);
        }

        $city = $this->resolveCity($values['city'], $country, $state, $lookups['cities']);
        if (is_string($city)) {
            return $this->failedImportRow($city);
        }

        $zone = $this->resolveRequiredMaster($values['zone'], $lookups['zones'], 'Zone');
        if (is_string($zone)) {
            return $this->failedImportRow($zone);
        }

        $website = $values['website'];
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
            return $this->failedImportRow("Website '{$website}' is not a valid URL.");
        }

        $postalCode = $values['postal_code'];
        if (mb_strlen($postalCode) > 20) {
            return $this->failedImportRow('Postal code may not be greater than 20 characters.');
        }

        $status = $this->normalizeImportStatus($values['status']);
        if ($status === null) {
            return $this->failedImportRow("Invalid status '{$values['status']}'. Allowed values: 1, 2, 15.");
        }

        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'brand';
        }

        return [
            'reason' => null,
            'payload' => [
                'name' => $name,
                'slug' => $baseSlug,
                'brand_type_id' => $brandType->id,
                'industry_id' => $industry->id,
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'zone_id' => $zone->id,
                'website' => $website !== '' ? $website : null,
                'postal_code' => $postalCode !== '' ? $postalCode : null,
                'status' => $status,
            ],
        ];
    }

    /**
     * @param string $reason
     * @return array{reason: string, payload: array<string, mixed>}
     */
    private function failedImportRow(string $reason): array
    {
        return [
            'reason' => $reason,
            'payload' => [],
        ];
    }

    /**
     * @param string $name
     * @param array<string, object> $lookup
     * @param string $label
     * @return object|string
     */
    private function resolveRequiredMaster(string $name, array $lookup, string $label)
    {
        if ($name === '') {
            return "{$label} is required.";
        }

        $record = $lookup[mb_strtolower($name)] ?? null;
        if (!$record) {
            return "{$label} '{$name}' not found.";
        }

        return $record;
    }

    /**
     * @param string $name
     * @param object $country
     * @param array<string, array<int, object>> $states
     * @return object|string
     */
    private function resolveState(string $name, object $country, array $states)
    {
        if ($name === '') {
            return 'State is required.';
        }

        $matches = $states[mb_strtolower($name)] ?? [];
        if ($matches === []) {
            return "State '{$name}' not found.";
        }

        foreach ($matches as $state) {
            if ((int) $state->country_id === (int) $country->id) {
                return $state;
            }
        }

        return "State '{$name}' does not belong to country '{$country->name}'.";
    }

    /**
     * @param string $name
     * @param object $country
     * @param object $state
     * @param array<string, array<int, object>> $cities
     * @return object|string
     */
    private function resolveCity(string $name, object $country, object $state, array $cities)
    {
        if ($name === '') {
            return 'City is required.';
        }

        $matches = $cities[mb_strtolower($name)] ?? [];
        if ($matches === []) {
            return "City '{$name}' not found.";
        }

        foreach ($matches as $city) {
            $matchesState = (int) $city->state_id === (int) $state->id;
            $matchesCountry = $city->country_id === null || (int) $city->country_id === (int) $country->id;
            if ($matchesState && $matchesCountry) {
                return $city;
            }
        }

        return "City '{$name}' does not belong to state '{$state->name}'.";
    }

    private function normalizeImportStatus(string $status): ?string
    {
        if ($status === '') {
            return '1';
        }

        $normalized = strtolower($status);
        if (is_numeric($normalized)) {
            $normalized = (string) (int) $normalized;
        }

        return match ($normalized) {
            '1', 'active' => '1',
            '2', 'deactivated', 'inactive' => '2',
            '15' => '15',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @param int|null $createdBy
     * @param mixed $now
     * @return array<string, mixed>
     */
    private function buildInsertRow(array $payload, ?int $createdBy, $now): array
    {
        return [
            'name' => $payload['name'],
            'slug' => $payload['slug'] . '-tmp-' . Str::lower(Str::random(8)),
            'brand_type_id' => $payload['brand_type_id'],
            'industry_id' => $payload['industry_id'],
            'country_id' => $payload['country_id'],
            'state_id' => $payload['state_id'],
            'city_id' => $payload['city_id'],
            'zone_id' => $payload['zone_id'],
            'website' => $payload['website'],
            'postal_code' => $payload['postal_code'],
            'status' => $payload['status'],
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param int $id
     * @param array<string, mixed> $payload
     * @param mixed $now
     * @return array<string, mixed>
     */
    private function buildUpdateRow(int $id, array $payload, $now): array
    {
        return [
            'id' => $id,
            'brand_type_id' => $payload['brand_type_id'],
            'industry_id' => $payload['industry_id'],
            'country_id' => $payload['country_id'],
            'state_id' => $payload['state_id'],
            'city_id' => $payload['city_id'],
            'zone_id' => $payload['zone_id'],
            'website' => $payload['website'],
            'postal_code' => $payload['postal_code'],
            'status' => $payload['status'],
            'updated_at' => $now,
        ];
    }

    private function uniqueSlugForBrand(string $name, int $id): string
    {
        $slugBase = Str::slug($name);
        if ($slugBase === '') {
            $slugBase = 'brand';
        }

        $finalSlug = $slugBase . '-' . $id;
        $existing = Brand::withTrashed()
            ->where('slug', $finalSlug)
            ->where('id', '!=', $id)
            ->exists();

        if ($existing) {
            $finalSlug = $slugBase . '-' . $id . '-' . Str::random(4);
        }

        return $finalSlug;
    }
}
