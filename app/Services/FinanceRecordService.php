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
use App\Models\Brief;
use App\Models\FinanceRecord;
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

    /**
     * Inject the finance record and finance status repositories.
     */
    public function __construct(
        FinanceRecordRepositoryInterface $financeRecordRepository,
        FinanceStatusRepositoryInterface $financeStatusRepository
    ) {
        $this->financeRecordRepository = $financeRecordRepository;
        $this->financeStatusRepository = $financeStatusRepository;
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

            return $this->saveCostSheet((int) $planner->brief_id, $plannerId, (int) $financeStatus->id, $uploadedFile['path']);
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
     *
     * @throws Throwable
     */
    protected function saveCostSheet(int $briefId, int $plannerId, int $financeStatusId, string $path): ?FinanceRecord
    {
        try {
            $assignBy = auth()->id() ? (int) auth()->id() : null;
            $existing = $this->financeRecordRepository->findActiveByBriefAndPlanner($briefId, $plannerId);

            if ($existing) {
                $financeRecord = $this->financeRecordRepository->updateCostSheet((int) $existing->id, $path, $assignBy);
            } else {
                $financeRecord = $this->financeRecordRepository->create([
                    'uuid' => (string) Str::uuid(),
                    'brief_id' => $briefId,
                    'planner_id' => $plannerId,
                    'finance_status_id' => $financeStatusId,
                    'cost_sheet' => $path,
                    'assign_by' => $assignBy,
                    'assign_to' => null,
                    'status' => '1',
                ]);
            }

            (new Brief())->markCostSheetSubmitted($briefId);

            return $financeRecord;
        } catch (Throwable $e) {
            Log::error('Error saving cost sheet', [
                'brief_id' => $briefId,
                'planner_id' => $plannerId,
                'exception' => $e,
            ]);

            throw $e;
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
