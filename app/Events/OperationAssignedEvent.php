<?php
/**
 * OperationAssignedEvent
 *
 * @package App\Events
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class OperationAssignedEvent
{
    use SerializesModels;

    /**
     * The ID of the operation.
     *
     * @var int
     */
    protected int $operationId;

    /**
     * The ID of the user the operation is assigned to.
     *
     * @var int
     */
    protected int $assignToUserId;

    /**
     * The ID of the user who assigned the operation.
     *
     * @var int|null
     */
    protected ?int $assignedByUserId;

    /**
     * The ID of the user who was previously assigned to this operation.
     *
     * @var int|null
     */
    protected ?int $previousAssignToUserId;

    /**
     * Optional assignment comment.
     *
     * @var string|null
     */
    protected ?string $comment;

    /**
     * Create a new event instance.
     *
     * @param int $operationId
     * @param int $assignToUserId
     * @param int|null $assignedByUserId
     * @param int|null $previousAssignToUserId
     * @param string|null $comment
     * @return void
     */
    public function __construct(
        int $operationId,
        int $assignToUserId,
        ?int $assignedByUserId = null,
        ?int $previousAssignToUserId = null,
        ?string $comment = null
    ) {
        $this->operationId = $operationId;
        $this->assignToUserId = $assignToUserId;
        $this->assignedByUserId = $assignedByUserId;
        $this->previousAssignToUserId = $previousAssignToUserId;
        $this->comment = $comment;
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
     * Get assigned to user ID.
     *
     * @return int
     */
    public function getAssignToUserId(): int
    {
        return $this->assignToUserId;
    }

    /**
     * Get assigned by user ID.
     *
     * @return int|null
     */
    public function getAssignedByUserId(): ?int
    {
        return $this->assignedByUserId;
    }

    /**
     * Get previous assigned to user ID.
     *
     * @return int|null
     */
    public function getPreviousAssignToUserId(): ?int
    {
        return $this->previousAssignToUserId;
    }

    /**
     * Get assignment comment.
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
