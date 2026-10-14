<?php
/**
 * LeadSourceController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\LeadSourceService;
use App\Services\ResponseService;
use App\Http\Resources\LeadSourceResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Validation\ValidationException;

class LeadSourceController extends Controller
{
    /**
     * @var LeadSourceService
     */
    protected $leadSourceService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new LeadSourceController instance.
     *
     * @param LeadSourceService $leadSourceService
     * @param ResponseService $responseService
     */
    public function __construct(
        LeadSourceService $leadSourceService, 
        ResponseService $responseService
    ) {
        $this->leadSourceService = $leadSourceService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of lead sources.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search'   => 'nullable|string|max:100'
            ]);
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search', null);
            $leadSources = $this->leadSourceService->getAllLeadSources((int) $perPage, $searchTerm);
            $leadSources->setCollection(
                $leadSources->getCollection()->map(fn($item) => new LeadSourceResource($item))
            );

            return $this->responseService->paginated($leadSources, 'Lead sources fetched successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sources.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get list of lead sources with only id and name.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        try {
            $leadSources = $this->leadSourceService->getAllLeadSources(10000);
            $data = $leadSources->items() ? collect($leadSources->items())->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Lead sources list retrieved');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead sources list.', [$e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created lead source in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:lead_source',
                'description' => 'nullable|string',
                'status' => 'nullable|in:1,2,15',
            ]);

            $leadSource = $this->leadSourceService->createNewLeadSource($request->all());

            return $this->responseService->created(
                new LeadSourceResource($leadSource),
                'Lead source created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Exception $e) {
            return $this->responseService->error('Failed to create lead source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified lead source.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $leadSource = $this->leadSourceService->getLeadSource($id);

            return $this->responseService->success(
                new LeadSourceResource($leadSource),
                'Lead source fetched successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead source not found.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to fetch lead source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Update the specified lead source in storage.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'name' => 'sometimes|required|string|max:255|unique:lead_source,name,' . $id,
                'description' => 'nullable|string',
                'status' => 'sometimes|required|in:1,2,15',
            ]);

            $leadSource = $this->leadSourceService->updateLeadSource($id, $request->all());

            return $this->responseService->updated(
                new LeadSourceResource($leadSource),
                'Lead source updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead source not found for update.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to update lead source.', [$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified lead source from storage.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->leadSourceService->deleteLeadSource($id);
            return $this->responseService->deleted('Lead source deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->responseService->notFound('Lead source not found for deletion.');
        } catch (Exception $e) {
            return $this->responseService->error('Failed to delete lead source.', [$e->getMessage()], 500);
        }
    }
}
