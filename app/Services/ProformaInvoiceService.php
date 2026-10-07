<?php

namespace App\Services;

use App\Contracts\Repositories\ProformaInvoiceRepositoryInterface;
use App\Models\Brand;
use App\Models\ProformaInvoice;
use App\Support\AmountInWords;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProformaInvoiceService
{
    private const PI_SERIES = 'PI';

    protected ProformaInvoiceRepositoryInterface $proformaInvoiceRepository;

    public function __construct(ProformaInvoiceRepositoryInterface $proformaInvoiceRepository)
    {
        $this->proformaInvoiceRepository = $proformaInvoiceRepository;
    }

    /**
     * List proforma invoices.
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->proformaInvoiceRepository->paginate($perPage, $filters);
        } catch (Throwable $e) {
            Log::error('Error listing proforma invoices', ['filters' => $filters, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find one proforma invoice by ID.
     */
    public function find(int $id): ?ProformaInvoice
    {
        try {
            return $this->proformaInvoiceRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error finding proforma invoice', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a proforma invoice and its items.
     */
    public function create(array $payload, $uploadedFile = null, $user = null): ProformaInvoice
    {
        try {
            $brandId = (int) ($payload['brand_id'] ?? 0);
            $brand = Brand::find($brandId);

            if (!$brand) {
                throw new DomainException('Selected brand not found');
            }

            $brandName = trim((string) ($payload['brand_name'] ?? $brand->name));
            $gstNo = !empty($payload['gst_no'])
                ? trim((string) $payload['gst_no'])
                : (!empty($payload['gst_number']) ? trim((string) $payload['gst_number']) : $brand->gst_no);
            $address = !empty($payload['address'])
                ? trim((string) $payload['address'])
                : $brand->address;

            $sgstRate = (float) ($payload['sgst_rate'] ?? $payload['sgst'] ?? 0);
            $cgstRate = (float) ($payload['cgst_rate'] ?? $payload['cgst'] ?? 0);
            $igstRate = (float) ($payload['igst_rate'] ?? $payload['igst'] ?? 0);

            $items = $payload['orders'] ?? $payload['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                throw new DomainException('At least one order line item is required');
            }

            $calculated = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);

            $piNumber = !empty($payload['pi_number'])
                ? trim((string) $payload['pi_number'])
                : $this->nextPiNumber();

            $piPath = null;
            if ($uploadedFile instanceof UploadedFile) {
                $piPath = $this->storeUploadedFile($uploadedFile, $piNumber);
            } elseif (!empty($payload['pi_path'])) {
                $piPath = trim((string) $payload['pi_path']);
            } elseif (!empty($payload['file_path'])) {
                $piPath = trim((string) $payload['file_path']);
            }

            $headerData = [
                'pi_number' => $piNumber,
                'brand_id' => $brand->id,
                'brand_name' => $brandName,
                'gst_no' => $gstNo,
                'address' => $address,
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
                'pi_path' => $piPath,
                'created_by' => $user ? $user->id : auth()->id(),
                'status' => (string) ($payload['status'] ?? '1'),
            ];

            $invoice = $this->proformaInvoiceRepository->create($headerData);
            $this->proformaInvoiceRepository->createItems($invoice, $calculated['items']);

            return $invoice->fresh(['brand', 'creator', 'items']);
        } catch (Throwable $e) {
            Log::error('Error creating proforma invoice', ['payload' => $payload, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Update an existing proforma invoice.
     */
    public function update(int $id, array $payload, $uploadedFile = null, $user = null): ?ProformaInvoice
    {
        try {
            $invoice = $this->proformaInvoiceRepository->find($id);
            if (!$invoice) {
                return null;
            }

            $updateData = [];

            if (isset($payload['brand_id'])) {
                $brand = Brand::find((int) $payload['brand_id']);
                if ($brand) {
                    $updateData['brand_id'] = $brand->id;
                    $updateData['brand_name'] = trim((string) ($payload['brand_name'] ?? $brand->name));
                }
            } elseif (isset($payload['brand_name'])) {
                $updateData['brand_name'] = trim((string) $payload['brand_name']);
            }

            if (isset($payload['gst_no']) || isset($payload['gst_number'])) {
                $updateData['gst_no'] = trim((string) ($payload['gst_no'] ?? $payload['gst_number']));
            }

            if (isset($payload['address'])) {
                $updateData['address'] = trim((string) $payload['address']);
            }

            if (isset($payload['status'])) {
                $updateData['status'] = (string) $payload['status'];
            }

            if (isset($payload['pi_number'])) {
                $updateData['pi_number'] = trim((string) $payload['pi_number']);
            }

            // File handling
            if ($uploadedFile instanceof UploadedFile) {
                $currentNumber = $updateData['pi_number'] ?? $invoice->pi_number;
                $updateData['pi_path'] = $this->storeUploadedFile($uploadedFile, $currentNumber);
            } elseif (isset($payload['pi_path'])) {
                $updateData['pi_path'] = trim((string) $payload['pi_path']);
            } elseif (isset($payload['file_path'])) {
                $updateData['pi_path'] = trim((string) $payload['file_path']);
            }

            // Recalculate if items or taxes provided
            $items = $payload['orders'] ?? $payload['items'] ?? null;
            $hasItems = is_array($items) && !empty($items);

            $sgstRate = isset($payload['sgst_rate']) || isset($payload['sgst'])
                ? (float) ($payload['sgst_rate'] ?? $payload['sgst'])
                : (float) $invoice->sgst_rate;

            $cgstRate = isset($payload['cgst_rate']) || isset($payload['cgst'])
                ? (float) ($payload['cgst_rate'] ?? $payload['cgst'])
                : (float) $invoice->cgst_rate;

            $igstRate = isset($payload['igst_rate']) || isset($payload['igst'])
                ? (float) ($payload['igst_rate'] ?? $payload['igst'])
                : (float) $invoice->igst_rate;

            if ($hasItems) {
                $calculated = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);
                $updateData['subtotal'] = $calculated['subtotal'];
                $updateData['sgst_rate'] = $calculated['sgst_rate'];
                $updateData['sgst_amount'] = $calculated['sgst_amount'];
                $updateData['cgst_rate'] = $calculated['cgst_rate'];
                $updateData['cgst_amount'] = $calculated['cgst_amount'];
                $updateData['igst_rate'] = $calculated['igst_rate'];
                $updateData['igst_amount'] = $calculated['igst_amount'];
                $updateData['total_tax'] = $calculated['total_tax'];
                $updateData['total_amount'] = $calculated['total_amount'];
                $updateData['amount_in_words'] = $calculated['amount_in_words'];

                $this->proformaInvoiceRepository->syncItems($invoice, $calculated['items']);
            } elseif (isset($payload['sgst_rate']) || isset($payload['cgst_rate']) || isset($payload['igst_rate']) || isset($payload['sgst']) || isset($payload['cgst']) || isset($payload['igst'])) {
                $subtotal = (float) $invoice->subtotal;
                $sgstAmount = round($subtotal * ($sgstRate / 100), 2);
                $cgstAmount = round($subtotal * ($cgstRate / 100), 2);
                $igstAmount = round($subtotal * ($igstRate / 100), 2);
                $totalTax = round($sgstAmount + $cgstAmount + $igstAmount, 2);
                $totalAmount = round($subtotal + $totalTax, 2);

                $updateData['sgst_rate'] = $sgstRate;
                $updateData['sgst_amount'] = $sgstAmount;
                $updateData['cgst_rate'] = $cgstRate;
                $updateData['cgst_amount'] = $cgstAmount;
                $updateData['igst_rate'] = $igstRate;
                $updateData['igst_amount'] = $igstAmount;
                $updateData['total_tax'] = $totalTax;
                $updateData['total_amount'] = $totalAmount;
                $updateData['amount_in_words'] = AmountInWords::rupees($totalAmount);
            }

            return $this->proformaInvoiceRepository->update($id, $updateData);
        } catch (Throwable $e) {
            Log::error('Error updating proforma invoice', ['id' => $id, 'payload' => $payload, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Delete a proforma invoice.
     */
    public function delete(int $id): bool
    {
        try {
            return $this->proformaInvoiceRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting proforma invoice', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Upload or update PI file.
     */
    public function uploadFile(int $id, UploadedFile $file): ?ProformaInvoice
    {
        try {
            $invoice = $this->proformaInvoiceRepository->find($id);
            if (!$invoice) {
                return null;
            }

            $piPath = $this->storeUploadedFile($file, $invoice->pi_number);
            return $this->proformaInvoiceRepository->update($id, ['pi_path' => $piPath]);
        } catch (Throwable $e) {
            Log::error('Error uploading file for proforma invoice', ['id' => $id, 'exception' => $e]);
            throw $e;
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
            $slot = (float) ($item['slot'] ?? $item['qty'] ?? 1);
            $rate = (float) ($item['rate'] ?? 0);
            $amount = isset($item['amount']) && (float) $item['amount'] > 0
                ? (float) $item['amount']
                : round($slot * $rate, 2);

            $subtotal += $amount;

            $processedItems[] = [
                'order_name' => trim((string) ($item['order_name'] ?? $item['order'] ?? $item['description'] ?? 'Media Display')),
                'hsn_sac' => isset($item['hsn_sac']) ? trim((string) $item['hsn_sac']) : null,
                'city' => isset($item['city']) ? trim((string) $item['city']) : null,
                'slot' => $slot,
                'rate' => $rate,
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
    protected function storeUploadedFile(UploadedFile $file, string $piNumber): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '_', $piNumber);
        $filename = 'PI_' . $safeNumber . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        $folder = 'proforma-invoices';

        $file->storeAs('public/' . $folder, $filename, config('filesystems.default', 'local'));

        return 'public/' . $folder . '/' . $filename;
    }

    /**
     * Generate the next Proforma Invoice number.
     */
    public function nextPiNumber(): string
    {
        try {
            $suffix = $this->financialYearSuffix();
            $prefix = self::PI_SERIES . '/';
            $latest = $this->proformaInvoiceRepository->latestByNumberPrefix($prefix . '%/' . $suffix);
            $sequence = 1;

            if ($latest && preg_match('/\/(\d+)\//', (string) $latest->pi_number, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('%s/%04d/%s', self::PI_SERIES, $sequence, $suffix);
        } catch (Throwable $e) {
            Log::error('Error generating PI number', ['exception' => $e]);
            return sprintf('PI/%04d/%s', 1, $this->financialYearSuffix());
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
     * Resolve server file path.
     */
    public function resolveFilePath(string $storedPath): ?string
    {
        try {
            $storedPath = ltrim(str_replace('\\', '/', $storedPath), '/');
            $candidates = [
                storage_path('app/' . $storedPath),
                storage_path('app/public/' . $storedPath),
            ];

            if (str_starts_with($storedPath, 'public/')) {
                $candidates[] = storage_path('app/public/' . substr($storedPath, strlen('public/')));
            }

            $storageRoot = realpath(storage_path('app'));

            foreach ($candidates as $candidate) {
                if (!is_file($candidate)) {
                    continue;
                }

                $realFile = realpath($candidate);

                if ($storageRoot && $realFile && str_starts_with($realFile, $storageRoot)) {
                    return $realFile;
                }
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
