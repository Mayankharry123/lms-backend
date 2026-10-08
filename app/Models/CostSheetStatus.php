<?php

/**
 * CostSheetStatus
 * -----------------------------------------
 * This model represents the cost_sheet_statuses table,
 * which stores the statuses of cost sheets.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CostSheetStatus extends BaseModel
{
    protected $table = 'cost_sheet_statuses';

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
     * Get all cost sheet statuses with pagination.
     */
    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()->latest()->paginate($perPage);
    }

    /**
     * Find a cost sheet status by ID.
     */
    public function findById(int $id): ?self
    {
        return $this->newQuery()->find($id);
    }

    /**
     * Find a cost sheet status by UUID.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a cost sheet status by name.
     */
    public function findByName(string $name): ?self
    {
        return $this->newQuery()->where('name', $name)->first();
    }

    /**
     * Find a cost sheet status by slug.
     */
    public function findBySlug(string $slug): ?self
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    /**
     * Find the first active cost sheet status.
     */
    public function findFirstActive(): ?self
    {
        return $this->newQuery()
            ->where('status', '1')
            ->orderBy('id')
            ->first();
    }

    /**
     * Create a cost sheet status.
     */
    public function storeRecord(array $data): self
    {
        return $this->create($data);
    }

    /**
     * Update a cost sheet status by ID.
     */
    public function updateById(int $id, array $data): bool
    {
        $costSheetStatus = $this->findById($id);

        if (!$costSheetStatus) {
            return false;
        }

        return (bool) $costSheetStatus->update($data);
    }

    /**
     * Soft delete a cost sheet status by ID and set status to 15.
     */
    public function softDeleteById(int $id): bool
    {
        $costSheetStatus = $this->findById($id);

        if (!$costSheetStatus) {
            return false;
        }

        $costSheetStatus->status = '15';
        $costSheetStatus->save();

        return (bool) $costSheetStatus->delete();
    }

    /**
     * Search cost sheet statuses by criteria with pagination.
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
