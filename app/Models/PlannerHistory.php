<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PlannerHistory extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'planner_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'planner_id',
        'brief_id',
        'created_by',
        'planner_status_id',
        'submitted_plan',
        'backup_plan',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_plan' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: A planner history belongs to a planner.
     */
    public function planner()
    {
        return $this->belongsTo(Planner::class);
    }

    /**
     * Relationship: A planner history has a planner status.
     */
    public function plannerStatus()
    {
        return $this->belongsTo(PlannerStatus::class, 'planner_status_id');
    }

    /**
     * Relationship: A planner history belongs to a brief.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class);
    }

    /**
     * Relationship: A planner history was created by a user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getAllPlannerHistories(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = self::with('planner', 'brief', 'creator', 'plannerStatus');

        foreach (['planner_id', 'brief_id', 'status', 'created_by'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public static function getPlannerHistories(int $plannerId, int $perPage = 10): LengthAwarePaginator
    {
        return self::with('planner', 'brief', 'creator', 'plannerStatus')
            ->where('planner_id', $plannerId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public static function getBriefPlannerHistories(int $briefId, int $perPage = 10): LengthAwarePaginator
    {
        return self::with('planner', 'brief', 'creator', 'plannerStatus')
            ->where('brief_id', $briefId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public static function getByStatus(string $status, int $perPage = 10): LengthAwarePaginator
    {
        return self::with('planner', 'brief', 'creator', 'plannerStatus')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public static function getRecentHistories(int $limit = 10): Collection
    {
        return self::with('planner', 'brief', 'creator', 'plannerStatus')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function createHistory(array $data): self
    {
        return self::create($data);
    }

    public static function getSubmittedPlanHistoriesForBrief(int $briefId): Collection
    {
        return self::query()
            ->where('brief_id', $briefId)
            ->where('status', '!=', '15')
            ->whereNotNull('submitted_plan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /** @param array<int, int> $briefIds */
    public static function getSubmittedPlanHistoriesForBriefs(array $briefIds): Collection
    {
        if ($briefIds === []) {
            return new Collection();
        }

        return self::query()
            ->whereIn('brief_id', $briefIds)
            ->where('status', '!=', '15')
            ->whereNotNull('submitted_plan')
            ->orderBy('brief_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
