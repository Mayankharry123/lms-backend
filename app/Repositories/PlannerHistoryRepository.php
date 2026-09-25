<?php

namespace App\Repositories;

use App\Contracts\Repositories\PlannerHistoryRepositoryInterface;
use App\Models\PlannerHistory;
use App\Http\Resources\PlannerHistoryResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PlannerHistoryRepository extends BaseRepository implements PlannerHistoryRepositoryInterface
{
    /**
     * Get the model class
     */
    protected function getModelClass(): string
    {
        return PlannerHistory::class;
    }

    /**
     * Get all planner histories with pagination and filters
     *
     * @param int $perPage
     * @param array $filters
     * @return mixed
     */
    public function getAllPlannerHistories(int $perPage = 10, array $filters = [])
    {
        $query = PlannerHistory::with('planner', 'brief', 'creator', 'plannerStatus');

        // Filter by planner_id
        if (isset($filters['planner_id'])) {
            $query->where('planner_id', $filters['planner_id']);
        }

        // Filter by brief_id
        if (isset($filters['brief_id'])) {
            $query->where('brief_id', $filters['brief_id']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by created_by
        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return PlannerHistoryResource::collection(
            $query->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    /**
     * Get planner histories for a specific planner
     *
     * @param int $plannerId
     * @param int $perPage
     * @return mixed
     */
    public function getPlannerHistories(int $plannerId, int $perPage = 10)
    {
        return PlannerHistoryResource::collection(
            PlannerHistory::with('planner', 'brief', 'creator', 'plannerStatus')
                ->where('planner_id', $plannerId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
        );
    }

    /**
     * Get planner histories for a specific brief
     *
     * @param int $briefId
     * @param int $perPage
     * @return mixed
     */
    public function getBriefPlannerHistories(int $briefId, int $perPage = 10)
    {
        return PlannerHistoryResource::collection(
            PlannerHistory::with('planner', 'brief', 'creator', 'plannerStatus')
                ->where('brief_id', $briefId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
        );
    }

    /**
     * Get planner histories by status
     *
     * @param string $status
     * @param int $perPage
     * @return mixed
     */
    public function getByStatus(string $status, int $perPage = 10)
    {
        return PlannerHistoryResource::collection(
            PlannerHistory::with('planner', 'brief', 'creator', 'plannerStatus')
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
        );
    }

    /**
     * Get recent planner histories
     *
     * @param int $limit
     * @return mixed
     */
    public function getRecentHistories(int $limit = 10)
    {
        return PlannerHistoryResource::collection(
            PlannerHistory::with('planner', 'brief', 'creator', 'plannerStatus')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Create a planner history record
     *
     * @param array $data
     * @return PlannerHistory
     */
    public function createHistory(array $data): PlannerHistory
    {
        return PlannerHistory::create($data);
    }

    /**
     * Planner history rows for a brief that store a submitted_plan value.
     *
     * @param int $briefId
     * @return Collection<int, PlannerHistory>
     */
    public function getSubmittedPlanHistoriesForBrief(int $briefId): Collection
    {
        return PlannerHistory::query()
            ->where('brief_id', $briefId)
            ->where('status', '!=', '15')
            ->whereNotNull('submitted_plan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Submitted-plan history rows for many briefs, oldest first within each brief.
     *
     * @param array<int, int> $briefIds
     * @return Collection<int, PlannerHistory>
     */
    public function getSubmittedPlanHistoriesForBriefs(array $briefIds): Collection
    {
        if ($briefIds === []) {
            return new Collection();
        }

        return PlannerHistory::query()
            ->whereIn('brief_id', $briefIds)
            ->where('status', '!=', '15')
            ->whereNotNull('submitted_plan')
            ->orderBy('brief_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
