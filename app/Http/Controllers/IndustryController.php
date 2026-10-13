<?php
/**
 * IndustryController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\IndustryService;
use App\Services\ResponseService;
use App\Http\Resources\IndustryResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class IndustryController extends Controller
{
    use ValidatesRequests;

    /**
     * @var IndustryService
     */
    protected $industryService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new IndustryController instance.
     *
     * @param IndustryService $industryService
     * @param ResponseService $responseService
     */
    public function __construct(IndustryService $industryService, ResponseService $responseService)
    {
        $this->industryService = $industryService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of industries.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search', null);
            $industries = $this->industryService->getAllIndustries($perPage, $searchTerm);

            return $this->responseService->paginated(
                IndustryResource::collection($industries),
                'Industries fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of industries with only id and name (e.g., /api/v1/industries/list)
     */
    public function list(): JsonResponse
    {
        try {
            $industries = $this->industryService->getAllIndustries(perPage: 10000);
            $data = $industries->items() ? collect($industries->items())->map(function ($industry) {
                return [
                    'id' => $industry->id,
                    'name' => $industry->name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Industries list retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created industry in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            if (isset($data['industry_name']) && empty($data['name'])) {
                $data['name'] = $data['industry_name'];
            }

            $rules = [
                'name' => 'required|string|max:255|unique:industries,name,NULL,id,deleted_at,NULL',
                'slug' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            if (empty($validatedData['slug']) && !empty($validatedData['name'])) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }

            $industry = $this->industryService->createNewIndustry($validatedData);
            return $this->responseService->created(new IndustryResource($industry), 'Industry created successfully.');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified industry.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $industry = $this->industryService->getIndustry($id);
            return $this->responseService->success(new IndustryResource($industry), 'Industry fetched successfully.');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified industry in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();

            if (isset($data['industry_name']) && empty($data['name'])) {
                $data['name'] = $data['industry_name'];
            }

            $rules = [
                'name' => 'required|string|max:255|unique:industries,name,' . $id . ',id,deleted_at,NULL',
                'slug' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            if (empty($validatedData['slug']) && !empty($validatedData['name'])) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }

            $industry = $this->industryService->updateIndustry($id, $validatedData);
            return $this->responseService->updated(new IndustryResource($industry), 'Industry updated successfully.');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified industry from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->industryService->deleteIndustry($id);
            return $this->responseService->deleted('Industry deleted successfully.');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
