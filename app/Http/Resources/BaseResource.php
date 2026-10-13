<?php
/**
 * BaseResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

abstract class BaseResource extends JsonResource
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
            //'uuid' => $this->uuid ?? null,
            // 'created_at' => $this->created_at?->setTimezone('Asia/Kolkata')->toISOString(),
            // 'updated_at' => $this->updated_at?->setTimezone('Asia/Kolkata')->toISOString(),
            'created_at_formatted' => $this->created_at ? $this->created_at->format('Y-m-d h:i:s A') : null,
            'updated_at_formatted' => $this->updated_at ? $this->updated_at->format('Y-m-d h:i:s A') : null,
            'created_at_human' => $this->created_at_human ?? null,
            'updated_at_human' => $this->updated_at_human ?? null,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => Carbon::now('Asia/Kolkata')->format('Y-m-d h:i:s A'),
            ]
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response): void
    {
        $response->header('Content-Tzype', 'application/json');
    }
}
