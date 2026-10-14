<?php
/**
 * Event LeadAssignedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

use Illuminate\Queue\SerializesModels;

class LeadAssignedEvent
{
    use SerializesModels;

    /**
     * The ID of the lead.
     *
     * @var int
     */
    protected int $leadId;

    /**
     * The ID of the assigned user.
     *
     * @var int
     */
    protected int $userId;

    /**
     * Create a new event instance.
     *
     * @param int $leadId
     * @param int $userId
     * @return void
     */
    public function __construct(int $leadId, int $userId)
    {
        $this->leadId = $leadId;
        $this->userId = $userId;
    }

    /**
     * Get lead ID.
     *
     * @return int
     */
    public function getLeadId(): int
    {
        return $this->leadId;
    }

    /**
     * Get user ID.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
}