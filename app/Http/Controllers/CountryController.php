<?php
/**
 * CountryController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\CountryService;
use App\Services\ResponseService;
use App\Http\Resources\CountryResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class CountryController extends Controller
{
    use ValidatesRequests;

    /**
     * @var CountryService
     */
    protected $countryService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new CountryController instance.
     *
     * @param CountryService $countryService
     * @param ResponseService $responseService
     */
    public function __construct(CountryService $countryService, ResponseService $responseService)
    {
        $this->countryService = $countryService;
        $this->responseService = $responseService;
    }

    /**
     * Get paginated list of countries (e.g., /api/v1/countries)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $search = $request->input('search', null);
            
            $countries = $this->countryService->getPaginatedCountries($perPage, $search);
            // Transform paginator items with CountryResource while keeping pagination meta
            $countries->getCollection()->transform(function ($country) {
                return new CountryResource($country);
            });
            return $this->responseService->paginated($countries, 'Countries retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of all countries (e.g., /api/v1/countries/all)
     * Useful for populating dropdowns.
     */
    public function getAll(): JsonResponse
    {
        try {
            $countries = $this->countryService->getAllCountries();
            return $this->responseService->success(CountryResource::collection($countries), 'All countries retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of countries with only id and name (e.g., /api/v1/countries/list)
     */
    public function list(): JsonResponse
    {
        try {
            $countries = $this->countryService->getAllCountries();
            $data = $countries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                ];
            });
            return $this->responseService->success($data, 'Countries list retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created country in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:countries,name',
            ];

            $validatedData = $this->validate($request, $rules);

            $country = $this->countryService->createCountry($validatedData);
            return $this->responseService->created(new CountryResource($country), 'Country created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display a specific country
     */
    public function show(int $id): JsonResponse
    {
        try {
            $country = $this->countryService->getCountryById($id);
            return $this->responseService->success(new CountryResource($country), 'Country retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified country
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:countries,name,' . $id,
            ];

            $validatedData = $this->validate($request, $rules);

            $country = $this->countryService->updateCountry($id, $validatedData);
            return $this->responseService->updated(new CountryResource($country), 'Country updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete the specified country (HARD delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->countryService->deleteCountry($id);
            return $this->responseService->deleted('Country deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}

