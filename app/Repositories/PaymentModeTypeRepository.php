<?php

namespace App\Repositories;

use App\Contracts\Repositories\PaymentModeTypeRepositoryInterface;
use App\Models\PaymentModeType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentModeTypeRepository implements PaymentModeTypeRepositoryInterface
{
    protected PaymentModeType $model;

    public function __construct(PaymentModeType $model)
    {
        $this->model = $model;
    }

    /**
     * Get all active payment mode types.
     */
    public function getActiveList(?string $search = null): Collection
    {
        return $this->model->getActiveList($search);
    }

    /**
     * Get paginated active payment mode types.
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->model->paginateActive($perPage, $search);
    }

    /**
     * Get active payment mode type by ID.
     */
    public function getById(int $id): ?PaymentModeType
    {
        return $this->model->findActiveById($id);
    }

    /**
     * Get active payment mode type by slug.
     */
    public function getBySlug(string $slug): ?PaymentModeType
    {
        return $this->model->findActiveBySlug($slug);
    }
}
