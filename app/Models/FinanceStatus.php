<?php

/**
 * FinanceStatus
 * -----------------------------------------
 * This model represents the finance_statuses table,
 * which stores the statuses of finance records.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceStatus extends BaseModel
{
    protected $table = 'finance_statuses';

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
     * Get all finance statuses with pagination.
     */
    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()->latest()->paginate($perPage);
    }

    /**
     * Find a finance status by ID.
     */
    public function findById(int $id): ?self
    {
        return $this->newQuery()->find($id);
    }

    /**
     * Find a finance status by UUID.
     */
    public function findByUuid(string $uuid): ?self
    {
        return $this->newQuery()->where('uuid', $uuid)->first();
    }

    /**
     * Find a finance status by name.
     */
    public function findByName(string $name): ?self
    {
        return $this->newQuery()->where('name', $name)->first();
    }

    /**
     * Find a finance status by slug.
     */
    public function findBySlug(string $slug): ?self
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    /**
     * Find the first active finance status.
     */
    public function findFirstActive(): ?self
    {
        return $this->newQuery()
            ->where('status', '1')
            ->orderBy('id')
            ->first();
    }

    /**
     * Create a finance status.
     */
    public function storeRecord(array $data): self
    {
        return $this->create($data);
    }

    /**
     * Update a finance status by ID.
     */
    public function updateById(int $id, array $data): bool
    {
        $financeStatus = $this->findById($id);

        if (!$financeStatus) {
            return false;
        }

        return (bool) $financeStatus->update($data);
    }

    /**
     * Soft delete a finance status by ID and set status to 15.
     */
    public function softDeleteById(int $id): bool
    {
        $financeStatus = $this->findById($id);

        if (!$financeStatus) {
            return false;
        }

        $financeStatus->status = '15';
        $financeStatus->save();

        return (bool) $financeStatus->delete();
    }

    /**
     * Search finance statuses by criteria with pagination.
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
