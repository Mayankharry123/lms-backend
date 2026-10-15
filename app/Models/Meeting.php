<?php
/**
 * Meeting
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class Meeting extends Model
{
    use SoftDeletes;

    protected $table = 'meetings';

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'lead_id',
        'attendees_id',
        'type',
        'location',
        'agenda',
        'link',
        'meeting_start_date',
        'meeting_end_date',
        'status',
        'google_event',
    ];

    protected $casts = [
        'meeting_start_date' => 'datetime',
        'meeting_end_date' => 'datetime',
        'attendees_id' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship with Lead model
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the route key for model binding
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get Google Event URL from JSON data
     */
    public function getGoogleEventUrl()
    {
        if (!$this->google_event) {
            return null;
        }

        try {
            $googleEvent = is_string($this->google_event) 
                ? json_decode($this->google_event, true) 
                : $this->google_event;
            
            return $googleEvent['html_link'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array{all: Collection<int, self>, meeting: self|null}
     */
    public static function getLeadHistoryContext(int $leadId): array
    {
        $allMeetings = self::where('lead_id', $leadId)->get();
        $meeting = self::where('lead_id', $leadId)
            ->where('status', '1')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$meeting) {
            $meeting = self::where('lead_id', $leadId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return ['all' => $allMeetings, 'meeting' => $meeting];
    }

    /**
     * Scope a query to only include active (non-completed) meetings whose end date has passed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiredActive($query)
    {
        return $query->where('status', '!=', '15')
            ->whereNotNull('meeting_end_date')
            ->where('meeting_end_date', '<=', Carbon::now());
    }

    /**
     * Fetch all expired active meetings.
     *
     * @return Collection<int, self>
     */
    public static function getExpiredActiveMeetings(): Collection
    {
        return self::expiredActive()->get();
    }

    /**
     * Mark a meeting as completed (status = 15).
     *
     * @param int $id
     * @return bool
     */
    public static function markMeetingCompleted(int $id): bool
    {
        return (bool) self::where('id', $id)->update(['status' => '15']);
    }
}
