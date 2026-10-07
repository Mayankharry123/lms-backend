<?php

namespace App\Services;

use App\Contracts\Repositories\VoucherTypeRepositoryInterface;
use App\Models\VoucherType;
use App\Support\ExpenseVoucherTemplateBuilder;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VoucherTypeService
{
    protected VoucherTypeRepositoryInterface $voucherTypeRepository;
    protected ExpenseVoucherTemplateBuilder $templateBuilder;

    public function __construct(
        VoucherTypeRepositoryInterface $voucherTypeRepository,
        ExpenseVoucherTemplateBuilder $templateBuilder
    ) {
        $this->voucherTypeRepository = $voucherTypeRepository;
        $this->templateBuilder = $templateBuilder;
    }

    /**
     * Get list of active voucher types.
     *
     * @param string|null $search
     * @return Collection
     * @throws DomainException
     */
    public function list(?string $search = null): Collection
    {
        try {
            return $this->voucherTypeRepository->getActiveList($search);
        } catch (QueryException $e) {
            Log::error('Database error fetching voucher types', ['exception' => $e]);
            throw new DomainException('Database error while fetching voucher types.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching voucher types', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching voucher types.');
        }
    }

    /**
     * Get paginated active voucher types.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        try {
            return $this->voucherTypeRepository->getPaginated($perPage, $search);
        } catch (QueryException $e) {
            Log::error('Database error paginating voucher types', ['exception' => $e]);
            throw new DomainException('Database error while paginating voucher types.');
        } catch (Exception $e) {
            Log::error('Unexpected error paginating voucher types', ['exception' => $e]);
            throw new DomainException('Unexpected error while paginating voucher types.');
        }
    }

    /**
     * Find a single voucher type by ID.
     *
     * @param int $id
     * @return VoucherType|null
     * @throws DomainException
     */
    public function find(int $id): ?VoucherType
    {
        try {
            return $this->voucherTypeRepository->getById($id);
        } catch (QueryException $e) {
            Log::error('Database error finding voucher type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while finding voucher type.');
        } catch (Exception $e) {
            Log::error('Unexpected error finding voucher type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while finding voucher type.');
        }
    }

    /**
     * Generate sample Excel spreadsheet for the specified voucher type.
     *
     * @param int $id
     * @param mixed $user
     * @return array{id: int, name: string, slug: string, filename: string, content: string, mime: string, relative_path: string, voucher_type: VoucherType}
     * @throws DomainException
     */
    public function generateSampleExcel(int $id, $user = null): array
    {
        try {
            $voucherType = $this->voucherTypeRepository->getById($id);
            if (!$voucherType) {
                throw new DomainException('Voucher type not found.');
            }

            // Delegate spreadsheet layout, styling, and rendering to dedicated builder
            $content = $this->templateBuilder->render($voucherType, $user);

            $slug = Str::slug($voucherType->name);
            $filename = "{$slug}-sample-format.xlsx";

            // Save to public storage directory for direct URL access via /storage/sample-formats/...
            $relativeDir = 'sample-formats';
            $fullDir = storage_path('app/public/' . $relativeDir);
            if (!file_exists($fullDir)) {
                @mkdir($fullDir, 0755, true);
            }
            $storageFilePath = $fullDir . DIRECTORY_SEPARATOR . $filename;
            @file_put_contents($storageFilePath, $content);

            return [
                'id' => $voucherType->id,
                'name' => $voucherType->name,
                'slug' => $voucherType->slug,
                'filename' => $filename,
                'content' => $content,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'relative_path' => 'storage/' . $relativeDir . '/' . $filename,
                'voucher_type' => $voucherType,
            ];
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error generating voucher type sample excel', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Failed to generate sample Excel for voucher type.');
        }
    }
}
