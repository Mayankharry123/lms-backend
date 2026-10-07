<?php

namespace App\Services;

use App\Contracts\Repositories\PaymentModeTypeRepositoryInterface;
use App\Models\PaymentModeType;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PaymentModeTypeService
{
    protected PaymentModeTypeRepositoryInterface $paymentModeTypeRepository;

    public function __construct(PaymentModeTypeRepositoryInterface $paymentModeTypeRepository)
    {
        $this->paymentModeTypeRepository = $paymentModeTypeRepository;
    }

    /**
     * Get list of active payment mode types.
     *
     * @param string|null $search
     * @return Collection
     * @throws DomainException
     */
    public function list(?string $search = null): Collection
    {
        try {
            return $this->paymentModeTypeRepository->getActiveList($search);
        } catch (QueryException $e) {
            Log::error('Database error fetching payment mode types', ['exception' => $e]);
            throw new DomainException('Database error while fetching payment mode types.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching payment mode types', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching payment mode types.');
        }
    }

    /**
     * Get paginated active payment mode types.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        try {
            return $this->paymentModeTypeRepository->getPaginated($perPage, $search);
        } catch (QueryException $e) {
            Log::error('Database error paginating payment mode types', ['exception' => $e]);
            throw new DomainException('Database error while paginating payment mode types.');
        } catch (Exception $e) {
            Log::error('Unexpected error paginating payment mode types', ['exception' => $e]);
            throw new DomainException('Unexpected error while paginating payment mode types.');
        }
    }

    /**
     * Find a single payment mode type by ID.
     *
     * @param int $id
     * @return PaymentModeType|null
     * @throws DomainException
     */
    public function find(int $id): ?PaymentModeType
    {
        try {
            return $this->paymentModeTypeRepository->getById($id);
        } catch (QueryException $e) {
            Log::error('Database error finding payment mode type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while finding payment mode type.');
        } catch (Exception $e) {
            Log::error('Unexpected error finding payment mode type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while finding payment mode type.');
        }
    }
}
