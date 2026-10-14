<?php
/**
 * ProfileResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProfileResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'bio' => $this->bio,
            'website' => $this->website,
            'location' => $this->location,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'social_links' => $this->social_links,
            'preferences' => $this->preferences,
        ]);
    }
}
