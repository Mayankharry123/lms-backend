<?php

/**
 * Operation Repository
 * -----------------------------------------
 * Implements the Operation repository interface for the operations index query.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Repositories;

use App\Contracts\Repositories\OperationRepositoryInterface;
use App\Models\Operation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationRepository implements OperationRepositoryInterface
{
    protected Operation $model;

    public function __construct(Operation $model)
    {
        $this->model = $model;
    }

    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with($this->listRelations());

        if (!empty($criteria['brief_id'])) {
            $query->where('brief_id', $criteria['brief_id']);
        }

        if (!empty($criteria['planner_id'])) {
            $query->where('planner_id', $criteria['planner_id']);
        }

        if (!empty($criteria['operation_status_id'])) {
            $query->where('operation_status_id', $criteria['operation_status_id']);
        }

        if (!empty($criteria['assign_by'])) {
            $query->where('assign_by', $criteria['assign_by']);
        }

        if (!empty($criteria['assign_to'])) {
            $query->where('assign_to', $criteria['assign_to']);
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where('status', $criteria['status']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Operation
    {
        return $this->model->newQuery()
            ->with($this->listRelations())
            ->find($id);
    }

    /**
     * Relations needed by the operations list and detail payloads.
     *
     * @return array<int|string, mixed>
     */
    protected function listRelations(): array
    {
        return [
            'brief:id,name,product_name,campaign_start_date,campaign_end_date,created_by',
            'brief.createdByUser:id,name',
            'planner:id,created_by,backup_plan',
            'planner.creator:id,name',
            'operationStatus:id,name',
            'assignedTo:id,name',
        ];
    }

    public function existsForPlanner(int $plannerId): bool
    {
        return $this->model->newQuery()
            ->where('planner_id', $plannerId)
            ->exists();
    }

    public function create(array $data): Operation
    {
        return $this->model->create($data);
    }

    public function updateStatus(int $id, int $operationStatusId): ?Operation
    {
        $operation = $this->model->newQuery()->find($id);

        if (!$operation) {
            return null;
        }

        $operation->update(['operation_status_id' => $operationStatusId]);

        return $operation->refresh()->load($this->listRelations());
    }

    public function updateAssignUser(int $id, int $assignTo, int $assignBy): ?Operation
    {
        $operation = $this->model->newQuery()->find($id);

        if (!$operation) {
            return null;
        }

        $operation->update([
            'assign_to' => $assignTo,
            'assign_by' => $assignBy,
        ]);

        return $operation->refresh()->load($this->listRelations());
    }

    public function delete(int $id): bool
    {
        $operation = $this->model->newQuery()->find($id);

        if (!$operation) {
            return false;
        }

        $operation->status = '15';
        $operation->save();

        return (bool) $operation->delete();
    }
}
