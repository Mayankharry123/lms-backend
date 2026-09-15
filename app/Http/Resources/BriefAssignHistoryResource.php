<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefAssignHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    /**
     * Updated assignment history response to include brief details,
     * assigned-by/assigned-to users, brief status, IDs, and timestamps.
     */
    public function toArray($request): array
    {
        return [
            // Basic Information
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,

            // Brief Information
            'brief' => $this->whenLoaded('brief', function () {
                return $this->brief ? new BriefResource($this->brief) : null;
            }),
            'brief_id' => $this->brief_id,

            // Assignment Information
            'assigned_by' => $this->whenLoaded('assignedBy', function () {
                return $this->assignedBy ? new UserResource($this->assignedBy) : null;
            }),
            'assign_by_id' => $this->assign_by_id,

            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? new UserResource($this->assignedTo) : null;
            }),
            'assign_to_id' => $this->assign_to_id,

            // Status Information
            'brief_status' => $this->whenLoaded('briefStatus', function () {
                return $this->briefStatus ? new BriefStatusResource($this->briefStatus) : null;
            }),
            'brief_status_id' => $this->brief_status_id,
            'brief_status_time' => $this->brief_status_time ? $this->brief_status_time->format('d-m-Y H:i:s') : null,

            // Dates
            'submission_date' => $this->submission_date ? $this->submission_date->format('d-m-Y H:i:s') : null,
            'comment' => $this->comment,
            'attachment' => $this->attachment,

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s A') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s A') : null,   
            //'deleted_at' => $this->deleted_at->toIso8601String(),
        ];
    }
}
