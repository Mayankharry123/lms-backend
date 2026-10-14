<?php
/**
 * PaymentModeTypeResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentModeTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'status' => (string) $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s A'),
            
        ];
    }
}
