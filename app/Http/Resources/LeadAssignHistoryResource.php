<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadAssignHistoryResource extends JsonResource
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
            'current_user_id' => $this->current_user_id !== null ? (int) $this->current_user_id : null,
            'current_user_name' => $this->currentUser?->name,
            'lead_comment' => $this->lead_comment,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
        ];
    }
}
