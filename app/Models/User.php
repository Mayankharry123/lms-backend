<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\HasTimestamps;
use App\Traits\HasUuid;
use App\Traits\HasApiTokens;
use App\Models\LoginLog;
use App\Models\Organisation;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\NotificationTrait;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory, HasTimestamps, HasUuid, HasApiTokens, SoftDeletes, NotificationTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'refresh_token',
        'phone',
        'avatar',
        'status',
        'email_verified_at',
        'last_login_at',
        'organisation_id',
        'zone_id',    
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        
        'password',
        'refresh_token',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        //'created_at_formatted',
        //'updated_at_formatted',
        'created_at_human',
        'updated_at_human',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        $roles = $this->roles()->pluck('name')->toArray();
        return [
            'roles' => $roles,
            'status' => $this->status,
        ];
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === '1';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get user's avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Illuminate\Support\Facades\Storage::url('avatars/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        });
    }

    /**
     * Build nested tree structure for children recursively filtered by organisation
     * 
     * @param int|array<int> $organisationId
     * @param array<int> $zoneIds
     * @param array<int, string> $excludedRoleSlugs Role slugs omitted at every level. Their children are omitted too.
     * @return array
     */
    public function getChildTreeByOrganisation($organisationId, array $leadFilters = [], array $zoneIds = [], array $excludedRoleSlugs = []): array
    {
        $organisationIds = is_array($organisationId) ? $organisationId : [$organisationId];
        $excludedRoleSlugs = array_values(array_filter($excludedRoleSlugs, fn ($slug) => is_string($slug) && $slug !== ''));

        $children = $this->children()
            ->when($excludedRoleSlugs !== [], function ($query) use ($excludedRoleSlugs) {
                $query->whereDoesntHave('roles', function ($roleQuery) use ($excludedRoleSlugs) {
                    $roleQuery->whereIn('roles.slug', $excludedRoleSlugs);
                });
            })
            ->when(!empty($organisationIds) || !empty($zoneIds), function ($query) use ($organisationIds, $zoneIds) {
                if (!empty($organisationIds)) {
                    $query->where(function ($organisationQuery) use ($organisationIds) {
                        $organisationQuery->whereIn('users.organisation_id', $organisationIds)
                            ->orWhereHas('organisations', function ($pivotQuery) use ($organisationIds) {
                                $pivotQuery->whereIn('organisations.id', $organisationIds);
                            });
                    });
                }

                if (!empty($zoneIds)) {
                    $query->where(function ($zoneQuery) use ($zoneIds) {
                        $zoneQuery->whereIn('users.zone_id', $zoneIds)
                            ->orWhereHas('zones', function ($pivotQuery) use ($zoneIds) {
                                $pivotQuery->whereIn('zones.id', $zoneIds);
                            });
                    });
                }
            })
            ->when(!empty($leadFilters['name']), function ($query) use ($leadFilters) {
                $query->where('users.name', 'LIKE', '%' . $leadFilters['name'] . '%');
            })
            ->select('users.id', 'users.name', 'users.email')
            ->withCount(['assignedLeads as assigned_leads_count' => function ($query) use ($leadFilters) {
                foreach (['call_status', 'lead_status', 'priority'] as $column) {
                    if (!array_key_exists($column, $leadFilters) || $leadFilters[$column] === null || $leadFilters[$column] === '') {
                        continue;
                    }

                    $values = is_array($leadFilters[$column])
                        ? $leadFilters[$column]
                        : explode(',', (string) $leadFilters[$column]);
                    $values = array_values(array_filter(array_map('intval', $values), fn ($value) => $value > 0));

                    if ($values !== []) {
                        $query->whereIn($column === 'priority' ? 'priority_id' : $column, $values);
                    }
                }
            }])
            ->orderBy('users.name', 'asc')
            ->get();
        
        $tree = [];
        foreach ($children as $child) {
            $tree[] = [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'assigned_leads_count' => $child->assigned_leads_count,
                'children' => $child->getChildTreeByOrganisation($organisationIds, $leadFilters, $zoneIds, $excludedRoleSlugs)
            ];
        }
        
        return $tree;
    }


    /**
     * Build nested tree structure for children recursively filtered by organisation and department slug
     * 
     * @param int $organisationId
     * @param string $departmentSlug
     * @return array
     */
    public function getChildTreeByOrganisationAndDepartmentSlug($organisationId, $departmentSlug = 'planner'): array
    {
        $children = $this->children()
            ->where(function ($query) use ($organisationId) {
                $query->where('users.organisation_id', $organisationId)
                    ->orWhereHas('organisations', function ($organisationQuery) use ($organisationId) {
                        $organisationQuery->where('organisations.id', $organisationId);
                    });
            })
            ->whereHas('departments', function($query) use ($departmentSlug) {
                $query->where('departments.slug', $departmentSlug);
            })
            ->select('users.id', 'users.name')
            ->orderBy('users.name', 'asc')
            ->get();
        
        $tree = [];
        foreach ($children as $child) {
            $tree[] = [
                'id' => $child->id,
                'name' => $child->name,
                'children' => $child->getChildTreeByOrganisationAndDepartmentSlug($organisationId, $departmentSlug)
            ];
        }
        
        return $tree;
    }

    public function getChildTreeByDepartments(
        array $departmentIds = [],
        array $departmentSlugs = [],
        ?int $organisationId = null,
        bool $includeAssignedBriefCount = false
    ): array {
        $query = $this->children()->select('users.id', 'users.name', 'users.organisation_id');

        if ($organisationId !== null) {
            $query->with(['organisations:id']);
        }

        if ($includeAssignedBriefCount) {
            $query->withCount(['assignedBriefs as assigned_brief_count' => function ($briefQuery) {
                $briefQuery->where('briefs.status', '!=', '15');
            }]);
        }

        if ($departmentIds !== [] || $departmentSlugs !== []) {
            $query->whereHas('departments', function ($departmentQuery) use ($departmentIds, $departmentSlugs) {
                $departmentQuery->where(function ($filterQuery) use ($departmentIds, $departmentSlugs) {
                    if ($departmentIds !== []) {
                        $filterQuery->whereIn('departments.id', $departmentIds);
                    }

                    if ($departmentSlugs !== []) {
                        if ($departmentIds !== []) {
                            $filterQuery->orWhereIn('departments.slug', $departmentSlugs);
                        } else {
                            $filterQuery->whereIn('departments.slug', $departmentSlugs);
                        }
                    }
                });
            });
        }

        $children = $query->orderBy('users.name', 'asc')->get();
        $tree = [];

        foreach ($children as $child) {
            $nestedChildren = $child->getChildTreeByDepartments(
                $departmentIds,
                $departmentSlugs,
                $organisationId,
                $includeAssignedBriefCount
            );

            if ($organisationId !== null && !$child->belongsToOrganisation($organisationId)) {
                foreach ($nestedChildren as $descendant) {
                    $tree[] = $descendant;
                }
                continue;
            }

            $node = [
                'id' => $child->id,
                'name' => $child->name,
            ];

            if ($includeAssignedBriefCount) {
                $node['assigned_brief_count'] = (int) $child->assigned_brief_count;
            }

            $node['children'] = $nestedChildren;
            $tree[] = $node;
        }

        return $tree;
    }

    private function belongsToOrganisation(int $organisationId): bool
    {
        if ((int) $this->organisation_id === $organisationId) {
            return true;
        }

        $organisations = $this->relationLoaded('organisations')
            ? $this->organisations
            : $this->organisations()->get(['organisations.id']);

        return $organisations->contains(fn ($organisation) => (int) $organisation->id === $organisationId);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeVisibleToAuthenticatedUser($query, $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        return $query->whereIn(
            'users.id',
            \App\Support\UserAccessScope::getStrictDescendantsInOrganisation($user)
        );
    }

    public static function getRepositoryUsers(int $perPage = 15): LengthAwarePaginator
    {
        return self::with([
            'roles', 'permissions', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone',
        ])->visibleToAuthenticatedUser()->latest()->paginate($perPage);
    }

    public static function findRepositoryUser(int $id): ?self
    {
        return self::visibleToAuthenticatedUser()->where('id', $id)->first();
    }

    public static function findRepositoryUserByEmail(string $email): ?self
    {
        return self::visibleToAuthenticatedUser()->where('email', $email)->first();
    }

    public static function findRepositoryUserWithRelations(int $id, array $relations = []): ?self
    {
        return self::with($relations)->visibleToAuthenticatedUser()->where('id', $id)->first();
    }

    public static function searchRepositoryUsers(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = self::with([
            'roles', 'permissions', 'parents', 'children', 'organisation', 'organisations', 'departments', 'zone',
        ]);

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $departmentIds = [];
        $departmentSlugs = [];

        foreach ($criteria as $field => $value) {
            if ($field === 'search' || $value === null || $value === '') {
                continue;
            }
            if ($field === 'role') {
                $query->whereHas('roles', function ($q) use ($value) {
                    $q->where('name', $value);
                });
            } elseif (in_array($field, ['departments_id', 'departments_ids', 'department_id', 'department_ids'], true)) {
                $raw = is_string($value) ? explode(',', $value) : (array) $value;
                $ids = array_values(array_filter(array_map('intval', $raw), fn ($id) => $id > 0));
                $departmentIds = array_merge($departmentIds, $ids);
            } elseif (in_array($field, ['departments_slug', 'departments_slugs', 'department_slug', 'department_slugs'], true)) {
                $raw = is_string($value) ? explode(',', $value) : (array) $value;
                $slugs = array_values(array_filter(array_map('trim', $raw), fn ($slug) => $slug !== ''));
                $departmentSlugs = array_merge($departmentSlugs, $slugs);
            } elseif (!in_array($field, ['page', 'per_page'], true)) {
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

        return $query->visibleToAuthenticatedUser()->latest()->paginate($perPage);
    }

    public static function findRepositoryUsersBy(array $conditions): Collection
    {
        return self::where($conditions)->visibleToAuthenticatedUser()->get();
    }

    public static function findFirstRepositoryUserBy(array $conditions): ?self
    {
        return self::where($conditions)->visibleToAuthenticatedUser()->first();
    }

    public static function countRepositoryUsersBy(array $conditions): int
    {
        return self::where($conditions)->visibleToAuthenticatedUser()->count();
    }

    public static function updateRepositoryLastLogin(int $userId): ?self
    {
        $user = self::findRepositoryUser($userId);
        if (!$user) {
            return null;
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        return $user;
    }

    public static function getRepositoryStatistics(): array
    {
        $baseQuery = self::query()->visibleToAuthenticatedUser();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
            'verified' => (clone $baseQuery)->whereNotNull('email_verified_at')->count(),
            'unverified' => (clone $baseQuery)->whereNull('email_verified_at')->count(),
        ];
    }

    public static function findActivePlannerAdminsByOrganisation(int $organisationId): Collection
    {
        return self::findActiveUsersByRoleAndOrganisation('planner-admin', $organisationId);
    }

    /**
     * Active ops-admin users that belong to the given organisation.
     *
     * @return Collection<int, self>
     */
    public static function findActiveOpsAdminsByOrganisation(int $organisationId): Collection
    {
        return self::findActiveUsersByRoleAndOrganisation('ops-admin', $organisationId);
    }

    /**
     * @return Collection<int, self>
     */
    protected static function findActiveUsersByRoleAndOrganisation(string $roleSlug, int $organisationId): Collection
    {
        return self::query()
            ->active()
            ->whereHas('roles', function ($query) use ($roleSlug) {
                $query->where('slug', $roleSlug);
            })
            ->where(function ($query) use ($organisationId) {
                $query->where('users.organisation_id', $organisationId)
                    ->orWhereHas('organisations', function ($organisationQuery) use ($organisationId) {
                        $organisationQuery->where('organisations.id', $organisationId);
                    });
            })
            ->get();
    }

    public static function syncRepositoryDepartments(int $userId, array $departmentIds): void
    {
        $user = self::find($userId);
        if ($user) {
            $user->syncValidDepartments($departmentIds);
        }
    }

    public static function syncRepositoryRoles(int $userId, array $roleIds): void
    {
        if (!self::find($userId)) {
            return;
        }

        DB::table('role_user')->where('user_id', $userId)->delete();

        $insertData = [];
        foreach ($roleIds as $roleId) {
            $insertData[] = [
                'role_id' => $roleId,
                'user_id' => $userId,
                'user_type' => self::class,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($insertData !== []) {
            DB::table('role_user')->insert($insertData);
        }
    }

    public static function syncRepositoryParents(int $userId, array $parentIds): void
    {
        if (!self::find($userId)) {
            return;
        }

        DB::table('user_parent')->where('user_id', $userId)->delete();

        $insertData = [];
        foreach ($parentIds as $parentId) {
            $parentId = (int) $parentId;

            if ($parentId <= 0 || $parentId === $userId || !self::find($parentId)) {
                continue;
            }

            $insertData[] = [
                'user_id' => $userId,
                'is_parent' => $parentId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($insertData !== []) {
            DB::table('user_parent')->insert($insertData);
        }
    }

    public static function syncRepositoryOrganisations(int $userId, array $organisationIds): void
    {
        if (!self::find($userId)) {
            return;
        }

        DB::table('organisation_user')->where('user_id', $userId)->delete();

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

        if ($insertData !== []) {
            DB::table('organisation_user')->insert($insertData);
        }
    }

    public static function syncRepositoryZones(int $userId, array $zoneIds): void
    {
        if (!self::find($userId)) {
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

        if ($insertData !== []) {
            DB::table('zone_user')->insert($insertData);
        }
    }

    /**
     * Get the organisation that the user belongs to
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Organisations assigned to this user.
     */
    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class, 'organisation_user', 'user_id', 'organisation_id')
            ->withTimestamps();
    }

    /**
     * Leads currently assigned to this user.
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'current_assign_user');
    }

    /**
     * Briefs currently assigned to this user.
     */
    public function assignedBriefs(): HasMany
    {
        return $this->hasMany(Brief::class, 'assign_user_id');
    }

    /**
     * Departments assigned to this user.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_department', 'user_id', 'department_id')
            ->withTimestamps();
    }

    /**
     * Sync valid departments for this user.
     *
     * @param array $departmentIds
     * @return void
     */
    public function syncValidDepartments(array $departmentIds): void
    {
        $uniqueIds = array_unique(array_filter(array_map('intval', $departmentIds), fn($id) => $id > 0));

        $validIds = [];
        if (!empty($uniqueIds)) {
            $validIds = Department::whereIn('id', $uniqueIds)->pluck('id')->toArray();
        }

        $this->departments()->sync($validIds);
    }

    /**
     * Organisation-user pivot records.
     */
    public function organisationUsers(): HasMany
    {
        return $this->hasMany(OrganisationUser::class);
    }

    /**
     * Get the zone that the user belongs to
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Zones assigned to this user.
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'zone_user', 'user_id', 'zone_id')
            ->withTimestamps();
    }

    /**
     * Get user roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('user_type')
            ->withTimestamps();
    }

    /**
     * Get user permissions
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user', 'user_id', 'permission_id')
            ->withPivot('user_type')
            ->withTimestamps();
    }

    /**
     * Get user profile
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get user parent relationships
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(UserParent::class, 'user_id');
    }

    /**
     * Get the parent users of this user
     */
    public function parents()
    {
        return $this->belongsToMany(
            User::class,
            'user_parent',
            'user_id',
            'is_parent'
        )->withTimestamps();
    }

    /**
     * Get the child users (users that have this user as parent)
     */
    public function children()
    {
        return $this->belongsToMany(
            User::class,
            'user_parent',
            'is_parent',
            'user_id'
        )->withTimestamps();
    }

    public function isChildOf(User $potentialParent): bool
    {
        return $this->hasMany(UserParent::class, 'user_id')
            ->where('is_parent', $potentialParent->id)
            ->exists();
    }

    /**
     * Get the login logs for the user.
     */
    public function loginLogs(): HasMany
    {
        // Sort by login_time in descending order (latest first)
        return $this->hasMany(LoginLog::class)->latest('login_time');
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoleCount = $this->roles()->whereIn('name', $roles)->count();
        return $userRoleCount === count($roles);
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        // Check direct permissions
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check role permissions
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Check direct permissions
        if ($this->permissions()->whereIn('name', $permissions)->exists()) {
            return true;
        }

        // Check role permissions
        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('name', $permissions);
        })->exists();
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissionCount = $this->permissions()->whereIn('name', $permissions)->count();
        
        if ($userPermissionCount === count($permissions)) {
            return true;
        }

        // Check role permissions
        $rolePermissionCount = $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('name', $permissions);
        })->count();

        return ($userPermissionCount + $rolePermissionCount) >= count($permissions);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'user_type' => static::class,
            ]
        ]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    /**
     * Give permission to user
     */
    public function givePermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'user_type' => static::class,
            ]
        ]);
    }

    /**
     * Remove permission from user
     */
    public function removePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Get all permissions for user (including role permissions)
     */
    public function getAllPermissions()
    {
        $directPermissions = $this->permissions;
        $rolePermissions = $this->roles()->with('permissions')->get()->pluck('permissions')->flatten();
        
        return $directPermissions->merge($rolePermissions)->unique('id');
    }
}