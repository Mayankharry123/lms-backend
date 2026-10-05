<?php

/**
 * CostSheet Resource
 * -----------------------------------------
 * Cost sheet list and detail fields: purchase order, brief, plan, assignees, and statuses.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CostSheetResource extends JsonResource
{
    /**
     * Transform the cost sheet into the list payload.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $brief = $this->relationLoaded('brief') ? $this->brief : null;
        $planner = $this->relationLoaded('planner') ? $this->planner : null;
        $purchaseOrder = $this->relationLoaded('latestPurchaseOrder') ? $this->latestPurchaseOrder : null;

        return [
            'id' => $this->id,
            'purchase_order_id' => $purchaseOrder?->id,
            'brief_id' => $this->brief_id,
            'brief_name' => $brief?->name,
            'plan_id' => $this->planner_id,
            'planner_name' => $planner && $planner->relationLoaded('creator') ? $planner->creator?->name : null,
            'submitted_date' => $this->created_at?->format('Y-m-d H:i:s A'),
            'assign_by' => $this->userPayload('assignedBy'),
            'assign_to' => $this->userPayload('assignedTo'),
            'cost_sheet_status' => $brief && $brief->relationLoaded('costSheetStatus') && $brief->costSheetStatus
                ? [
                    'id' => $brief->costSheetStatus->id,
                    'name' => $brief->costSheetStatus->name,
                ]
                : null,
            'finance_status' => $this->relationLoaded('financeStatus') && $this->financeStatus
                ? [
                    'id' => $this->financeStatus->id,
                    'name' => $this->financeStatus->name,
                ]
                : null,
            'cost_sheet' => $this->costSheetUrl($request),
        ];
    }

    /**
     * User id and name for an assignment relation.
     *
     * @return array<string, mixed>|null
     */
    private function userPayload(string $relation): ?array
    {
        if (!$this->relationLoaded($relation) || !$this->{$relation}) {
            return null;
        }

        return [
            'id' => $this->{$relation}->id,
            'name' => $this->{$relation}->name,
        ];
    }

    /**
     * Public URL for the stored cost sheet file.
     */
    private function costSheetUrl($request): ?string
    {
        $path = ltrim((string) $this->cost_sheet, '/');

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return rtrim($request->root(), '/') . '/storage/' . $path;
    }
}
