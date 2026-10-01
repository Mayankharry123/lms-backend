<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLeadPerformanceResource extends JsonResource
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
            'lead_id' => (int) $this->id,
            'contact_person_name' => $this->name ?? null,
            'call_status_id' => $this->callStatusRelation ? (int) $this->callStatusRelation->id : null,
            'call_status' => $this->callStatusRelation?->name ?? null,
            'lead_status_id' => $this->leadStatusRelation ? (int) $this->leadStatusRelation->id : null,
            'lead_status' => $this->leadStatusRelation?->name ?? null,
            'priority_id' => $this->priority ? (int) $this->priority->id : null,
            'priority' => $this->priority?->name ?? null,
        ];
    }
}
