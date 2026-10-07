<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $fileUrl = $this->resolveFileUrl($request);
        $downloadUrl = $this->id ? rtrim($request->root(), '/') . '/api/v1/proforma-invoices/' . $this->id . '/download' : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'pi_number' => $this->pi_number,
            'brand_id' => $this->brand_id,
            'brand' => $this->relationLoaded('brand') && $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null,
            'brand_name' => $this->brand_name ?: ($this->relationLoaded('brand') ? $this->brand?->name : null),
            'gst_no' => $this->gst_no,
            'address' => $this->address,
            'subtotal' => (float) $this->subtotal,
            'sgst_rate' => (float) $this->sgst_rate,
            'sgst_amount' => (float) $this->sgst_amount,
            'cgst_rate' => (float) $this->cgst_rate,
            'cgst_amount' => (float) $this->cgst_amount,
            'igst_rate' => (float) $this->igst_rate,
            'igst_amount' => (float) $this->igst_amount,
            'total_tax' => (float) $this->total_tax,
            'total_amount' => (float) $this->total_amount,
            'amount_in_words' => $this->amount_in_words,
            'pi_path' => $this->pi_path,
            'file_path' => $this->pi_path,
            'pi_url' => $fileUrl,
            'file_url' => $fileUrl,
            'download_url' => $downloadUrl,
            'status' => $this->status,
            'creator' => $this->relationLoaded('creator') && $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'items' => $this->relationLoaded('items')
                ? ProformaInvoiceItemResource::collection($this->items)
                : [],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Resolve the public file URL.
     */
    protected function resolveFileUrl($request): ?string
    {
        if (empty($this->pi_path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $this->pi_path), '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $baseUrl = rtrim((string) config('app.url', env('APP_URL', $request->root())), '/');
        return $baseUrl . '/storage/' . $path;
    }
}
