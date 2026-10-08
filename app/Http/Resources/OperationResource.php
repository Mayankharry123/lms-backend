<?php

/**
 * Operation Resource
 * -----------------------------------------
 * Transforms an Operation into the JSON payload used by the index endpoint.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
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
        $planner = $this->relationLoaded('planner') ? $this->planner : null;

        $organisation = null;
        if ($brief && $brief->relationLoaded('contactPerson') && $brief->contactPerson?->relationLoaded('organisation') && $brief->contactPerson?->organisation) {
            $organisation = [
                'id' => $brief->contactPerson->organisation->id,
                'name' => $brief->contactPerson->organisation->name,
            ];
        } elseif ($this->relationLoaded('assignedTo') && $this->assignedTo?->relationLoaded('organisation') && $this->assignedTo?->organisation) {
            $organisation = [
                'id' => $this->assignedTo->organisation->id,
                'name' => $this->assignedTo->organisation->name,
            ];
        }

        $department = null;
        if ($brief && $brief->relationLoaded('contactPerson') && $brief->contactPerson?->relationLoaded('department') && $brief->contactPerson?->department) {
            $department = [
                'id' => $brief->contactPerson->department->id,
                'name' => $brief->contactPerson->department->name,
            ];
        } elseif ($this->relationLoaded('assignedTo') && $this->assignedTo && $this->assignedTo->relationLoaded('departments') && $this->assignedTo->departments->isNotEmpty()) {
            $firstDept = $this->assignedTo->departments->first();
            $department = [
                'id' => $firstDept->id,
                'name' => $firstDept->name,
            ];
        }

        return [
            'id' => $this->id,
            'brief_id' => $this->brief_id,
            'brief_name' => $brief?->name,
            'product_name' => $brief?->product_name,
            'campaign_start_date' => $brief?->campaign_start_date?->format('Y-m-d'),
            'campaign_end_date' => $brief?->campaign_end_date?->format('Y-m-d'),
            'sales_user_name' => $brief?->createdByUser?->name,
            'planner_name' => $planner?->creator?->name,
            'assign_user_name' => $this->relationLoaded('assignedTo') ? $this->assignedTo?->name : null,
            'operation_status_id' => $this->operation_status_id,
            'operation_status' => $this->relationLoaded('operationStatus') ? $this->operationStatus?->name : null,
            'backup_plan' => $planner?->backup_plan,
            'backup_plan_url' => $this->backupPlanUrl($request, $planner?->backup_plan),
            'organisation' => $organisation,
            'department' => $department,
        ];
    }

    /**
     * Direct download link on the same host that served this API response.
     */
    private function backupPlanUrl($request, ?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return rtrim($request->root(), '/') . '/api/v1/operations/' . $this->id . '/backup-plan';
    }
}
