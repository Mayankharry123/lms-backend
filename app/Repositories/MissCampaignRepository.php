<?php

/**
 * MissCampaign Repository
 * -----------------------------------------
 * Handles data access operations for miss campaigns, providing CRUD methods and query logic.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-09
 */

namespace App\Repositories;

use App\Contracts\Repositories\MissCampaignRepositoryInterface;
use App\Models\MissCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MissCampaignRepository implements MissCampaignRepositoryInterface
{
    /**
     * Eager-load relations; skip soft-deleted related records where applicable.
     *
     * @return array<string|callable>
     */
    protected function eagerLoadRelations(): array
    {
        $notTrashed = static fn (string $table) => static fn ($query) => $query->whereNull($table . '.deleted_at');

        return [
            'brand' => $notTrashed('brands'),
            'leadSource' => $notTrashed('lead_source'),
            'leadSubSource' => $notTrashed('lead_sub_source'),
            'mediaType' => $notTrashed('media_types'),
            'industry' => $notTrashed('industries'),
            'country',
            'state',
            'city',
            'assignBy' => $notTrashed('users'),
            'assignTo' => $notTrashed('users'),
            'lead.organisation',
        ];
    }

    /**
     * @var MissCampaign
     */
    protected MissCampaign $model;

    /**
     * MissCampaignRepository constructor.
     *
     * @param MissCampaign $missCampaign
     */
    public function __construct(MissCampaign $missCampaign)
    {
        $this->model = $missCampaign;
    }

    /**
     * Get all miss campaigns with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllMissCampaigns(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        return $this->buildListingQuery($searchTerm)
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Count miss campaigns matching search term for export.
     *
     * @param string|null $searchTerm
     * @return int
     */
    public function countMissCampaignsForExport(?string $searchTerm = null): int
    {
        return (int) $this->buildListingQuery($searchTerm)->count();
    }

    /**
     * Iterate over miss campaign export records in chunks.
     *
     * @param string|null $searchTerm
     * @param int $chunkSize
     * @param callable $callback
     * @return void
     */
    public function eachMissCampaignExportChunk(?string $searchTerm, int $chunkSize, callable $callback): void
    {
        $this->buildListingQuery($searchTerm)
            ->reorder('miss_campaigns.id')
            ->chunkById($chunkSize, function ($campaigns) use ($callback) {
                $callback($campaigns);
            }, 'miss_campaigns.id', 'id');
    }

    /**
     * Base query for listing and export (scoped to user, active only).
     */
    protected function buildListingQuery(?string $searchTerm = null)
    {
        $query = $this->model
            ->with($this->eagerLoadRelations())
            ->accessibleToUser(Auth::user())
            ->notDeleted()
            ->where('status', '1');

        $this->applyOrganisationValidation($query, Auth::user());

        if ($searchTerm !== null && $searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('brand', function ($brandQuery) use ($searchTerm) {
                      $brandQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('leadSource', function ($sourceQuery) use ($searchTerm) {
                      $sourceQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('leadSubSource', function ($subSourceQuery) use ($searchTerm) {
                      $subSourceQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('mediaType', function ($mediaQuery) use ($searchTerm) {
                      $mediaQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('industry', function ($industryQuery) use ($searchTerm) {
                      $industryQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('country', function ($countryQuery) use ($searchTerm) {
                      $countryQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('state', function ($stateQuery) use ($searchTerm) {
                      $stateQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('city', function ($cityQuery) use ($searchTerm) {
                      $cityQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get a list of miss campaigns (id, name).
     *
     * @return Collection|null
     */
    public function getMissCampaignList(): ?Collection
    {
        $query = $this->model
            ->select('id', 'name')
            ->notDeleted()
            ->accessibleToUser(Auth::user());

        $this->applyOrganisationValidation($query, Auth::user());

        return $query->where('status', '1')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Get a miss campaign by ID.
     *
     * @param int $id
     * @return MissCampaign|null
     */
    public function getMissCampaignById(int $id): ?MissCampaign
    {
        $query = $this->model
            ->with($this->eagerLoadRelations())
            ->accessibleToUser(Auth::user())
            ->notDeleted()
            ->where('status', '1');

        $this->applyOrganisationValidation($query, Auth::user());

        return $query->find($id);
    }

    /**
     * Get a miss campaign by slug.
     *
     * @param string $slug
     * @return MissCampaign|null
     */
    public function getMissCampaignBySlug(string $slug): ?MissCampaign
    {
        $query = $this->model
            ->with($this->eagerLoadRelations())
            ->accessibleToUser(Auth::user())
            ->notDeleted()
            ->where('status', '1')
            ->where('slug', $slug);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query->first();
    }

    /**
     * Create a new miss campaign.
     *
     * @param array $data
     * @return MissCampaign
     */
    public function createMissCampaign(array $data): MissCampaign
    {
        return $this->model->create($data);
    }

    /**
     * Update a miss campaign by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateMissCampaign(int $id, array $data): bool
    {
        $query = $this->model->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());
        $item = $query->findOrFail($id);

        return $item->update($data);
    }

    /**
     * Delete a miss campaign by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteMissCampaign(int $id): bool
    {
        $query = $this->model->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());
        $item = $query->findOrFail($id);

        return $item->delete();
    }

    /**
     * Update status of a miss campaign.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $query = $this->model->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());
        $item = $query->findOrFail($id);

        return $item->update(['status' => $status]);
    }

    /**
     * Assign a user to a miss campaign.
     *
     * @param int $id
     * @param int $userId
     * @param int $assignBy
     * @return bool
     */
    public function assignUser(int $id, int $userId, int $assignBy): bool
    {
        $query = $this->model->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());
        $item = $query->findOrFail($id);

        return $item->update([
            'assign_to' => $userId,
            'assign_by' => $assignBy,
        ]);
    }

    /**
     * Ensure miss campaigns belong to the user's organisation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user
     */
    protected function applyOrganisationValidation($query, $user): void
    {
        if (!$user) {
            return;
        }

        $userOrgIds = \App\Support\UserAccessScope::getAccessibleOrganisationIds($user);

        if (empty($userOrgIds)) {
            // If user has no organisation, they see NO campaigns
            $query->whereRaw('0 = 1');
        } else {
            // If user has an organisation, they MUST only see campaigns from that organisation
            $orgUserIds = \App\Support\DashboardFilters::getOrganisationUserIds($userOrgIds);

            if (empty($orgUserIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($orgUserIds) {
                    $q->whereIn('assign_to', $orgUserIds)
                      ->orWhereIn('assign_by', $orgUserIds);
                });

                // Explicitly exclude campaigns created (assigned by) the user's ancestors (e.g. parents)
                $ancestorIds = \App\Support\UserAccessScope::getAncestorIds($user);
                if (!empty($ancestorIds)) {
                    $query->where(function ($q) use ($ancestorIds, $user) {
                        $descendantIds = \App\Support\UserAccessScope::getStrictDescendantIds($user);
                        $q->whereNotIn('assign_by', $ancestorIds)
                          ->orWhereIn('assign_to', $descendantIds);
                    });
                }
            }
        }
    }
}
