<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'uuid' => (string) $this->uuid,
            'voucher_number' => (string) $this->voucher_number,
            'voucher_type_id' => (int) $this->voucher_type_id,
            'voucher_type' => $this->whenLoaded('voucherType', function () {
                return $this->voucherType ? [
                    'id' => (int) $this->voucherType->id,
                    'name' => (string) $this->voucherType->name,
                    'slug' => (string) $this->voucherType->slug,
                ] : null;
            }),
            'person_name' => (string) $this->person_name,
            'month' => $this->month,
            'subtotal' => (float) $this->subtotal,
            'sgst_rate' => (float) $this->sgst_rate,
            'sgst_amount' => (float) $this->sgst_amount,
            'cgst_rate' => (float) $this->cgst_rate,
            'cgst_amount' => (float) $this->cgst_amount,
            'igst_rate' => (float) $this->igst_rate,
            'igst_amount' => (float) $this->igst_amount,
            'total_tax' => (float) $this->total_tax,
            'total_amount' => (float) $this->total_amount,
            'amount_in_words' => (string) $this->amount_in_words,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'notes' => $this->notes,
            'status' => (string) ($this->status ?? 'active'),
            'created_by' => (int) $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => (int) $this->creator->id,
                    'name' => (string) ($this->creator->name ?? trim(($this->creator->first_name ?? '') . ' ' . ($this->creator->last_name ?? ''))),
                    'email' => (string) ($this->creator->email ?? ''),
                ] : null;
            }),
            'items' => VoucherItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
