<?php
/**
 * OrganisationController
 * -----------------------------------------
 * Handles API operations for organisations including listing,
 * pagination, and fetching organisation details by ID.
 * Utilizes service layer for business logic, API resources for
 * data transformation, and ResponseService for standardized responses.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.1
 * @since 2026-05-05
 */
namespace App\Http\Controllers;

use App\Services\OrganisationService;
use App\Services\ResponseService;
use App\Http\Resources\OrganisationResource;
use App\Traits\ValidatesRequests;
use App\Models\Organisation;
use App\Support\UserAccessScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class OrganisationController extends Controller
{
    use ValidatesRequests;

    /**
     * @var OrganisationService
     */
    protected $organisationService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new OrganisationController instance.
     *
     * @param OrganisationService $organisationService
     * @param ResponseService $responseService
     */
    public function __construct(OrganisationService $organisationService, ResponseService $responseService)
    {
        $this->organisationService = $organisationService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of organisations.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10) ?? 10;
            $searchTerm = $request->get('search', null);
            $organisations = $this->organisationService->getAllOrganisations($perPage, $searchTerm);

            return $this->responseService->paginated(
                OrganisationResource::collection($organisations),
                'Organisations fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of organisations with only id and name (e.g., /api/v1/organisations/list)
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->responseService->success([], 'Organisations list fetched successfully.');
            }

            $accessibleOrgIds = UserAccessScope::getAccessibleOrganisationIds($user);

            if (empty($accessibleOrgIds)) {
                return $this->responseService->success([], 'Organisations list fetched successfully.');
            }

            $data = $this->organisationService->getOrganisationsListByIds($accessibleOrgIds);

            return $this->responseService->success(
                $data,
                'Organisations list fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified organisation.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $organisation = $this->organisationService->getOrganisationById($id);

            return $this->responseService->success(
                new OrganisationResource($organisation),
                'Organisation fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
