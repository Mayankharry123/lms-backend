<?php

/**
 * OperationStatus
 * -----------------------------------------
 * This model represents the operation_statuses table,
 * which stores the statuses of operations.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationStatus extends BaseModel
{
    protected $table = 'operation_statuses';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all operation statuses with pagination.
     */
    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()->latest()->paginate($perPage);
    }

    /**
     * Find an operation status by ID.
     */
    public function findById(int $id): ?self
    {
        return $this->newQuery()->find($id);
    }

    /**
     * Find an operation status by UUID.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find an operation status by name.
     */
    public function findByName(string $name): ?self
    {
        return $this->newQuery()->where('name', $name)->first();
    }

    /**
     * Find an operation status by slug.
     */
    public function findBySlug(string $slug): ?self
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    /**
     * Create an operation status record.
     */
    public function storeRecord(array $data): self
    {
        return $this->create($data);
    }

    /**
     * Update an operation status by ID.
     */
    public function updateById(int $id, array $data): bool
    {
        $operationStatus = $this->findById($id);

        if (!$operationStatus) {
            return false;
        }

        return (bool) $operationStatus->update($data);
    }

    /**
     * Soft delete an operation status by ID and set status to 15.
     */
    public function softDeleteById(int $id): bool
    {
        $operationStatus = $this->findById($id);

        if (!$operationStatus) {
            return false;
        }

        $operationStatus->status = '15';
        $operationStatus->save();

        return (bool) $operationStatus->delete();
    }

    /**
     * Search operation statuses by criteria with pagination.
     */
    public function searchRecords(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery();

        if (!empty($criteria['q'])) {
            $q = $criteria['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if (!empty($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }

        if (!empty($criteria['slug'])) {
            $query->where('slug', $criteria['slug']);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }
}
