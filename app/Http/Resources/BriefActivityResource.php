<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BriefActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $reminderEnabled = (bool) $this->reminder;

        $data = [
            'current_user_id' => $this->assign_by_id,
            'current_user_name' => $this->assignedBy?->name,
            'brief_comment' => $this->comment,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'reminder' => $reminderEnabled,
        ];

        if ($reminderEnabled) {
            $data['reminder_at'] = $this->reminder_at?->format('Y-m-d H:i:s');
            $data['reminder_before'] = $this->reminder_before !== null
                ? (int) $this->reminder_before
                : null;
            $data['reminder_before_unit'] = $this->reminder_before_unit;
        }

        return $data;
    }
}