<?php

namespace App\Contracts\Repositories;

use App\Models\PaymentModeType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentModeTypeRepositoryInterface
{
    /**
     * Get all active payment mode types.
     *
     * @param string|null $search
     * @return Collection
     */
    public function getActiveList(?string $search = null): Collection;

    /**
     * Get paginated active payment mode types.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    /**
     * Get active payment mode type by ID.
     *
     * @param int $id
     * @return PaymentModeType|null
     */
    public function getById(int $id): ?PaymentModeType;

    /**
     * Get active payment mode type by slug.
     *
     * @param string $slug
     * @return PaymentModeType|null
     */
    public function getBySlug(string $slug): ?PaymentModeType;
}
