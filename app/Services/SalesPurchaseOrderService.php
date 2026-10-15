<?php

/**
 * SalesPurchaseOrderService
 * -----------------------------------------
 * Service implementing business workflows for Sales Purchase Orders.
 * Handles calculation of items, taxes, totals, and PO file uploads.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Services;

use App\Contracts\Repositories\SalesPurchaseOrderRepositoryInterface;
use App\Models\Brand;
use App\Models\Brief;
use App\Models\Planner;
use App\Models\SalesPurchaseOrder;
use App\Support\AmountInWords;
use App\Traits\HandlesFileUploads;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SalesPurchaseOrderService
{
    use HandlesFileUploads;

    /**
     * @var SalesPurchaseOrderRepositoryInterface
     */
    protected SalesPurchaseOrderRepositoryInterface $salesPurchaseOrderRepository;

    /**
     * SalesPurchaseOrderService constructor.
     *
     * @param SalesPurchaseOrderRepositoryInterface $salesPurchaseOrderRepository
     */
    public function __construct(SalesPurchaseOrderRepositoryInterface $salesPurchaseOrderRepository)
    {
        $this->salesPurchaseOrderRepository = $salesPurchaseOrderRepository;
    }

    /**
     * List sales purchase orders with filters and pagination.
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->salesPurchaseOrderRepository->paginate($perPage, $filters);
        } catch (Throwable $e) {
            Log::error('Error listing sales purchase orders', ['filters' => $filters, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find a single sales purchase order by ID.
     */
    public function find(int $id): ?SalesPurchaseOrder
    {
        try {
            return $this->salesPurchaseOrderRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error finding sales purchase order', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a sales purchase order, its line items, and upload PO file if provided.
     */
    public function create(array $payload, $uploadedFile = null, $user = null): SalesPurchaseOrder
    {
        DB::beginTransaction();

        try {
            $brandId = !empty($payload['brand_id']) ? (int) $payload['brand_id'] : null;
            $briefId = !empty($payload['brief_id']) ? (int) $payload['brief_id'] : null;
            $plannerId = !empty($payload['planner_id']) ? (int) $payload['planner_id'] : null;

            $brand = $brandId ? Brand::find($brandId) : null;
            $brief = $briefId ? Brief::find($briefId) : null;

            $clientName = trim((string) (
                $payload['client_name']
                ?? $payload['company_name']
                ?? ($brand ? $brand->name : ($brief ? $brief->name : ''))
            ));

            $companyName = trim((string) ($payload['company_name'] ?? $clientName));
            $primaryEmail = !empty($payload['primary_email']) ? trim((string) $payload['primary_email']) : null;
            $gstNumber = !empty($payload['gst_number']) ? trim((string) $payload['gst_number']) : ($payload['gst_no'] ?? ($brand?->gst_no));
            $panNumber = !empty($payload['pan_number']) ? trim((string) $payload['pan_number']) : null;
            $address = !empty($payload['address']) ? trim((string) $payload['address']) : ($brand?->address);
            $city = !empty($payload['city']) ? trim((string) $payload['city']) : null;
            $state = !empty($payload['state']) ? trim((string) $payload['state']) : null;
            $country = !empty($payload['country']) ? trim((string) $payload['country']) : null;
            $pincode = !empty($payload['pincode']) ? trim((string) $payload['pincode']) : null;

            $poDate = !empty($payload['po_date'])
                ? Carbon::parse($payload['po_date'])->format('Y-m-d')
                : Carbon::now('Asia/Kolkata')->format('Y-m-d');

            $campaign = !empty($payload['campaign']) ? trim((string) $payload['campaign']) : null;
            $period = !empty($payload['period']) ? trim((string) $payload['period']) : null;

            $sgstRate = (float) ($payload['sgst_rate'] ?? $payload['sgst'] ?? 0);
            $cgstRate = (float) ($payload['cgst_rate'] ?? $payload['cgst'] ?? 0);
            $igstRate = (float) ($payload['igst_rate'] ?? $payload['igst'] ?? 0);

            $items = $payload['orders'] ?? $payload['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                // If direct form creation without sub-items, allow empty items with default amount or compute
                $items = [];
            }

            $calculated = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);

            // Allow manual override for totals if items were empty but totals were directly supplied
            if (empty($items) && isset($payload['subtotal'])) {
                $subtotal = (float) $payload['subtotal'];
                $taxAmount = (float) ($payload['tax_amount'] ?? 0);
                $totalAmount = (float) ($payload['total_amount'] ?? ($subtotal + $taxAmount));

                $calculated['subtotal'] = $subtotal;
                $calculated['tax_amount'] = $taxAmount;
                $calculated['total_amount'] = $totalAmount;
                $calculated['amount_in_words'] = !empty($payload['amount_in_words'])
                    ? trim((string) $payload['amount_in_words'])
                    : AmountInWords::rupees($totalAmount);
            }

            $poNumber = !empty($payload['po_number'])
                ? trim((string) $payload['po_number'])
                : SalesPurchaseOrder::generatePoNumber(Carbon::parse($poDate));

            $poFile = null;
            if ($uploadedFile instanceof UploadedFile) {
                $poFile = $this->storeUploadedFile($uploadedFile, $poNumber);
            } elseif (!empty($payload['po_file'])) {
                $poFile = trim((string) $payload['po_file']);
            } elseif (!empty($payload['file_path'])) {
                $poFile = trim((string) $payload['file_path']);
            }

            $createdBy = !empty($payload['created_by'])
                ? (int) $payload['created_by']
                : ($user ? $user->id : null);

            $assignTo = !empty($payload['assign_to'])
                ? (int) $payload['assign_to']
                : null;

            $financeStatusId = !empty($payload['finance_status_id'])
                ? (int) $payload['finance_status_id']
                : null;

            $headerData = [
                'uuid' => (string) Str::uuid(),
                'po_number' => $poNumber,
                'po_date' => $poDate,
                'brief_id' => $briefId,
                'planner_id' => $plannerId,
                'brand_id' => $brandId,
                'assign_to' => $assignTo,
                'created_by' => $createdBy,
                'finance_status_id' => $financeStatusId,
                'company_name' => $companyName,
                'client_name' => $clientName,
                'primary_email' => $primaryEmail,
                'gst_number' => $gstNumber,
                'pan_number' => $panNumber,
                'account_holder_name' => $payload['account_holder_name'] ?? null,
                'account_number' => $payload['account_number'] ?? null,
                'ifsc_code' => $payload['ifsc_code'] ?? null,
                'bank_name' => $payload['bank_name'] ?? null,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'pincode' => $pincode,
                'campaign' => $campaign,
                'period' => $period,
                'subtotal' => $calculated['subtotal'],
                'sgst_rate' => $calculated['sgst_rate'],
                'sgst_amount' => $calculated['sgst_amount'],
                'cgst_rate' => $calculated['cgst_rate'],
                'cgst_amount' => $calculated['cgst_amount'],
                'igst_rate' => $calculated['igst_rate'],
                'igst_amount' => $calculated['igst_amount'],
                'tax_amount' => $calculated['tax_amount'],
                'total_amount' => $calculated['total_amount'],
                'amount_in_words' => $calculated['amount_in_words'],
                'po_file' => $poFile,
                'status' => (string) ($payload['status'] ?? '1'),
            ];

            $order = $this->salesPurchaseOrderRepository->create($headerData);

            if (!empty($calculated['items'])) {
                $this->salesPurchaseOrderRepository->createItems($order, $calculated['items']);
            }

            DB::commit();

            return $this->find($order->id);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error creating sales purchase order', ['payload' => $payload, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Update an existing sales purchase order and sync line items.
     */
    public function update(int $id, array $payload, $uploadedFile = null): ?SalesPurchaseOrder
    {
        DB::beginTransaction();

        try {
            $order = $this->salesPurchaseOrderRepository->find($id);
            if (!$order) {
                return null;
            }

            $updateData = [];

            foreach ([
                'po_date', 'brief_id', 'planner_id', 'brand_id', 'assign_to',
                'finance_status_id', 'company_name', 'client_name', 'primary_email',
                'gst_number', 'pan_number', 'account_holder_name', 'account_number',
                'ifsc_code', 'bank_name', 'address', 'city', 'state', 'country',
                'pincode', 'campaign', 'period', 'status'
            ] as $field) {
                if (array_key_exists($field, $payload)) {
                    $updateData[$field] = $payload[$field];
                }
            }

            if (!empty($payload['po_date'])) {
                $updateData['po_date'] = Carbon::parse($payload['po_date'])->format('Y-m-d');
            }

            if ($uploadedFile instanceof UploadedFile) {
                $updateData['po_file'] = $this->storeUploadedFile($uploadedFile, $order->po_number);
            } elseif (!empty($payload['po_file'])) {
                $updateData['po_file'] = trim((string) $payload['po_file']);
            }

            $items = $payload['orders'] ?? $payload['items'] ?? null;
            if (is_array($items)) {
                $sgstRate = (float) ($payload['sgst_rate'] ?? $payload['sgst'] ?? $order->sgst_rate);
                $cgstRate = (float) ($payload['cgst_rate'] ?? $payload['cgst'] ?? $order->cgst_rate);
                $igstRate = (float) ($payload['igst_rate'] ?? $payload['igst'] ?? $order->igst_rate);

                $calculated = $this->calculate($items, $sgstRate, $cgstRate, $igstRate);

                $updateData['subtotal'] = $calculated['subtotal'];
                $updateData['sgst_rate'] = $calculated['sgst_rate'];
                $updateData['sgst_amount'] = $calculated['sgst_amount'];
                $updateData['cgst_rate'] = $calculated['cgst_rate'];
                $updateData['cgst_amount'] = $calculated['cgst_amount'];
                $updateData['igst_rate'] = $calculated['igst_rate'];
                $updateData['igst_amount'] = $calculated['igst_amount'];
                $updateData['tax_amount'] = $calculated['tax_amount'];
                $updateData['total_amount'] = $calculated['total_amount'];
                $updateData['amount_in_words'] = $calculated['amount_in_words'];

                $this->salesPurchaseOrderRepository->syncItems($order, $calculated['items']);
            } elseif (isset($payload['subtotal'])) {
                $subtotal = (float) $payload['subtotal'];
                $taxAmount = (float) ($payload['tax_amount'] ?? 0);
                $totalAmount = (float) ($payload['total_amount'] ?? ($subtotal + $taxAmount));

                $updateData['subtotal'] = $subtotal;
                $updateData['tax_amount'] = $taxAmount;
                $updateData['total_amount'] = $totalAmount;
                if (!empty($payload['amount_in_words'])) {
                    $updateData['amount_in_words'] = trim((string) $payload['amount_in_words']);
                }
            }

            if (!empty($updateData)) {
                $this->salesPurchaseOrderRepository->update($id, $updateData);
            }

            DB::commit();

            return $this->find($id);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error updating sales purchase order', ['id' => $id, 'payload' => $payload, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Delete a sales purchase order.
     */
    public function delete(int $id): bool
    {
        try {
            return $this->salesPurchaseOrderRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting sales purchase order', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Upload or update the PO document file.
     */
    public function uploadPoFile(int $id, UploadedFile $file): ?SalesPurchaseOrder
    {
        try {
            $order = $this->salesPurchaseOrderRepository->find($id);
            if (!$order) {
                return null;
            }

            $path = $this->storeUploadedFile($file, $order->po_number);
            return $this->salesPurchaseOrderRepository->update($id, ['po_file' => $path]);
        } catch (Throwable $e) {
            Log::error('Error uploading PO file', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Calculate line items, subtotal, taxes, and total amount.
     */
    public function calculate(array $items, float $sgstRate = 0, float $cgstRate = 0, float $igstRate = 0): array
    {
        $processedItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $slot = (float) ($item['slot'] ?? $item['qty'] ?? 1);
            $rate = (float) ($item['rate'] ?? 0);
            $amount = isset($item['amount']) && (float) $item['amount'] > 0
                ? (float) $item['amount']
                : round($slot * $rate, 2);

            $subtotal += $amount;

            $processedItems[] = [
                'order_name' => trim((string) ($item['order_name'] ?? $item['order'] ?? $item['description'] ?? 'Line Item')),
                'description' => isset($item['description']) ? trim((string) $item['description']) : null,
                'hsn_sac' => isset($item['hsn_sac']) ? trim((string) $item['hsn_sac']) : (isset($item['hsn_code']) ? trim((string) $item['hsn_code']) : '998361'),
                'city' => isset($item['city']) ? trim((string) $item['city']) : null,
                'slot' => $slot,
                'qty' => $slot,
                'rate' => $rate,
                'amount' => $amount,
                'status' => '1',
            ];
        }

        $sgstAmount = round($subtotal * ($sgstRate / 100), 2);
        $cgstAmount = round($subtotal * ($cgstRate / 100), 2);
        $igstAmount = round($subtotal * ($igstRate / 100), 2);
        $taxAmount = round($sgstAmount + $cgstAmount + $igstAmount, 2);
        $totalAmount = round($subtotal + $taxAmount, 2);

        return [
            'items' => $processedItems,
            'subtotal' => $subtotal,
            'sgst_rate' => $sgstRate,
            'sgst_amount' => $sgstAmount,
            'cgst_rate' => $cgstRate,
            'cgst_amount' => $cgstAmount,
            'igst_rate' => $igstRate,
            'igst_amount' => $igstAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_in_words' => AmountInWords::rupees($totalAmount),
        ];
    }

    /**
     * Store uploaded file into storage and return relative path.
     */
    protected function storeUploadedFile(UploadedFile $file, string $poNumber): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '_', $poNumber);
        $filename = 'PO_' . $safeNumber . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        $folder = 'sales-purchase-orders';

        $file->storeAs('public/' . $folder, $filename, config('filesystems.default', 'local'));

        return 'public/' . $folder . '/' . $filename;
    }
}
