<?php

/**
 * LeadTypeController
 * -----------------------------------------
 * This controller manages lead types, providing endpoints to create,
 * retrieve, and list lead types. It utilizes the LeadTypeService for
 * business logic and ResponseService for consistent API responses.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Http\Controllers;

use App\Http\Resources\LeadTypeResource;
use App\Services\LeadTypeService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeadTypeController extends Controller
{


    /**
     * @var LeadTypeService
     */
    protected $leadTypeService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new LeadTypeController instance.
     *
     * @param LeadTypeService $leadTypeService
     * @param ResponseService $responseService
     */
    public function __construct(LeadTypeService $leadTypeService, ResponseService $responseService)
    {
        $this->leadTypeService = $leadTypeService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of lead types.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10) ?? 10;
            $searchTerm = $request->get('search', null);
            $leadTypes = $this->leadTypeService->getAllLeadTypes($perPage, $searchTerm);

            return $this->responseService->paginated(
                LeadTypeResource::collection($leadTypes),
                'Lead types fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of lead types with only id and name (e.g., /api/v1/lead-types/list)
     */
    public function list(): JsonResponse
    {
        try {
            $leadTypes = $this->leadTypeService->listLeadTypes();
            $data = $leadTypes->map(function ($leadType) {
                return [
                    'id' => $leadType->id,
                    'name' => $leadType->name,
                ];
            });

            return $this->responseService->success(
                $data,
                'Lead types list fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified lead type.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $leadType = $this->leadTypeService->getLeadTypeById($id);

            return $this->responseService->success(
                new LeadTypeResource($leadType),
                'Lead type fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created lead type in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:lead_types,name',
                'status' => 'nullable|in:1,2,15',
            ]);

            $payload = $request->only(['name', 'status']);
            $payload['slug'] = \Illuminate\Support\Str::slug($payload['name']);
            $payload['status'] = (string) ($payload['status'] ?? '2');

            $leadType = $this->leadTypeService->createLeadType($payload);

            return $this->responseService->created(
                new LeadTypeResource($leadType),
                'Lead type created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
