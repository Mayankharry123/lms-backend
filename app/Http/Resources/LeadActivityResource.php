<?php

namespace App\Http\Resources;

use App\Models\LeadAssignHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadActivityResource extends JsonResource
{
    public function __construct($resource, protected ?LeadAssignHistory $history = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $reminderEnabled = (bool) ($this->history?->reminder ?? false);

        $data = [
            'call_status_relation' => $this->relationSummary($this->callStatusRelation),
            'lead_status_relation' => $this->relationSummary($this->leadStatusRelation),
            'reminder' => $reminderEnabled,
        ];

        if ($reminderEnabled) {
            $data['reminder_at'] = $this->history?->reminder_at
                ? $this->history->reminder_at->format('Y-m-d H:i:s')
                : null;
            $data['reminder_before'] = $this->history?->reminder_before !== null
                ? (int) $this->history->reminder_before
                : null;
            $data['reminder_before_unit'] = $this->history?->reminder_before_unit;
        }

        return $data;
    }

    /**
     * @param mixed $relation
     * @return array{id: int, name: string}|null
     */
    private function relationSummary($relation): ?array
    {
        if (!$relation) {
            return null;
        }

        return [
            'id' => (int) $relation->id,
            'name' => $relation->name,
        ];
    }
}
