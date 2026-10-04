<?php

/**
 * CostSheetStatus Resource
 * -----------------------------------------
 * Transforms CostSheetStatus model data into JSON API responses.
 *
 * @package App\Http\Resources
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CostSheetStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s A'),
        ];
    }
}
