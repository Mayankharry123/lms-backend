<?php
/**
 * AgencyResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\AgencyTypeResource;

class AgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $brandData = null;
        
        if ($this->relationLoaded('brand') && $this->brand && count($this->brand) > 0) {
            $brandData = BrandResource::collection($this->brand);
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'contact_person_count' => isset($this->contact_persons_count)
                ? (int) $this->contact_persons_count
                : (int) $this->getContactPersonCount(),
            'agency_type' => $this->getAgencyTypeData(),
            'is_parent' => $this->getParentAgencyData(),
            'status' => $this->status,
            'childs' => $this->childs,
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
            'brand' => $brandData,
        ];
    }

    /**
     * Get agency type data
     */
    private function getAgencyTypeData()
    {
        if ($this->relationLoaded('agencyType') && $this->agencyType) {
            return [
                'id' => $this->agencyType->id,
                'name' => $this->agencyType->name,
            ];
        }
        return null;
    }

    /**
     * Get parent agency data
     */
    private function getParentAgencyData()
    {
        if ($this->relationLoaded('parentAgency') && $this->parentAgency) {
            return new AgencyResource($this->parentAgency);
        }
        return null;
    }
}