<?php

/**
 * BrandController
 * -----------------------------------------
 * This controller manages CRUD operations for brands, including listing,
 * creating, updating, and deleting brand records. It also handles
 * relationships between brands and agencies.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Services\BrandImportService;
use App\Services\BrandService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Illuminate\Validation\ValidationException;
use DomainException;

class BrandController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var BrandService
     */
    protected BrandService $brandService;

    /**
     * @var BrandImportService
     */
    protected BrandImportService $brandImportService;

    /**
     * Create a new BrandController instance.
     *
     * @param ResponseService $responseService
     * @param BrandService $brandService
     * @param BrandImportService $brandImportService
     */
    public function __construct(
        ResponseService $responseService,
        BrandService $brandService,
        BrandImportService $brandImportService
    ) {
        $this->responseService = $responseService;
        $this->brandService = $brandService;
        $this->brandImportService = $brandImportService;
    }

    /**
     * Display a listing of the brands.
     *
     * GET /brands
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $searchTerm = $request->input('search', null);

            $brands = $this->brandService->getAllBrands($perPage, $searchTerm);

            return $this->responseService->paginated(
                BrandResource::collection($brands),
                'Brands retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified brand.
     *
     * GET /brands/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $brand = $this->brandService->getBrand($id);

            if (!$brand) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new BrandResource($brand),
                'Brand retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created brand in storage.
     *
     * POST /brands
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|unique:brands,name,NULL,id,deleted_at,NULL|string|max:255',
                'brand_type_id' => 'required|integer|exists:brand_types,id',
                'industry_id' => 'required|integer|exists:industries,id',
                'country_id' => 'required|integer|exists:countries,id',
                'website' => 'nullable|url|max:255',
                'address' => 'nullable|string|max:1000',
                'gst_no' => 'nullable',
                'gst_number' => 'nullable|string|max:50',
                'gst_numbers' => 'nullable|array',
                'gst_numbers.*' => 'string|max:50',
                'postal_code' => 'nullable|string|max:20',
                'state_id' => 'required|integer|exists:states,id',
                'city_id' => 'required|integer|exists:cities,id',
                'zone_id' => 'required|integer|exists:zones,id',
                'agency_id' => 'nullable|integer|exists:agency,id',
                'agency_ids' => 'nullable|array',
                'agency_ids.*' => 'integer|exists:agency,id',
            ];

            $validatedData = $this->validate($request, $rules);

            // Trim whitespace from name
            $validatedData['name'] = trim($validatedData['name']);

            // Normalize GST numbers and Address
            $gstNumbers = [];
            $primaryGst = null;

            if ($request->has('gst_numbers') && is_array($request->input('gst_numbers'))) {
                $gstNumbers = array_values(array_filter(array_map('trim', $request->input('gst_numbers'))));
                $primaryGst = $gstNumbers[0] ?? null;
            } elseif ($request->has('gst_no')) {
                $rawGst = $request->input('gst_no');
                if (is_array($rawGst)) {
                    $gstNumbers = array_values(array_filter(array_map('trim', $rawGst)));
                    $primaryGst = $gstNumbers[0] ?? null;
                } elseif (is_string($rawGst) && trim($rawGst) !== '') {
                    $primaryGst = trim($rawGst);
                    $gstNumbers = [$primaryGst];
                }
            } elseif ($request->has('gst_number') && is_string($request->input('gst_number')) && trim($request->input('gst_number')) !== '') {
                $primaryGst = trim($request->input('gst_number'));
                $gstNumbers = [$primaryGst];
            }

            if ($primaryGst !== null) {
                $validatedData['gst_no'] = $primaryGst;
            }
            if (!empty($gstNumbers)) {
                $validatedData['gst_numbers'] = $gstNumbers;
            }
            if ($request->has('address')) {
                $validatedData['address'] = $request->input('address') ? trim((string) $request->input('address')) : null;
            }

            // Add system-generated fields
            // Generate a temporary unique slug to avoid constraint violations
            // The actual slug will be finalized in the repository with the brand ID
            $validatedData['slug'] = Str::slug($request->name) . '-temp-' . Str::random(6);
            $validatedData['created_by'] = Auth::id();
            $validatedData['status'] = '1';

            $brand = $this->brandService->createBrand($validatedData);

            return $this->responseService->created(
                new BrandResource($brand),
                'Brand created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a new brand with only name.
     *
     * POST /brands/name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeByName(Request $request): JsonResponse
    {
        try {
            $nameInput = $request->input('name');

            $validatedData = $this->validate($request, [
                'name' => 'required|string|max:255|unique:brands,name,NULL,id,deleted_at,NULL',
            ]);

            $validatedData['name'] = trim($nameInput);
            $validatedData['created_by'] = Auth::id();
            $validatedData['status'] = '1';

            $brand = $this->brandService->createBasicBrand($validatedData);

            return $this->responseService->created(
                new BrandResource($brand),
                'Brand created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->validationError(
                ['brand' => [$e->getMessage()]],
                $e->getMessage()
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified brand in storage.
     *
     * PUT /brands/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => "sometimes|required|unique:brands,name,{$id},id,deleted_at,NULL|string|max:255",
                'brand_type_id' => 'sometimes|required|integer|exists:brand_types,id',
                'industry_id' => 'sometimes|required|integer|exists:industries,id',
                'country_id' => 'sometimes|required|integer|exists:countries,id',
                'website' => 'sometimes|nullable|url|max:255',
                'address' => 'sometimes|nullable|string|max:1000',
                'gst_no' => 'sometimes|nullable',
                'gst_number' => 'sometimes|nullable|string|max:50',
                'gst_numbers' => 'sometimes|nullable|array',
                'gst_numbers.*' => 'string|max:50',
                'postal_code' => 'sometimes|nullable|string|max:20',
                'state_id' => 'sometimes|required|integer|exists:states,id',
                'city_id' => 'sometimes|required|integer|exists:cities,id',
                'zone_id' => 'sometimes|required|integer|exists:zones,id',
                'agency_id' => 'sometimes|nullable|integer|exists:agency,id',
                'agency_ids' => 'nullable|array',
                'agency_ids.*' => 'integer|exists:agency,id',
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['name'] = trim($validatedData['name']);
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }

            // Normalize GST numbers and Address
            if ($request->has('gst_numbers') || $request->has('gst_no') || $request->has('gst_number')) {
                $gstNumbers = [];
                $primaryGst = null;

                if ($request->has('gst_numbers') && is_array($request->input('gst_numbers'))) {
                    $gstNumbers = array_values(array_filter(array_map('trim', $request->input('gst_numbers'))));
                    $primaryGst = $gstNumbers[0] ?? null;
                } elseif ($request->has('gst_no')) {
                    $rawGst = $request->input('gst_no');
                    if (is_array($rawGst)) {
                        $gstNumbers = array_values(array_filter(array_map('trim', $rawGst)));
                        $primaryGst = $gstNumbers[0] ?? null;
                    } elseif (is_string($rawGst)) {
                        $primaryGst = trim($rawGst) !== '' ? trim($rawGst) : null;
                        $gstNumbers = $primaryGst ? [$primaryGst] : [];
                    }
                } elseif ($request->has('gst_number')) {
                    $raw = (string) $request->input('gst_number');
                    $primaryGst = trim($raw) !== '' ? trim($raw) : null;
                    $gstNumbers = $primaryGst ? [$primaryGst] : [];
                }

                $validatedData['gst_no'] = $primaryGst;
                $validatedData['gst_numbers'] = $gstNumbers;
            }

            if ($request->has('address')) {
                $validatedData['address'] = $request->input('address') ? trim((string) $request->input('address')) : null;
            }

            $this->brandService->updateBrand($id, $validatedData);

            // Fetch updated brand with relationships
            $brand = $this->brandService->getBrand($id);

            if (!$brand) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->updated(
                new BrandResource($brand),
                'Brand updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->validationError(
                ['brand' => [$e->getMessage()]],
                $e->getMessage()
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified brand from storage (Soft Delete).
     *
     * DELETE /brands/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->brandService->deleteBrand($id);

            return $this->responseService->deleted('Brand deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Import brands from an Excel file.
     *
     * POST /brands/import
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'file' => 'required|file|mimes:xlsx,xls|max:51200',
            ], [
                'file.required' => 'Excel file is required.',
                'file.file' => 'The uploaded file is invalid.',
                'file.mimes' => 'The file must be a valid Excel file (.xlsx or .xls).',
                'file.max' => 'The Excel file may not be greater than 50 MB.',
            ]);

            $result = $this->brandImportService->import(
                $request->file('file'),
                Auth::id()
            );

            $message = ($result['status'] ?? '') === 'failed'
                ? 'Brand Excel import failed.'
                : 'Brand import completed.';

            if (($result['status'] ?? '') === 'failed') {
                return $this->responseService->apiResponse(
                    false,
                    $result,
                    $message,
                    ResponseService::HTTP_UNPROCESSABLE_ENTITY,
                    null,
                    'IMPORT_FAILED'
                );
            }

            return $this->responseService->success($result, $message);
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->validationError(
                ['file' => [$e->getMessage()]],
                $e->getMessage()
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download a demo Excel template for brand import.
     *
     * GET /brands/import-template
     *
     * @return StreamedResponse|JsonResponse
     */
    public function importTemplate(): StreamedResponse|JsonResponse
    {
        try {
            $contents = $this->brandService->generateBrandImportTemplate();
            $filename = 'brand-import-template.xlsx';

            return response()->streamDownload(
                function () use ($contents) {
                    echo $contents;
                },
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ]
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download the failed-records Excel generated by a brand import.
     *
     * GET /brands/import-failed-files/{token}
     *
     * @param string $token
     * @return StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function downloadFailedRecords(string $token)
    {
        try {
            return $this->brandImportService->downloadFailedRecordsFile($token);
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of brands (for dropdowns)
     * If brand_id is provided, return the agency for that brand
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $brands = $this->brandService->getBrandList();
            return $this->responseService->success($brands, 'Brand list retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all agencies for a specific brand
     *
     * GET /brands/{id}/agencies
     *
     * @param int $id
     * @return JsonResponse
     */
    public function agencies(int $id): JsonResponse
    {
        try {
            $brand = $this->brandService->getBrand($id);

            if (!$brand) {
                return $this->responseService->notFound('Brand not found');
            }

            // Get all agencies related to this brand through the pivot table
            $agencies = $brand->agencies()
                ->get()
                ->map(function ($agency) {
                    return [
                        'id' => $agency->id,
                        'name' => $agency->name,
                    ];
                });

            return $this->responseService->success(
                $agencies,
                'Agencies retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get GST number and address details by Brand ID.
     *
     * GET /brands/{id}/gst-address
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getGstAndAddress(int $id): JsonResponse
    {
        try {
            $details = $this->brandService->getBrandBillingDetails($id);

            if (!$details) {
                return $this->responseService->notFound('Brand not found');
            }

            return $this->responseService->success(
                $details,
                'Brand GST and address details retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}