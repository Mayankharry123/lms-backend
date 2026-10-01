<?php
/**
 * BrandService
 * -----------------------------------------
 * This service class provides methods to manage brands, including
 * creating, updating, retrieving, and deleting brand records. It also
 * handles relationships between brands and agencies.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Services;

use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Contracts\Repositories\BrandTypeRepositoryInterface;
use App\Contracts\Repositories\CityRepositoryInterface;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Contracts\Repositories\IndustryRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Models\Agency;
use App\Models\AgencyType;
use App\Models\Brand;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrandService
{
    /**
     * Excel columns accepted by brand import / template APIs.
     *
     * @var array<int, string>
     */
    public const IMPORT_HEADERS = [
        'name',
        'brand_type',
        'industry',
        'country',
        'state',
        'city',
        'zone',
        'website',
        'postal_code',
        'status',
    ];

    /**
     * @var BrandRepositoryInterface
     */
    protected BrandRepositoryInterface $brandRepository;

    /**
     * @var BrandTypeRepositoryInterface
     */
    protected BrandTypeRepositoryInterface $brandTypeRepository;

    /**
     * @var IndustryRepositoryInterface
     */
    protected IndustryRepositoryInterface $industryRepository;

    /**
     * @var CountryRepositoryInterface
     */
    protected CountryRepositoryInterface $countryRepository;

    /**
     * @var StateRepositoryInterface
     */
    protected StateRepositoryInterface $stateRepository;

    /**
     * @var CityRepositoryInterface
     */
    protected CityRepositoryInterface $cityRepository;

    /**
     * @var ZoneRepositoryInterface
     */
    protected ZoneRepositoryInterface $zoneRepository;

    /**
     * @var ExcelService
     */
    protected ExcelService $excelService;

    /**
     * Create a new BrandService instance.
     *
     * @param BrandRepositoryInterface $brandRepository
     * @param BrandTypeRepositoryInterface $brandTypeRepository
     * @param IndustryRepositoryInterface $industryRepository
     * @param CountryRepositoryInterface $countryRepository
     * @param StateRepositoryInterface $stateRepository
     * @param CityRepositoryInterface $cityRepository
     * @param ZoneRepositoryInterface $zoneRepository
     * @param ExcelService $excelService
     */
    public function __construct(
        BrandRepositoryInterface $brandRepository,
        BrandTypeRepositoryInterface $brandTypeRepository,
        IndustryRepositoryInterface $industryRepository,
        CountryRepositoryInterface $countryRepository,
        StateRepositoryInterface $stateRepository,
        CityRepositoryInterface $cityRepository,
        ZoneRepositoryInterface $zoneRepository,
        ExcelService $excelService
    ) {
        $this->brandRepository = $brandRepository;
        $this->brandTypeRepository = $brandTypeRepository;
        $this->industryRepository = $industryRepository;
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->cityRepository = $cityRepository;
        $this->zoneRepository = $zoneRepository;
        $this->excelService = $excelService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all brands with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllBrands(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->brandRepository->getAllBrands($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching brands', ['exception' => $e]);
            throw new DomainException('Database error while fetching brands.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brands', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brands.');
        }
    }

    /**
     * Get a brand by ID.
     *
     * @param int $id
     * @return Brand|null
     * @throws DomainException
     */
    public function getBrand(int $id): ?Brand
    {
        try {
            return $this->brandRepository->getBrandById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching brand', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brand', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brand.');
        }
    }

    /**
     * Get a brand by slug.
     *
     * @param string $slug
     * @return Brand|null
     * @throws DomainException
     */
    public function getBrandBySlug(string $slug): ?Brand
    {
        try {
            return $this->brandRepository->getBrandBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching brand by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching brand by slug.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brand by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brand by slug.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brand.
     *
     * @param array $data
     * @return Brand
     * @throws DomainException
     */
    public function createBrand(array $data): Brand
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Brand name is required.');
            }

            // Check if brand name already exists
            if (Brand::where('name', $data['name'])->exists()) {
                throw new DomainException('Brand name must be unique. This brand name already exists.');
            }

            // Validate required fields: state, city, zone
            if (empty($data['state_id'])) {
                throw new DomainException('State is required.');
            }
            if (empty($data['city_id'])) {
                throw new DomainException('City is required.');
            }
            if (empty($data['zone_id'])) {
                throw new DomainException('Zone is required.');
            }

            // Extract agency_ids if provided, otherwise use default
            $agencyIds = [];
            if (!empty($data['agency_ids']) && is_array($data['agency_ids'])) {
                $agencyIds = $data['agency_ids'];
                unset($data['agency_ids']);
            } elseif (!empty($data['agency_id'])) {
                $agencyIds = [$data['agency_id']];
                unset($data['agency_id']);
            } else {
                // Attach to default "Direct" agency if none provided
                $agencyIds = [$this->getOrCreateDirectAgency()];
            }

            // Create the brand
            $brand = $this->brandRepository->createBrand($data);

            // Attach agencies to brand with timestamps
            if (!empty($agencyIds)) {
                $attachData = [];
                $now = \Carbon\Carbon::now();
                foreach ($agencyIds as $agencyId) {
                    $attachData[$agencyId] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $brand->agencies()->attach($attachData);
            }

            return $brand;
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating brand.');
        }
    }

    /**
     * Create a brand with minimal fields (name and slug).
     *
     * @param array $data
     * @return Brand
     * @throws DomainException
     */
    public function createBasicBrand(array $data): Brand
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Brand name is required.');
            }

            if (Brand::where('name', $data['name'])->whereNull('deleted_at')->exists()) {
                throw new DomainException('Brand name must be unique. This brand name already exists.');
            }

            $baseSlug = Str::slug($data['name']);
            if ($baseSlug === '') {
                $baseSlug = 'brand';
            }

            $slug = $baseSlug;
            $counter = 1;
            while (Brand::where('slug', $slug)->whereNull('deleted_at')->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $payload = [
                'name' => $data['name'],
                'slug' => $slug,
                'status' => $data['status'] ?? '1',
                'created_by' => $data['created_by'] ?? null,
            ];

            $brand = $this->brandRepository->createBrand($payload);

            // Keep behavior consistent with full brand creation:
            // always attach the new brand to default "Direct" agency.
            $directAgencyId = $this->getOrCreateDirectAgency();
            $brand->agencies()->attach([
                $directAgencyId => [
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ],
            ]);

            return $brand;
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating basic brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating basic brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating basic brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating basic brand.');
        }
    }

    /**
     * Update an existing brand.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws DomainException
     */
    public function updateBrand(int $id, array $data): bool
    {
        try {
            // Get the current brand to compare
            $currentBrand = Brand::withTrashed()->find($id);
            
            if (!$currentBrand) {
                throw new DomainException('Brand not found.');
            }

            // Check if brand name is being updated and if it already exists (excluding current brand)
            if (!empty($data['name'])) {
                // Only check uniqueness if the name is actually different from current
                if ($currentBrand->name !== $data['name']) {
                    if (Brand::where('name', $data['name'])
                        ->where('id', '!=', $id)
                        ->whereNull('deleted_at')
                        ->exists()) {
                        throw new DomainException('Brand name must be unique. This brand name already exists.');
                    }
                }
            }

            // Validate required fields: state, city, zone (if provided)
            if (isset($data['state_id']) && empty($data['state_id'])) {
                throw new DomainException('State is required.');
            }
            if (isset($data['city_id']) && empty($data['city_id'])) {
                throw new DomainException('City is required.');
            }
            if (isset($data['zone_id']) && empty($data['zone_id'])) {
                throw new DomainException('Zone is required.');
            }

            // Extract agency_ids if provided
            $agencyIds = null;
            if (isset($data['agency_ids'])) {
                $agencyIds = $data['agency_ids'];
                unset($data['agency_ids']);
            } elseif (isset($data['agency_id'])) {
                $agencyIds = [$data['agency_id']];
                unset($data['agency_id']);
            }

            // Update the brand
            $updated = $this->brandRepository->updateBrand($id, $data);

            // Sync agencies if provided (with timestamps)
            if ($agencyIds !== null && !empty($agencyIds)) {
                // Get Direct agency ID
                $directAgencyId = $this->getOrCreateDirectAgency();
                
                // Filter out Direct agency from the new agency list
                $validAgencyIds = array_filter($agencyIds, fn($id) => $id != $directAgencyId);
                
                // If there are valid agencies (other than Direct), sync them
                if (!empty($validAgencyIds)) {
                    $syncData = [];
                    $now = \Carbon\Carbon::now();
                    foreach ($validAgencyIds as $agencyId) {
                        $syncData[$agencyId] = [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    // sync() replaces all relationships - removes old ones and adds new ones
                    $currentBrand->agencies()->sync($syncData);
                }
            }

            return $updated;
        } catch (QueryException $e) {
            Log::error('Database error updating brand', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating brand.');
        } catch (DomainException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error updating brand', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating brand.');
        }
    }

    /**
     * Delete a brand (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteBrand(int $id): bool
    {
        try {
            return $this->brandRepository->deleteBrand($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting brand', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting brand', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting brand.');
        }
    }

    

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    /**
     * Get the default Direct agency id, creating it when missing.
     *
     * @return int
     */
    public function getDirectAgencyId(): int
    {
        return $this->getOrCreateDirectAgency();
    }

    /**
     * Get or create a default "Direct" agency.
     *
     * @return int
     */
    private function getOrCreateDirectAgency(): int
    {
        $directAgency = Agency::withTrashed()
            ->whereRaw('LOWER(name) = ?', ['direct'])
            ->first();

        if (!$directAgency) {
            $defaultAgencyType = $this->getOrCreateDefaultAgencyType();
            
            $directAgency = Agency::create([
                'name' => 'Direct',
                'slug' => 'direct',
                'status' => '1',
                'agency_type' => $defaultAgencyType->id,
            ]);
        } elseif ($directAgency->trashed()) {
            $directAgency->restore();
        }

        return $directAgency->id;
    }

    /**
     * Get or create a default agency type.
     *
     * @return AgencyType
     */
    private function getOrCreateDefaultAgencyType(): AgencyType
    {
        $defaultAgencyType = AgencyType::withTrashed()
            ->whereRaw('LOWER(slug) = ?', ['online'])
            ->first();

        if (!$defaultAgencyType) {
            $defaultAgencyType = AgencyType::withTrashed()->first();
        }

        if (!$defaultAgencyType) {
            $defaultAgencyType = AgencyType::create([
                'name' => 'Online',
                'slug' => 'online',
                'status' => 1,
            ]);
        } elseif ($defaultAgencyType->trashed()) {
            $defaultAgencyType->restore();
        }

        return $defaultAgencyType;
    }

    /**
     * Get a simple list of brands (ID and Name).
     *
     * @return \Illuminate\Support\Collection|null
     * @throws DomainException
     */
    public function getBrandList(): ?\Illuminate\Support\Collection
    {
        try {
            // Hum repository se list maangenge
            return $this->brandRepository->getBrandList();
        } catch (QueryException $e) {
            Log::error('Database error fetching brand list', ['exception' => $e]);
            throw new DomainException('Database error while fetching brand list.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brand list', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brand list.');
        }
    }

    // ============================================================================
    // EXCEL IMPORT / TEMPLATE
    // ============================================================================

    /**
     * Generate a demo Excel template compatible with the brand import API.
     *
     * @return string Binary .xlsx contents
     * @throws DomainException
     */
    public function generateBrandImportTemplate(): string
    {
        try {
            return $this->excelService->write(
                self::IMPORT_HEADERS,
                [
                    [
                        'LAVA',
                        'Local',
                        'Technology',
                        'India',
                        'Maharashtra',
                        'Mumbai',
                        'West Zone',
                        'https://www.lava.in',
                        '400001',
                        '1',
                    ],
                    [
                        'Demo Brand',
                        'National',
                        'FMCG',
                        'India',
                        'Delhi',
                        'New Delhi',
                        'North Zone',
                        'https://www.example.com',
                        '110001',
                        '1',
                    ],
                ],
                'Brands'
            );
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error generating brand import template', ['exception' => $e]);
            throw new DomainException('Unexpected error while generating the brand import template.');
        }
    }

    /**
     * Import brands from an Excel file. Invalid rows are skipped and reported.
     *
     * @param UploadedFile $file
     * @param int|null $createdBy
     * @return array{
     *     total_rows: int,
     *     success_rows: int,
     *     failed_rows: int,
     *     failed_records: array<int, array{row: int, brand_name: string, reason: string}>
     * }
     * @throws DomainException
     */
    public function importBrandsFromExcel(UploadedFile $file, ?int $createdBy = null): array
    {
        try {
            $this->assertValidExcelUpload($file);

            $sheetRows = $this->excelService->read($file->getRealPath());
            if ($sheetRows === []) {
                throw new DomainException('The Excel file is empty. Please use the brand import template.');
            }

            $columnMap = $this->mapImportColumns($sheetRows[0] ?? []);
            $dataRows = array_slice($sheetRows, 1, null, true);

            $parsedRows = [];
            foreach ($dataRows as $index => $row) {
                $excelRowNumber = (int) $index + 1;
                $values = $this->extractRowValues($row, $columnMap);
                if ($this->isEmptyImportRow($values)) {
                    continue;
                }

                $parsedRows[] = [
                    'row' => $excelRowNumber,
                    'values' => $values,
                ];
            }

            $lookups = $this->loadImportLookups($parsedRows);
            $seenNames = $lookups['existing_brand_names'];

            $successRows = 0;
            $failedRecords = [];

            foreach ($parsedRows as $parsedRow) {
                $values = $parsedRow['values'];
                $brandName = $values['name'];
                $validated = $this->validateImportRow($values, $lookups, $seenNames);

                if ($validated['reason'] !== null) {
                    $failedRecords[] = [
                        'row' => $parsedRow['row'],
                        'brand_name' => $brandName,
                        'reason' => $validated['reason'],
                    ];
                    continue;
                }

                try {
                    $this->createBrand($validated['payload'] + [
                        'slug' => Str::slug($validated['payload']['name']) . '-temp-' . Str::random(6),
                        'created_by' => $createdBy,
                    ]);
                    $seenNames[mb_strtolower($validated['payload']['name'])] = true;
                    $successRows++;
                } catch (DomainException $e) {
                    $failedRecords[] = [
                        'row' => $parsedRow['row'],
                        'brand_name' => $brandName,
                        'reason' => $e->getMessage(),
                    ];
                } catch (Exception $e) {
                    Log::error('Unexpected error importing brand row', [
                        'row' => $parsedRow['row'],
                        'exception' => $e,
                    ]);
                    $failedRecords[] = [
                        'row' => $parsedRow['row'],
                        'brand_name' => $brandName,
                        'reason' => 'Unexpected error while creating brand.',
                    ];
                }
            }

            return [
                'total_rows' => count($parsedRows),
                'success_rows' => $successRows,
                'failed_rows' => count($failedRecords),
                'failed_records' => $failedRecords,
            ];
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error importing brands', ['exception' => $e]);
            throw new DomainException('Database error while importing brands.');
        } catch (Exception $e) {
            Log::error('Unexpected error importing brands', ['exception' => $e]);
            throw new DomainException('Unexpected error while importing brands.');
        }
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
        foreach (self::IMPORT_HEADERS as $header) {
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

    /**
     * @param mixed $header
     * @return string
     */
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
        foreach (self::IMPORT_HEADERS as $header) {
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

    /**
     * @param mixed $value
     * @return string
     */
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

        $brandTypes = $this->indexByName(
            $this->brandTypeRepository->findByNames($collect($parsedRows, 'brand_type'))
        );
        $industries = $this->indexByName(
            $this->industryRepository->findByNames($collect($parsedRows, 'industry'))
        );
        $countries = $this->indexByName(
            $this->countryRepository->findByNames($collect($parsedRows, 'country'))
        );
        $zones = $this->indexByName(
            $this->zoneRepository->findByNames($collect($parsedRows, 'zone'))
        );

        $states = [];
        foreach ($this->stateRepository->findByNames($collect($parsedRows, 'state')) as $state) {
            $states[mb_strtolower(trim((string) $state->name))][] = $state;
        }

        $cities = [];
        foreach ($this->cityRepository->findByNames($collect($parsedRows, 'city')) as $city) {
            $cities[mb_strtolower(trim((string) $city->name))][] = $city;
        }

        $existingBrandNames = [];
        foreach ($this->brandRepository->findExistingNames($collect($parsedRows, 'name')) as $name) {
            $existingBrandNames[$name] = true;
        }

        return [
            'brand_types' => $brandTypes,
            'industries' => $industries,
            'countries' => $countries,
            'states' => $states,
            'cities' => $cities,
            'zones' => $zones,
            'existing_brand_names' => $existingBrandNames,
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
     * @param array<string, bool> $seenNames
     * @return array{reason: string|null, payload: array<string, mixed>}
     */
    private function validateImportRow(array $values, array $lookups, array $seenNames): array
    {
        $name = $values['name'];
        if ($name === '') {
            return $this->failedImportRow('Brand name is required.');
        }
        if (mb_strlen($name) > 255) {
            return $this->failedImportRow('Brand name may not be greater than 255 characters.');
        }

        $nameKey = mb_strtolower($name);
        if (isset($seenNames[$nameKey])) {
            return $this->failedImportRow('Brand name must be unique. This brand name already exists.');
        }

        $brandType = $this->resolveRequiredMaster(
            $values['brand_type'],
            $lookups['brand_types'],
            'Brand Type'
        );
        if (is_string($brandType)) {
            return $this->failedImportRow($brandType);
        }

        $industry = $this->resolveRequiredMaster(
            $values['industry'],
            $lookups['industries'],
            'Industry'
        );
        if (is_string($industry)) {
            return $this->failedImportRow($industry);
        }

        $country = $this->resolveRequiredMaster(
            $values['country'],
            $lookups['countries'],
            'Country'
        );
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

        $zone = $this->resolveRequiredMaster(
            $values['zone'],
            $lookups['zones'],
            'Zone'
        );
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

        return [
            'reason' => null,
            'payload' => [
                'name' => $name,
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

    /**
     * @param string $status
     * @return string|null
     */
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
}