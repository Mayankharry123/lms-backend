<?php

/**
 * Finance Record History Resource
 * -----------------------------------------
 * Transforms a FinanceRecordHistory record into an API response payload.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Http\Resources;

use App\Support\DateTimeFormatter;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRecordHistoryResource extends JsonResource
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
        $financeStatus = $this->relationLoaded('financeStatus') ? $this->financeStatus : null;
        $assignedBy = $this->relationLoaded('assignedBy') ? $this->assignedBy : null;
        $assignedTo = $this->relationLoaded('assignedTo') ? $this->assignedTo : null;

        return [
            'id' => $this->id,
            //'uuid' => $this->uuid,
            'finance_record_id' => $this->finance_record_id,
            'brief_id' => $this->brief_id,
            'brief_name' => $brief?->name,
            'planner_id' => $this->planner_id,
            'finance_status_id' => $this->finance_status_id,
            'finance_status' => $financeStatus?->name,
            'cost_sheet' => $this->cost_sheet,
            'cost_sheet_url' => $this->costSheetUrl($request),
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
