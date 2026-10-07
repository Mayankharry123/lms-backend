<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlannerResource extends JsonResource
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
            'planner_id' => $this->id,
            'planner_name' => $this->creator?->name ?? $this->brief?->assignedUser?->name ?? null,
            'planner_user_id' => $this->creator?->id ?? $this->brief?->assign_user_id ?? null,
            'planner' => [
                'id' => $this->id,
                'user_id' => $this->creator?->id ?? $this->brief?->assign_user_id ?? null,
                'name' => $this->creator?->name ?? $this->brief?->assignedUser?->name ?? null,
                'email' => $this->creator?->email ?? $this->brief?->assignedUser?->email ?? null,
            ],
           // 'uuid' => $this->uuid,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Relationships
            'brief' => $this->whenLoaded('brief', function () {
                return [
                    'id' => $this->brief->id,
                    'uuid' => $this->brief->uuid,
                    'name' => $this->brief->name,
                    'product_name' => $this->brief->product_name,
                    'assigned_user' => $this->brief->assignedUser ? [
                        'id' => $this->brief->assignedUser->id,
                        'name' => $this->brief->assignedUser->name,
                        'email' => $this->brief->assignedUser->email,
                    ] : null,
                    'organisation' => $this->brief->contactPerson?->organisation ? [
                        'id' => $this->brief->contactPerson->organisation->id,
                        'name' => $this->brief->contactPerson->organisation->name,
                    ] : null,
                    'department' => $this->brief->contactPerson?->department ? [
                        'id' => $this->brief->contactPerson->department->id,
                        'name' => $this->brief->contactPerson->department->name,
                    ] : null,
                ];
            }),

            'organisation' => $this->getOrganisationData(),
            'department' => $this->getDepartmentData(),

            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'planner_status' => $this->whenLoaded('plannerStatus', function () {
                return [
                    'id' => $this->plannerStatus->id,
                    'name' => $this->plannerStatus->name,
                ];
            }),

            // Files with URLs
            'submitted_plan' => $this->getSubmittedPlanWithUrls(),
            'submitted_plan_count' => $this->getSubmittedPlanCount(),
            'backup_plan' => $this->backup_plan,
            'backup_plan_url' => $this->getBackupPlanUrl(),
            'has_backup_plan' => $this->hasBackupPlan(),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s A'),
        ];
    }

    /**
     * Get submitted plan with URLs.
     *
     * @return array|null
     */
    private function getSubmittedPlanWithUrls(): ?array
    {
        if (!$this->hasSubmittedPlans()) {
            return null;
        }

        return array_map(function ($filePath) {
            // Now submitted_plan contains just paths (strings)
            $path = is_string($filePath) ? $filePath : ($filePath['path'] ?? $filePath);
            return [
                'path' => $path,
                'url' => $this->getFileUrlFromPath($path),
                'name' => basename($path),
            ];
        }, $this->submitted_plan);
    }

    /**
     * Get backup plan URL.
     *
     * @return string|null
     */
    private function getBackupPlanUrl(): ?string
    {
        if (!$this->backup_plan) {
            return null;
        }

        return $this->getFileUrlFromPath($this->backup_plan);
    }

    /**
     * Generate file URL from path using Storage.
     *
     * @param string $path
     * @return string|null
     */
    private function getFileUrlFromPath(string $path): ?string
    {
        try {
            $disk = config('filesystems.default', 'local');
            
            // Get base URL from storage
            $appUrl = rtrim(config('app.url'), '/');
            
            // For local disk, construct URL manually with proper encoding
            if ($disk === 'local') {
                // Encode only the filename part, keep directory structure intact
                $pathParts = explode('/', $path);
                $filename = array_pop($pathParts);
                $directory = implode('/', $pathParts);
                
                // Remove 'public' from directory path
                $directory = str_replace('public/', '', $directory);
                
                // Construct URL with encoded filename
                $url = $appUrl . '/storage/' . $directory . '/' . rawurlencode($filename);
                return $url;
            }
            
            // For other disks (S3, etc.), construct URL directly
            $relativeUrl = '/storage/' . $path;
            
            if (str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://')) {
                return $relativeUrl;
            }
            
            return $appUrl . $relativeUrl;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the status label.
     *
     * @return string
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            '1' => 'Active',
            '2' => 'Deactivated',
            '15' => 'Deleted',
            default => 'Unknown',
        };
    }

    /**
     * Get organisation data for the planner.
     *
     * @return array|null
     */
    private function getOrganisationData(): ?array
    {
        if ($this->relationLoaded('brief') && $this->brief?->contactPerson?->organisation) {
            return [
                'id' => $this->brief->contactPerson->organisation->id,
                'name' => $this->brief->contactPerson->organisation->name,
            ];
        }

        if ($this->relationLoaded('creator') && $this->creator?->organisation) {
            return [
                'id' => $this->creator->organisation->id,
                'name' => $this->creator->organisation->name,
            ];
        }

        return null;
    }

    /**
     * Get department data for the planner.
     *
     * @return array|null
     */
    private function getDepartmentData(): ?array
    {
        if ($this->relationLoaded('brief') && $this->brief?->contactPerson?->department) {
            return [
                'id' => $this->brief->contactPerson->department->id,
                'name' => $this->brief->contactPerson->department->name,
            ];
        }

        if ($this->relationLoaded('creator') && $this->creator?->relationLoaded('departments') && $this->creator?->departments?->isNotEmpty()) {
            $dept = $this->creator->departments->first();
            return [
                'id' => $dept->id,
                'name' => $dept->name,
            ];
        }

        return null;
    }
}
