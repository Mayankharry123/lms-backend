<?php
/**
 * Event BriefStatusChangedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

class BriefStatusChangedEvent
{
    /**
     * The ID of the brief.
     *
     * @var int|mixed
     */
    protected $briefId;

    /**
     * The name of the brief.
     *
     * @var string|mixed
     */
    protected $briefName;

    /**
     * The previous status ID.
     *
     * @var int|mixed|null
     */
    protected $previousStatusId;

    /**
     * The previous status name.
     *
     * @var string|mixed|null
     */
    protected $previousStatusName;

    /**
     * The new status ID.
     *
     * @var int|mixed
     */
    protected $newStatusId;

    /**
     * The new status name.
     *
     * @var string|mixed
     */
    protected $newStatusName;

    /**
     * The user ID who updated the brief status.
     *
     * @var int|mixed
     */
    protected $updatedByUserId;

    /**
     * The username of the user who updated the brief status.
     *
     * @var string|mixed
     */
    protected $updatedByUserName;

    /**
     * The timestamp of when the status changed.
     *
     * @var mixed
     */
    protected $timestamp;

    /**
     * Create a new event instance.
     *
     * @param mixed $briefId
     * @param mixed $briefName
     * @param mixed $previousStatusId
     * @param mixed $previousStatusName
     * @param mixed $newStatusId
     * @param mixed $newStatusName
     * @param mixed $updatedByUserId
     * @param mixed $updatedByUserName
     * @param mixed $timestamp
     * @return void
     */
    public function __construct($briefId, $briefName, $previousStatusId, $previousStatusName, $newStatusId, $newStatusName, $updatedByUserId, $updatedByUserName, $timestamp = null)
    {
        $this->briefId = $briefId;
        $this->briefName = $briefName;
        $this->previousStatusId = $previousStatusId;
        $this->previousStatusName = $previousStatusName;
        $this->newStatusId = $newStatusId;
        $this->newStatusName = $newStatusName;
        $this->updatedByUserId = $updatedByUserId;
        $this->updatedByUserName = $updatedByUserName;
        $this->timestamp = $timestamp ?? now();
    }

    /**
     * Get brief ID.
     *
     * @return mixed
     */
    public function getBriefId()
    {
        return $this->briefId;
    }

    /**
     * Get brief name.
     *
     * @return mixed
     */
    public function getBriefName()
    {
        return $this->briefName;
    }

    /**
     * Get previous status ID.
     *
     * @return mixed
     */
    public function getPreviousStatusId()
    {
        return $this->previousStatusId;
    }

    /**
     * Get previous status name.
     *
     * @return mixed
     */
    public function getPreviousStatusName()
    {
        return $this->previousStatusName;
    }

    /**
     * Get new status ID.
     *
     * @return mixed
     */
    public function getNewStatusId()
    {
        return $this->newStatusId;
    }

    /**
     * Get new status name.
     *
     * @return mixed
     */
    public function getNewStatusName()
    {
        return $this->newStatusName;
    }

    /**
     * Get updated by user ID.
     *
     * @return mixed
     */
    public function getUpdatedByUserId()
    {
        return $this->updatedByUserId;
    }

    /**
     * Get updated by user name.
     *
     * @return mixed
     */
    public function getUpdatedByUserName()
    {
        return $this->updatedByUserName;
    }

    /**
     * Get timestamp of the status change.
     *
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
