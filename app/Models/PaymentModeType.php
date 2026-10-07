<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentModeType extends BaseModel
{
    protected $table = 'payment_mode_types';

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
     * Relationship: Has many voucher items.
     */
    public function voucherItems(): HasMany
    {
        return $this->hasMany(VoucherItem::class, 'payment_mode_type_id');
    }

    /**
     * Get active payment mode types as a collection.
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

        return $query->orderBy('id', 'asc')->get();
    }

    /**
     * Get active payment mode types with pagination.
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

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Find active payment mode type by ID.
     */
    public function findActiveById(int $id): ?self
    {
        return $this->newQuery()
            ->where('id', $id)
            ->where('status', '1')
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Find active payment mode type by slug.
     */
    public function findActiveBySlug(string $slug): ?self
    {
        return $this->newQuery()
            ->where('slug', $slug)
            ->where('status', '1')
            ->whereNull('deleted_at')
            ->first();
    }
}
