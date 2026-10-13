<?php
/**
 * Event LeadStatusChangedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

class LeadStatusChangedEvent
{
    /**
     * The ID of the lead.
     *
     * @var int
     */
    protected int $leadId;

    /**
     * The new status ID.
     *
     * @var int
     */
    protected int $status;

    /**
     * Create a new event instance.
     *
     * @param int $leadId
     * @param int $status
     * @return void
     */
    public function __construct(int $leadId, int $status)
    {
        $this->leadId = $leadId;
        $this->status = $status;
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
     * Get status ID.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}