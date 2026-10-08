<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaInvoiceItemResource extends JsonResource
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
            'proforma_invoice_id' => $this->proforma_invoice_id,
            'order_name' => $this->order_name,
            'order' => $this->order_name,
            'description' => $this->order_name,
            'hsn_sac' => $this->hsn_sac,
            'city' => $this->city,
            'slot' => (float) $this->slot,
            'qty' => (float) $this->slot,
            'rate' => (float) $this->rate,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
