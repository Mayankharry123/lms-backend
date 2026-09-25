<?php

namespace App\Repositories;

use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;   

class ZoneRepository implements ZoneRepositoryInterface
{
    protected $model;

    /**
     * EloquentZoneRepository constructor.
     *
     * @param Zone $model
     */
    public function __construct(Zone $model)
    {
        $this->model = $model;
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function allPaginated(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator 
    {
        // 1. Query builder shuru karein
        $query = $this->model->query(); 

        // 2. Search logic add karein
        if ($searchTerm) {
            // Yahaan 'name' column mein search kar rahe hain.
            // Aap ise apne database column ke naam se badal sakte hain.
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // 3. Puraana logic (latest) aur paginate karein
        return $query->latest()->paginate($perPage)->appends(request()->query());
    }

    /**
     * @return Collection
     */
    public function getActiveList(): Collection
    {
        return $this->model->where('status', '1')->latest()->get();
    }

    /**
     * @param int $id
     * @return Zone
     */
    public function findById(int $id): Zone
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @param array $data
     * @return Zone
     */
    public function create(array $data): Zone
    {
        return $this->model->create($data);
    }

    /**
     * @param Zone $zone
     * @param array $data
     * @return bool
     */
    public function update(Zone $zone, array $data): bool
    {
        return $zone->update($data);
    }

    /**
     * @param Zone $zone
     * @return bool
     */
    public function delete(Zone $zone): bool
    {
        return $zone->delete();
    }

    /**
     * Find non-deleted zones by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return Collection
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
            return $this->model->newCollection();
        }

        return $this->model
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name']);
    }
}