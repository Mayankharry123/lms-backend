<?php
/**
 * PermissionResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            //'uuid' => $this->uuid,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'url' => $this->url ?? null,
            'icon_file' => $this->icon_file,
            'icon_text' => $this->icon_text ?? null,
            'description' => $this->description,
            'is_parent' => $this->is_parent,
            //'status' => $this->status,
            'order' => $this->order,
            'children' => $this->when(
                $this->relationLoaded('children') && $this->children->isNotEmpty(),
                fn() => PermissionResource::collection($this->children)
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            //'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
            //'deleted_at' => $this->deleted_at->toIso8601String(),
        ];
    }
}

