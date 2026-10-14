<?php
/**
 * LeadSubSourceController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
/*
 * LeadSubSourceController
 * -----------------------------------------
 * This controller handles the API endpoints for managing lead sub-sources,
 * including listing, creating, updating, and deleting sub-source records.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-06
 */

namespace App\Http\Controllers;

use App\Http\Resources\LeadSubSourceResource;
use App\Services\LeadSubSourceService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LeadSubSourceController extends Controller
{
    /**
     * The LeadSubSourceService instance.
     *
     * @var LeadSubSourceService
     */
    protected $leadSubSourceService;

    /**
     * The ResponseService instance.
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new LeadSubSourceController instance.
     *
     * @param LeadSubSourceService $leadSubSourceService
     * @param ResponseService $responseService
     */
    public function __construct(
        LeadSubSourceService $leadSubSourceService,
        ResponseService $responseService
    ) {
        $this->leadSubSourceService = $leadSubSourceService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of lead sub-sources with optional filtering.
     * 
     * GET /lead-sub-sources
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Validate the lead_source_id and search filters
            $this->validate($request, [
                'lead_source_id' => 'nullable|integer|exists:lead_source,id',
                'search'         => 'nullable|string|max:100' // Add validation for search parameter
            ]);

            // 1. Get pagination parameter
            $perPage = (int) $request->get('per_page', 10);

            // 2. Collect all filters in a single array for consistency
            $filters = [
                'lead_source_id' => $request->input('lead_source_id'),
                'search'         => $request->input('search', null) // Include search parameter in filters
            ];

            // 3. Pass only filters and perPage to service
            // Repository will handle the search filter internally
            $leadSubSources = $this->leadSubSourceService->getAllLeadSubSources($filters, $perPage);

            // Handle empty result set gracefully
            if ($leadSubSources->isEmpty() && !($leadSubSources instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $leadSubSources->total() > 0)) {
                return $this->responseService->success([], 'No lead sub-sources found.');
            }

            return $this->responseService->paginated(
                LeadSubSourceResource::collection($leadSubSources),
                'Lead sub-sources fetched successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sub-sources.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get list of lead sub-sources with only id and name (e.g., /api/v1/lead-sub-sources/list)
     */
    public function list()
    {
        try {
            $leadSubSources = $this->leadSubSourceService->getAllLeadSubSources([], 10000);
            $data = $leadSubSources->items() ? collect($leadSubSources->items())->map(function ($subSource) {
                return [
                    'id' => $subSource->id,
                    'name' => $subSource->name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Lead sub-sources list retrieved');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sub-sources list.', [$e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created lead sub-source.
     * 
     * POST /lead-sub-sources
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'lead_source_id' => 'required|integer|exists:lead_source,id',
                'name' => 'required|string|max:255|unique:lead_sub_source,name,NULL,id,lead_source_id,' . $request->lead_source_id . ',deleted_at,NULL',
                'description' => 'nullable|string',
                'status' => 'nullable|in:1,2,15',
            ], [
                'name.unique' => 'This sub source already exists for the selected lead source.'
            ]);
            $leadSubSource = $this->leadSubSourceService->createNewLeadSubSource($request->all());

            return $this->responseService->created(
                new LeadSubSourceResource($leadSubSource),
                'Lead sub-source created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Exception $e) {
            return $this->responseService->error('Failed to create lead sub-source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created lead sub-source under the default "direct" lead source (no lead_source_id in body).
     *
     * POST /lead-sub-sources/name
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUnderDirect(Request $request)
    {
        try {
            $directSourceId = $this->leadSubSourceService->getDirectLeadSourceId();
        } catch (InvalidArgumentException $e) {
            Log::warning('Lead sub-source: default direct lead source missing or invalid', ['exception' => $e]);

            return $this->responseService->error(
                'The default direct lead source is not available.',
                ['lead_source' => ['Unable to configure default lead source.']],
                ResponseService::HTTP_UNPROCESSABLE_ENTITY,
                'DIRECT_LEAD_SOURCE_UNAVAILABLE'
            );
        } catch (Exception $e) {
            Log::error('Lead sub-source: internal error resolving default lead source', ['exception' => $e]);

            return $this->responseService->error(
                'An internal error occurred while resolving the default lead source.',
                ['lead_source' => ['Unable to resolve default lead source']],
                ResponseService::HTTP_SERVER_ERROR,
                'DIRECT_LEAD_SOURCE_LOOKUP_FAILED'
            );
        }

        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:lead_sub_source,name,NULL,id,lead_source_id,' . $directSourceId . ',deleted_at,NULL',
                'description' => 'nullable|string',
                'status' => 'nullable|in:1,2,15',
            ], [
                'name.unique' => 'This sub source already exists for the selected lead source.',
            ]);

            $payload = array_merge($request->all(), ['lead_source_id' => $directSourceId]);
            $leadSubSource = $this->leadSubSourceService->createNewLeadSubSource($payload);

            return $this->responseService->created(
                new LeadSubSourceResource($leadSubSource),
                'Lead sub-source created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Exception $e) {
            return $this->responseService->error(
                'Failed to create lead sub-source.',
                [$e->getMessage()],
                ResponseService::HTTP_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified lead sub-source.
     * 
     * GET /lead-sub-sources/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $leadSubSource = $this->leadSubSourceService->getLeadSubSource($id);

            return $this->responseService->success(
                new LeadSubSourceResource($leadSubSource),
                'Lead sub-source fetched successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead sub-source not found.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sub-source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Update the specified lead sub-source.
     * 
     * PUT /lead-sub-sources/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $leadSubSource = $this->leadSubSourceService->getLeadSubSource($id);
            $leadSourceId = $request->input('lead_source_id', $leadSubSource->lead_source_id);

            $this->validate($request, [
                'lead_source_id' => 'sometimes|required|integer|exists:lead_source,id',
                'name' => 'sometimes|required|string|max:255|unique:lead_sub_source,name,' . $id . ',id,lead_source_id,' . ($request->lead_source_id ?? $leadSubSource->lead_source_id) . ',deleted_at,NULL',
                'description' => 'nullable|string',
                'status' => 'sometimes|required|in:1,2,15',
            ], [
                'name.unique' => 'This sub source already exists for the selected lead source.'
            ]);

            $leadSubSource = $this->leadSubSourceService->updateLeadSubSource($id, $request->all());

            return $this->responseService->updated(
                new LeadSubSourceResource($leadSubSource),
                'Lead sub-source updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead sub-source not found for update.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to update lead sub-source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified lead sub-source.
     * 
     * DELETE /lead-sub-sources/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->leadSubSourceService->deleteLeadSubSource($id);
            return $this->responseService->deleted('Lead sub-source deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead sub-source not found for deletion.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to delete lead sub-source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get lead sub-sources by source ID
     * 
     * GET /lead-sub-sources/by-source/{sourceId}
     * 
     * @param int $sourceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySourceId($sourceId)
    {
        try {
            $leadSubSources = $this->leadSubSourceService->getLeadSubSourcesBySourceId($sourceId);

            $response = $this->responseService->success(
                LeadSubSourceResource::collection($leadSubSources),
                'Lead sub-sources fetched successfully by source ID.'
            );

            // Add total to meta
            $responseData = $response->getData(true);
            $responseData['meta']['total'] = $leadSubSources->count();

            return response()->json($responseData, 200);
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sub-sources.', [$e->getMessage()], 500);
        }
    }
}
