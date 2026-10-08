<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentModeTypeResource;
use App\Services\PaymentModeTypeService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentModeTypeController extends Controller
{
    use ValidatesRequests;

    protected PaymentModeTypeService $paymentModeTypeService;
    protected ResponseService $responseService;

    public function __construct(
        PaymentModeTypeService $paymentModeTypeService,
        ResponseService $responseService
    ) {
        $this->paymentModeTypeService = $paymentModeTypeService;
        $this->responseService = $responseService;
    }

    /**
     * Get list of active payment mode types (supports pagination or full list).
     *
     * GET /payment-mode-types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'paginate' => 'nullable|boolean',
            ]);

            $search = $request->input('search');
            $shouldPaginate = $request->has('per_page') || $request->has('page') || $request->boolean('paginate');

            if ($shouldPaginate) {
                $perPage = (int) $request->input('per_page', 15);
                $paymentModes = $this->paymentModeTypeService->paginate($perPage, $search);

                return $this->responseService->paginated(
                    PaymentModeTypeResource::collection($paymentModes),
                    'Payment mode types retrieved successfully'
                );
            }

            $paymentModes = $this->paymentModeTypeService->list($search);

            return $this->responseService->success(
                PaymentModeTypeResource::collection($paymentModes),
                'Payment mode types retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get active payment mode types specifically formatted for dropdown list.
     *
     * GET /payment-mode-types/list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $paymentModes = $this->paymentModeTypeService->list($search);

            return $this->responseService->success(
                PaymentModeTypeResource::collection($paymentModes),
                'Payment mode types list retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show a single payment mode type by ID.
     *
     * GET /payment-mode-types/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $paymentMode = $this->paymentModeTypeService->find($id);

            if (!$paymentMode) {
                return $this->responseService->notFound('Payment mode type not found');
            }

            return $this->responseService->success(
                new PaymentModeTypeResource($paymentMode),
                'Payment mode type retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
