<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoucherResource;
use App\Services\ResponseService;
use App\Services\VoucherService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class VoucherController extends Controller
{
    use ValidatesRequests;

    protected VoucherService $voucherService;
    protected ResponseService $responseService;

    public function __construct(
        VoucherService $voucherService,
        ResponseService $responseService
    ) {
        $this->voucherService = $voucherService;
        $this->responseService = $responseService;
    }

    /**
     * List vouchers with optional filters and pagination.
     *
     * GET /vouchers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'voucher_type_id',
                'person_name',
                'month',
                'status',
                'from_date',
                'to_date',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $vouchers = $this->voucherService->list($perPage, $filters);

            return $this->responseService->paginated(
                VoucherResource::collection($vouchers),
                'Vouchers retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show a single voucher by ID.
     *
     * GET /vouchers/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->find($id);

            if (!$voucher) {
                return $this->responseService->notFound('Voucher not found');
            }

            return $this->responseService->success(
                new VoucherResource($voucher),
                'Voucher retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created voucher.
     *
     * POST /vouchers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->normalizeArrayInputs($request);

            $validated = $this->validate($request, [
                'voucher_type_id' => 'required|integer|exists:voucher_types,id',
                'person_name' => 'required|string|max:255',
                'voucher_number' => 'nullable|string|max:100|unique:vouchers,voucher_number',
                'month' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|string|max:50',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'orders' => 'nullable|array',
                'items' => 'nullable|array',
                'orders.*.date' => 'nullable|string|max:50',
                'orders.*.particular' => 'nullable|string|max:500',
                'orders.*.purpose' => 'nullable|string|max:500',
                'orders.*.mode' => 'nullable|string|max:100',
                'orders.*.payment_mode_type_id' => 'nullable|integer|exists:payment_mode_types,id',
                'orders.*.amount' => 'nullable|numeric|min:0',
                'items.*.date' => 'nullable|string|max:50',
                'items.*.particular' => 'nullable|string|max:500',
                'items.*.purpose' => 'nullable|string|max:500',
                'items.*.mode' => 'nullable|string|max:100',
                'items.*.payment_mode_type_id' => 'nullable|integer|exists:payment_mode_types,id',
                'items.*.amount' => 'nullable|numeric|min:0',
                'file' => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv|max:10240',
                'supporting_document' => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv|max:10240',
            ]);

            $file = $request->file('file') ?? $request->file('supporting_document');
            $user = $request->user() ?? Auth::user();

            $voucher = $this->voucherService->create($validated, $file, $user);

            return $this->responseService->created(
                new VoucherResource($voucher),
                'Voucher created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update an existing voucher.
     *
     * PUT/POST /vouchers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->normalizeArrayInputs($request);

            $validated = $this->validate($request, [
                'voucher_type_id' => 'nullable|integer|exists:voucher_types,id',
                'person_name' => 'nullable|string|max:255',
                'voucher_number' => "nullable|string|max:100|unique:vouchers,voucher_number,{$id}",
                'month' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|string|max:50',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'orders' => 'nullable|array',
                'items' => 'nullable|array',
                'orders.*.id' => 'nullable|integer',
                'orders.*.date' => 'nullable|string|max:50',
                'orders.*.particular' => 'nullable|string|max:500',
                'orders.*.purpose' => 'nullable|string|max:500',
                'orders.*.mode' => 'nullable|string|max:100',
                'orders.*.payment_mode_type_id' => 'nullable|integer|exists:payment_mode_types,id',
                'orders.*.amount' => 'nullable|numeric|min:0',
                'items.*.id' => 'nullable|integer',
                'items.*.date' => 'nullable|string|max:50',
                'items.*.particular' => 'nullable|string|max:500',
                'items.*.purpose' => 'nullable|string|max:500',
                'items.*.mode' => 'nullable|string|max:100',
                'items.*.payment_mode_type_id' => 'nullable|integer|exists:payment_mode_types,id',
                'items.*.amount' => 'nullable|numeric|min:0',
                'file' => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv|max:10240',
                'supporting_document' => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv|max:10240',
            ]);

            $file = $request->file('file') ?? $request->file('supporting_document');
            $user = $request->user() ?? Auth::user();

            $voucher = $this->voucherService->update($id, $validated, $file, $user);

            if (!$voucher) {
                return $this->responseService->notFound('Voucher not found');
            }

            return $this->responseService->success(
                new VoucherResource($voucher),
                'Voucher updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete a voucher.
     *
     * DELETE /vouchers/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->voucherService->delete($id);

            if (!$deleted) {
                return $this->responseService->notFound('Voucher not found or could not be deleted');
            }

            return $this->responseService->success(
                null,
                'Voucher deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload supporting document for a voucher.
     *
     * POST /vouchers/{id}/upload-file
     */
    public function uploadFile(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'file' => 'required|file|mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv|max:10240',
            ]);

            $voucher = $this->voucherService->uploadFile($id, $request->file('file'));

            if (!$voucher) {
                return $this->responseService->notFound('Voucher not found');
            }

            return $this->responseService->success(
                new VoucherResource($voucher),
                'Supporting document uploaded successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download the supporting document for a voucher.
     *
     * GET /vouchers/{id}/download
     */
    public function download(int $id)
    {
        try {
            $voucher = $this->voucherService->find($id);

            if (!$voucher) {
                return $this->responseService->notFound('Voucher not found');
            }

            $fullPath = $this->voucherService->resolveFilePath($voucher);

            if (!$fullPath || !file_exists($fullPath)) {
                return $this->responseService->notFound('Supporting document not found on server');
            }

            return response()->download($fullPath, $voucher->file_name ?: basename($fullPath));
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
