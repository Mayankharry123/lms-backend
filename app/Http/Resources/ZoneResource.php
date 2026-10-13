<?php
/**
 * ZoneResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Define the JSON structure for the response
        return [
            'id' => $this->id,
            'name' => $this->name,
            //'slug' => $this->slug,
            // 'status' => $this->status,
            // 'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            // 'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
        ];
    }

    /**
     * Helper function to get readable status
     */
    protected function getStatusLabel()
    {
        switch ($this->status) {
            case '1':
                return 'Active';
            case '2':
                return 'Deactivated';
            case '15':
                return 'User Soft Delete';
            default:
                return 'Unknown';
        }
    }
}