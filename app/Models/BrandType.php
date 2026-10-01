<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandType extends Model
{
    use SoftDeletes;
    protected $table = 'brand_types';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function getAllActive(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->where('status', '1')
            ->whereNull('deleted_at');

        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getBrandsCount(int $id): int
    {
        return $this->newQuery()->findOrFail($id)->brands()->count();
    }

    /**
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names): \Illuminate\Support\Collection
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name']);
    }

    /**
     * @param array<int, string> $names
     * @return array<int, string>
     */
    private function normalizeNameList(array $names): array
    {
        $normalized = [];
        foreach ($names as $name) {
            $value = mb_strtolower(trim((string) $name));
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }
}
