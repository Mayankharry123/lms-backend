<?php
/**
 * NotificationResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // start with the base data but strip out unwanted timestamp helpers
        $data = parent::toArray($request);

        // drop the formatted/human timestamp keys added by BaseResource
        $data = \Illuminate\Support\Arr::except($data, [
            'created_at_formatted',
            'updated_at_formatted',
            'created_at_human',
            'updated_at_human',
        ]);

        return array_merge($data, [
            //'type' => $this->type,
            //'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'data' => $this->data,
            'category' => $this->category,
            'read_at' => $this->read_at ? $this->read_at->format('Y-m-d h:i:s A') : null,
            'created_at' => $this->created_at->format('Y-m-d h:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d h:i:s A'),
        ]);
    }
}
