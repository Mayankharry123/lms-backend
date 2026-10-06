<?php

/**
 * PurchaseOrder Controller
 * -----------------------------------------
 * Accepts a publisher id and order lines, then returns the created purchase order.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Http\Controllers;

use App\Http\Resources\PurchaseOrderResource;
use App\Services\PurchaseOrderService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseOrderController extends Controller
{
    use ValidatesRequests;

    protected PurchaseOrderService $purchaseOrderService;
    protected ResponseService $responseService;

    /**
     * Inject the purchase order service and response service.
     */
    public function __construct(PurchaseOrderService $purchaseOrderService, ResponseService $responseService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
        $this->responseService = $responseService;
    }

    /**
     * List purchase orders.
     *
     * GET /purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'organisation_id' => 'nullable|integer|min:1',
                'organisation_ids' => 'nullable',
                'department_id' => 'nullable|integer|min:1',
                'department_ids' => 'nullable',
                'user_id' => 'nullable|integer|min:1',
                'user_ids' => 'nullable',
                'assign_to' => 'nullable|integer|min:1',
                'assign_by' => 'nullable|integer|min:1',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $filters = array_filter([
                'organisation_id' => $validated['organisation_id'] ?? null,
                'organisation_ids' => $validated['organisation_ids'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'department_ids' => $validated['department_ids'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'user_ids' => $validated['user_ids'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
            $purchaseOrders = $this->purchaseOrderService->list($perPage, $filters, Auth::user());

            return $this->responseService->paginated(
                PurchaseOrderResource::collection($purchaseOrders),
                'Purchase orders retrieved successfully'
            );
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return $this->responseService->validationError($e->errors(), 'Validation failed');
            }

            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show one purchase order.
     *
     * GET /purchase-orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->find($id, Auth::user());

            if (!$purchaseOrder) {
                return $this->responseService->notFound('Purchase order not found');
            }

            return $this->responseService->success(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Create a purchase order and its PDF.
     *
     * POST /purchase-orders
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'publisher_id' => 'required|integer|min:1',
                'publisher_address_id' => 'nullable|integer|min:1',
                'finance_record_id' => 'required|integer|min:1',
                'campaign' => 'nullable|string|max:255',
                'period' => 'nullable|string|max:255',
                'orders' => 'required|array|min:1',
                'orders.*.description' => 'required|string|max:1000',
                'orders.*.hsn_sac' => 'required|string|max:20',
                'orders.*.city' => 'nullable|string|max:255',
                'orders.*.qty' => 'required|numeric|gt:0',
                'orders.*.rate' => 'required|numeric|gte:0',
            ]);

            $purchaseOrder = $this->purchaseOrderService->create($validated);
            $purchaseOrder['pdf_url'] = rtrim($request->root(), '/') . '/storage/' . $purchaseOrder['pdf_path'];
            unset($purchaseOrder['pdf_path']);

            return $this->responseService->created(
                $purchaseOrder,
                'Purchase order created successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
