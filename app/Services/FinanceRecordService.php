<?php

/**
 * FinanceRecord Service
 * -----------------------------------------
 * Uploads a cost sheet for a brief only when that brief has an approved plan.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Services;

use App\Contracts\Repositories\FinanceRecordRepositoryInterface;
use App\Contracts\Repositories\FinanceStatusRepositoryInterface;
use App\Models\FinanceRecord;
use App\Traits\HandlesFileUploads;
use DomainException;
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
     * Store a cost sheet against the approved plan of a brief.
     *
     * @throws DomainException
     * @throws ValidationException
     * @throws Throwable
     */
    public function uploadCostSheet(int $briefId, UploadedFile $file): ?FinanceRecord
    {
        try {
            if (!$this->financeRecordRepository->briefExists($briefId)) {
                return null;
            }

            $planner = $this->financeRecordRepository->findApprovedPlannerByBriefId($briefId);

            if (!$planner) {
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

            return $this->saveCostSheet($briefId, (int) $planner->id, (int) $financeStatus->id, $uploadedFile['path']);
        } catch (Throwable $e) {
            if (!$e instanceof DomainException && !$e instanceof ValidationException) {
                Log::error('Error uploading cost sheet', [
                    'brief_id' => $briefId,
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
                return $this->financeRecordRepository->updateCostSheet((int) $existing->id, $path, $assignBy);
            }

            return $this->financeRecordRepository->create([
                'uuid' => (string) Str::uuid(),
                'brief_id' => $briefId,
                'planner_id' => $plannerId,
                'finance_status_id' => $financeStatusId,
                'cost_sheet' => $path,
                'assign_by' => $assignBy,
                'assign_to' => null,
                'status' => '1',
            ]);
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
