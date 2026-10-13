<?php
/**
 * CountryResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// Import StateResource for relationship handling
use App\Http\Resources\StateResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Define the structure for JSON response
        return [
            'id' => $this->id,
            'name' => $this->name,
            // 'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            // 'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),

            // Relationships
            // States will only be loaded when eager loaded using with('states') in the controller
            //'states' => StateResource::collection($this->whenLoaded('states')),
        ];
    }
}
