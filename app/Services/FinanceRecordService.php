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
}
