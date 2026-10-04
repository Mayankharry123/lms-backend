<?php

/**
 * FinanceRecord Resource
 * -----------------------------------------
 * Transforms a finance record into the JSON payload returned after a cost sheet upload.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRecordResource extends JsonResource
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
            'brief_id' => $this->brief_id,
            'brief_name' => $this->relationLoaded('brief') ? $this->brief?->name : null,
            'planner_id' => $this->planner_id,
            'finance_status' => $this->relationLoaded('financeStatus') ? $this->financeStatus?->name : null,
            'cost_sheet' => $this->cost_sheet,
            'assign_by_name' => $this->relationLoaded('assignedBy') ? $this->assignedBy?->name : null,
            'assign_to_name' => $this->relationLoaded('assignedTo') ? $this->assignedTo?->name : null,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s A'),
        ];
    }
}
