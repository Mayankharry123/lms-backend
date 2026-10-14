<?php

/**
 * PlannerHistoryService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Repositories\PlannerHistoryRepository;
use App\Models\PlannerHistory;
use App\Contracts\Repositories\PlannerHistoryRepositoryInterface;
use DomainException;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlannerHistoryService
{
    /**
     * @var PlannerHistoryRepository
     */
    protected PlannerHistoryRepositoryInterface $repository;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;
    /**
     * PlannerHistoryService constructor.
     *
     * @param PlannerHistoryRepositoryInterface $repository
     * @param ResponseService $responseService
     */
    public function __construct(PlannerHistoryRepositoryInterface $repository, ResponseService $responseService)
    {
        $this->repository = $repository;
        $this->responseService = $responseService;
    }

    /**
     * Get all planner histories with filters
     *
     * @param int $perPage
     * @param array $filters
     * @return mixed
     * @throws DomainException
     */
    public function getAllPlannerHistories(int $perPage = 10, array $filters = [])
    {
        try {
            return $this->repository->getAllPlannerHistories($perPage, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching all planner histories', ['exception' => $e, 'filters' => $filters]);
            throw new DomainException('Database error while fetching planner histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching all planner histories', ['exception' => $e, 'filters' => $filters]);
            throw new DomainException('Unexpected error while fetching planner histories.');
        }
    }

    /**
     * Get planner histories for a specific planner
     *
     * @param int $plannerId
     * @param int $perPage
     * @return mixed
     * @throws DomainException
     */
    public function getPlannerHistories(int $plannerId, int $perPage = 10)
    {
        try {
            return $this->repository->getPlannerHistories($plannerId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching planner histories', ['plannerId' => $plannerId, 'exception' => $e]);
            throw new DomainException('Database error while fetching planner histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching planner histories', ['plannerId' => $plannerId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching planner histories.');
        }
    }

    /**
     * Get planner histories for a specific brief
     *
     * @param int $briefId
     * @param int $perPage
     * @return mixed
     * @throws DomainException
     */
    public function getBriefPlannerHistories(int $briefId, int $perPage = 10)
    {
        try {
            return $this->repository->getBriefPlannerHistories($briefId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief planner histories', ['briefId' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief planner histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief planner histories', ['briefId' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief planner histories.');
        }
    }

    /**
     * Get planner histories by status
     *
     * @param string $status
     * @param int $perPage
     * @return mixed
     * @throws DomainException
     */
    public function getByStatus(string $status, int $perPage = 10)
    {
        try {
            return $this->repository->getByStatus($status, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching planner histories by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Database error while fetching planner histories by status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching planner histories by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching planner histories by status.');
        }
    }

    /**
     * Get recent planner histories
     *
     * @param int $limit
     * @return mixed
     * @throws DomainException
     */
    public function getRecentHistories(int $limit = 10)
    {
        try {
            return $this->repository->getRecentHistories($limit);
        } catch (QueryException $e) {
            Log::error('Database error fetching recent planner histories', ['limit' => $limit, 'exception' => $e]);
            throw new DomainException('Database error while fetching recent planner histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching recent planner histories', ['limit' => $limit, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching recent planner histories.');
        }
    }

    /**
     * Create a planner history record
     *
     * @param array $data
     * @return PlannerHistory|null
     * @throws Throwable
     */
    public function createHistory(array $data): ?PlannerHistory
    {
        try {
            return $this->repository->createHistory($data);
        } catch (QueryException $e) {
            Log::error('Database error creating planner history', ['exception' => $e, 'data' => $data]);
            throw new DomainException('Database error while creating planner history.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating planner history', ['exception' => $e, 'data' => $data]);
            throw new DomainException('Unexpected error while creating planner history.');
        }
    }
}

