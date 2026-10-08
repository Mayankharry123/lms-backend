<?php

namespace App\Services;

use App\Contracts\Repositories\PaymentModeTypeRepositoryInterface;
use App\Contracts\Repositories\VoucherRepositoryInterface;
use App\Contracts\Repositories\VoucherTypeRepositoryInterface;
use App\Models\Voucher;
use App\Support\AmountInWords;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class VoucherService
{
    public const VOUCHER_SERIES = 'VCH';

    protected VoucherRepositoryInterface $voucherRepository;
    protected VoucherTypeRepositoryInterface $voucherTypeRepository;
    protected PaymentModeTypeRepositoryInterface $paymentModeTypeRepository;

    public function __construct(
        VoucherRepositoryInterface $voucherRepository,
        VoucherTypeRepositoryInterface $voucherTypeRepository,
        PaymentModeTypeRepositoryInterface $paymentModeTypeRepository
    ) {
        $this->voucherRepository = $voucherRepository;
        $this->voucherTypeRepository = $voucherTypeRepository;
        $this->paymentModeTypeRepository = $paymentModeTypeRepository;
    }

    /**
     * Get paginated list of vouchers with filtering.
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->voucherRepository->paginate($perPage, $filters);
        } catch (Throwable $e) {
            Log::error('Error listing vouchers', ['exception' => $e, 'filters' => $filters]);
            throw new DomainException('Failed to retrieve vouchers list.');
        }
    }

    /**
     * Find a voucher by ID with relationships.
     *
     * @param int $id
     * @return Voucher|null
     * @throws DomainException
     */
    public function find(int $id): ?Voucher
    {
        try {
            return $this->voucherRepository->findById($id);
        } catch (Throwable $e) {
            Log::error('Error fetching voucher by id', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Failed to retrieve voucher details.');
        }
    }

    /**
     * Create a new voucher with line items and optional file attachment.
     *
     * @param array $payload
     * @param UploadedFile|null $uploadedFile
     * @param mixed $user
     * @return Voucher
     * @throws DomainException
     */
    public function create(array $payload, ?UploadedFile $uploadedFile = null, $user = null): Voucher
    {
        try {
            $voucherTypeId = (int) ($payload['voucher_type_id'] ?? 0);
            $voucherType = $this->voucherTypeRepository->getById($voucherTypeId);
            if (!$voucherType) {
                throw new DomainException('Selected voucher type is invalid or does not exist.');
            }

            $personName = trim((string) ($payload['person_name'] ?? ''));
            if ($personName === '') {
                throw new DomainException('Person name is required.');
            }

            $items = !empty($payload['orders']) ? $payload['orders'] : (!empty($payload['items']) ? $payload['items'] : []);
            if (!is_array($items) || count($items) === 0) {
                throw new DomainException('At least one order item is required for the voucher.');
            }

            $sgstRate = (float) ($payload['sgst_rate'] ?? $payload['sgst'] ?? 0);
            $cgstRate = (float) ($payload['cgst_rate'] ?? $payload['cgst'] ?? 0);
            $igstRate = (float) ($payload['igst_rate'] ?? $payload['igst'] ?? 0);

            $calculated = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);

            $voucherNumber = !empty($payload['voucher_number'])
                ? trim((string) $payload['voucher_number'])
                : $this->nextVoucherNumber();

            $filePath = null;
            if ($uploadedFile instanceof UploadedFile) {
                $filePath = $this->storeUploadedFile($uploadedFile, $voucherNumber);
            } elseif (!empty($payload['file_path'])) {
                $filePath = trim((string) $payload['file_path']);
            }

            $headerData = [
                'voucher_number' => $voucherNumber,
                'voucher_type_id' => $voucherType->id,
                'person_name' => $personName,
                'file_path' => $filePath,
                'subtotal' => $calculated['subtotal'],
                'sgst_rate' => $calculated['sgst_rate'],
                'sgst_amount' => $calculated['sgst_amount'],
                'cgst_rate' => $calculated['cgst_rate'],
                'cgst_amount' => $calculated['cgst_amount'],
                'igst_rate' => $calculated['igst_rate'],
                'igst_amount' => $calculated['igst_amount'],
                'total_tax' => $calculated['total_tax'],
                'total_amount' => $calculated['total_amount'],
                'amount_in_words' => $calculated['amount_in_words'],
                'month' => !empty($payload['month']) ? (string) $payload['month'] : date('M-y'),
                'notes' => !empty($payload['notes']) ? (string) $payload['notes'] : null,
                'created_by' => $user ? $user->id : auth()->id(),
                'status' => (string) ($payload['status'] ?? '1'),
            ];

            $voucher = $this->voucherRepository->create($headerData);
            $this->voucherRepository->createItems($voucher, $calculated['items']);

            return $voucher->fresh(['voucherType', 'creator', 'items.paymentModeType']);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error creating voucher', ['exception' => $e, 'payload' => $payload]);
            throw new DomainException('Failed to create voucher: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing voucher and sync items if provided.
     *
     * @param int $id
     * @param array $payload
     * @param UploadedFile|null $uploadedFile
     * @param mixed $user
     * @return Voucher
     * @throws DomainException
     */
    public function update(int $id, array $payload, ?UploadedFile $uploadedFile = null, $user = null): Voucher
    {
        try {
            $voucher = $this->voucherRepository->findById($id);
            if (!$voucher) {
                throw new DomainException('Voucher not found.');
            }

            $updateData = [];

            if (isset($payload['voucher_type_id'])) {
                $type = $this->voucherTypeRepository->getById((int) $payload['voucher_type_id']);
                if (!$type) {
                    throw new DomainException('Invalid voucher type.');
                }
                $updateData['voucher_type_id'] = $type->id;
            }

            if (isset($payload['person_name'])) {
                $updateData['person_name'] = trim((string) $payload['person_name']);
            }

            if (isset($payload['voucher_number'])) {
                $updateData['voucher_number'] = trim((string) $payload['voucher_number']);
            }

            if (isset($payload['month'])) {
                $updateData['month'] = (string) $payload['month'];
            }

            if (isset($payload['notes'])) {
                $updateData['notes'] = (string) $payload['notes'];
            }

            if (isset($payload['status'])) {
                $updateData['status'] = (string) $payload['status'];
            }

            // Handle file upload
            if ($uploadedFile instanceof UploadedFile) {
                $vNum = $updateData['voucher_number'] ?? $voucher->voucher_number;
                $updateData['file_path'] = $this->storeUploadedFile($uploadedFile, $vNum);
            } elseif (isset($payload['file_path'])) {
                $updateData['file_path'] = trim((string) $payload['file_path']);
            }

            // Check if recalculation needed
            $hasItems = isset($payload['orders']) || isset($payload['items']);
            $hasTaxRates = isset($payload['sgst_rate']) || isset($payload['cgst_rate']) || isset($payload['igst_rate']);

            if ($hasItems || $hasTaxRates) {
                $items = !empty($payload['orders']) ? $payload['orders'] : (!empty($payload['items']) ? $payload['items'] : []);
                if (empty($items)) {
                    // Use existing items if only tax rates updated
                    $items = $voucher->items->map(function ($it) {
                        return [
                            'date' => $it->date,
                            'particular' => $it->particular,
                            'purpose' => $it->purpose,
                            'mode' => $it->mode,
                            'payment_mode_type_id' => $it->payment_mode_type_id,
                            'amount' => $it->amount,
                        ];
                    })->toArray();
                }

                $sgstRate = (float) ($payload['sgst_rate'] ?? $voucher->sgst_rate);
                $cgstRate = (float) ($payload['cgst_rate'] ?? $voucher->cgst_rate);
                $igstRate = (float) ($payload['igst_rate'] ?? $voucher->igst_rate);

                $calc = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);

                $updateData['subtotal'] = $calc['subtotal'];
                $updateData['sgst_rate'] = $calc['sgst_rate'];
                $updateData['sgst_amount'] = $calc['sgst_amount'];
                $updateData['cgst_rate'] = $calc['cgst_rate'];
                $updateData['cgst_amount'] = $calc['cgst_amount'];
                $updateData['igst_rate'] = $calc['igst_rate'];
                $updateData['igst_amount'] = $calc['igst_amount'];
                $updateData['total_tax'] = $calc['total_tax'];
                $updateData['total_amount'] = $calc['total_amount'];
                $updateData['amount_in_words'] = $calc['amount_in_words'];

                if ($hasItems) {
                    $this->voucherRepository->syncItems($voucher, $calc['items']);
                }
            }

            $updated = $this->voucherRepository->update($id, $updateData);
            return $updated ?: $voucher->fresh(['voucherType', 'creator', 'items.paymentModeType']);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error updating voucher', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Failed to update voucher: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete a voucher by ID.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function delete(int $id): bool
    {
        try {
            $voucher = $this->voucherRepository->findById($id);
            if (!$voucher) {
                throw new DomainException('Voucher not found.');
            }

            return $this->voucherRepository->delete($id);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error deleting voucher', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Failed to delete voucher.');
        }
    }

    /**
     * Upload supporting document for an existing voucher.
     *
     * @param int $id
     * @param UploadedFile $file
     * @return Voucher
     * @throws DomainException
     */
    public function uploadFile(int $id, UploadedFile $file): Voucher
    {
        try {
            $voucher = $this->voucherRepository->findById($id);
            if (!$voucher) {
                throw new DomainException('Voucher not found.');
            }

            $filePath = $this->storeUploadedFile($file, $voucher->voucher_number);
            $this->voucherRepository->update($id, ['file_path' => $filePath]);

            return $voucher->fresh(['voucherType', 'creator', 'items']);
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error uploading voucher file', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Failed to upload voucher file.');
        }
    }

    /**
     * Calculate line items and taxes.
     */
    public function calculate(array $items, float $sgstRate = 0, float $cgstRate = 0, float $igstRate = 0): array
    {
        $subtotal = 0;
        $processedItems = [];

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $subtotal += $amount;

            $paymentModeTypeId = !empty($item['payment_mode_type_id']) ? (int) $item['payment_mode_type_id'] : null;
            $mode = null;

            if ($paymentModeTypeId) {
                $pm = $this->paymentModeTypeRepository->getById($paymentModeTypeId);
                if ($pm) {
                    $mode = $pm->name;
                }
            } elseif (!empty($item['mode'])) {
                $rawMode = trim((string) $item['mode']);
                $pm = $this->paymentModeTypeRepository->getBySlug(Str::slug($rawMode));
                if ($pm) {
                    $paymentModeTypeId = $pm->id;
                    $mode = $pm->name;
                } else {
                    $mode = $rawMode;
                }
            }

            $processedItems[] = [
                'date' => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : null,
                'particular' => trim((string) ($item['particular'] ?? $item['order'] ?? $item['description'] ?? '')),
                'purpose' => isset($item['purpose']) ? trim((string) $item['purpose']) : null,
                'mode' => $mode,
                'payment_mode_type_id' => $paymentModeTypeId,
                'amount' => $amount,
                'status' => '1',
            ];
        }

        $sgstAmount = round($subtotal * ($sgstRate / 100), 2);
        $cgstAmount = round($subtotal * ($cgstRate / 100), 2);
        $igstAmount = round($subtotal * ($igstRate / 100), 2);
        $totalTax = round($sgstAmount + $cgstAmount + $igstAmount, 2);
        $totalAmount = round($subtotal + $totalTax, 2);

        return [
            'items' => $processedItems,
            'subtotal' => $subtotal,
            'sgst_rate' => $sgstRate,
            'sgst_amount' => $sgstAmount,
            'cgst_rate' => $cgstRate,
            'cgst_amount' => $cgstAmount,
            'igst_rate' => $igstRate,
            'igst_amount' => $igstAmount,
            'total_tax' => $totalTax,
            'total_amount' => $totalAmount,
            'amount_in_words' => AmountInWords::rupees($totalAmount),
        ];
    }

    /**
     * Store uploaded file into storage and return relative path.
     */
    protected function storeUploadedFile(UploadedFile $file, string $voucherNumber): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '_', $voucherNumber);
        $filename = 'VCH_' . $safeNumber . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        $folder = 'vouchers';

        $file->storeAs('public/' . $folder, $filename, config('filesystems.default', 'local'));

        return 'public/' . $folder . '/' . $filename;
    }

    /**
     * Generate the next voucher number matching VCH series (e.g. VCH/26-27/0001).
     */
    public function nextVoucherNumber(): string
    {
        try {
            $suffix = $this->financialYearSuffix();
            $prefix = self::VOUCHER_SERIES . '/' . $suffix . '/';
            $latest = $this->voucherRepository->getLatestByPrefix($prefix . '%');
            $sequence = 1;

            if ($latest && preg_match('/\/(\d+)$/', (string) $latest->voucher_number, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('%s%04d', $prefix, $sequence);
        } catch (Throwable $e) {
            Log::error('Error generating voucher number', ['exception' => $e]);
            return sprintf('VCH/%s/%04d', $this->financialYearSuffix(), 1);
        }
    }

    /**
     * Indian financial year suffix, e.g., 26-27.
     */
    protected function financialYearSuffix(): string
    {
        $today = Carbon::now('Asia/Kolkata');
        $year = (int) $today->format('Y');
        $start = ((int) $today->format('n') < 4) ? $year - 1 : $year;

        return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
    }

    /**
     * Resolve full disk path of uploaded file for download.
     *
     * @param string|Voucher|null $storedPath
     * @return string|null
     */
    public function resolveFilePath($storedPath): ?string
    {
        if ($storedPath instanceof Voucher) {
            $storedPath = $storedPath->file_path;
        }

        if (empty($storedPath) || !is_string($storedPath)) {
            return null;
        }

        $storedPath = ltrim(str_replace('\\', '/', $storedPath), '/');
        $candidates = [
            storage_path('app/' . $storedPath),
            storage_path('app/public/' . $storedPath),
        ];

        if (str_starts_with($storedPath, 'public/')) {
            $candidates[] = storage_path('app/' . $storedPath);
            $candidates[] = storage_path('app/public/' . substr($storedPath, strlen('public/')));
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }
}
