<?php

namespace App\Repositories;

use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
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
        $modelClass = $this->modelClass;
        $query = $modelClass::with(['roles', 'permissions', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone']);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a user by ID
     */
    public function find(int $id): ?User
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::where('id', $id);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::where('email', $email);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->first();
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
        $modelClass = $this->modelClass;
        $query = $modelClass::with($relations)->where('id', $id);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->first();
    }

    /**
     * Search users by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::with(['roles', 'permissions', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone']);

        // Handle the generic 'search' parameter
        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        /**
         * Added department-based filtering support using department IDs
         * and department slugs, including multiple request parameter formats.
         */
        $departmentIds = [];
        $departmentSlugs = [];

        foreach ($criteria as $field => $value) {
            if ($field === 'search' || $value === null || $value === '') {
                continue;
            } elseif ($field === 'role') {
                // Handle role search through relationships
                $query->whereHas('roles', function ($q) use ($value) {
                    $q->where('name', $value);
                });
            } elseif (in_array($field, ['departments_id', 'departments_ids', 'department_id', 'department_ids'], true)) {
                $raw = is_string($value) ? explode(',', $value) : (array) $value;
                $ids = array_values(array_filter(array_map('intval', $raw), fn($id) => $id > 0));
                $departmentIds = array_merge($departmentIds, $ids);
            } elseif (in_array($field, ['departments_slug', 'departments_slugs', 'department_slug', 'department_slugs'], true)) {
                $raw = is_string($value) ? explode(',', $value) : (array) $value;
                $slugs = array_values(array_filter(array_map('trim', $raw), fn($s) => $s !== ''));
                $departmentSlugs = array_merge($departmentSlugs, $slugs);
            } elseif (in_array($field, ['page', 'per_page'], true)) {
                continue;
            } else {
                $query->where($field, $value);
            }
        }

        if (!empty($departmentIds) || !empty($departmentSlugs)) {
            $query->whereHas('departments', function ($q) use ($departmentIds, $departmentSlugs) {
                $q->where(function ($subQ) use ($departmentIds, $departmentSlugs) {
                    if (!empty($departmentIds)) {
                        $subQ->whereIn('departments.id', array_unique($departmentIds));
                    }
                    if (!empty($departmentSlugs)) {
                        if (!empty($departmentIds)) {
                            $subQ->orWhereIn('departments.slug', array_unique($departmentSlugs));
                        } else {
                            $subQ->whereIn('departments.slug', array_unique($departmentSlugs));
                        }
                    }
                });
            });
        }

        $this->applyOrganisationValidation($query, auth()->user());

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get users by conditions
     */
    public function findBy(array $conditions): Collection
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::where($conditions);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->get();
    }

    /**
     * Get first user by conditions
     */
    public function findFirstBy(array $conditions): ?User
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::where($conditions);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->first();
    }

    /**
     * Count users by conditions
     */
    public function countBy(array $conditions): int
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::where($conditions);
        $this->applyOrganisationValidation($query, auth()->user());
        
        return $query->count();
    }

    /**
     * Update the last login timestamp of a user
     */
    public function updateLastLogin(int $userId): ?User
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        return $user;
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $modelClass = $this->modelClass;
        
        $baseQuery = $modelClass::query();
        $this->applyOrganisationValidation($baseQuery, auth()->user());
        
        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
            'verified' => (clone $baseQuery)->whereNotNull('email_verified_at')->count(),
            'unverified' => (clone $baseQuery)->whereNull('email_verified_at')->count(),
        ];
    }

    /**
     * Ensure users belong to the authenticated user's organisation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user
     */
    protected function applyOrganisationValidation($query, $user): void
    {
        if (!$user) {
            return;
        }

        // First check with the organization, AND then check with the parent child flow.
        // This ensures a user can ONLY see their descendants who are ALSO in their organization.
        // Peers in the organization will NOT be shown.
        $strictDescendantIds = \App\Support\UserAccessScope::getStrictDescendantsInOrganisation($user);
        
        $query->whereIn('users.id', $strictDescendantIds);
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
        $modelClass = $this->modelClass;
        $user = $modelClass::find($userId);

        if (!$user) {
            return;
        }

        $user->syncValidDepartments($departmentIds);
    }
}