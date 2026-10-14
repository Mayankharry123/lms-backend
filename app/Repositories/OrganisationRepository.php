<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrganisationRepositoryInterface;
use App\Models\Organisation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Organisation Repository
 * -----------------------------------------
 * Implements OrganisationRepositoryInterface to handle
 * data access operations for organisations using Eloquent ORM.
 * Supports pagination and search functionality following
 * repository pattern and clean architecture principles.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

class OrganisationRepository implements OrganisationRepositoryInterface
{
    /**
     * @var Organisation
     */
    protected Organisation $model;

    /**
     * Create a new OrganisationRepository instance.
     *
     * @param Organisation $organisation
     */
    public function __construct(Organisation $organisation)
    {
        $this->model = $organisation;
    }

    /**
     * Fetch paginated list of organisations.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllOrganisations(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($searchTerm !== null && $searchTerm !== '') {
            $query->where('name', 'LIKE', '%' . $searchTerm . '%');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Fetch a single organisation by ID.
     *
     * @param int $id
     * @return Organisation|null
     */
    public function getOrganisationById(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a basic list of organisations by IDs.
     *
     * @param array $ids
     * @return \Illuminate\Support\Collection
     */
    public function getOrganisationsListByIds(array $ids)
    {
        return $this->model->getListByIds($ids);
    }
}