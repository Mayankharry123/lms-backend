<?php

/**
 * FinanceRecord Service
 * -----------------------------------------
 * Uploads a cost sheet for a planner only when that plan is approved.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Services;

use App\Contracts\Repositories\FinanceRecordRepositoryInterface;
use App\Contracts\Repositories\FinanceStatusRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Brief;
use App\Models\FinanceRecord;
use App\Models\Planner;
use App\Support\UserAccessScope;
use App\Traits\HandlesFileUploads;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinanceRecordService
{
    use HandlesFileUploads;

    protected FinanceRecordRepositoryInterface $financeRecordRepository;
    protected FinanceStatusRepositoryInterface $financeStatusRepository;
    protected UserRepositoryInterface $userRepository;

    /**
     * Inject the finance record, finance status, and user repositories.
     */
    public function __construct(
        FinanceRecordRepositoryInterface $financeRecordRepository,
        FinanceStatusRepositoryInterface $financeStatusRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->financeRecordRepository = $financeRecordRepository;
        $this->financeStatusRepository = $financeStatusRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get a paginated list of finance records.
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->financeRecordRepository->paginate($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching finance records', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find one finance record.
     *
     * @throws Throwable
     */
    public function find(int $id): ?FinanceRecord
    {
        try {
            return $this->financeRecordRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching finance record by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Store a cost sheet against an approved planner.
     *
     * @throws DomainException
     * @throws ValidationException
     * @throws Throwable
     */
    public function uploadCostSheet(int $plannerId, UploadedFile $file): ?FinanceRecord
    {
        try {
            $planner = $this->financeRecordRepository->findPlannerById($plannerId);

            if (!$planner) {
                return null;
            }

            if (!$planner->isPlanApproved()) {
                throw new DomainException('Plan is not approved, you cannot upload the cost sheet.');
            }

            $financeStatus = $this->financeStatusRepository->findFirstActive();

            if (!$financeStatus) {
                throw new DomainException('Finance status not found');
            }

            $uploadedFile = $this->uploadFile(
                $file,
                $this->detectFileType($file),
                'public/finance-records/cost-sheets'
            );

            return $this->saveCostSheet($planner, (int) $financeStatus->id, $uploadedFile['path']);
        } catch (Throwable $e) {
            if (!$e instanceof DomainException && !$e instanceof ValidationException) {
                Log::error('Error uploading cost sheet', [
                    'planner_id' => $plannerId,
                    'exception' => $e,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Update the existing finance record, or create one when this brief has none.
     * The sheet is assigned to the organisation finance admin.
     *
     * @throws Throwable
     */
    protected function saveCostSheet(Planner $planner, int $financeStatusId, string $path): ?FinanceRecord
    {
        try {
            $briefId = (int) $planner->brief_id;
            $plannerId = (int) $planner->id;
            $assignBy = auth()->id() ? (int) auth()->id() : null;
            $assignTo = $this->resolveFinanceAdminId($planner);
            $existing = $this->financeRecordRepository->findActiveByBriefAndPlanner($briefId, $plannerId);

            if ($existing) {
                $financeRecord = $this->financeRecordRepository->updateCostSheet((int) $existing->id, $path, $assignBy, $assignTo);
            } else {
                $financeRecord = $this->financeRecordRepository->create([
                    'uuid' => (string) Str::uuid(),
                    'brief_id' => $briefId,
                    'planner_id' => $plannerId,
                    'finance_status_id' => $financeStatusId,
                    'cost_sheet' => $path,
                    'assign_by' => $assignBy,
                    'assign_to' => $assignTo,
                    'status' => '1',
                ]);
            }

            (new Brief())->markCostSheetSubmitted($briefId);

            return $financeRecord;
        } catch (Throwable $e) {
            Log::error('Error saving cost sheet', [
                'brief_id' => $planner->brief_id,
                'planner_id' => $planner->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Finance admin who should receive this cost sheet.
     */
    protected function resolveFinanceAdminId(Planner $planner): ?int
    {
        try {
            $organisationId = $this->resolveOrganisationId($planner);

            if (!$organisationId) {
                Log::warning('Cost sheet has no organisation; assign_to left empty', [
                    'planner_id' => $planner->id,
                    'brief_id' => $planner->brief_id,
                ]);

                return null;
            }

            $financeAdminId = $this->resolveTopFinanceAdminUserId($organisationId);

            if (!$financeAdminId) {
                Log::warning('No finance-admin found for organisation; assign_to left empty', [
                    'planner_id' => $planner->id,
                    'organisation_id' => $organisationId,
                ]);
            }

            return $financeAdminId;
        } catch (Throwable $e) {
            Log::error('Error resolving finance admin for cost sheet', [
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
            Log::error('Error resolving organisation ID for cost sheet', [
                'planner_id' => $planner->id,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Top finance-admin in the organisation. Same hierarchy rule as planner-admin assignment.
     */
    protected function resolveTopFinanceAdminUserId(int $organisationId): ?int
    {
        try {
            $financeAdmins = $this->userRepository->findActiveFinanceAdminsByOrganisation($organisationId);

            if ($financeAdmins->isEmpty()) {
                return null;
            }

            if ($financeAdmins->count() === 1) {
                return (int) $financeAdmins->first()->id;
            }

            $adminIds = $financeAdmins->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($financeAdmins as $admin) {
                $ancestorIds = UserAccessScope::getAncestorIds($admin);
                $financeAdminAncestors = array_intersect($ancestorIds, $adminIds);

                if ($financeAdminAncestors === []) {
                    return (int) $admin->id;
                }
            }

            return (int) $financeAdmins->first()->id;
        } catch (Throwable $e) {
            Log::error('Error resolving top finance admin user ID', [
                'organisation_id' => $organisationId,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * List cost sheets.
     *
     * @throws Throwable
     */
    public function listCostSheets(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->financeRecordRepository->paginateCostSheets($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheets', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find one cost sheet.
     *
     * @throws Throwable
     */
    public function findCostSheet(int $id): ?FinanceRecord
    {
        try {
            return $this->financeRecordRepository->findCostSheet($id);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Update assignment, statuses, and the cost sheet file.
     *
     * @param array<string, mixed> $data
     * @throws DomainException
     * @throws Throwable
     */
    public function updateCostSheetRecord(int $id, array $data, ?UploadedFile $file = null): ?FinanceRecord
    {
        try {
            $financeRecord = $this->financeRecordRepository->findCostSheet($id);

            if (!$financeRecord) {
                return null;
            }

            $updates = [];

            foreach (['assign_by', 'assign_to', 'finance_status_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($file) {
                $uploadedFile = $this->uploadFile(
                    $file,
                    $this->detectFileType($file),
                    'public/finance-records/cost-sheets'
                );
                $updates['cost_sheet'] = $uploadedFile['path'];
            }

            if (array_key_exists('cost_sheet_status_id', $data) && $financeRecord->brief_id) {
                $updated = (new Brief())->assignCostSheetStatus(
                    (int) $financeRecord->brief_id,
                    (int) $data['cost_sheet_status_id']
                );

                if (!$updated) {
                    throw new DomainException('Cost sheet status not found');
                }
            }

            if ($updates === []) {
                return $this->financeRecordRepository->findCostSheet($id);
            }

            return $this->financeRecordRepository->updateRecord($id, $updates);
        } catch (Throwable $e) {
            if (!$e instanceof DomainException && !$e instanceof ValidationException) {
                Log::error('Error updating cost sheet', ['id' => $id, 'exception' => $e]);
            }

            throw $e;
        }
    }

    /**
     * Change the finance status of one cost sheet.
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function updateFinanceStatus(int $id, int $financeStatusId): ?FinanceRecord
    {
        try {
            $financeRecord = $this->financeRecordRepository->findCostSheet($id);

            if (!$financeRecord) {
                return null;
            }

            $financeStatus = $this->financeStatusRepository->find($financeStatusId);

            if (!$financeStatus || (string) $financeStatus->status !== '1') {
                throw new DomainException('Finance status not found');
            }

            return $this->financeRecordRepository->updateRecord($id, [
                'finance_status_id' => $financeStatusId,
            ]);
        } catch (Throwable $e) {
            if (!$e instanceof DomainException) {
                Log::error('Error updating cost sheet finance status', [
                    'id' => $id,
                    'finance_status_id' => $financeStatusId,
                    'exception' => $e,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Assign or reassign a finance record. assign_by is the authenticated user.
     *
     * @throws Throwable
     */
    public function updateAssignUser(int $id, int $assignTo, int $assignBy): ?FinanceRecord
    {
        try {
            $financeRecord = $this->financeRecordRepository->findCostSheet($id);

            if (!$financeRecord) {
                return null;
            }

            return $this->financeRecordRepository->updateRecord($id, [
                'assign_to' => $assignTo,
                'assign_by' => $assignBy,
            ]);
        } catch (Throwable $e) {
            Log::error('Error updating finance record assignee', [
                'id' => $id,
                'assign_to' => $assignTo,
                'assign_by' => $assignBy,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Soft delete a cost sheet. The brief returns to pending when no sheet remains.
     *
     * @throws Throwable
     */
    public function deleteCostSheet(int $id): bool
    {
        try {
            $financeRecord = $this->financeRecordRepository->findCostSheet($id);

            if (!$financeRecord) {
                return false;
            }

            $deleted = $this->financeRecordRepository->deleteRecord($id);

            if (
                $deleted
                && $financeRecord->brief_id
                && !$this->financeRecordRepository->hasOtherActiveCostSheet((int) $financeRecord->brief_id, $id)
            ) {
                (new Brief())->markCostSheetPending((int) $financeRecord->brief_id);
            }

            return $deleted;
        } catch (Throwable $e) {
            Log::error('Error deleting cost sheet', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }
}
