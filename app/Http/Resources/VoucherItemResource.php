<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'voucher_id' => $this->voucher_id,
            'date' => $this->date ? ($this->date instanceof \DateTimeInterface ? $this->date->format('Y-m-d') : (string) $this->date) : null,
            'particular' => $this->particular,
            'purpose' => $this->purpose,
            'mode' => $this->mode,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at instanceof \DateTimeInterface ? $this->created_at->format('Y-m-d H:i:s A') : ($this->created_at ? (string) $this->created_at : null),
        ];
    }
}
