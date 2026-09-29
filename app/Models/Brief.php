<?php

namespace App\Models;

use App\Support\UserAccessScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Brief extends Model
{
    use HasFactory, SoftDeletes;

    // Campaign Mode Constants
    const MODE_PROGRAMMATIC = 'programmatic';
    const MODE_NON_PROGRAMMATIC = 'non_programmatic';

    // Campaign Type Constants (Media Types)
    const TYPE_PROGRAMMATIC_DOOH = 'dooh';
    const TYPE_PROGRAMMATIC_CTV = 'ctv';
    const TYPE_NON_PROGRAMMATIC_DOOH = 'dooh';
    const TYPE_NON_PROGRAMMATIC_OOH = 'ooh';

    // Fields to track for history
    const TRACKED_FIELDS = ['assign_user_id', 'brief_status_id', 'submission_date', 'comment'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'briefs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'product_name',
        'contact_person_id',
        'brand_id',
        'agency_id',
        'mode_of_campaign',
        'media_type',
        'budget',
        'assign_user_id',
        'created_by',
        'brief_status_id',
        'priority_id',
        'comment',
        'attachment',
        'submission_date',
        'status',
        'campaign_start_date',
        'campaign_end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submission_date' => 'datetime',
        'campaign_start_date' => 'date',
        'campaign_end_date' => 'date',
        'budget' => 'decimal:2',
        'attachment' => 'string',
        'campaign_duration' => 'integer',
    ];

    /**
     * The attributes that should be appended to JSON.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'latest_planner_id',
    ];

    /**
     * The "booted" method of the model.
     * Register model event listeners.
     */
    protected static function booted()
    {
        static::creating(function ($brief) {
            $brief->calculateCampaignDuration();
        });

        static::created(function ($brief) {
            if ($brief->assign_user_id !== null || filled($brief->comment)) {
                $brief->createAssignHistory([
                    'assign_user_id' => ['new' => $brief->assign_user_id],
                ]);
            }
        });

        static::updating(function ($brief) {
            $brief->calculateCampaignDuration();
        });

        static::updated(function ($brief) {
            $brief->saveHistoryIfFieldsChanged();
        });
    }

    /**
     * Get brief count statistics for a priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public static function getBriefCountStatsForPriority(int $priorityId, array $filters): array
    {
        $totalBriefQuery = self::accessibleToUser()
            ->whereNull('deleted_at')
            ->whereRaw('briefs.status != 15');
        \App\Support\DashboardFilters::applyBriefDashboardFilters($totalBriefQuery, $filters, 'briefs');

        $priorityBriefQuery = self::accessibleToUser()
            ->whereNull('deleted_at')
            ->whereRaw('briefs.status != 15')
            ->where('priority_id', $priorityId);
        \App\Support\DashboardFilters::applyBriefDashboardFilters($priorityBriefQuery, $filters, 'briefs');

        return [
            'total_briefs' => $totalBriefQuery->count(),
            'priority_brief_count' => $priorityBriefQuery->count(),
        ];
    }

    /**
     * Scope to filter briefs accessible to the given user.
     * Super Admin sees all. Others see only briefs where they are creator or assigned user.
     *
     * @param Builder $query
     * @param mixed $user
     * @return Builder
     */
    public function scopeAccessibleToUser(Builder $query, $user = null): Builder
    {
        $user = $user ?? auth()->user();

        // If no user is authenticated, return empty query
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        // Super Admin with organisation assignment may view all records (dashboard uses org filters).
        if (UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['created_by', 'assign_user_id'],
            $this->getTable()
        );

        return $query;
    }

    // ===================================================================
    // RELATIONSHIPS
    // ===================================================================

    /**
     * Get the brief assign histories for this brief.
     */
    public function assignHistories()
    {
        return $this->hasMany(BriefAssignHistory::class, 'brief_id');
    }

    /**
     * Get the contact person (lead) associated with this brief.
     */
    public function contactPerson()
    {
        return $this->belongsTo(Lead::class, 'contact_person_id');
    }

    /**
     * Get the brand associated with this brief.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the agency associated with this brief.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    /**
     * Get the user assigned to this brief.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assign_user_id');
    }

    /**
     * Get the user who created this brief.
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the brief status associated with this brief.
     */
    public function briefStatus()
    {
        return $this->belongsTo(BriefStatus::class, 'brief_status_id');
    }

    /**
     * Get the priority associated with this brief.
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Get all notifications for this brief.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the latest planner id from planner history.
     *
     * @return int|null
     */
    public function getLatestPlannerIdAttribute(): ?int
    {
        return PlannerHistory::where('brief_id', $this->id)
            ->latest()
            ->value('planner_id');
    }

    // ===================================================================
    // HISTORY TRACKING METHODS
    // ===================================================================

    /**
     * Calculate campaign duration in days based on start and end dates.
     * Auto-calculates and sets the campaign_duration attribute.
     */
    private function calculateCampaignDuration(): void
    {
        if (!$this->campaign_start_date || !$this->campaign_end_date) {
            $this->campaign_duration = 0;
            return;
        }

        $startDate = Carbon::parse($this->campaign_start_date);
        $endDate = Carbon::parse($this->campaign_end_date);
        
        // Check for invalid date range (start after end)
        if ($startDate->greaterThan($endDate)) {
            $this->campaign_duration = 0;
            return;
        }
        
        // Calculate difference in days (inclusive of both start and end dates)
        $this->campaign_duration = $endDate->diffInDays($startDate) + 1;
    }

    /**
     * Check if any tracked fields have changed and save history if needed.
     */
    private function saveHistoryIfFieldsChanged(): void
    {
        $changeData = $this->getTrackedFieldChanges();
        
        if (empty($changeData)) {
            return;
        }

        try {
            $this->createAssignHistory($changeData);
        } catch (\Exception $e) {
            Log::error('Failed to create BriefAssignHistory', [
                'brief_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the changes for tracked fields.
     */
    private function getTrackedFieldChanges(): array
    {
        $changeData = [];

        foreach (self::TRACKED_FIELDS as $field) {
            if ($this->isDirty($field)) {
                $changeData[$field] = [
                    'old' => $this->getOriginal($field),
                    'new' => $this->getAttribute($field),
                ];
            }
        }

        return $changeData;
    }

    /**
     * Create a brief assign history record.
     */
    private function createAssignHistory(array $changeData): void
    {
        BriefAssignHistory::create([
            'uuid' => Str::uuid(),
            'brief_id' => $this->id,
            'assign_by_id' => $this->getCurrentUserId(),
            'assign_to_id' => $changeData['assign_user_id']['new'] ?? $this->assign_user_id ?? $this->getCurrentUserId(),
            'brief_status_id' => $changeData['brief_status_id']['new'] ?? $this->brief_status_id,
            'brief_status_time' => now(),
            'submission_date' => $changeData['submission_date']['new'] ?? $this->submission_date,
            'comment' => $this->comment,
            'status' => '2',
        ]);
    }

    /**
     * Get the current authenticated user ID.
     */
    private function getCurrentUserId(): ?int
    {
        return Auth::id() ?? $this->created_by;
    }
    // ===================================================================
    // STATIC HELPER METHODS
    // ===================================================================

    /**
     * Get valid campaign types for a given campaign mode.
     *
     * @param string $mode
     * @return array
     */
    public static function getCampaignTypesByMode(string $mode): array
    {
        return match ($mode) {
            self::MODE_PROGRAMMATIC => [self::TYPE_PROGRAMMATIC_DOOH, self::TYPE_PROGRAMMATIC_CTV],
            self::MODE_NON_PROGRAMMATIC => [self::TYPE_NON_PROGRAMMATIC_DOOH, self::TYPE_NON_PROGRAMMATIC_OOH],
            default => [],
        };
    }

    /**
     * Get all available campaign modes.
     *
     * @return array
     */
    public static function getCampaignModes(): array
    {
        return [
            self::MODE_PROGRAMMATIC,
            self::MODE_NON_PROGRAMMATIC,
        ];
    }

    /**
     * Get campaign mode display labels.
     *
     * @return array
     */
    public static function getCampaignModeLabels(): array
    {
        return [
            self::MODE_PROGRAMMATIC => 'Programmatic',
            self::MODE_NON_PROGRAMMATIC => 'Non-Programmatic',
        ];
    }

    /**
     * Get campaign type display labels.
     *
     * @return array
     */
    public static function getCampaignTypeLabels(): array
    {
        return [
            self::TYPE_PROGRAMMATIC_DOOH => 'DOOH',
            self::TYPE_PROGRAMMATIC_CTV => 'CTV',
            self::TYPE_NON_PROGRAMMATIC_DOOH => 'DOOH',
            self::TYPE_NON_PROGRAMMATIC_OOH => 'OOH',
        ];
    }
}
