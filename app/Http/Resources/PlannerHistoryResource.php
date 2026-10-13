<?php
/**
 * PlannerHistoryResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannerHistoryResource extends JsonResource
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
            'status_label' => $this->getStatusLabel(),

            // Relationships
            'brief' => $this->whenLoaded('brief', function () {
                return [
                    'id' => $this->brief->id,
                    'name' => $this->brief->name,
                ];
            }),

            'planner' => $this->whenLoaded('planner', function () {
                return [
                    'id' => $this->planner->id,
                    'submitted_plan' => $this->getSubmittedPlanWithUrls(),
                    'backup_plan' => $this->getBackupPlanWithUrl(),
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),

            'planner_status' => $this->whenLoaded('plannerStatus', function () {
                return [
                    'id' => $this->plannerStatus->id,
                    'name' => $this->plannerStatus->name,
                    'slug' => $this->plannerStatus->slug,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
        ];
    }

    /**
     * Get submitted plan with URLs.
     *
     * @return array|null
     */
    private function getSubmittedPlanWithUrls(): ?array
    {
        if (empty($this->submitted_plan) || !is_array($this->submitted_plan)) {
            return null;
        }

        return array_map(function ($filePath) {
            $path = is_string($filePath) ? $filePath : ($filePath['path'] ?? $filePath);
            return [
                'path' => $path,
                'url' => $this->getFileUrlFromPath($path),
                'name' => basename($path),
            ];
        }, $this->submitted_plan);
    }

    /**
     * Get backup plan with URL.
     *
     * @return array|null
     */
    private function getBackupPlanWithUrl(): ?array
    {
        if (empty($this->backup_plan)) {
            return null;
        }

        $path = is_string($this->backup_plan) ? $this->backup_plan : ($this->backup_plan['path'] ?? $this->backup_plan);
        
        return [
            'path' => $path,
            'url' => $this->getFileUrlFromPath($path),
            'name' => basename($path),
        ];
    }

    /**
     * Generate file URL from path.
     *
     * @param string $path
     * @return string|null
     */
    private function getFileUrlFromPath(string $path): ?string
    {
        try {
            $appUrl = rtrim(config('app.url'), '/');
            
            // Remove 'public/' from path if present
            $path = str_replace('public/', '', $path);
            
            // URL encode only the filename part
            $pathParts = explode('/', $path);
            $filename = array_pop($pathParts);
            $directory = implode('/', $pathParts);
            
            $url = $appUrl . '/storage/' . $directory . '/' . rawurlencode($filename);
            
            return $url;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get status label
     *
     * @return string
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            '1' => 'Active',
            '2' => 'Deactivated',
            '15' => 'Soft Deleted',
            default => 'Unknown',
        };
    }
}
