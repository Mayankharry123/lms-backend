<?php
/**
 * LeadSourceResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadSourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // 'created_at' => $this->created_at?->format('Y-m-d h:i:s A'),
            // 'updated_at' => $this->updated_at?->format('Y-m-d h:i:s A'),
        ];
    }
}
