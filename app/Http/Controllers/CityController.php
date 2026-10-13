<?php
/**
 * CityController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\CityService;
use App\Services\ResponseService;
use App\Http\Resources\CityResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    use ValidatesRequests;

    /**
     * @var CityService
     */
    protected $cityService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new CityController instance.
     *
     * @param CityService $cityService
     * @param ResponseService $responseService
     */
    public function __construct(CityService $cityService, ResponseService $responseService)
    {
        $this->cityService = $cityService;
        $this->responseService = $responseService;
    }

    /**
     * Get paginated list of cities (e.g., /api/v1/cities)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $search = $request->input('search', null);
            
            $cities = $this->cityService->getPaginatedCities($perPage, $search);
            // Transform paginator items while preserving pagination meta
            $cities->getCollection()->transform(function ($city) {
                return new CityResource($city);
            });
            return $this->responseService->paginated($cities, 'Cities retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of all cities (e.g., /api/v1/cities/all)
     */
    public function getAll(): JsonResponse
    {
        try {
            $cities = $this->cityService->getAllCities();
            $data = CityResource::collection($cities);
            return $this->responseService->success($data, 'All cities retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of cities with only id and name for a specific state (e.g., /api/v1/cities/list?state_id=1)
     * state_id is mandatory
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'state_id' => 'required|integer|exists:states,id',
            ]);
            
            $stateId = $request->input('state_id');
            
            // Get cities for specific state
            $cities = $this->cityService->getCitiesByState($stateId);
            
            $data = $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                ];
            });
            return $this->responseService->success($data, 'Cities list retrieved');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all cities for a specific state
     * (e.g., /api/v1/states/1/cities)
     */
    public function getCitiesByState($stateId): JsonResponse
    {
        try {
            $cities = $this->cityService->getCitiesByState($stateId);
            $data = CityResource::collection($cities);
            return $this->responseService->success($data, 'Cities for state retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all cities for a specific country
     * (e.g., /api/v1/countries/1/cities)
     */
    public function getCitiesByCountry($countryId): JsonResponse
    {
        try {
            $cities = $this->cityService->getCitiesByCountry($countryId);
            $data = CityResource::collection($cities);
            return $this->responseService->success($data, 'Cities for country retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a new city in the database
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'country_id' => 'required|integer|exists:countries,id',
                'state_id' => 'required|integer|exists:states,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('cities')->where(function ($query) use ($request) {
                        return $query->where('state_id', $request->state_id);
                    }),
                ],
            ];

            $validatedData = $this->validate($request, $rules);

            $city = $this->cityService->createCity($validatedData);
            $data = new CityResource($city->load(['country', 'state']));
            return $this->responseService->created($data, 'City created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display a specific city
     */
    public function show(int $id): JsonResponse
    {
        try {
            $city = $this->cityService->getCityById($id);
            $data = new CityResource($city);
            return $this->responseService->success($data, 'City retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified city
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'country_id' => 'required|integer|exists:countries,id',
                'state_id' => 'required|integer|exists:states,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('cities')->where(function ($query) use ($request) {
                        return $query->where('state_id', $request->state_id);
                    })->ignore($id),
                ],
            ];

            $validatedData = $this->validate($request, $rules);

            $city = $this->cityService->updateCity($id, $validatedData);
            $data = new CityResource($city->load(['country', 'state']));
            return $this->responseService->updated($data, 'City updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified city from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->cityService->deleteCity($id);
            return $this->responseService->deleted('City deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
