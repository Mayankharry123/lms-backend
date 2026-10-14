<?php
/**
 * Organisation
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Organisation Model
 * -----------------------------------------
 * Represents the organisations table and manages data interactions
 * using Laravel's Eloquent ORM. Handles mass assignment for
 * name, slug, and status attributes.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

class Organisation extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'status'];

    /**
     * Users assigned to this organisation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organisation_user', 'organisation_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Organisation-user pivot records.
     */
    public function organisationUsers()
    {
        return $this->hasMany(OrganisationUser::class);
    }

    /**
     * Get a formatted list of organisations by IDs.
     *
     * @param array $ids
     * @return \Illuminate\Support\Collection
     */
    public static function getListByIds(array $ids)
    {
        return self::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($organisation) => [
                'id' => $organisation->id,
                'name' => $organisation->name,
            ]);
    }
}
