<?php

namespace App\Repositories;

use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Return the model class associated with this repository
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Get all users with pagination
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return User::getRepositoryUsers($perPage);
    }

    /**
     * Find a user by ID
     */
    public function find(int $id): ?User
    {
        return User::findRepositoryUser($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::findRepositoryUserByEmail($email);
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        return parent::create($data);
    }

    /**
     * Update user by ID
     */
    public function update(int $id, array $data): bool
    {
        return parent::update($id, $data);
    }

    /**
     * Delete user by ID
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Get user with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return User::findRepositoryUserWithRelations($id, $relations);
    }

    /**
     * Search users by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return User::searchRepositoryUsers($criteria, $perPage);
    }

    /**
     * Get users by conditions
     */
    public function findBy(array $conditions): Collection
    {
        return User::findRepositoryUsersBy($conditions);
    }

    /**
     * Get first user by conditions
     */
    public function findFirstBy(array $conditions): ?User
    {
        return User::findFirstRepositoryUserBy($conditions);
    }

    /**
     * Count users by conditions
     */
    public function countBy(array $conditions): int
    {
        return User::countRepositoryUsersBy($conditions);
    }

    /**
     * Update the last login timestamp of a user
     */
    public function updateLastLogin(int $userId): ?User
    {
        return User::updateRepositoryLastLogin($userId);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        return User::getRepositoryStatistics();
    }

    /**
     * Sync user departments.
     *
     * @param int $userId
     * @param array $departmentIds
     * @return void
     */
    public function syncDepartments(int $userId, array $departmentIds): void
    {
        User::syncRepositoryDepartments($userId, $departmentIds);
    }

    /**
     * Active planner-admin users that belong to the given organisation.
     *
     * @param int $organisationId
     * @return Collection<int, User>
     */
    public function findActivePlannerAdminsByOrganisation(int $organisationId): Collection
    {
        return User::findActivePlannerAdminsByOrganisation($organisationId);
    }

    /**
     * Active ops-admin users that belong to the given organisation.
     *
     * @param int $organisationId
     * @return Collection<int, User>
     */
    public function findActiveOpsAdminsByOrganisation(int $organisationId): Collection
    {
        return User::findActiveOpsAdminsByOrganisation($organisationId);
    }

    /**
     * Active finance-admin users that belong to the given organisation.
     *
     * @param int $organisationId
     * @return Collection<int, User>
     */
    public function findActiveFinanceAdminsByOrganisation(int $organisationId): Collection
    {
        return User::findActiveFinanceAdminsByOrganisation($organisationId);
    }
}