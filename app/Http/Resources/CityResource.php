<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// Import Country and State resources for relationship handling
use App\Http\Resources\CountryResource;
use App\Http\Resources\StateResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            // 'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            // 'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),

            // Relationships
            'country' => $this->whenLoaded('country', function () {
                return $this->country ? new CountryResource($this->country) : null;
            }),
            'state' => $this->whenLoaded('state', function () {
                return $this->state ? new StateResource($this->state) : null;
            }),
        ];
    }
}
