<?php
/**
 *  Operation Status Changed Event
 *
 * @package App\Events
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class OperationStatusChangedEvent
{
    use SerializesModels;

    /**
     * The ID of the operation.
     *
     * @var int
     */
    protected int $operationId;

    /**
     * The previous status ID.
     *
     * @var int|null
     */
    protected ?int $previousStatusId;

    /**
     * The previous status name.
     *
     * @var string|null
     */
    protected ?string $previousStatusName;

    /**
     * The new status ID.
     *
     * @var int
     */
    protected int $newStatusId;

    /**
     * The new status name.
     *
     * @var string|null
     */
    protected ?string $newStatusName;

    /**
     * The ID of the user who updated the status.
     *
     * @var int|null
     */
    protected ?int $updatedByUserId;

    /**
     * The name of the user who updated the status.
     *
     * @var string|null
     */
    protected ?string $updatedByUserName;

    /**
     * Optional comment on status change.
     *
     * @var string|null
     */
    protected ?string $comment;

    /**
     * The timestamp of when the status was updated.
     *
     * @var mixed
     */
    protected $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $operationId
     * @param int|null $previousStatusId
     * @param string|null $previousStatusName
     * @param int $newStatusId
     * @param string|null $newStatusName
     * @param int|null $updatedByUserId
     * @param string|null $updatedByUserName
     * @param string|null $comment
     * @param mixed $timestamp
     * @return void
     */
    public function __construct(
        int $operationId,
        ?int $previousStatusId,
        ?string $previousStatusName,
        int $newStatusId,
        ?string $newStatusName,
        ?int $updatedByUserId = null,
        ?string $updatedByUserName = null,
        ?string $comment = null,
        $timestamp = null
    ) {
        $this->operationId = $operationId;
        $this->previousStatusId = $previousStatusId;
        $this->previousStatusName = $previousStatusName;
        $this->newStatusId = $newStatusId;
        $this->newStatusName = $newStatusName;
        $this->updatedByUserId = $updatedByUserId;
        $this->updatedByUserName = $updatedByUserName;
        $this->comment = $comment;
        $this->timestamp = $timestamp ?? now();
    }

    /**
     * Get operation ID.
     *
     * @return int
     */
    public function getOperationId(): int
    {
        return $this->operationId;
    }

    /**
     * Get previous status ID.
     *
     * @return int|null
     */
    public function getPreviousStatusId(): ?int
    {
        return $this->previousStatusId;
    }

    /**
     * Get previous status name.
     *
     * @return string|null
     */
    public function getPreviousStatusName(): ?string
    {
        return $this->previousStatusName;
    }

    /**
     * Get new status ID.
     *
     * @return int
     */
    public function getNewStatusId(): int
    {
        return $this->newStatusId;
    }

    /**
     * Get new status name.
     *
     * @return string|null
     */
    public function getNewStatusName(): ?string
    {
        return $this->newStatusName;
    }

    /**
     * Get updated by user ID.
     *
     * @return int|null
     */
    public function getUpdatedByUserId(): ?int
    {
        return $this->updatedByUserId;
    }

    /**
     * Get updated by username.
     *
     * @return string|null
     */
    public function getUpdatedByUserName(): ?string
    {
        return $this->updatedByUserName;
    }

    /**
     * Get status update comment.
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
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
