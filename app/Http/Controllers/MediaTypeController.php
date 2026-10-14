<?php

/**
 * MediaType Controller
 * -----------------------------------------
 * Handles HTTP requests for media type management, providing CRUD API endpoints.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Http\Controllers;

use App\Http\Resources\MediaTypeResource;
use App\Services\MediaTypeService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class MediaTypeController extends Controller
{
    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var MediaTypeService
     */
    protected MediaTypeService $mediaTypeService;

    /**
     * Create a new MediaTypeController instance.
     *
     * @param MediaTypeService $mediaTypeService
     * @param ResponseService $responseService
     */
    public function __construct(MediaTypeService $mediaTypeService, ResponseService $responseService)
    {
        $this->mediaTypeService = $mediaTypeService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of media types.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
            ]);

            $perPage = (int) $request->get('per_page', 15);
            $mediaTypes = $this->mediaTypeService->getAll($perPage);

            return $this->responseService->paginated(
                MediaTypeResource::collection($mediaTypes),
                'Media types retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created media type in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:media_types,name,NULL,id,deleted_at,NULL',
                //'status' => 'nullable|boolean',
            ]);

            $validatedData = $validator->validate();
            $mediaType = $this->mediaTypeService->create($validatedData);

            return $this->responseService->created(
                new MediaTypeResource($mediaType),
                'Media type created successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified media type.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $mediaType = $this->mediaTypeService->findById((int) $id);

            return $this->responseService->success(
                new MediaTypeResource($mediaType),
                'Media type retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified media type in storage.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('media_types', 'name')
                        ->ignore($id)
                        ->whereNull('deleted_at')
                ],
                //'status' => 'nullable|boolean',
            ]);

            $validatedData = $validator->validate();
            $mediaType = $this->mediaTypeService->update((int) $id, $validatedData);

            return $this->responseService->success(
                new MediaTypeResource($mediaType),
                'Media type updated successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified media type from storage.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->mediaTypeService->delete((int) $id);

            return $this->responseService->deleted('Media type deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of all media types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        try {
            $mediaTypes = $this->mediaTypeService->list();

            return $this->responseService->success(
                MediaTypeResource::collection($mediaTypes),
                'Media types list retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
