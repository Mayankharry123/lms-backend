<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefResource extends JsonResource
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
            // Basic Information
            'id' => $this->id,
            //'uuid' => $this->uuid,
            'name' => $this->name,
            'product_name' => $this->product_name,
            'mode_of_campaign' => $this->mode_of_campaign,
            'media_type' => $this->media_type,
            'budget' => $this->budget,
            'assign_user_id' => $this->assign_user_id,
            'comment' => $this->comment,
            'attachment' => $this->attachment,
            'submission_date' => $this->formatSubmissionDate(),
            'status' => $this->status,
            'left_time' => $this->calculateLeftTime(),
            'campaign_start_date' => $this->campaign_start_date ? $this->campaign_start_date->format('Y-m-d') : null,
            'campaign_end_date' => $this->campaign_end_date ? $this->campaign_end_date->format('Y-m-d') : null,
            'campaign_duration' => $this->campaign_duration,

            // Relationships
            'contact_person' => $this->whenLoaded('contactPerson', function () {
                return [
                    'id' => $this->contactPerson->id,
                    'name' => $this->contactPerson->name,
                    'email' => $this->contactPerson->email,
                ];
            }),
            'organisation' => $this->whenLoaded('contactPerson', function () {
                if ($this->contactPerson && $this->contactPerson->relationLoaded('organisation') && $this->contactPerson->organisation) {
                    return [
                        'id' => $this->contactPerson->organisation->id,
                        'name' => $this->contactPerson->organisation->name,
                    ];
                }
                return null;
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'agency' => $this->whenLoaded('agency', function () {
                return [
                    'id' => $this->agency->id,
                    'name' => $this->agency->name,
                ];
            }),
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                ];
            }),
            'created_by_user' => $this->whenLoaded('createdByUser', function () {
                return [
                    'id' => $this->createdByUser->id,
                    'name' => $this->createdByUser->name,
                    'email' => $this->createdByUser->email,
                ];
            }),
            'brief_status' => $this->whenLoaded('briefStatus', function () {
                return [
                    'id' => $this->briefStatus->id,
                    'name' => $this->briefStatus->name,
                    'percentage' => $this->briefStatus->percentage,
                ];
            }),
            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'id' => $this->priority->id,
                    'name' => $this->priority->name,
                ];
            }),
            'planner_status' => $this->getFirstPlannerStatus(),
            'planner_id' => $this->latest_planner_id,
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
        ];
    }

    /**
     * Format submission date properly
     */
    private function formatSubmissionDate(): ?string
    {
        if (!$this->submission_date) {
            return null;
        }

        // If it's already a Carbon instance, convert to simple format
        if ($this->submission_date instanceof \Carbon\Carbon) {
            return $this->submission_date->format('Y-m-d H:i:s A');
        }

        // If it's a string, try to parse and convert it
        if (is_string($this->submission_date)) {
            try {
                return \Carbon\Carbon::parse($this->submission_date)->format('Y-m-d H:i:s A');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Calculate left time until submission date
     */
    private function calculateLeftTime(): ?string
    {
        if (!$this->submission_date) {
            return null;
        }

        $submissionDate = $this->submission_date instanceof \Carbon\Carbon
            ? $this->submission_date
            : \Carbon\Carbon::parse($this->submission_date);

        $now = now();
        $diff = $submissionDate->diff($now);

        if ($submissionDate->gt($now)) {
            return $diff->format('%d days %h hours %i minutes left');
        }

        return 'Expired';
    }

    /**
     * Get the latest planner status for this brief
     */
    private function getFirstPlannerStatus(): ?array
    {
        $planner = \App\Models\Planner::where('brief_id', $this->id)
            ->with('plannerStatus')
            ->latest()
            ->first();
        
        if ($planner && $planner->plannerStatus) {
            return [
                'id' => $planner->plannerStatus->id,
                'name' => $planner->plannerStatus->name,
            ];
        }
        return null;
    }
}
