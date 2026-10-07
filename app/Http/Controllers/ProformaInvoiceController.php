<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProformaInvoiceResource;
use App\Services\ProformaInvoiceService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProformaInvoiceController extends Controller
{
    use ValidatesRequests;

    protected ProformaInvoiceService $proformaInvoiceService;
    protected ResponseService $responseService;

    public function __construct(
        ProformaInvoiceService $proformaInvoiceService,
        ResponseService $responseService
    ) {
        $this->proformaInvoiceService = $proformaInvoiceService;
        $this->responseService = $responseService;
    }

    /**
     * List proforma invoices.
     *
     * GET /proforma-invoices
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'brand_id',
                'status',
                'from_date',
                'to_date',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $invoices = $this->proformaInvoiceService->list($perPage, $filters);

            return $this->responseService->paginated(
                ProformaInvoiceResource::collection($invoices),
                'Proforma invoices retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show one proforma invoice by ID.
     *
     * GET /proforma-invoices/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->proformaInvoiceService->find($id);

            if (!$invoice) {
                return $this->responseService->notFound('Proforma invoice not found');
            }

            return $this->responseService->success(
                new ProformaInvoiceResource($invoice),
                'Proforma invoice retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Create a new proforma invoice.
     *
     * POST /proforma-invoices
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Handle JSON string for array inputs if sent via multipart/form-data
            $this->normalizeArrayInputs($request);

            $validated = $this->validate($request, [
                'brand_id' => 'required|integer|exists:brands,id',
                'brand_name' => 'nullable|string|max:255',
                'client_name' => 'nullable|string|max:255',
                'gst_no' => 'nullable|string|max:50',
                'gst_number' => 'nullable|string|max:50',
                'state_code' => 'nullable|string|max:10',
                'address' => 'nullable|string|max:1000',
                'invoice_date' => 'nullable|date',
                'po_no' => 'nullable|string|max:100',
                'po_date' => 'nullable|string|max:50',
                'period' => 'nullable|string|max:100',
                'campaign' => 'nullable|string|max:255',
                'kind_attn' => 'nullable|string|max:255',
                'pi_number' => 'nullable|string|max:100|unique:proforma_invoices,pi_number',
                'orders' => 'nullable|array|min:1',
                'items' => 'nullable|array|min:1',
                'orders.*.order' => 'nullable|string|max:500',
                'orders.*.order_name' => 'nullable|string|max:500',
                'orders.*.description' => 'nullable|string|max:500',
                'orders.*.hsn_sac' => 'nullable|string|max:50',
                'orders.*.city' => 'nullable|string|max:255',
                'orders.*.slot' => 'nullable|numeric|min:0',
                'orders.*.qty' => 'nullable|numeric|min:0',
                'orders.*.rate' => 'nullable|numeric|min:0',
                'orders.*.amount' => 'nullable|numeric|min:0',
                'items.*.order' => 'nullable|string|max:500',
                'items.*.order_name' => 'nullable|string|max:500',
                'items.*.description' => 'nullable|string|max:500',
                'items.*.hsn_sac' => 'nullable|string|max:50',
                'items.*.city' => 'nullable|string|max:255',
                'items.*.slot' => 'nullable|numeric|min:0',
                'items.*.qty' => 'nullable|numeric|min:0',
                'items.*.rate' => 'nullable|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'pi_path' => 'nullable|string|max:500',
                'file_path' => 'nullable|string|max:500',
                'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:51200',
                'pi_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:51200',
                'status' => 'nullable|string|in:1,2',
            ]);

            $file = $request->file('file') ?? $request->file('pi_file');
            $invoice = $this->proformaInvoiceService->create($validated, $file, $request->user());

            return $this->responseService->created(
                new ProformaInvoiceResource($invoice),
                'Proforma invoice created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update an existing proforma invoice.
     *
     * PUT/POST /proforma-invoices/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->normalizeArrayInputs($request);

            $validated = $this->validate($request, [
                'brand_id' => 'nullable|integer|exists:brands,id',
                'brand_name' => 'nullable|string|max:255',
                'client_name' => 'nullable|string|max:255',
                'gst_no' => 'nullable|string|max:50',
                'gst_number' => 'nullable|string|max:50',
                'state_code' => 'nullable|string|max:10',
                'address' => 'nullable|string|max:1000',
                'invoice_date' => 'nullable|date',
                'po_no' => 'nullable|string|max:100',
                'po_date' => 'nullable|string|max:50',
                'period' => 'nullable|string|max:100',
                'campaign' => 'nullable|string|max:255',
                'kind_attn' => 'nullable|string|max:255',
                'pi_number' => "nullable|string|max:100|unique:proforma_invoices,pi_number,{$id}",
                'orders' => 'nullable|array',
                'items' => 'nullable|array',
                'orders.*.order' => 'nullable|string|max:500',
                'orders.*.order_name' => 'nullable|string|max:500',
                'orders.*.description' => 'nullable|string|max:500',
                'orders.*.hsn_sac' => 'nullable|string|max:50',
                'orders.*.city' => 'nullable|string|max:255',
                'orders.*.slot' => 'nullable|numeric|min:0',
                'orders.*.qty' => 'nullable|numeric|min:0',
                'orders.*.rate' => 'nullable|numeric|min:0',
                'orders.*.amount' => 'nullable|numeric|min:0',
                'items.*.order' => 'nullable|string|max:500',
                'items.*.order_name' => 'nullable|string|max:500',
                'items.*.description' => 'nullable|string|max:500',
                'items.*.hsn_sac' => 'nullable|string|max:50',
                'items.*.city' => 'nullable|string|max:255',
                'items.*.slot' => 'nullable|numeric|min:0',
                'items.*.qty' => 'nullable|numeric|min:0',
                'items.*.rate' => 'nullable|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'sgst_rate' => 'nullable|numeric|min:0|max:100',
                'cgst_rate' => 'nullable|numeric|min:0|max:100',
                'igst_rate' => 'nullable|numeric|min:0|max:100',
                'sgst' => 'nullable|numeric|min:0|max:100',
                'cgst' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0|max:100',
                'pi_path' => 'nullable|string|max:500',
                'file_path' => 'nullable|string|max:500',
                'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:51200',
                'pi_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:51200',
                'status' => 'nullable|string|in:1,2,15',
            ]);

            $file = $request->file('file') ?? $request->file('pi_file');
            $invoice = $this->proformaInvoiceService->update($id, $validated, $file, $request->user());

            if (!$invoice) {
                return $this->responseService->notFound('Proforma invoice not found');
            }

            return $this->responseService->success(
                new ProformaInvoiceResource($invoice),
                'Proforma invoice updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete proforma invoice.
     *
     * DELETE /proforma-invoices/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->proformaInvoiceService->delete($id);

            if (!$deleted) {
                return $this->responseService->notFound('Proforma invoice not found');
            }

            return $this->responseService->success(null, 'Proforma invoice deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload / replace file for proforma invoice.
     *
     * POST /proforma-invoices/{id}/upload-file
     */
    public function uploadFile(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:51200',
            ]);

            $invoice = $this->proformaInvoiceService->uploadFile($id, $request->file('file'));

            if (!$invoice) {
                return $this->responseService->notFound('Proforma invoice not found');
            }

            return $this->responseService->success(
                new ProformaInvoiceResource($invoice),
                'Proforma invoice file uploaded successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download the proforma invoice file.
     *
     * GET /proforma-invoices/{id}/download
     */
    public function download(int $id)
    {
        try {
            $invoice = $this->proformaInvoiceService->find($id);

            if (!$invoice) {
                return $this->responseService->notFound('Proforma invoice not found');
            }

            $storedPath = (string) ($invoice->pi_path ?: '');
            $fullPath = $this->proformaInvoiceService->resolveFilePath($storedPath, $invoice);

            if (!$fullPath || !file_exists($fullPath)) {
                return $this->responseService->notFound('Proforma invoice file not found on server');
            }

            return response()->download($fullPath, basename($fullPath));
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
