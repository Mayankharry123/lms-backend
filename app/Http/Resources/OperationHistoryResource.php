<?php

/**
 * Operation History Resource
 * -----------------------------------------
 * Transforms an OperationHistory record into an API response payload.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Http\Resources;

use App\Support\DateTimeFormatter;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $brief = $this->relationLoaded('brief') ? $this->brief : null;
        $operationStatus = $this->relationLoaded('operationStatus') ? $this->operationStatus : null;
        $assignedBy = $this->relationLoaded('assignedBy') ? $this->assignedBy : null;
        $assignedTo = $this->relationLoaded('assignedTo') ? $this->assignedTo : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'operation_id' => $this->operation_id,
            'brief_id' => $this->brief_id,
            'brief_name' => $brief?->name,
            'planner_id' => $this->planner_id,
            'operation_status_id' => $this->operation_status_id,
            'operation_status' => $operationStatus?->name,
            'assign_by' => $this->assign_by,
            'assign_by_name' => $assignedBy?->name,
            'assign_to' => $this->assign_to,
            'assign_to_name' => $assignedTo?->name,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => DateTimeFormatter::format($this->created_at),
            'updated_at' => DateTimeFormatter::format($this->updated_at),
        ];
    }
}
