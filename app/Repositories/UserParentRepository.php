<?php

/**
 * UserParentRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\UserParentRepositoryInterface;
use App\Models\UserParent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserParentRepository extends BaseRepository implements UserParentRepositoryInterface
{
    /**
     * Get the model class
     */
    protected function getModelClass(): string
    {
        return UserParent::class;
    }

    /**
     * Get all parent relationships with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithPagination(int $perPage = 15): LengthAwarePaginator
    {
        return UserParent::with(['user', 'parent'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get parent relationships for a specific user
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getParentsByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return UserParent::with(['parent'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get child relationships for a specific parent user
     *
     * @param int $parentId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChildrenByParentId(int $parentId, int $perPage = 15): LengthAwarePaginator
    {
        return UserParent::with(['user'])
            ->where('is_parent', $parentId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if a user has a specific parent
     *
     * @param int $userId
     * @param int $parentId
     * @return bool
     */
    public function hasParent(int $userId, int $parentId): bool
    {
        return UserParent::where('user_id', $userId)
            ->where('is_parent', $parentId)
            ->exists();
    }

    /**
     * Assign a parent to a user
     *
     * @param int $userId
     * @param int $parentId
     * @return Model
     */
    public function assignParent(int $userId, int $parentId): Model
    {
        // Check if relationship already exists
        if ($this->hasParent($userId, $parentId)) {
            return UserParent::where('user_id', $userId)
                ->where('is_parent', $parentId)
                ->first();
        }

        return UserParent::create([
            'user_id' => $userId,
            'is_parent' => $parentId,
        ]);
    }

    /**
     * Remove parent relationship
     *
     * @param int $userId
     * @param int $parentId
     * @return bool
     */
    public function removeParent(int $userId, int $parentId): bool
    {
        return UserParent::where('user_id', $userId)
            ->where('is_parent', $parentId)
            ->delete() > 0;
    }

    /**
     * Get parent relationships with related user data
     *
     * @param int $userId
     * @return Collection
     */
    public function getParentRelationshipsWithUsers(int $userId): Collection
    {
        return UserParent::with(['user', 'parent'])
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get child relationships with related user data
     *
     * @param int $parentId
     * @return Collection
     */
    public function getChildRelationshipsWithUsers(int $parentId): Collection
    {
        return UserParent::with(['user', 'parent'])
            ->where('is_parent', $parentId)
            ->get();
    }
}
