<?php

/**
 * PlannerStatusService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\PlannerStatusRepositoryInterface;
use App\Models\PlannerStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Exception;

class PlannerStatusService
{
    protected PlannerStatusRepositoryInterface $plannerStatusRepository;
    protected ResponseService $responseService;
    /**
     * PlannerStatusService constructor.
     *
     * @param PlannerStatusRepositoryInterface $plannerStatusRepository
     * @param ResponseService $responseService
     */
    public function __construct(PlannerStatusRepositoryInterface $plannerStatusRepository, ResponseService $responseService)
    {
        $this->plannerStatusRepository = $plannerStatusRepository;
        $this->responseService = $responseService;
    }

    /**
     * Get paginated planner statuses
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->plannerStatusRepository->search($criteria, $perPage);
    }

    /**
     * Find planner status by id
     */
    public function find(int $id): ?PlannerStatus
    {
        return $this->plannerStatusRepository->find($id);
    }

    /**
     * Find planner status by uuid
     */
    public function findByUuid(string $uuid): ?PlannerStatus
    {
        return $this->plannerStatusRepository->findByUuid($uuid);
    }

    /**
     * Find planner status by name
     */
    public function findByName(string $name): ?PlannerStatus
    {
        return $this->plannerStatusRepository->findByName($name);
    }

    /**
     * Find planner status by slug
     */
    public function findBySlug(string $slug): ?PlannerStatus
    {
        return $this->plannerStatusRepository->findBySlug($slug);
    }

    /**
     * Create a new planner status
     *
     * @param array $data Planner status data (name, slug, status)
     * @return PlannerStatus
     * @throws ValidationException
     */
    public function create(array $data): PlannerStatus
    {
        $this->validatePlannerStatusData($data);

        // Auto-generate UUID if not provided
        if (!isset($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        // Auto-generate slug from name if not provided
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = '1';
        }

        return $this->plannerStatusRepository->create($data);
    }

    /**
     * Update a planner status
     *
     * @param int $id Planner status ID
     * @param array $data Planner status data to update
     * @return bool
     * @throws ValidationException
     */
    public function update(int $id, array $data): bool
    {
        $plannerStatus = $this->plannerStatusRepository->find($id);

        if (!$plannerStatus) {
            throw new Exception('Planner Status not found');
        }

        $this->validatePlannerStatusData($data, $id);

        // Auto-generate slug from name if name is being updated
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->plannerStatusRepository->update($id, $data);
    }

    /**
     * Delete a planner status
     *
     * @param int $id Planner status ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        $plannerStatus = $this->plannerStatusRepository->find($id);

        if (!$plannerStatus) {
            throw new Exception('Planner Status not found');
        }

        return $this->plannerStatusRepository->delete($id);
    }

    /**
     * Validate planner status data
     *
     * @param array $data Data to validate
     * @param int|null $id For update validation
     * @throws ValidationException
     */
    protected function validatePlannerStatusData(array $data, ?int $id = null): void
    {
        $rules = [
            'name' => 'required|string|max:255|unique:planner_statuses,name' . ($id ? ",$id,id" : ''),
            'slug' => 'nullable|string|max:255|unique:planner_statuses,slug' . ($id ? ",$id,id" : ''),
            'status' => 'nullable|in:1,2,15',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
