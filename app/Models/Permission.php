<?php
/**
 * Permission
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HandlesFileUploads;

class Permission extends BaseModel
{
    use HandlesFileUploads;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'url',
        'icon_file',
        'icon_text',
        'is_parent',   // will now store parent permission ID
        'status',
        'order',
        'uuid',
    ];

    protected $casts = [
        // correct timestamps
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Permission (belongsTo)
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'is_parent');
    }

    /**
     * Children Permissions (hasMany)
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'is_parent');
    }

    /**
     * Get the roles that have this permission.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'permission_id', 'role_id')->withTimestamps();
    }

    /**
     * Get the users that have this permission.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'permission_user', 'permission_id', 'user_id')
            ->withPivot('user_type')
            ->withTimestamps();
    }

    /**
     * Check if the permission is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === '1';
    }
}
