<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Zone extends Model
{
    use SoftDeletes;
    protected $table = 'zones';
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];
    public $timestamps = true;

    // Scope for active records
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    public function getPaginated(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->newQuery();

        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        return $query->latest()->paginate($perPage)->appends(request()->query());
    }

    public function getActiveList(): Collection
    {
        return $this->newQuery()->active()->latest()->get();
    }

    /**
     * @param array<int, string> $names
     * @return Collection<int, static>
     */
    public function findByNames(array $names): Collection
    {
        $normalized = [];
        foreach ($names as $name) {
            $value = mb_strtolower(trim((string) $name));
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        $normalized = array_values($normalized);
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
}
