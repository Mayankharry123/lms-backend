<?php
/**
 * MeetingResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get attendees based on attendees_id JSON array
        $attendees = [];
        if ($this->attendees_id && is_array($this->attendees_id)) {
            $attendees = User::whereIn('id', $this->attendees_id)
                ->select('id', 'name', 'email')
                ->get()
                ->toArray();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'location' => $this->location,
            'agenda' => $this->agenda,
            'link' => $this->link,
            'url' => $this->getGoogleEventUrl(),
            'meetin_start_date' => $this->meeting_start_date ? $this->meeting_start_date->format('Y-m-d H:i A') : null,
            'meetin_end_date' => $this->meeting_end_date ? $this->meeting_end_date->format('Y-m-d H:i A') : null,
            'status' => $this->status,
            
            'lead' => $this->when($this->lead, [
                'id' => $this->lead->id ?? null,
                'name' => $this->lead->name ?? null,
                'email' => $this->lead->email ?? null,
            ]),
            
            'attendees' => $this->when($this->attendees_id, $attendees),
            'created_at' => $this->created_at->format('Y-m-d H:i:s A'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s A'),
        ];
    }
}