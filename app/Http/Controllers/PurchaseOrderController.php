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

use App\Services\PurchaseOrderService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
