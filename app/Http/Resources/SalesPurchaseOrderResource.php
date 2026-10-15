<?php

/**
 * SalesPurchaseOrderResource
 * -----------------------------------------
 * API Resource for Sales Purchase Order header and relations.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesPurchaseOrderResource extends JsonResource
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

        return [
            'id' => $this->id,
            //'uuid' => $this->uuid,
            'po_number' => $this->po_number,
            'po_date' => $this->po_date ? (is_string($this->po_date) ? $this->po_date : $this->po_date->format('Y-m-d')) : $this->created_at?->format('Y-m-d'),

            'brief_id' => $this->brief_id ? (int) $this->brief_id : null,
            'brief' => $this->relationLoaded('brief') && $this->brief ? [
                'id' => $this->brief->id,
                'name' => $this->brief->name,
            ] : null,

            'planner_id' => $this->planner_id ? (int) $this->planner_id : null,
            'planner' => $this->relationLoaded('planner') && $this->planner ? [
                'id' => $this->planner->id,
                'name' => $this->planner->creator?->name ?? $this->planner->name ?? null,
            ] : null,
            'planner_name' => $this->relationLoaded('planner') && $this->planner
                ? ($this->planner->creator?->name ?? $this->planner->name ?? null)
                : null,

            'brand_id' => $this->brand_id ? (int) $this->brand_id : null,
            'brand' => $this->relationLoaded('brand') && $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null,

            'assign_to' => $this->assign_to ? (int) $this->assign_to : null,
            'assigned_to' => $this->relationLoaded('assignedTo') && $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'email' => $this->assignedTo->email,
            ] : null,

            'created_by' => $this->created_by ? (int) $this->created_by : null,
            'creator' => $this->relationLoaded('creator') && $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null,

            'finance_status_id' => $this->finance_status_id ? (int) $this->finance_status_id : null,
            'finance_status' => $this->relationLoaded('financeStatus') && $this->financeStatus ? [
                'id' => $this->financeStatus->id,
                'name' => $this->financeStatus->name,
                'slug' => $this->financeStatus->slug,
            ] : null,

            'company_name' => $this->company_name,
            'client_name' => $this->client_name,
            'primary_email' => $this->primary_email,
            'gst_number' => $this->gst_number,
            'pan_number' => $this->pan_number,
            'account_holder_name' => $this->account_holder_name,
            'account_number' => $this->account_number,
            'ifsc_code' => $this->ifsc_code,
            'bank_name' => $this->bank_name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'pincode' => $this->pincode,
            'campaign' => $this->campaign,
            'period' => $this->period,

            'subtotal' => (float) $this->subtotal,
            'sgst_rate' => (float) $this->sgst_rate,
            'sgst_amount' => (float) $this->sgst_amount,
            'cgst_rate' => (float) $this->cgst_rate,
            'cgst_amount' => (float) $this->cgst_amount,
            'igst_rate' => (float) $this->igst_rate,
            'igst_amount' => (float) $this->igst_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'amount_in_words' => $this->amount_in_words,

            'po_file' => $this->po_file,
            'po_file_url' => $fileUrl,

            'status' => $this->status,

            'items' => $this->relationLoaded('items')
                ? SalesPurchaseOrderItemResource::collection($this->items)
                : [],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Resolve the public file URL for the uploaded PO file.
     */
    protected function resolveFileUrl($request): ?string
    {
        if (empty($this->po_file)) {
            return null;
        }

        if (str_starts_with($this->po_file, 'http://') || str_starts_with($this->po_file, 'https://')) {
            return $this->po_file;
        }

        $path = ltrim(str_replace('\\', '/', $this->po_file), '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $baseUrl = rtrim((string) config('app.url', env('APP_URL', $request->root())), '/');
        return $baseUrl . '/storage/' . $path;
    }
}
