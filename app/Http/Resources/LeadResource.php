<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            //'slug' => $this->slug,
            'email' => $this->email,
            'profile_url' => $this->profile_url,
            'mobile_number' => $this->whenLoaded('mobileNumbers', function () {
                return $this->mobileNumbers->map(function ($mobile) {
                    return [
                        'id' => $mobile->id,
                        'number' => $mobile->mobile_number,
                        //'is_primary' => $mobile->is_primary,
                        //'is_verified' => $mobile->is_verified,
                    ];
                })->toArray();
            }),
            'type' => $this-> whenLoaded('leadType', function () {
                return $this->leadType->name ?? null;
            }),
            //'lead_type_id' => $this->lead_type_id,
            'status' => $this->status,
            'comment' => $this->comment,

            // Foreign Key IDs
            //'brand_id' => $this->brand_id,
            //'created_by' => $this->created_by,
            // 'agency_id' => $this->agency_id,
            // 'current_assign_user' => $this->current_assign_user,
            // 'priority_id' => $this->priority_id,
            // 'lead_status' => $this->lead_status,
            // 'designation_id' => $this->designation_id,
            // 'department_id' => $this->department_id,
            // 'sub_source_id' => $this->sub_source_id,
            // 'country_id' => $this->country_id,
            // 'state_id' => $this->state_id,
            // 'city_id' => $this->city_id,
            // 'zone_id' => $this->zone_id,
            'postal_code' => $this->postal_code,

            // Special Fields
            //'call_status' => $this->call_status,
            'call_attempt' => $this->call_attempt,
    
            // Relationship Objects or Call feedback
            'call_status_relation' => $this->whenLoaded('callStatusRelation', function () {
                return [
                    'id' => $this->callStatusRelation->id ?? null,
                    'name' => $this->callStatusRelation->name ?? null,
                ];
            }),

            // Status of the lead
            'lead_status_relation' => $this->whenLoaded('leadStatusRelation', function () {
                return [
                    'id' => $this->leadStatusRelation->id ?? null,
                    'name' => $this->leadStatusRelation->name ?? null,
                ];
            }),

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id ?? null,
                    'name' => $this->brand->name ?? null,
                ];
            }),

            'agency' => $this->whenLoaded('agency', function () {
                return [
                    'id' => $this->agency->id ?? null,
                    'name' => $this->agency->name ?? null,
                ];
            }),

            'lead_type' => $this->whenLoaded('leadType', function () {
                return [
                    'id' => $this->leadType->id ?? null,
                    'name' => $this->leadType->name ?? null,
                ];
            }),

            'created_by_user' => $this->whenLoaded('createdByUser', function () {
                return [
                    'id' => $this->createdByUser->id ?? null,
                    'name' => $this->createdByUser->name ?? null,
                    'email' => $this->createdByUser->email ?? null,
                ];
            }),

            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id ?? null,
                    'name' => $this->assignedUser->name ?? null,
                    'email' => $this->assignedUser->email ?? null,
                ];
            }),

            'organisation' => $this->whenLoaded('organisation', function () {
                return [
                    'id' => $this->organisation->id ?? null,
                    'name' => $this->organisation->name ?? null,
                ];
            }),

            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'id' => $this->priority->id ?? null,
                    'name' => $this->priority->name ?? null,
                ];
            }),

            'designation' => $this->whenLoaded('designation', function () {
                return [
                    'id' => $this->designation->id ?? null,
                    'name' => $this->designation->title ?? null,
                ];
            }),

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id ?? null,
                    'name' => $this->department->name ?? null,
                ];
            }),

            'source' => $this->whenLoaded('source', function () {
                return [
                    'id' => $this->source->id ?? null,
                    'name' => $this->source->name ?? null,
                ];
            }),

            'sub_source' => $this->whenLoaded('subSource', function () {
                return [
                    'id' => $this->subSource->id ?? null,
                    'name' => $this->subSource->name ?? null,
                ];
            }),

            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id ?? null,
                    'name' => $this->country->name ?? null,
                ];
            }),

            'state' => $this->whenLoaded('state', function () {
                return [
                    'id' => $this->state->id ?? null,
                    'name' => $this->state->name ?? null,
                ];
            }),

            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id ?? null,
                    'name' => $this->city->name ?? null,
                ];
            }),

            'zone' => $this->whenLoaded('zone', function () {
                return [
                    'id' => $this->zone->id ?? null,
                    'name' => $this->zone->name ?? null,
                ];
            }),

            // 'status_relation' => $this->whenLoaded('statusRelation', function () {
            //     return [
            //         'id' => $this->statusRelation->id ?? null,
            //         'name' => $this->statusRelation->name ?? null,
            //     ];
            // }),

            // // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
        ];
    }
}
