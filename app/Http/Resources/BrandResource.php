<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,

            // Relationships (IDs)
            // 'brand_type_id' => $this->brand_type_id,
            //'industry_id' => $this->industry_id,
            // 'zone_id' => $this->zone_id,
            // 'country_id' => $this->country_id,
            // 'state_id' => $this->state_id,
            // 'city_id' => $this->city_id,

            // Location Information
            'website' => $this->website,
            'address' => $this->address,
            'gst_no' => $this->gst_no,
            'gst_number' => $this->gst_no,
            'gst_numbers' => $this->gst_numbers ?? ($this->gst_no ? [$this->gst_no] : []),
            'postal_code' => $this->postal_code,

            // Relationships (Objects)
            'brand_type' => $this->whenLoaded('brandType', function () {
                return [
                    'id'   => $this->brandType->id ?? null,
                    'name' => $this->brandType->name ?? null,
                ];
            }),

            'industry' => $this->whenLoaded('industry', function () {
                return [
                    'id'   => $this->industry->id ?? null,
                    'name' => $this->industry->name ?? null,
                ];
            }),

            'agencies' => $this->whenLoaded('agencies', function () {
                return $this->agencies->map(function ($agency) {
                    return [
                        'id'   => $agency->id,
                        'name' => $agency->name,
                    ];
                });
            }),

            'zone' => $this->whenLoaded('zone', function () {
                return [
                    'id'   => $this->zone->id ?? null,
                    'name' => $this->zone->name ?? null,
                ];
            }),

            'country' => $this->whenLoaded('country', function () {
                return [
                    'id'   => $this->country->id ?? null,
                    'name' => $this->country->name ?? null,
                ];
            }),

            'state' => $this->whenLoaded('state', function () {
                return [
                    'id'   => $this->state->id ?? null,
                    'name' => $this->state->name ?? null,
                ];
            }),

            'city' => $this->whenLoaded('city', function () {
                return [
                    'id'   => $this->city->id ?? null,
                    'name' => $this->city->name ?? null,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name ?? null,
                ];
            }),
            'contact_person_count' => $this->getContactPersonCount(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),   
            //'deleted_at' => $this->deleted_at->toIso8601String(),
        ];
    }
}