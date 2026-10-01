<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\UserAccessScope;

class BriefAssignHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brief_assign_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'brief_id',
        'assign_by_id',
        'assign_to_id',
        'brief_status_id',
        'brief_status_time',
        'submission_date',
        'comment',
        'reminder',
        'reminder_at',
        'reminder_before',
        'reminder_before_unit',
        'attachment',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => '2', // Inactive by default
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'brief_status_time' => 'datetime',
        'submission_date' => 'datetime',
        'reminder' => 'boolean',
        'reminder_at' => 'datetime',
        'reminder_before' => 'integer',
        'attachment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the brief that this history belongs to.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Get the user who assigned this brief.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by_id');
    }

    /**
     * Get the user to whom the brief was assigned.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to_id');
    }

    /**
     * Get the brief status.
     */
    public function briefStatus()
    {
        return $this->belongsTo(BriefStatus::class, 'brief_status_id');
    }

    /**
     * Scope to get only active records.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope to get only inactive records.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', '2');
    }

    /**
     * Check if the record is active.
     */
    public function isActive(): bool
    {
        return $this->status === '1';
    }

    /**
     * Check if the record is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === '2';
    }

    public function getAllBriefAssignHistories(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);

        if ($searchTerm) {
            $query->where('comment', 'like', "%{$searchTerm}%")
                ->orWhereHas('brief', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
        }

        return $query->paginate($perPage);
    }

    public function getBriefAssignHistoryById(int $id): ?self
    {
        return $this->newQuery()->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus'])->find($id);
    }

    public function getBriefAssignHistoryByUuid(string $uuid): ?self
    {
        return $this->newQuery()->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function getBriefAssignHistoriesByBriefId(
        int $briefId,
        int $perPage = 10,
        ?User $user = null
    ): LengthAwarePaginator {
        $query = $this->newQuery()->where('brief_id', $briefId)
            ->with(['assignedBy', 'assignedTo', 'briefStatus']);

        $user = $user ?? auth()->user();
        if ($user && !UserAccessScope::isSuperAdmin($user)) {
            $descendantIds = UserAccessScope::getStrictDescendantIds($user);
            $query->where(function ($q) use ($descendantIds) {
                $q->whereIn('assign_to_id', $descendantIds)
                    ->orWhereIn('assign_by_id', $descendantIds);
            });
        }

        return $query->paginate($perPage);
    }

    public function getBriefAssignHistoryChat(int $briefId): Collection
    {
        return $this->newQuery()
            ->with(['assignedBy:id,name'])
            ->where('brief_id', $briefId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function createBriefActivity(int $briefId, int $currentUserId, array $data): self
    {
        return DB::transaction(function () use ($briefId, $currentUserId, $data) {
            $brief = Brief::query()->findOrFail($briefId);
            Brief::query()->whereKey($brief->id)->update(['comment' => $data['comment']]);

            $reminderEnabled = (bool) ($data['reminder'] ?? false);

            return $this->newQuery()->create([
                'uuid' => (string) Str::uuid(),
                'brief_id' => $brief->id,
                'assign_by_id' => $currentUserId,
                'assign_to_id' => $brief->assign_user_id ?? $currentUserId,
                'brief_status_id' => $brief->brief_status_id,
                'brief_status_time' => now(),
                'submission_date' => $brief->submission_date,
                'comment' => $data['comment'],
                'reminder' => $reminderEnabled,
                'reminder_at' => $reminderEnabled ? ($data['reminder_at'] ?? null) : null,
                'reminder_before' => $reminderEnabled ? ($data['reminder_before'] ?? null) : null,
                'reminder_before_unit' => $reminderEnabled ? ($data['reminder_before_unit'] ?? null) : null,
                'status' => '2',
            ])->load('assignedBy:id,name');
        });
    }

    public function getBriefAssignHistoriesByAssignBy(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->newQuery()->where('assign_by_id', $userId)
            ->with(['brief', 'assignedTo', 'briefStatus'])
            ->paginate($perPage);
    }

    public function getBriefAssignHistoriesByAssignTo(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->newQuery()->where('assign_to_id', $userId)
            ->with(['brief', 'assignedBy', 'briefStatus'])
            ->paginate($perPage);
    }

    public function getAssignmentHistoriesForBrief(int $briefId): Collection
    {
        return $this->newQuery()
            ->with(['assignedTo:id,name', 'brief:id,assign_user_id,created_at'])
            ->where('brief_id', $briefId)
            ->where('status', '!=', '15')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /** @param array<int, int> $briefIds */
    public function getAssignmentHistoriesForBriefs(array $briefIds): Collection
    {
        if ($briefIds === []) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->with(['brief:id,assign_user_id,created_at'])
            ->whereIn('brief_id', $briefIds)
            ->where('status', '!=', '15')
            ->orderBy('brief_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function getAssignmentHistoriesForUserCycles(int $userId): Collection
    {
        $briefIds = $this->newQuery()
            ->where('assign_to_id', $userId)
            ->where('status', '!=', '15')
            ->whereHas('brief', function ($query) {
                $query->where('status', '!=', '15');
            })
            ->distinct()
            ->pluck('brief_id');

        if ($briefIds->isEmpty()) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->with(['assignedTo:id,name', 'brief:id,name,assign_user_id,created_at'])
            ->whereIn('brief_id', $briefIds)
            ->where('status', '!=', '15')
            ->orderBy('brief_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}

