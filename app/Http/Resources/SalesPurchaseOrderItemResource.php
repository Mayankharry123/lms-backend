<?php

/**
 * SalesPurchaseOrderItemResource
 * -----------------------------------------
 * API Resource for Sales Purchase Order line items.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesPurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sales_purchase_order_id' => (int) $this->sales_purchase_order_id,
            'order_name' => $this->order_name,
            'description' => $this->description,
            'hsn_sac' => $this->hsn_sac,
            'city' => $this->city,
            'slot' => (float) $this->slot,
            'qty' => (float) $this->qty,
            'rate' => (float) $this->rate,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
