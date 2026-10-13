<?php

/**
 * BaseRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var string
     */
    protected string $modelClass;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
    }

    /**
     * Each repository must return its model class.
     *
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * Get all records with pagination.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function all(int $perPage = 15)
    {
        $modelClass = $this->modelClass;
        return $modelClass::paginate($perPage);
    }

    /**
     * Find a record by its primary key ID.
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        $modelClass = $this->modelClass;
        return $modelClass::find($id);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $modelClass = $this->modelClass;
        return $modelClass::create($data);
    }

    /**
     * Update a record by its primary key ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) return false;
        return $model->update($data);
    }

    /**
     * Delete a record by its primary key ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if (!$model) return false;
        return $model->delete();
    }

    /**
     * Find a record with specified relations by ID.
     *
     * @param int $id
     * @param array $relations
     * @return Model|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Model
    {
        $modelClass = $this->modelClass;
        return $modelClass::with($relations)->find($id);
    }

    /**
     * Search records by field-value criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15)
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find records matching the given conditions.
     *
     * @param array $conditions
     * @return Collection
     */
    public function findBy(array $conditions): Collection
    {
        $modelClass = $this->modelClass;
        return $modelClass::where($conditions)->get();
    }

    /**
     * Find first record matching the given conditions.
     *
     * @param array $conditions
     * @return Model|null
     */
    public function findFirstBy(array $conditions): ?Model
    {
        $modelClass = $this->modelClass;
        return $modelClass::where($conditions)->first();
    }

    /**
     * Count records matching the given conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function countBy(array $conditions): int
    {
        $modelClass = $this->modelClass;
        return $modelClass::where($conditions)->count();
    }
}