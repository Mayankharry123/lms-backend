<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefStatusResource extends JsonResource
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
            'status' => $this->status,
            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s A') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s A') : null,
        ];
    }
}
