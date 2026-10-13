<?php
/**
 * Interface BaseRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    /**
     * Get all records with pagination
     */
    public function all(int $perPage = 15);

    /**
     * Find record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool;

    /**
     * Get records with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Model;

    /**
     * Search records by criteria
     */
    public function search(array $criteria, int $perPage = 15);

    /**
     * Get records by conditions
     */
    public function findBy(array $conditions): Collection;

    /**
     * Get first record by conditions
     */
    public function findFirstBy(array $conditions): ?Model;

    /**
     * Count records by conditions
     */
    public function countBy(array $conditions): int;
}