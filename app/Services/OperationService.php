<?php

/**
 * Operation Service
 * -----------------------------------------
 * Handles the business rule for listing operations.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Services;

use App\Contracts\Repositories\OperationRepositoryInterface;
use App\Contracts\Repositories\OperationStatusRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Operation;
use App\Models\Planner;
use App\Support\UserAccessScope;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OperationService
{
    protected OperationRepositoryInterface $operationRepository;
    protected OperationStatusRepositoryInterface $operationStatusRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        OperationRepositoryInterface $operationRepository,
        OperationStatusRepositoryInterface $operationStatusRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->operationRepository = $operationRepository;
        $this->operationStatusRepository = $operationStatusRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get a paginated list of operations.
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->operationRepository->paginate($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching operations list', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find one operation for the detail endpoint.
     *
     * @throws Throwable
     */
    public function find(int $id): ?Operation
    {
        try {
            return $this->operationRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching operation by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Change the operation status of one operation.
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function updateStatus(int $id, int $operationStatusId, ?string $comment = null): ?Operation
    {
        try {
            $operation = $this->operationRepository->find($id);

            if (!$operation) {
                return null;
            }

            $operationStatus = $this->operationStatusRepository->find($operationStatusId);

            if (!$operationStatus || (string) $operationStatus->status !== '1') {
                throw new DomainException('Operation status not found');
            }

            return $this->operationRepository->updateStatus($id, $operationStatusId, $comment);
        } catch (Throwable $e) {
            if (!$e instanceof DomainException) {
                Log::error('Error updating operation status', [
                    'id' => $id,
                    'operation_status_id' => $operationStatusId,
                    'exception' => $e,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Assign or reassign an operation. assign_by is the authenticated user.
     *
     * @throws Throwable
     */
    public function updateAssignUser(int $id, int $assignTo, int $assignBy, ?string $comment = null): ?Operation
    {
        try {
            $operation = $this->operationRepository->find($id);

            if (!$operation) {
                return null;
            }

            return $this->operationRepository->updateAssignUser($id, $assignTo, $assignBy, $comment);
        } catch (Throwable $e) {
            Log::error('Error updating operation assignee', [
                'id' => $id,
                'assign_to' => $assignTo,
                'assign_by' => $assignBy,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Soft delete an operation.
     *
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            return $this->operationRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting operation', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create an operation for a newly approved plan and assign the organisation OPS Head.
     * Missing organisation or status data is logged and does not fail the planner update.
     */
    public function createFromApprovedPlan(Planner $planner, ?int $assignedBy = null): ?Operation
    {
        try {
            if ($this->operationRepository->existsForPlanner((int) $planner->id)) {
                return null;
            }

            $organisationId = $this->resolveOrganisationId($planner);

            if (!$organisationId) {
                Log::warning('Approved plan has no organisation; operation was not created', [
                    'planner_id' => $planner->id,
                    'brief_id' => $planner->brief_id,
                ]);

                return null;
            }

            $pendingStatus = $this->operationStatusRepository->findBySlug('pending');

            if (!$pendingStatus) {
                Log::warning('Pending operation status was not found; operation was not created', [
                    'planner_id' => $planner->id,
                ]);

                return null;
            }

            $opsHeadId = $this->resolveTopOpsAdminUserId($organisationId);

            if (!$opsHeadId) {
                Log::warning('No ops-admin found for organisation; operation assign_to left empty', [
                    'planner_id' => $planner->id,
                    'organisation_id' => $organisationId,
                ]);
            }

            return $this->operationRepository->create([
                'uuid' => (string) Str::uuid(),
                'brief_id' => $planner->brief_id,
                'planner_id' => $planner->id,
                'operation_status_id' => $pendingStatus->id,
                'assign_by' => $assignedBy ?? (auth()->id() ? (int) auth()->id() : null) ?? $planner->created_by,
                'assign_to' => $opsHeadId,
                'status' => '1',
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create operation for approved plan', [
                'planner_id' => $planner->id,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Organisation from the brief contact, then the planner creator.
     */
    protected function resolveOrganisationId(Planner $planner): ?int
    {
        try {
            $planner->loadMissing(['brief.contactPerson', 'creator']);

            $organisationId = $planner->brief?->contactPerson?->organisation_id
                ?? $planner->creator?->organisation_id;

            return $organisationId ? (int) $organisationId : null;
        } catch (Throwable $e) {
            Log::error('Error resolving organisation ID for planner', [
                'planner_id' => $planner->id,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Top ops-admin in the organisation. Same hierarchy rule as planner-admin assignment.
     */
    protected function resolveTopOpsAdminUserId(int $organisationId): ?int
    {
        try {
            $opsAdmins = $this->userRepository->findActiveOpsAdminsByOrganisation($organisationId);

            if ($opsAdmins->isEmpty()) {
                return null;
            }

            if ($opsAdmins->count() === 1) {
                return (int) $opsAdmins->first()->id;
            }

            $adminIds = $opsAdmins->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($opsAdmins as $admin) {
                $ancestorIds = UserAccessScope::getAncestorIds($admin);
                $opsAdminAncestors = array_intersect($ancestorIds, $adminIds);

                if ($opsAdminAncestors === []) {
                    return (int) $admin->id;
                }
            }

            return (int) $opsAdmins->first()->id;
        } catch (Throwable $e) {
            Log::error('Error resolving top ops admin user ID', [
                'organisation_id' => $organisationId,
                'exception' => $e,
            ]);

            return null;
        }
    }
}
