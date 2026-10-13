<?php
/**
 * Event LeadCallStatusAddedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

use Carbon\CarbonInterface;

class LeadCallStatusAddedEvent
{
    /**
     * The ID of the lead.
     *
     * @var int
     */
    protected int $leadId;

    /**
     * The ID of the new call status.
     *
     * @var int
     */
    protected int $callStatusId;

    /**
     * The ID of the previous call status.
     *
     * @var int|null
     */
    protected ?int $previousCallStatusId;

    /**
     * The ID of the user who updated the call status.
     *
     * @var int
     */
    protected int $updatedByUserId;

    /**
     * The timestamp of when the call status was updated.
     *
     * @var CarbonInterface
     */
    protected $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $leadId
     * @param int $callStatusId
     * @param int|null $previousCallStatusId
     * @param int $updatedByUserId
     * @param CarbonInterface|null $timestamp
     * @return void
     */
    public function __construct(
        int $leadId,
        int $callStatusId,
        ?int $previousCallStatusId,
        int $updatedByUserId,
        ?CarbonInterface $timestamp = null
    ) {
        $this->leadId = $leadId;
        $this->callStatusId = $callStatusId;
        $this->previousCallStatusId = $previousCallStatusId;
        $this->updatedByUserId = $updatedByUserId;
        $this->timestamp = $timestamp ?? now();
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
     * Get call status ID.
     *
     * @return int
     */
    public function getCallStatusId(): int
    {
        return $this->callStatusId;
    }

    /**
     * Get previous call status ID.
     *
     * @return int|null
     */
    public function getPreviousCallStatusId(): ?int
    {
        return $this->previousCallStatusId;
    }

    /**
     * Get updated by user ID.
     *
     * @return int
     */
    public function getUpdatedByUserId(): int
    {
        return $this->updatedByUserId;
    }

    /**
     * Get timestamp of the status update.
     *
     * @return CarbonInterface
     */
    public function getTimestamp(): CarbonInterface
    {
        return $this->timestamp;
    }
}