<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\UserParentRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * The user repository instance
     *
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * The user parent repository instance
     *
     * @var UserParentRepositoryInterface
     */
    protected $userParentRepository;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository
     * @param UserParentRepositoryInterface $userParentRepository
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        UserParentRepositoryInterface $userParentRepository
    ){
        $this->userRepository = $userRepository;
        $this->userParentRepository = $userParentRepository;
    }

    /**
     * Get all users with pagination
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllUsers(int $perPage = 15)
    {
        return $this->userRepository->all($perPage);
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findWithRelations($id, ['profile', 'roles', 'permissions', 'parentRelationships', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone', 'zones']);
    }

    /**
     * Get user by email
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function createUser(array $data): User
    {
        $data = $this->prepareUserInput($data);

        $this->validateUserData($data);
    
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Set default values
        $data['status'] = $data['status'] ?? '1';

        // Extract role_id or role (accept both formats)
        $roleIds = $data['role_id'] ?? $data['role'] ?? [];
        unset($data['role_id'], $data['role']);

        // Extract parent IDs for user_parent relationships
        $parentIds = $data['is_parent'] ?? [];
        unset($data['is_parent']);

        // Extract organisation IDs for organisation_user relationships
        $organisationIds = $this->extractOrganisationIds($data);

        // Extract zone IDs for zone_user relationships
        $zoneIds = $this->extractZoneIds($data);

        // Extract department IDs for user_department relationships
        $departmentIds = $data['department_ids'] ?? [];

        $data = $this->stripNonPersistedFields($data);

        $user = $this->userRepository->create($data);

        // Sync roles if provided
        if (!empty($roleIds)) {
            $this->syncUserRoles($user->id, $roleIds);
        }

        // Sync parents if provided
        if (!empty($parentIds)) {
            $this->syncUserParents($user->id, $parentIds);
        }

        // Sync organisations if provided
        if (!empty($organisationIds)) {
            $this->syncUserOrganisations($user->id, $organisationIds);
        }

        if (!empty($zoneIds)) {
            $this->syncUserZones($user->id, $zoneIds);
        }

        // Sync departments if provided
        if (!empty($departmentIds)) {
            $this->syncUserDepartments($user->id, $departmentIds);
        }

        // Reload user with relationships
        return $user->load(['profile', 'roles', 'permissions', 'parentRelationships', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone', 'zones']);
    }

    /**
     * Update user
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ValidationException
     */
    public function updateUser(int $id, array $data): bool
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return false;
        }

        $data = $this->prepareUserInput($data);

        // Validate data for update (includes role_id validation)
        $this->validateUserData($data, $id);

        // Extract role_id or role (accept both formats)
        $roleIds = $data['role_id'] ?? $data['role'] ?? null;

        // Extract parent IDs for user_parent relationships
        $parentIds = $data['is_parent'] ?? null;

        // Extract organisation IDs for organisation_user relationships
        $organisationIds = array_key_exists('organisation_ids', $data)
            ? $this->extractOrganisationIds($data)
            : (array_key_exists('organisation_id', $data) ? [(int) $data['organisation_id']] : null);

        $zoneIds = array_key_exists('zone_ids', $data)
            ? $this->extractZoneIds($data)
            : (array_key_exists('zone_id', $data) ? [(int) $data['zone_id']] : null);

        // Extract department IDs for user_department relationships
        $departmentIds = $data['department_ids'] ?? null;

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data = $this->stripNonPersistedFields($data);

        $success = $this->userRepository->update($id, $data);

        // Sync roles if provided
        if ($roleIds !== null && is_array($roleIds)) {
            $this->syncUserRoles($id, $roleIds);
        }

        // Sync parents if provided
        if ($parentIds !== null && is_array($parentIds)) {
            $this->syncUserParents($id, $parentIds);
        }

        // Sync organisations if provided
        if ($organisationIds !== null && is_array($organisationIds)) {
            $this->syncUserOrganisations($id, $organisationIds);
        }

        if ($zoneIds !== null && is_array($zoneIds)) {
            $this->syncUserZones($id, $zoneIds);
        }

        // Sync departments if provided
        if ($departmentIds !== null && is_array($departmentIds)) {
            $this->syncUserDepartments($id, $departmentIds);
        }

        return $success;
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Authenticate user
     *
     * @param array $credentials
     * @return User|null
     */
    public function authenticateUser(array $credentials): ?User
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;  
        }

        if (!$user->isActive()) {
            
            return null;
        }

        // Update last login time
        $this->userRepository->updateLastLogin($user->id);

        return $user;
    }

    /**
     * Search users
     *
     * @param array $criteria
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchUsers(array $criteria, int $perPage = 15)
    {
        return $this->userRepository->search($criteria, $perPage);
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getUserStatistics(): array
    {
        return $this->userRepository->getStatistics();
    }

    /**
     * Change user password
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     * @throws ValidationException
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return false;
        }

        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.']
            ]);
        }

        $this->validatePassword($newPassword);

        return $this->userRepository->update($userId, [
            'password' => Hash::make($newPassword)
        ]);
    }

    /**
     * Validate user data
     *
     * @param array $data
     * @param int|null $userId
     * @return void
     * @throws ValidationException
     */
    protected function validateUserData(array $data, ?int $userId = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'sometimes|required|string|min:8',
            'phone' => 'nullable|integer|digits:10',
            'role_id' => 'sometimes|array',
            'role_id.*' => 'integer|exists:roles,id',
            'role' => 'sometimes|array',
            'role.*' => 'integer|exists:roles,id',
            'status' => 'sometimes|in:1,2,3',
            'is_parent' => 'nullable|array',
            'is_parent.*' => 'integer|exists:users,id',
            'organisation_ids' => 'nullable|array',
            'organisation_ids.*' => 'integer|exists:organisations,id',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'integer|exists:zones,id',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ];

        // Make organisation_id and zone_id required for new users, nullable for updates
        if (!$userId) {
            $rules['organisation_id'] = 'required_without:organisation_ids|nullable|integer|exists:organisations,id';
            $rules['organisation_ids'] = 'required_without:organisation_id|nullable|array|min:1';
            $rules['zone_id'] = 'required_without:zone_ids|nullable|integer|exists:zones,id';
            $rules['zone_ids'] = 'required_without:zone_id|nullable|array|min:1';
        } else {
            $rules['organisation_id'] = 'nullable|integer|exists:organisations,id';
            $rules['zone_id'] = 'nullable|integer|exists:zones,id';
        }

        // Add unique email rule if creating new user or updating email
        if (!$userId || isset($data['email'])) {
            $emailRule = Rule::unique('users', 'email')->whereNull('deleted_at');
            if ($userId) {
                $emailRule->ignore($userId);
            }
            $rules['email'] = ['required', 'email', 'max:255', $emailRule];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate password
     *
     * @param string $password
     * @return void
     * @throws ValidationException
     */
    protected function validatePassword(string $password): void
    {
        $validator = Validator::make(['password' => $password], [
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Sync user roles
     *
     * @param int $userId
     * @param array $roleIds
     * @return void
     */
    public function syncUserRoles(int $userId, array $roleIds): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        // Get the user type class
        $userType = User::class;

        // First, delete all existing role entries for this user
        DB::table('role_user')
            ->where('user_id', $userId)
            ->delete();

        // Insert new role entries
        $insertData = [];
        foreach ($roleIds as $roleId) {
            $insertData[] = [
                'role_id' => $roleId,
                'user_id' => $userId,
                'user_type' => $userType,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertData)) {
            DB::table('role_user')->insert($insertData);
        }
    }

    /**
     * Sync user parents
     *
     * @param int $userId
     * @param array $parentIds
     * @return void
     */
    public function syncUserParents(int $userId, array $parentIds): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        // Delete all existing parent relationships for this user
        DB::table('user_parent')
            ->where('user_id', $userId)
            ->delete();

        // Insert new parent relationships
        $insertData = [];
        foreach ($parentIds as $parentId) {
            $parentId = (int) $parentId;

            if ($parentId <= 0) {
                continue;
            }

            // Ensure parent user exists and is not the same as the user
            if ($parentId !== $userId && User::find($parentId)) {
                $insertData[] = [
                    'user_id' => $userId,
                    'is_parent' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('user_parent')->insert($insertData);
        }
    }

    /**
     * Sync user organisations
     *
     * @param int $userId
     * @param array $organisationIds
     * @return void
     */
    public function syncUserOrganisations(int $userId, array $organisationIds): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        DB::table('organisation_user')
            ->where('user_id', $userId)
            ->delete();

        $insertData = [];
        $uniqueOrganisationIds = [];

        foreach ($organisationIds as $organisationId) {
            $organisationId = (int) $organisationId;

            if ($organisationId <= 0 || in_array($organisationId, $uniqueOrganisationIds, true)) {
                continue;
            }

            if (DB::table('organisations')->where('id', $organisationId)->exists()) {
                $uniqueOrganisationIds[] = $organisationId;
                $insertData[] = [
                    'user_id' => $userId,
                    'organisation_id' => $organisationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('organisation_user')->insert($insertData);
        }
    }

    /**
     * Sync user zones.
     *
     * @param int $userId
     * @param array $zoneIds
     * @return void
     */
    public function syncUserZones(int $userId, array $zoneIds): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        DB::table('zone_user')->where('user_id', $userId)->delete();

        $insertData = [];
        $uniqueZoneIds = [];

        foreach ($zoneIds as $zoneId) {
            $zoneId = (int) $zoneId;

            if ($zoneId <= 0 || in_array($zoneId, $uniqueZoneIds, true)) {
                continue;
            }

            if (DB::table('zones')->where('id', $zoneId)->exists()) {
                $uniqueZoneIds[] = $zoneId;
                $insertData[] = [
                    'user_id' => $userId,
                    'zone_id' => $zoneId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('zone_user')->insert($insertData);
        }
    }

    /**
     * Sync user departments
     *
     * @param int $userId
     * @param array $departmentIds
     * @return void
     */
    public function syncUserDepartments(int $userId, array $departmentIds): void
    {
        $this->userRepository->syncDepartments($userId, $departmentIds);
    }

    /**
     * Normalize request aliases before validation and persistence.
     */
    protected function prepareUserInput(array $data): array
    {
        if (isset($data['organisation']) && !isset($data['organisation_id'])) {
            $data['organisation_id'] = $data['organisation'];
        }

        if (isset($data['origination']) && !isset($data['organisation_id'])) {
            $data['organisation_id'] = $data['origination'];
        }

        if (isset($data['zone']) && !isset($data['zone_id'])) {
            $data['zone_id'] = $data['zone'];
        }

        if (isset($data['zone_id']) && is_array($data['zone_id']) && !isset($data['zone_ids'])) {
            $data['zone_ids'] = array_values($data['zone_id']);
            $data['zone_id'] = (int) $data['zone_ids'][0];
        }

        if (isset($data['department_id']) && !isset($data['department_ids'])) {
            $data['department_ids'] = [(int) $data['department_id']];
        }

        $organisationIds = $this->extractOrganisationIds($data);

        if (!empty($organisationIds) && empty($data['organisation_id'])) {
            $data['organisation_id'] = (int) $organisationIds[0];
        }

        $zoneIds = $this->extractZoneIds($data);

        if (!empty($zoneIds) && empty($data['zone_id'])) {
            $data['zone_id'] = (int) $zoneIds[0];
        }

        return $data;
    }

    /**
     * Extract organisation IDs from request payload.
     */
    protected function extractOrganisationIds(array $data): array
    {
        if (isset($data['organisation_ids']) && is_array($data['organisation_ids'])) {
            return array_values($data['organisation_ids']);
        }

        if (isset($data['organisation_id'])) {
            return [(int) $data['organisation_id']];
        }

        return [];
    }

    /**
     * Extract zone IDs from request payload.
     */
    protected function extractZoneIds(array $data): array
    {
        if (isset($data['zone_ids']) && is_array($data['zone_ids'])) {
            return array_values($data['zone_ids']);
        }

        if (isset($data['zone_id'])) {
            return [(int) $data['zone_id']];
        }

        return [];
    }

    /**
     * Remove fields that should not be persisted on the users table.
     */
    protected function stripNonPersistedFields(array $data): array
    {
        unset(
            $data['role_id'],
            $data['role'],
            $data['is_parent'],
            $data['organisation_ids'],
            $data['zone_ids'],
            $data['department_ids'],
            $data['organisation'],
            $data['origination'],
            $data['organisation_name'],
            $data['zone'],
            $data['zone_name'],
            $data['password_confirmation'],
            $data['_method']
        );

        return $data;
    }
}