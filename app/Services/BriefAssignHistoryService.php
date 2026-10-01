<?php

namespace App\Services;

use App\Contracts\Repositories\BriefAssignHistoryRepositoryInterface;
use App\Contracts\Repositories\PlannerHistoryRepositoryInterface;
use App\Models\BriefAssignHistory;
use App\Models\PlannerHistory;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BriefAssignHistoryService
{
    /**
     * @var BriefAssignHistoryRepositoryInterface
     */
    protected BriefAssignHistoryRepositoryInterface $repository;

    /**
     * @var PlannerHistoryRepositoryInterface
     */
    protected PlannerHistoryRepositoryInterface $plannerHistoryRepository;

    /**
     * Create a new BriefAssignHistoryService instance.
     *
     * @param BriefAssignHistoryRepositoryInterface $repository
     * @param PlannerHistoryRepositoryInterface $plannerHistoryRepository
     */
    public function __construct(
        BriefAssignHistoryRepositoryInterface $repository,
        PlannerHistoryRepositoryInterface $plannerHistoryRepository
    ) {
        $this->repository = $repository;
        $this->plannerHistoryRepository = $plannerHistoryRepository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all brief assign histories with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllBriefAssignHistories(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllBriefAssignHistories($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign histories', ['exception' => $e]);
            throw new DomainException('Database error while fetching brief assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign histories', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign histories.');
        }
    }

    /**
     * Get a brief assign history by ID.
     *
     * @param int $id
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function getBriefAssignHistory(int $id): ?BriefAssignHistory
    {
        try {
            return $this->repository->getBriefAssignHistoryById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign history.');
        }
    }

    /**
     * Get a brief assign history by UUID.
     *
     * @param string $uuid
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function getBriefAssignHistoryByUuid(string $uuid): ?BriefAssignHistory
    {
        try {
            return $this->repository->getBriefAssignHistoryByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign history by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign history by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign history.');
        }
    }

    /**
     * Get all assign histories for a specific brief.
     *
     * @param int $briefId
     * @param int $perPage
     * @param \App\Models\User|null $user
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    /**
     * Added service-layer support for retrieving brief assignment histories
     * with user visibility scope and database exception handling.
     */
    public function getBriefAssignHistoriesByBriefId(int $briefId, int $perPage = 10, ?\App\Models\User $user = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByBriefId($briefId, $perPage, $user);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by brief', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by brief', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    /**
     * Get all chat histories for a brief, newest first.
     *
     * @param int $briefId
     * @return \Illuminate\Database\Eloquent\Collection<int, BriefAssignHistory>
     * @throws DomainException
     */
    public function getBriefAssignHistoryChat(int $briefId): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return $this->repository->getBriefAssignHistoryChat($briefId);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assignment chat history', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief assignment chat history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assignment chat history', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assignment chat history.');
        }
    }

    /**
     * Store a brief activity entry and its reminder details.
     *
     * @param int $briefId
     * @param int $currentUserId
     * @param array<string, mixed> $data
     * @return BriefAssignHistory
     * @throws DomainException
     */
    public function createBriefActivity(int $briefId, int $currentUserId, array $data): BriefAssignHistory
    {
        try {
            return $this->repository->createBriefActivity($briefId, $currentUserId, $data);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating brief activity', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while saving brief activity.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief activity', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while saving brief activity.');
        }
    }

    /**
     * Get all assign histories assigned by a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefAssignHistoriesByAssignBy(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByAssignBy($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by assign_by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by assign_by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    /**
     * Get all assign histories assigned to a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefAssignHistoriesByAssignTo(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByAssignTo($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by assign_to user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by assign_to user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    /**
     * Time from each planner assignment to that planner's plan submission.
     *
    * Assignment time is brief_assign_histories.created_at, except the initial
    * assignment can be recovered from briefs.created_at when the first
    * assignment-history row confirms the same assignee. Consecutive rows with
    * the same assign_to_id stay in one cycle, because status and submission-
    * date updates also write history for the current assignee. A later row
    * for the same user starts a new cycle only after another planner has been
    * assigned in between.
     *
     * Plan submission time is the earliest planner_histories.created_at in
     * that cycle whose submitted_plan contains at least one file. The planner
     * user is planner_histories.created_by. planner_histories.planner_id is
     * the planners table id, so it is not compared with assign_to_id.
     * A submission counts only when its timestamp falls in
     * [assigned_at, next assignment).
     *
     * @param int $briefId
     * @return array{brief_id: int, assignment_cycles: array<int, array<string, mixed>>}
     * @throws DomainException
     */
    public function getAssignmentSubmissionDurations(int $briefId): array
    {
        try {
            $assignmentHistories = $this->repository->getAssignmentHistoriesForBrief($briefId);
            $submittedHistories = $this->plannerHistoryRepository
                ->getSubmittedPlanHistoriesForBrief($briefId)
                ->filter(fn (PlannerHistory $history) => $this->hasSubmittedPlan($history))
                ->values();

            $cycles = $this->buildAssignmentCycles($assignmentHistories);

            return [
                'brief_id' => $briefId,
                'assignment_cycles' => $this->attachSubmissionDurations($cycles, $submittedHistories),
            ];
        } catch (QueryException $e) {
            Log::error('Database error calculating assignment submission durations', [
                'brief_id' => $briefId,
                'exception' => $e,
            ]);
            throw new DomainException('Database error while calculating assignment submission durations.');
        } catch (Exception $e) {
            Log::error('Unexpected error calculating assignment submission durations', [
                'brief_id' => $briefId,
                'exception' => $e,
            ]);
            throw new DomainException('Unexpected error while calculating assignment submission durations.');
        }
    }

    /**
     * Assignment-to-submission durations for every cycle belonging to one planner.
     * Uses the same cycle windows as getAssignmentSubmissionDurations().
     *
     * @param int $userId
     * @return array<int, array<string, mixed>>
     * @throws DomainException
     */
    public function getUserAssignmentSubmissionDurations(int $userId): array
    {
        try {
            $assignmentHistories = $this->repository->getAssignmentHistoriesForUserCycles($userId);
            $briefIds = $assignmentHistories
                ->pluck('brief_id')
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $submittedByBrief = $this->plannerHistoryRepository
                ->getSubmittedPlanHistoriesForBriefs($briefIds)
                ->filter(fn (PlannerHistory $history) => $this->hasSubmittedPlan($history))
                ->groupBy(fn (PlannerHistory $history) => (int) $history->brief_id);

            $cycles = [];

            foreach ($assignmentHistories->groupBy(fn (BriefAssignHistory $history) => (int) $history->brief_id) as $briefId => $briefHistories) {
                $briefName = $briefHistories->first()?->brief?->name;
                $attached = $this->attachSubmissionDurations(
                    $this->buildAssignmentCycles($briefHistories),
                    $submittedByBrief->get((int) $briefId, collect())
                );

                foreach ($attached as $cycle) {
                    if ((int) $cycle['planner_id'] !== $userId) {
                        continue;
                    }

                    $cycles[] = [
                        'brief_id' => (int) $briefId,
                        'brief_name' => $briefName,
                        'assigned_at' => $cycle['assigned_at'],
                        'plan_submitted_at' => $cycle['plan_submitted_at'],
                        'duration_seconds' => $cycle['duration_seconds'],
                        'duration_minutes' => $cycle['duration_minutes'],
                        'duration' => $cycle['duration'],
                    ];
                }
            }

            usort($cycles, function (array $left, array $right): int {
                $byAssignedAt = strcmp((string) $left['assigned_at'], (string) $right['assigned_at']);

                return $byAssignedAt !== 0 ? $byAssignedAt : ($left['brief_id'] <=> $right['brief_id']);
            });

            return $cycles;
        } catch (QueryException $e) {
            Log::error('Database error calculating user assignment submission durations', [
                'user_id' => $userId,
                'exception' => $e,
            ]);
            throw new DomainException('Database error while calculating assignment submission durations.');
        } catch (Exception $e) {
            Log::error('Unexpected error calculating user assignment submission durations', [
                'user_id' => $userId,
                'exception' => $e,
            ]);
            throw new DomainException('Unexpected error while calculating assignment submission durations.');
        }
    }

    /**
     * Completed assignment-cycle durations in days, keyed by brief id.
     *
     * Uses the same cycle windows as getAssignmentSubmissionDurations().
     * A cycle is included only when both the assignment time and the plan
     * submission time exist. Incomplete cycles are omitted.
     *
     * @param array<int, int> $briefIds
     * @return array<int, array<int, float>>
     * @throws DomainException
     */
    public function getCompletedCycleDurationDaysByBrief(array $briefIds): array
    {
        try {
            $briefIds = array_values(array_unique(array_map('intval', $briefIds)));
            if ($briefIds === []) {
                return [];
            }

            $assignmentHistories = $this->repository->getAssignmentHistoriesForBriefs($briefIds);
            $submittedByBrief = $this->plannerHistoryRepository
                ->getSubmittedPlanHistoriesForBriefs($briefIds)
                ->filter(fn (PlannerHistory $history) => $this->hasSubmittedPlan($history))
                ->groupBy(fn (PlannerHistory $history) => (int) $history->brief_id);

            $daysByBrief = [];

            foreach ($assignmentHistories->groupBy(fn (BriefAssignHistory $history) => (int) $history->brief_id) as $briefId => $briefHistories) {
                $cycles = $this->attachSubmissionDurations(
                    $this->buildAssignmentCycles($briefHistories),
                    $submittedByBrief->get((int) $briefId, collect())
                );

                foreach ($cycles as $cycle) {
                    if ($cycle['duration_seconds'] === null) {
                        continue;
                    }

                    $daysByBrief[(int) $briefId][] = $cycle['duration_seconds'] / 86400;
                }
            }

            return $daysByBrief;
        } catch (QueryException $e) {
            Log::error('Database error calculating completed assignment cycle days', ['exception' => $e]);
            throw new DomainException('Database error while calculating assignment submission durations.');
        } catch (Exception $e) {
            Log::error('Unexpected error calculating completed assignment cycle days', ['exception' => $e]);
            throw new DomainException('Unexpected error while calculating assignment submission durations.');
        }
    }

    /**
     * Collapse consecutive assignment rows for the same planner into one cycle.
     *
     * @param Collection<int, BriefAssignHistory> $assignmentHistories
     * @return array<int, array{planner_id: int, planner_name: string|null, assigned_at: Carbon}>
     */
    private function buildAssignmentCycles(Collection $assignmentHistories): array
    {
        $cycles = [];

        $firstAssignment = $assignmentHistories->first(
            fn (BriefAssignHistory $history) => $history->assign_to_id !== null
        );
        $brief = $assignmentHistories->first()?->brief;

        if (
            $firstAssignment !== null
            && $brief?->assign_user_id !== null
            && (int) $firstAssignment->assign_to_id === (int) $brief->assign_user_id
            && $brief->created_at !== null
            && $brief->created_at->lt($firstAssignment->created_at)
        ) {
            $cycles[] = [
                'planner_id' => (int) $brief->assign_user_id,
                'planner_name' => $firstAssignment->assignedTo?->name,
                'assigned_at' => $brief->created_at->copy(),
            ];
        }

        foreach ($assignmentHistories as $history) {
            if ($history->assign_to_id === null) {
                continue;
            }

            $plannerId = (int) $history->assign_to_id;
            $previousCycle = $cycles[count($cycles) - 1] ?? null;

            if ($previousCycle !== null && $previousCycle['planner_id'] === $plannerId) {
                continue;
            }

            $cycles[] = [
                'planner_id' => $plannerId,
                'planner_name' => $history->assignedTo?->name,
                'assigned_at' => $history->created_at->copy(),
            ];
        }

        return $cycles;
    }

    /**
     * Match each assignment cycle to the earliest plan submission inside its window.
     *
     * @param array<int, array{planner_id: int, planner_name: string|null, assigned_at: Carbon}> $cycles
     * @param Collection<int, PlannerHistory> $submittedHistories
     * @return array<int, array<string, mixed>>
     */
    private function attachSubmissionDurations(array $cycles, Collection $submittedHistories): array
    {
        $results = [];

        foreach ($cycles as $index => $cycle) {
            $windowEnd = $cycles[$index + 1]['assigned_at'] ?? null;
            $submission = $this->findSubmissionInWindow(
                $submittedHistories,
                $cycle['planner_id'],
                $cycle['assigned_at'],
                $windowEnd
            );

            $results[] = $this->formatCycle($cycle, $submission);
        }

        return $results;
    }

    /**
     * Earliest submitted-plan history owned by the assigned planner inside the window.
     *
     * @param Collection<int, PlannerHistory> $submittedHistories
     * @param int $plannerUserId
     * @param Carbon $windowStart
     * @param Carbon|null $windowEnd
     * @return PlannerHistory|null
     */
    private function findSubmissionInWindow(
        Collection $submittedHistories,
        int $plannerUserId,
        Carbon $windowStart,
        ?Carbon $windowEnd
    ): ?PlannerHistory {
        return $submittedHistories->first(function (PlannerHistory $history) use ($plannerUserId, $windowStart, $windowEnd) {
            if ((int) $history->created_by !== $plannerUserId || $history->created_at === null) {
                return false;
            }

            if ($history->created_at->lt($windowStart)) {
                return false;
            }

            if ($windowEnd !== null && $history->created_at->gte($windowEnd)) {
                return false;
            }

            return true;
        });
    }

    /**
     * A plan is submitted when submitted_plan contains at least one file.
     * This matches Planner::hasSubmittedPlans() and PlannerMetrics.
     *
     * @param PlannerHistory $history
     * @return bool
     */
    private function hasSubmittedPlan(PlannerHistory $history): bool
    {
        $submittedPlan = $history->submitted_plan;

        return is_array($submittedPlan) && count($submittedPlan) > 0;
    }

    /**
     * @param array{planner_id: int, planner_name: string|null, assigned_at: Carbon} $cycle
     * @param PlannerHistory|null $submission
     * @return array<string, mixed>
     */
    private function formatCycle(array $cycle, ?PlannerHistory $submission): array
    {
        $assignedAt = $cycle['assigned_at'];
        $submittedAt = $submission?->created_at;
        $durationSeconds = null;

        if ($submittedAt !== null) {
            $durationSeconds = $submittedAt->getTimestamp() - $assignedAt->getTimestamp();
            if ($durationSeconds < 0) {
                $durationSeconds = 0;
            }
        }

        return [
            'planner_id' => $cycle['planner_id'],
            'planner_name' => $cycle['planner_name'],
            'assigned_at' => $assignedAt->format('Y-m-d H:i:s'),
            'plan_submitted_at' => $submittedAt?->format('Y-m-d H:i:s'),
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationSeconds === null ? null : intdiv($durationSeconds, 60),
            'duration' => $durationSeconds === null ? null : $this->formatDuration($durationSeconds),
        ];
    }

    /**
     * Human duration such as "4 hours 30 minutes".
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $days = intdiv($hours, 24);
        $hours %= 24;
        $remainingMinutes = $minutes % 60;
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days === 1 ? '1 day' : $days . ' days';
        }
        if ($hours > 0) {
            $parts[] = $hours === 1 ? '1 hour' : $hours . ' hours';
        }
        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes === 1 ? '1 minute' : $remainingMinutes . ' minutes';
        }
        if ($remainingSeconds > 0 && $days === 0 && $hours === 0 && $remainingMinutes === 0) {
            $parts[] = $remainingSeconds === 1 ? '1 second' : $remainingSeconds . ' seconds';
        }

        if ($parts === []) {
            return '0 minutes';
        }

        return implode(' ', $parts);
    }

    // ============================================================================
    // CREATE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief assign history.
     *
     * @param array $data
     * @return BriefAssignHistory
     * @throws DomainException
     */
    public function createBriefAssignHistory(array $data): BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::create($data);
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error creating brief assign history', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief assign history', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating brief assign history.');
        }
    }

    // ============================================================================
    // UPDATE OPERATIONS
    // ============================================================================

    /**
     * Update a brief assign history.
     *
     * @param int $id
     * @param array $data
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function updateBriefAssignHistory(int $id, array $data): ?BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::find($id);
            if (!$briefAssignHistory) {
                return null;
            }
            $briefAssignHistory->update($data);
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error updating brief assign history', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating brief assign history', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating brief assign history.');
        }
    }

    // ============================================================================
    // DELETE OPERATIONS
    // ============================================================================

    /**
     * Delete a brief assign history (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteBriefAssignHistory(int $id): bool
    {
        try {
            $briefAssignHistory = BriefAssignHistory::find($id);
            if (!$briefAssignHistory) {
                return false;
            }
            return (bool) $briefAssignHistory->delete();
        } catch (QueryException $e) {
            Log::error('Database error deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting brief assign history.');
        }
    }

    /**
     * Permanently delete a brief assign history.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function forceDeleteBriefAssignHistory(int $id): bool
    {
        try {
            $briefAssignHistory = BriefAssignHistory::withTrashed()->find($id);
            if (!$briefAssignHistory) {
                return false;
            }
            return (bool) $briefAssignHistory->forceDelete();
        } catch (QueryException $e) {
            Log::error('Database error force deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while force deleting brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error force deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while force deleting brief assign history.');
        }
    }

    /**
     * Restore a soft deleted brief assign history.
     *
     * @param int $id
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function restoreBriefAssignHistory(int $id): ?BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::onlyTrashed()->find($id);
            if (!$briefAssignHistory) {
                return null;
            }
            $briefAssignHistory->restore();
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error restoring brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while restoring brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error restoring brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while restoring brief assign history.');
        }
    }
}
