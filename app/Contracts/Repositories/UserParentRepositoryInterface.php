<?php
/**
 * Interface UserParentRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserParentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all parent relationships with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithPagination(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get parent relationships for a specific user
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getParentsByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get child relationships for a specific parent user
     *
     * @param int $parentId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChildrenByParentId(int $parentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if a user has a specific parent
     *
     * @param int $userId
     * @param int $parentId
     * @return bool
     */
    public function hasParent(int $userId, int $parentId): bool;

    /**
     * Assign a parent to a user
     *
     * @param int $userId
     * @param int $parentId
     * @return Model
     */
    public function assignParent(int $userId, int $parentId): Model;

    /**
     * Remove parent relationship
     *
     * @param int $userId
     * @param int $parentId
     * @return bool
     */
    public function removeParent(int $userId, int $parentId): bool;

    /**
     * Get parent relationships with related user data
     *
     * @param int $userId
     * @return Collection
     */
    public function getParentRelationshipsWithUsers(int $userId): Collection;

    /**
     * Get child relationships with related user data
     *
     * @param int $parentId
     * @return Collection
     */
    public function getChildRelationshipsWithUsers(int $parentId): Collection;
}
