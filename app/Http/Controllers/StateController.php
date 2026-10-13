<?php
/**
 * StateController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\StateService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\StateResource;

/**
 * Controller for managing states.
 * 
 * Handles CRUD operations for states and their relationships with countries.
 */
/**
 * Controller for managing states.
 * 
 * Handles CRUD operations for states and their relationships with countries.
 */
class StateController extends Controller
{
    use ValidatesRequests;

    protected $stateService;

    /**
     * The ResponseService instance.
     *
     * @var ResponseService
     */

    /**
     * The ResponseService instance.
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new StateController instance.
     *
     * @param StateService $stateService Service for state operations
     * @param ResponseService $responseService Service for standardized API responses
     */
    /**
     * Create a new StateController instance.
     *
     * @param StateService $stateService Service for state operations
     * @param ResponseService $responseService Service for standardized API responses
     */
    public function __construct(StateService $stateService, ResponseService $responseService)
    {
        $this->stateService = $stateService;
        $this->responseService = $responseService;
    }

    /**
     * Get paginated list of states (e.g., /api/v1/states)
     * Get paginated list of states (e.g., /api/v1/states)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $search = $request->input('search', null);
            
            $states = $this->stateService->getPaginatedStates($perPage, $search);
            // Transform paginator items while preserving pagination meta
            $states->getCollection()->transform(function ($state) {
                return new StateResource($state);
            });
            return $this->responseService->paginated($states, 'States retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get a list of all states.
     * 
     * GET /states/all
     * 
     * @return \Illuminate\Http\JsonResponse
     * Get a list of all states.
     * 
     * GET /states/all
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(): JsonResponse
    {
        try {
            $states = $this->stateService->getAllStates();
            return $this->responseService->success(StateResource::collection($states), 'All states retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of states with only id and name for a specific country (e.g., /api/v1/states/list?country_id=1)
     * country_id is mandatory
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'country_id' => 'required|integer|exists:countries,id',
            ]);
            
            $countryId = $request->input('country_id');
            
            // Get states for specific country
            $states = $this->stateService->getStatesByCountry($countryId);
            
            $data = $states->map(function ($state) {
                return [
                    'id' => $state->id,
                    'name' => $state->name,
                ];
            });
            return $this->responseService->success($data, 'States list retrieved');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all states for a specific country.
     * 
     * GET /countries/{countryId}/states
     * 
     * @param int $countryId
     * @return \Illuminate\Http\JsonResponse
     * Get all states for a specific country.
     * 
     * GET /countries/{countryId}/states
     * 
     * @param int $countryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatesByCountry($countryId): JsonResponse
    {
        try {
            $states = $this->stateService->getStatesByCountry($countryId);
            // Transform data using Resource collection
            // Transform data using Resource collection
            $data = StateResource::collection($states);
            return $this->responseService->success($data, 'States for country retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created state.
     * 
     * POST /states
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Store a newly created state.
     * 
     * POST /states
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'country_id' => 'required|integer|exists:countries,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $state = $this->stateService->createState($validatedData);
            $data = new StateResource($state);
            return $this->responseService->created($data, 'State created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified state.
     * 
     * GET /states/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * Display the specified state.
     * 
     * GET /states/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $state = $this->stateService->getStateById($id);
            // Transform data using Resource
            // Transform data using Resource
            $data = new StateResource($state);
            return $this->responseService->success($data, 'State retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified state.
     * 
     * PUT /states/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * Update the specified state.
     * 
     * PUT /states/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'country_id' => 'required|integer|exists:countries,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $state = $this->stateService->updateState($id, $validatedData);
            $data = new StateResource($state);
            return $this->responseService->updated($data, 'State updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified state from storage (HARD delete).
     * 
     * DELETE /states/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * Remove the specified state from storage (HARD delete).
     * 
     * DELETE /states/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->stateService->deleteState($id);
            return $this->responseService->deleted('State deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}

