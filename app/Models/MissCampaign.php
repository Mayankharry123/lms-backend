<?php

/**
 * MissCampaign Model
 * -----------------------------------------
 * Represents a miss campaign entity with relationships to brands, sources, and file handling capabilities.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Models;

use App\Support\UserAccessScope;
use App\Traits\HandlesFileUploads;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MissCampaign
 *
 * @package App\Models
 */
class MissCampaign extends BaseModel
{
    use HandlesFileUploads;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'miss_campaigns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'assign_by',
        'assign_to',
        'brand_id',
        'lead_source_id',
        'lead_sub_source_id',
        'image_path',
        'media_type_id',
        'industry_id',
        'country_id',
        'state_id',
        'city_id',
        'leads_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'assign_by' => 'integer',
        'assign_to' => 'integer',
        'brand_id' => 'integer',
        'lead_source_id' => 'integer',
        'lead_sub_source_id' => 'integer',
        'media_type_id' => 'integer',
        'industry_id' => 'integer',
        'country_id' => 'integer',
        'state_id' => 'integer',
        'city_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the brand that owns the campaign.
     *
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the lead source associated with the campaign.
     *
     * @return BelongsTo
     */
    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /**
     * Get the lead sub source associated with the campaign.
     *
     * @return BelongsTo
     */
    public function leadSubSource(): BelongsTo
    {
        return $this->belongsTo(LeadSubSource::class, 'lead_sub_source_id');
    }

    /**
     * Get the media type associated with the campaign.
     *
     * @return BelongsTo
     */
    public function mediaType(): BelongsTo
    {
        return $this->belongsTo(MediaType::class, 'media_type_id');
    }

    /**
     * Get the industry associated with the campaign.
     *
     * @return BelongsTo
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * Get the country associated with the campaign.
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the state associated with the campaign.
     *
     * @return BelongsTo
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Get the city associated with the campaign.
     *
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Get the user who assigned the campaign.
     *
     * @return BelongsTo
     */
    public function assignBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    /**
     * Get the user to whom the campaign is assigned.
     *
     * @return BelongsTo
     */
    public function assignTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Linked lead created from this pre-lead / miss campaign.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'leads_id');
    }

    /**
     * Scope to filter miss campaigns accessible to the given user.
     * Super Admin can view all campaigns.
     * Others can see campaigns where they are the assign_by or assign_to.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleToUser(\Illuminate\Database\Eloquent\Builder $query, $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $currentUser = $user ?: auth()->user();

        if (!$currentUser) {
            return $query->whereRaw('0 = 1');
        }

        if (UserAccessScope::hasGlobalRecordAccess($currentUser)) {
            return $query;
        }

        $visibleUserIds = UserAccessScope::getVisibleUserIds($currentUser);

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($visibleUserIds) {
            $q->whereIn('assign_by', $visibleUserIds)
              ->orWhereIn('assign_to', $visibleUserIds);
        });
    }

}