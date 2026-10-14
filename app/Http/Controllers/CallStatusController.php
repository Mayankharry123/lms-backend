<?php
/**
 * CallStatusController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\CallStatusResource;
use App\Services\CallStatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class CallStatusController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var CallStatusService
     */
    protected CallStatusService $callStatusService;

    /**
     * Create a new CallStatusController instance.
     *
     * @param ResponseService $responseService
     * @param CallStatusService $callStatusService
     */
    public function __construct(ResponseService $responseService, CallStatusService $callStatusService)
    {
        $this->responseService = $responseService;
        $this->callStatusService = $callStatusService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of the call statuses.
     *
     * GET /call-statuses
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

            $callStatuses = $this->callStatusService->getAllCallStatuses($perPage, $searchTerm);

            return $this->responseService->paginated(
                CallStatusResource::collection($callStatuses),
                'Call statuses retrieved successfully'
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
     * Display the specified call status.
     *
     * GET /call-statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $callStatus = $this->callStatusService->getCallStatus($id);

            if (!$callStatus) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new CallStatusResource($callStatus),
                'Call status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get priorities associated with a specific call status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getPriorities(int $id): JsonResponse
    {
    try {
        // Ensure call status exists
        $callStatus = $this->callStatusService->getCallStatus($id);
        if (!$callStatus) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $priorities = \App\Models\Priority::all()
            ->filter(fn ($priority) =>
                in_array($id, (array) $priority->call_status)
            )
            ->map(fn ($priority) => [
                'id'   => $priority->id,
                'name' => $priority->name,
            ])
            ->values();

        return $this->responseService->success(
            $priorities,
            'Priorities retrieved successfully for call status'
        );
    } catch (Throwable $e) {
        return $this->responseService->handleException($e);
    }
}


    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Store a newly created call status in storage.
     *
     * POST /call-statuses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:call_statuses,name',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Add system-generated fields
            $validatedData['slug'] = Str::slug($request->name);
            $validatedData['status'] = $validatedData['status'] ?? '1';

            $callStatus = $this->callStatusService->createCallStatus($validatedData);

            return $this->responseService->created(
                new CallStatusResource($callStatus),
                'Call status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified call status in storage.
     *
     * PUT /call-statuses/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:call_statuses,name,' . $id,
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['slug'] = Str::slug($request->name);
            }

            $callStatus = $this->callStatusService->updateCallStatus($id, $validatedData);

            return $this->responseService->updated(
                new CallStatusResource($callStatus),
                'Call status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified call status from storage (Soft Delete).
     *
     * DELETE /call-statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->callStatusService->deleteCallStatus($id);

            return $this->responseService->deleted('Call status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
