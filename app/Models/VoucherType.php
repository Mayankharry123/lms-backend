<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VoucherType extends BaseModel
{
    protected $table = 'voucher_types';

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
     * Get active voucher types as a collection.
     */
    public function getActiveList(?string $search = null): Collection
    {
        $query = $this->newQuery()
            ->where('status', '1')
            ->whereNull('deleted_at');

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('slug', 'LIKE', "%{$term}%");
            });
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Get active voucher types with pagination.
     */
    public function paginateActive(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->where('status', '1')
            ->whereNull('deleted_at');

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('slug', 'LIKE', "%{$term}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    /**
     * Find a voucher type by ID.
     */
    public function findById(int $id): ?self
    {
        return $this->newQuery()
            ->where('status', '1')
            ->whereNull('deleted_at')
            ->find($id);
    }

    /**
     * Find a voucher type by slug.
     */
    public function findBySlug(string $slug): ?self
    {
        return $this->newQuery()
            ->where('slug', $slug)
            ->where('status', '1')
            ->whereNull('deleted_at')
            ->first();
    }
}
