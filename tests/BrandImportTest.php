<?php

namespace Tests;

use App\Models\Brand;
use App\Models\BrandImport;
use App\Models\BrandType;
use App\Models\City;
use App\Models\Country;
use App\Models\Industry;
use App\Models\State;
use App\Models\User;
use App\Models\Zone;
use App\Services\BrandImportService;
use App\Services\BrandService;
use App\Services\ExcelService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BrandImportTest extends TestCase
{
    protected string $token;
    protected User $user;

    /** @var array<int, int> */
    protected array $createdBrandIds = [];

    /** @var array<int, string> */
    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::create([
            'name' => 'Brand Import Tester',
            'email' => 'brand_import_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $this->token = auth()->login($this->user);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdBrandIds)) {
            $brands = Brand::withTrashed()->whereIn('id', $this->createdBrandIds)->get();
            foreach ($brands as $brand) {
                $brand->agencies()->detach();
                $brand->forceDelete();
            }
        }

        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_import_template_downloads_xlsx_with_expected_headers_and_samples(): void
    {
        $this->get('/api/v1/brands/import-template', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->seeStatusCode(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $this->response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'brand-import-template.xlsx',
            (string) $this->response->headers->get('Content-Disposition')
        );

        $binary = $this->app->make(BrandService::class)->generateBrandImportTemplate();
        $path = $this->writeTempXlsx($binary);
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $this->assertSame(BrandService::IMPORT_HEADERS, array_map('strval', $rows[0]));
        $this->assertSame('LAVA', $rows[1][0]);
        $this->assertSame('Local', $rows[1][1]);
        $this->assertSame('Demo Brand', $rows[2][0]);
        $this->assertCount(3, $rows);
    }

    public function test_import_requires_excel_file(): void
    {
        $this->post('/api/v1/brands/import', [], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertFalse($payload['success']);
    }

    public function test_import_endpoint_accepts_multipart_excel_file(): void
    {
        $masters = $this->resolveMasters();
        if ($masters === null) {
            $this->markTestSkipped('Required brand master data is not available for import tests.');
        }

        $validName = 'Import Http ' . Str::upper(Str::random(8));
        $binary = $this->app->make(ExcelService::class)->write(
            BrandService::IMPORT_HEADERS,
            [[
                $validName,
                $masters['brand_type']->name,
                $masters['industry']->name,
                $masters['country']->name,
                $masters['state']->name,
                $masters['city']->name,
                $masters['zone']->name,
                '',
                '',
                '1',
            ]]
        );

        $this->call(
            'POST',
            '/api/v1/brands/import',
            [],
            [],
            ['file' => $this->makeUploadedExcel($binary)],
            [
                'HTTP_AUTHORIZATION' => "Bearer {$this->token}",
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->seeStatusCode(200);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Brand import completed.', $payload['message']);
        $this->assertSame(1, $payload['data']['total_rows']);
        $this->assertSame(1, $payload['data']['success_rows']);
        $this->assertSame(0, $payload['data']['failed_rows']);
        $this->assertSame(1, $payload['data']['total_count']);
        $this->assertSame(1, $payload['data']['success_count']);
        $this->assertSame(0, $payload['data']['failed_count']);
        $this->assertNull($payload['data']['failed_file_url']);
        $this->assertSame('completed', $payload['data']['status']);
        $this->assertSame(1, $payload['data']['created_records']);
        $this->assertNotNull(BrandImport::where('stored_filename', $payload['data']['stored_filename'])->first());

        $created = Brand::where('name', $validName)->first();
        $this->assertNotNull($created);
        $this->createdBrandIds[] = $created->id;
    }

    public function test_import_inserts_valid_rows_and_reports_invalid_rows(): void
    {
        $masters = $this->resolveMasters();
        if ($masters === null) {
            $this->markTestSkipped('Required brand master data is not available for import tests.');
        }

        $validName = 'Import Valid ' . Str::upper(Str::random(8));
        $duplicateName = 'Import Duplicate ' . Str::upper(Str::random(8));

        $existing = Brand::create([
            'name' => $duplicateName,
            'slug' => Str::slug($duplicateName) . '-tmp-' . Str::random(6),
            'brand_type_id' => $masters['brand_type']->id,
            'industry_id' => $masters['industry']->id,
            'country_id' => $masters['country']->id,
            'state_id' => $masters['state']->id,
            'city_id' => $masters['city']->id,
            'zone_id' => $masters['zone']->id,
            'status' => '1',
            'created_by' => $this->user->id,
        ]);
        $this->createdBrandIds[] = $existing->id;

        $binary = $this->app->make(ExcelService::class)->write(
            BrandService::IMPORT_HEADERS,
            [
                [
                    $validName,
                    $masters['brand_type']->name,
                    $masters['industry']->name,
                    $masters['country']->name,
                    $masters['state']->name,
                    $masters['city']->name,
                    $masters['zone']->name,
                    'https://valid-brand.example',
                    '400001',
                    '1',
                ],
                [
                    'Missing Industry Brand',
                    $masters['brand_type']->name,
                    'XYZ',
                    $masters['country']->name,
                    $masters['state']->name,
                    $masters['city']->name,
                    $masters['zone']->name,
                    '',
                    '',
                    '1',
                ],
                [
                    $duplicateName,
                    $masters['brand_type']->name,
                    $masters['industry']->name,
                    $masters['country']->name,
                    $masters['state']->name,
                    $masters['city']->name,
                    $masters['zone']->name,
                    '',
                    '',
                    '1',
                ],
            ]
        );

        $uploaded = $this->makeUploadedExcel($binary);
        $result = $this->app->make(BrandImportService::class)->import(
            $uploaded,
            $this->user->id
        );

        $this->assertSame(3, $result['total_rows']);
        $this->assertSame(2, $result['success_rows']);
        $this->assertSame(1, $result['failed_rows']);
        $this->assertSame(3, $result['total_count']);
        $this->assertSame(2, $result['success_count']);
        $this->assertSame(1, $result['failed_count']);
        $this->assertNotNull($result['failed_file_url']);
        $this->assertStringContainsString('/exports/brands/brand_import_failed_', $result['failed_file_url']);
        $this->assertStringStartsWith('http', (string) $result['failed_file_url']);

        $failedFilename = basename(parse_url($result['failed_file_url'], PHP_URL_PATH) ?: '');
        $failedPath = base_path('writable/exports/brands/' . $failedFilename);
        $publicPath = base_path('public/exports/brands/' . $failedFilename);
        $this->assertFileExists($failedPath);
        $this->assertFileExists($publicPath);
        $this->tempFiles[] = $failedPath;
        $this->tempFiles[] = $publicPath;

        $token = (string) preg_replace('/\.xlsx$/i', '', $failedFilename);
        $this->get('/api/v1/brands/import-failed-files/' . $token, [
            'Authorization' => "Bearer {$this->token}",
        ]);
        $this->seeStatusCode(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $this->response->headers->get('Content-Type')
        );

        $failedSpreadsheet = IOFactory::load($failedPath);
        $failedRows = $failedSpreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $this->assertSame(array_merge(BrandService::IMPORT_HEADERS, ['error_reason']), array_map('strval', $failedRows[0]));
        $this->assertSame('Missing Industry Brand', $failedRows[1][0]);
        $this->assertSame("Industry 'XYZ' not found.", $failedRows[1][10]);
        $this->assertSame(1, $result['created_records']);
        $this->assertSame(1, $result['updated_records']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame(3, $result['failed_records'][0]['row']);
        $this->assertSame('Missing Industry Brand', $result['failed_records'][0]['brand_name']);
        $this->assertSame("Industry 'XYZ' not found.", $result['failed_records'][0]['reason']);

        $created = Brand::where('name', $validName)->first();
        $this->assertNotNull($created);
        $this->createdBrandIds[] = $created->id;
        $this->assertSame($masters['brand_type']->id, (int) $created->brand_type_id);
        $this->assertSame($masters['industry']->id, (int) $created->industry_id);
        $this->assertSame($masters['country']->id, (int) $created->country_id);
        $this->assertSame($masters['state']->id, (int) $created->state_id);
        $this->assertSame($masters['city']->id, (int) $created->city_id);
        $this->assertSame($masters['zone']->id, (int) $created->zone_id);
        $this->assertSame('https://valid-brand.example', $created->website);
        $this->assertSame('400001', $created->postal_code);
        $this->assertSame('1', (string) $created->status);
        $this->assertNotNull($created->slug);
        $this->assertSame($this->user->id, (int) $created->created_by);

        $this->assertNull(Brand::where('name', 'Missing Industry Brand')->first());
        $this->assertSame(1, Brand::where('name', $duplicateName)->count());
        $existing->refresh();
        $this->assertSame($masters['brand_type']->id, (int) $existing->brand_type_id);
    }

    public function test_import_rejects_state_from_another_country(): void
    {
        $masters = $this->resolveMasters();
        if ($masters === null) {
            $this->markTestSkipped('Required brand master data is not available for import tests.');
        }

        $otherState = State::query()
            ->where('country_id', '!=', $masters['country']->id)
            ->whereNotIn('name', function ($query) use ($masters) {
                $query->select('name')
                    ->from('states')
                    ->where('country_id', $masters['country']->id);
            })
            ->first();
        if (!$otherState) {
            $this->markTestSkipped('No out-of-country state is available to test parent-child validation.');
        }

        $binary = $this->app->make(ExcelService::class)->write(
            BrandService::IMPORT_HEADERS,
            [[
                'Import Geo Fail ' . Str::upper(Str::random(6)),
                $masters['brand_type']->name,
                $masters['industry']->name,
                $masters['country']->name,
                $otherState->name,
                $masters['city']->name,
                $masters['zone']->name,
                '',
                '',
                '1',
            ]]
        );

        $result = $this->app->make(BrandImportService::class)->import(
            $this->makeUploadedExcel($binary),
            $this->user->id
        );

        $this->assertSame(0, $result['success_rows']);
        $this->assertSame(1, $result['failed_rows']);
        $this->assertSame(1, $result['failed_count']);
        $this->assertNotNull($result['failed_file_url']);
        $this->assertSame(
            "State '{$otherState->name}' does not belong to country '{$masters['country']->name}'.",
            $result['failed_records'][0]['reason']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMasters(): ?array
    {
        $brandType = BrandType::whereNull('deleted_at')->first();
        $industry = Industry::whereNull('deleted_at')->first();
        $zone = Zone::whereNull('deleted_at')->first();
        $country = Country::where('name', 'India')->first() ?? Country::first();

        if (!$brandType || !$industry || !$zone || !$country) {
            return null;
        }

        $state = State::where('country_id', $country->id)->first();
        if (!$state) {
            return null;
        }

        $city = City::where('state_id', $state->id)->first();
        if (!$city) {
            return null;
        }

        return [
            'brand_type' => $brandType,
            'industry' => $industry,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'zone' => $zone,
        ];
    }

    private function writeTempXlsx(string $binary): string
    {
        $path = sys_get_temp_dir() . '/brand_import_' . uniqid() . '.xlsx';
        file_put_contents($path, $binary);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeUploadedExcel(string $binary): UploadedFile
    {
        $path = $this->writeTempXlsx($binary);

        return new UploadedFile(
            $path,
            'brands.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
