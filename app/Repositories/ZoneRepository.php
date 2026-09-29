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
        return $this->model->getPaginated($perPage, $searchTerm);
    }

    /**
     * @return Collection
     */
    public function getActiveList(): Collection
    {
        return $this->model->getActiveList();
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
        return $this->model->findByNames($names);
    }
}