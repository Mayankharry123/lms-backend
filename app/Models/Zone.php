<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Zone extends Model
{
    use SoftDeletes;
    protected $table = 'zones';
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];
    public $timestamps = true;

    // Scope for active records
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    public function getPaginated(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->newQuery();

        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        return $query->latest()->paginate($perPage)->appends(request()->query());
    }

    public function getActiveList(): Collection
    {
        return $this->newQuery()->active()->latest()->get();
    }

    public static function getOrganisationLeadCounts(array $organisationIds): Collection
    {
        $organisationUserIds = User::query()
            ->where(function ($query) use ($organisationIds) {
                $query->whereIn('organisation_id', $organisationIds)
                    ->orWhereHas('organisations', function ($organisationQuery) use ($organisationIds) {
                        $organisationQuery->whereIn('organisations.id', $organisationIds);
                    });
            })
            ->pluck('id');

        return self::query()
            ->leftJoin('users', function ($join) use ($organisationUserIds) {
                $join->on('users.zone_id', '=', 'zones.id')
                    ->whereNull('users.deleted_at')
                    ->whereIn('users.id', $organisationUserIds);
            })
            ->leftJoin('leads', function ($join) {
                $join->on('leads.current_assign_user', '=', 'users.id')
                    ->whereNull('leads.deleted_at');
            })
            ->select(
                'zones.id as zone_id',
                'zones.name as zone_name',
                DB::raw('COUNT(leads.id) as assigned_leads_count')
            )
            ->groupBy('zones.id', 'zones.name')
            ->orderBy('zones.name')
            ->get()
            ->map(function ($zone) {
                return [
                    'zone_id' => (int) $zone->zone_id,
                    'zone_name' => $zone->zone_name,
                    'assigned_leads_count' => (int) $zone->assigned_leads_count,
                ];
            })
            ->values();
    }

    /**
     * @param array<int, string> $names
     * @return Collection<int, static>
     */
    public function findByNames(array $names): Collection
    {
        $normalized = [];
        foreach ($names as $name) {
            $value = mb_strtolower(trim((string) $name));
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        $normalized = array_values($normalized);
        if ($normalized === []) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name']);
    }
}
