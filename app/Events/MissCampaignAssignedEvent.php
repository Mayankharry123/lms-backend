<?php

/**
 * MissCampaignAssignedEvent
 * -----------------------------------------
 * Event triggered when a miss campaign is assigned to a user
 *
 * @package App\Events
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-19
 */

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class MissCampaignAssignedEvent
{
    use SerializesModels;

    /**
     * The ID of the miss campaign.
     *
     * @var int
     */
    protected int $missCampaignId;

    /**
     * The ID of the assigned user.
     *
     * @var int
     */
    protected int $userId;

    /**
     * Create a new event instance.
     *
     * @param int $missCampaignId
     * @param int $userId
     * @return void
     */
    public function __construct(int $missCampaignId, int $userId)
    {
        $this->missCampaignId = $missCampaignId;
        $this->userId = $userId;
    }

    /**
     * Get miss campaign ID.
     *
     * @return int
     */
    public function getMissCampaignId(): int
    {
        return $this->missCampaignId;
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
