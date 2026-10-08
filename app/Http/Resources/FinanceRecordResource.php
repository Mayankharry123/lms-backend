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
        $brief = $this->relationLoaded('brief') ? $this->brief : null;
        $assignedTo = $this->relationLoaded('assignedTo') ? $this->assignedTo : null;

        $organisation = null;
        if ($brief && $brief->relationLoaded('contactPerson') && $brief->contactPerson?->relationLoaded('organisation') && $brief->contactPerson?->organisation) {
            $organisation = [
                'id' => $brief->contactPerson->organisation->id,
                'name' => $brief->contactPerson->organisation->name,
            ];
        } elseif ($assignedTo && $assignedTo->relationLoaded('organisation') && $assignedTo->organisation) {
            $organisation = [
                'id' => $assignedTo->organisation->id,
                'name' => $assignedTo->organisation->name,
            ];
        }

        $department = null;
        if ($brief && $brief->relationLoaded('contactPerson') && $brief->contactPerson?->relationLoaded('department') && $brief->contactPerson?->department) {
            $department = [
                'id' => $brief->contactPerson->department->id,
                'name' => $brief->contactPerson->department->name,
            ];
        } elseif ($assignedTo && $assignedTo->relationLoaded('departments') && $assignedTo->departments->isNotEmpty()) {
            $firstDept = $assignedTo->departments->first();
            $department = [
                'id' => $firstDept->id,
                'name' => $firstDept->name,
            ];
        }

        return [
            'id' => $this->id,
            'brief_id' => $this->brief_id,
            'brief_name' => $brief?->name,
            'planner_id' => $this->planner_id,
            'finance_status' => $this->relationLoaded('financeStatus') ? $this->financeStatus?->name : null,
            'cost_sheet' => $this->cost_sheet,
            'assign_by_name' => $this->relationLoaded('assignedBy') ? $this->assignedBy?->name : null,
            'assign_to_name' => $assignedTo?->name,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s A'),
            'organisation' => $organisation,
            'department' => $department,
        ];
    }
}
