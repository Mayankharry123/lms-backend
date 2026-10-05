<?php

/**
 * PurchaseOrder Resource
 * -----------------------------------------
 * Purchase order list and detail: cost sheet context plus the purchase order PDF URL.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the purchase order into the list payload.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $financeRecord = $this->relationLoaded('financeRecord') ? $this->financeRecord : null;
        $brief = $financeRecord && $financeRecord->relationLoaded('brief') ? $financeRecord->brief : null;
        $planner = $financeRecord && $financeRecord->relationLoaded('planner') ? $financeRecord->planner : null;

        return [
            'purchase_order_id' => $this->id,
            'brief_id' => $financeRecord?->brief_id,
            'brief_name' => $brief?->name,
            'plan_id' => $financeRecord?->planner_id,
            'planner_name' => $planner && $planner->relationLoaded('creator') ? $planner->creator?->name : null,
            'submitted_date' => $financeRecord?->created_at?->format('Y-m-d H:i:s A'),
            'assign_by' => $this->userPayload($financeRecord, 'assignedBy'),
            'assign_to' => $this->userPayload($financeRecord, 'assignedTo'),
            'cost_sheet_status' => $brief && $brief->relationLoaded('costSheetStatus') && $brief->costSheetStatus
                ? [
                    'id' => $brief->costSheetStatus->id,
                    'name' => $brief->costSheetStatus->name,
                ]
                : null,
            'finance_status' => $financeRecord && $financeRecord->relationLoaded('financeStatus') && $financeRecord->financeStatus
                ? [
                    'id' => $financeRecord->financeStatus->id,
                    'name' => $financeRecord->financeStatus->name,
                ]
                : null,
            'purchase_order_url' => $this->purchaseOrderUrl($request),
        ];
    }

    /**
     * User id and name for an assignment relation on the finance record.
     *
     * @return array<string, mixed>|null
     */
    private function userPayload($financeRecord, string $relation): ?array
    {
        if (!$financeRecord || !$financeRecord->relationLoaded($relation) || !$financeRecord->{$relation}) {
            return null;
        }

        return [
            'id' => $financeRecord->{$relation}->id,
            'name' => $financeRecord->{$relation}->name,
        ];
    }

    /**
     * Public URL for the purchase order PDF.
     */
    private function purchaseOrderUrl($request): ?string
    {
        $poNumber = trim((string) $this->po_number);

        if ($poNumber === '') {
            return null;
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $poNumber) . '.pdf';

        return rtrim($request->root(), '/') . '/storage/purchase-orders/' . $filename;
    }
}
