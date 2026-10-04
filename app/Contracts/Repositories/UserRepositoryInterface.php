<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator<User>
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User;

    /**
     * Find a user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update a user by ID
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a user by ID
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Search users by criteria with pagination
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator<User>
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a user by ID with relationships
     *
     * @param int $id
     * @param array $relations
     * @return User|null
     */
    public function findWithRelations(int $id, array $relations = []): ?User;

    /**
     * Find a user by ID with relationships
     *
     * @param int $id
     * @param array $relations
     * @return User|null
     */
    public function updateLastLogin(int $id): ?User;

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Sync user departments.
     *
     * @param int $userId
     * @param array $departmentIds
     * @return void
     */
    public function syncDepartments(int $userId, array $departmentIds): void;

    /**
     * Active users in an organisation whose role slug is planner-admin.
     * Matches users.organisation_id and the organisation_user pivot.
     *
     * @param int $organisationId
     * @return Collection<int, User>
     */
    public function findActivePlannerAdminsByOrganisation(int $organisationId): Collection;

    /**
     * Active users in an organisation whose role slug is ops-admin.
     * Matches users.organisation_id and the organisation_user pivot.
     *
     * @param int $organisationId
     * @return Collection<int, User>
     */
    public function findActiveOpsAdminsByOrganisation(int $organisationId): Collection;
}