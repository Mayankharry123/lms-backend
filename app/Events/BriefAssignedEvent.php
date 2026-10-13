<?php
/**
 * Event BriefAssignedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

use Illuminate\Queue\SerializesModels;

class BriefAssignedEvent
{
    use SerializesModels;

    /**
     * The ID of the brief.
     *
     * @var int
     */
    protected int $briefId;

    /**
     * The ID of the assigned user.
     *
     * @var int
     */
    protected int $userId;

    /**
     * Create a new event instance.
     *
     * @param int $briefId
     * @param int $userId
     * @return void
     */
    public function __construct(int $briefId, int $userId)
    {
        $this->briefId = $briefId;
        $this->userId = $userId;
    }

    /**
     * Get brief ID.
     *
     * @return int
     */
    public function getBriefId(): int
    {
        return $this->briefId;
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