<?php

/**
 * SalesPurchaseOrderController
 * -----------------------------------------
 * Controller orchestrating HTTP requests for Sales Purchase Orders.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Http\Controllers;

use App\Http\Resources\SalesPurchaseOrderResource;
use App\Services\ResponseService;
use App\Services\SalesPurchaseOrderService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SalesPurchaseOrderController extends Controller
{
    use ValidatesRequests;

    /**
     * @var SalesPurchaseOrderService
     */
    protected SalesPurchaseOrderService $salesPurchaseOrderService;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * SalesPurchaseOrderController constructor.
     *
     * @param SalesPurchaseOrderService $salesPurchaseOrderService
     * @param ResponseService $responseService
     */
    public function __construct(
        SalesPurchaseOrderService $salesPurchaseOrderService,
        ResponseService $responseService
    ) {
        $this->salesPurchaseOrderService = $salesPurchaseOrderService;
        $this->responseService = $responseService;
    }

    /**
     * List sales purchase orders with filters.
     *
     * GET /api/v1/sales-purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'brand_id',
                'brief_id',
                'planner_id',
                'assign_to',
                'created_by',
                'finance_status_id',
                'status',
                'from_date',
                'to_date',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $orders = $this->salesPurchaseOrderService->list($perPage, $filters);

            return $this->responseService->paginated(
                SalesPurchaseOrderResource::collection($orders),
                'Sales purchase orders retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show one sales purchase order by ID.
     *
     * GET /api/v1/sales-purchase-orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->salesPurchaseOrderService->find($id);

            if (!$order) {
                return $this->responseService->notFound('Sales purchase order not found');
            }

            return $this->responseService->success(
                new SalesPurchaseOrderResource($order),
                'Sales purchase order retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Create a new sales purchase order.
     *
     * POST /api/v1/sales-purchase-orders
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->normalizeArrayInputs($request);

            $this->validate($request, [
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'created_by' => 'nullable|integer|exists:users,id',
                'finance_status_id' => 'nullable|integer|exists:finance_statuses,id',
                'client_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'primary_email' => 'nullable|email|max:255',
                'gst_number' => 'nullable|string|max:50',
                'gst_no' => 'nullable|string|max:50',
                'pan_number' => 'nullable|string|max:50',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'ifsc_code' => 'nullable|string|max:20',
                'bank_name' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:20',
                'campaign' => 'nullable|string|max:255',
                'period' => 'nullable|string|max:100',
                'po_date' => 'nullable|date',
                'po_number' => 'nullable|string|max:100|unique:sales_purchase_orders,po_number',
                'subtotal' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'amount_in_words' => 'nullable|string|max:500',
                'orders' => 'nullable|array',
                'items' => 'nullable|array',
                'orders.*.order_name' => 'nullable|string|max:500',
                'orders.*.description' => 'nullable|string|max:500',
                'orders.*.hsn_sac' => 'nullable|string|max:50',
                'orders.*.city' => 'nullable|string|max:255',
                'orders.*.slot' => 'nullable|numeric|min:0',
                'orders.*.qty' => 'nullable|numeric|min:0',
                'orders.*.rate' => 'nullable|numeric|min:0',
                'orders.*.amount' => 'nullable|numeric|min:0',
                'items.*.order_name' => 'nullable|string|max:500',
                'items.*.description' => 'nullable|string|max:500',
                'items.*.hsn_sac' => 'nullable|string|max:50',
                'items.*.city' => 'nullable|string|max:255',
                'items.*.slot' => 'nullable|numeric|min:0',
                'items.*.qty' => 'nullable|numeric|min:0',
                'items.*.rate' => 'nullable|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'po_file' => 'nullable',
                'file' => 'nullable',
                'status' => 'nullable|in:1,2,15',
            ]);

            $uploadedFile = $request->file('po_file') ?: $request->file('file');
            $user = $request->user();

            $order = $this->salesPurchaseOrderService->create(
                $request->all(),
                $uploadedFile,
                $user
            );

            return $this->responseService->success(
                new SalesPurchaseOrderResource($order),
                'Sales purchase order created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update an existing sales purchase order.
     *
     * PUT/POST /api/v1/sales-purchase-orders/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->normalizeArrayInputs($request);

            $this->validate($request, [
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'finance_status_id' => 'nullable|integer|exists:finance_statuses,id',
                'client_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'primary_email' => 'nullable|email|max:255',
                'gst_number' => 'nullable|string|max:50',
                'gst_no' => 'nullable|string|max:50',
                'pan_number' => 'nullable|string|max:50',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'ifsc_code' => 'nullable|string|max:20',
                'bank_name' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:20',
                'campaign' => 'nullable|string|max:255',
                'period' => 'nullable|string|max:100',
                'po_date' => 'nullable|date',
                'subtotal' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'amount_in_words' => 'nullable|string|max:500',
                'orders' => 'nullable|array',
                'items' => 'nullable|array',
                'po_file' => 'nullable',
                'file' => 'nullable',
                'status' => 'nullable|in:1,2,15',
            ]);

            $uploadedFile = $request->file('po_file') ?: $request->file('file');

            $order = $this->salesPurchaseOrderService->update(
                $id,
                $request->all(),
                $uploadedFile
            );

            if (!$order) {
                return $this->responseService->notFound('Sales purchase order not found');
            }

            return $this->responseService->success(
                new SalesPurchaseOrderResource($order),
                'Sales purchase order updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete a sales purchase order.
     *
     * DELETE /api/v1/sales-purchase-orders/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->salesPurchaseOrderService->delete($id);

            if (!$deleted) {
                return $this->responseService->notFound('Sales purchase order not found');
            }

            return $this->responseService->success(null, 'Sales purchase order deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload or update PO document file only.
     *
     * POST /api/v1/sales-purchase-orders/{id}/upload-file
     */
    public function uploadFile(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:51200',
                'po_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:51200',
            ]);

            $file = $request->file('po_file') ?: $request->file('file');
            if (!$file) {
                return $this->responseService->badRequest('No file uploaded');
            }

            $order = $this->salesPurchaseOrderService->uploadPoFile($id, $file);
            if (!$order) {
                return $this->responseService->notFound('Sales purchase order not found');
            }

            return $this->responseService->success(
                new SalesPurchaseOrderResource($order),
                'Sales purchase order file uploaded successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Normalize JSON-stringified array fields when submitted via FormData.
     */
    protected function normalizeArrayInputs(Request $request): void
    {
        foreach (['orders', 'items'] as $field) {
            $val = $request->input($field);
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }
}
