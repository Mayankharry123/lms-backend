<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoucherTypeResource;
use App\Services\ResponseService;
use App\Services\VoucherTypeService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VoucherTypeController extends Controller
{
    use ValidatesRequests;

    protected VoucherTypeService $voucherTypeService;
    protected ResponseService $responseService;

    public function __construct(VoucherTypeService $voucherTypeService, ResponseService $responseService)
    {
        $this->voucherTypeService = $voucherTypeService;
        $this->responseService = $responseService;
    }

    /**
     * Get list of active voucher types (supports pagination or full list).
     *
     * GET /voucher-types
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
                $voucherTypes = $this->voucherTypeService->paginate($perPage, $search);

                return $this->responseService->paginated(
                    VoucherTypeResource::collection($voucherTypes),
                    'Voucher types retrieved successfully'
                );
            }

            $voucherTypes = $this->voucherTypeService->list($search);

            return $this->responseService->success(
                VoucherTypeResource::collection($voucherTypes),
                'Voucher types retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get active voucher types list (for dropdowns).
     *
     * GET /voucher-types/list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $voucherTypes = $this->voucherTypeService->list($search);

            return $this->responseService->success(
                VoucherTypeResource::collection($voucherTypes),
                'Voucher types list retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show a single voucher type by ID.
     *
     * GET /voucher-types/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $voucherType = $this->voucherTypeService->find($id);

            if (!$voucherType) {
                return $this->responseService->notFound('Voucher type not found');
            }

            return $this->responseService->success(
                new VoucherTypeResource($voucherType),
                'Voucher type retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download or retrieve the download URL of sample Excel format for a specific voucher type by ID.
     *
     * GET /voucher-types/{id}/download-sample
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function downloadSampleExcel(Request $request, int $id)
    {
        try {
            $user = $request->user();
            $result = $this->voucherTypeService->generateSampleExcel($id, $user);

            $root = rtrim($request->root(), '/');
            $storageUrl = $root . '/' . ltrim($result['relative_path'], '/');

            // Binary download if explicitly requested via query param ?download=1
            $isDirectDownload = $request->boolean('download')
                || $request->query('format') === 'file';

            if ($isDirectDownload) {
                return response($result['content'], 200, [
                    'Content-Type' => $result['mime'],
                    'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
                    'Cache-Control' => 'no-cache, private',
                    'Access-Control-Expose-Headers' => 'Content-Disposition',
                ]);
            }

            // Return JSON response containing download URL parameter
            return $this->responseService->success([
                'id' => $result['id'],
                'name' => $result['name'],
                'file_name' => $result['filename'],
                'download_url' => $storageUrl,
                'url' => $storageUrl,
            ], 'Sample Excel download URL retrieved successfully');
        } catch (\DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
