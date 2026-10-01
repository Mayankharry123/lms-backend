<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\DateTimeFormatter;

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
            /**
             * Updated brief status response and standardized timestamp formatting.
             */
            'created_at' => DateTimeFormatter::format($this->created_at),
            'updated_at' => DateTimeFormatter::format($this->updated_at),
        ];
    }
}
