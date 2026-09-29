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
        return PlannerHistoryResource::collection(
            PlannerHistory::getAllPlannerHistories($perPage, $filters)
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
            PlannerHistory::getPlannerHistories($plannerId, $perPage)
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
            PlannerHistory::getBriefPlannerHistories($briefId, $perPage)
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
            PlannerHistory::getByStatus($status, $perPage)
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
            PlannerHistory::getRecentHistories($limit)
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
        return PlannerHistory::createHistory($data);
    }

    /**
     * Planner history rows for a brief that store a submitted_plan value.
     *
     * @param int $briefId
     * @return Collection<int, PlannerHistory>
     */
    public function getSubmittedPlanHistoriesForBrief(int $briefId): Collection
    {
        return PlannerHistory::getSubmittedPlanHistoriesForBrief($briefId);
    }

    /**
     * Submitted-plan history rows for many briefs, oldest first within each brief.
     *
     * @param array<int, int> $briefIds
     * @return Collection<int, PlannerHistory>
     */
    public function getSubmittedPlanHistoriesForBriefs(array $briefIds): Collection
    {
        return PlannerHistory::getSubmittedPlanHistoriesForBriefs($briefIds);
    }
}
