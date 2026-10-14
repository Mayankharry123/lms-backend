<?php

/**
 * MissCampaign Resource
 * -----------------------------------------
 * Transforms MissCampaign model data into JSON responses for API endpoints.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class MissCampaignResource extends JsonResource
{
    /**
     * Check if the authenticated user can view assignment fields.
     *
     * @return bool
     */
    private function canViewAssignmentFields(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super Admin can always view assignment fields
        /** @noinspection PhpUndefinedMethodInspection */
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // User who assigned this campaign can view
        if ($this->assign_by && $this->assign_by == $user->id) {
            return true;
        }

        // User who is assigned to this campaign can view
        if ($this->assign_to && $this->assign_to == $user->id) {
            return true;
        }

        return false;
    }

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
            //'status' => $this->status,
            'image_path' => $this->image_path,
            'image_url' => $this->when($this->image_path, function() {
                // use HandlesFileUploads trait helper on the underlying model
                return $this->getFileUrl($this->image_path);
            }),

            // Relationships (IDs)
            // 'brand_id' => $this->brand_id,
            // 'lead_source_id' => $this->lead_source_id,
            // 'lead_sub_source_id' => $this->lead_sub_source_id,

            // Relationships (Objects)
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'lead_source' => $this->whenLoaded('leadSource', function () {
                return [
                    'id' => $this->leadSource->id,
                    'name' => $this->leadSource->name,
                ];
            }),
            'lead_sub_source' => $this->whenLoaded('leadSubSource', function () {
                return $this->leadSubSource ? [
                    'id' => $this->leadSubSource->id,
                    'name' => $this->leadSubSource->name,
                ] : null;
            }),
            'media_type' => $this->whenLoaded('mediaType', function () {
                return $this->mediaType ? [
                    'id' => $this->mediaType->id,
                    'name' => $this->mediaType->name,
                ] : null;
            }),
            'industry' => $this->whenLoaded('industry', function () {
                return $this->industry ? [
                    'id' => $this->industry->id,
                    'name' => $this->industry->name,
                ] : null;
            }),
            'country' => $this->whenLoaded('country', function () {
                return $this->country ? [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                ] : null;
            }),
            'state' => $this->whenLoaded('state', function () {
                return $this->state ? [
                    'id' => $this->state->id,
                    'name' => $this->state->name,
                ] : null;
            }),
            'city' => $this->whenLoaded('city', function () {
                return $this->city ? [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ] : null;
            }),
            'organisation' => $this->whenLoaded('lead', function () {
                return $this->lead && $this->lead->relationLoaded('organisation') && $this->lead->organisation ? [
                    'id' => $this->lead->organisation->id,
                    'name' => $this->lead->organisation->name,
                ] : null;
            }),
            'assign_by' => $this->when(
                $this->relationLoaded('assignBy') && $this->canViewAssignmentFields(),
                function () {
                    return $this->assignBy ? [
                        'id' => $this->assignBy->id,
                        'name' => $this->assignBy->name,
                    ] : null;
                }
            ),
            'assign_to' => $this->when(
                $this->relationLoaded('assignTo') && $this->canViewAssignmentFields(),
                function () {
                    return $this->assignTo ? [
                        'id' => $this->assignTo->id,
                        'name' => $this->assignTo->name,
                    ] : null;
                }
            ),

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
            //'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}