<?php
/**
 * Event FinanceRecordAssignedEvent
 * 
 * @author Achal Sharma
 * @package App\Events
 * @version 1.0.1
 */
namespace App\Events;

use Illuminate\Queue\SerializesModels;

class FinanceRecordAssignedEvent
{
    use SerializesModels;

    /**
     * The ID of the finance record.
     *
     * @var int
     */
    protected int $financeRecordId;

    /**
     * The ID of the user the finance record is assigned to.
     *
     * @var int
     */
    protected int $assignToUserId;

    /**
     * The ID of the user who assigned the finance record.
     *
     * @var int|null
     */
    protected ?int $assignedByUserId;

    /**
     * The ID of the user who was previously assigned to this finance record.
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
     * @param int $financeRecordId
     * @param int $assignToUserId
     * @param int|null $assignedByUserId
     * @param int|null $previousAssignToUserId
     * @param string|null $comment
     * @return void
     */
    public function __construct(
        int $financeRecordId,
        int $assignToUserId,
        ?int $assignedByUserId = null,
        ?int $previousAssignToUserId = null,
        ?string $comment = null
    ) {
        $this->financeRecordId = $financeRecordId;
        $this->assignToUserId = $assignToUserId;
        $this->assignedByUserId = $assignedByUserId;
        $this->previousAssignToUserId = $previousAssignToUserId;
        $this->comment = $comment;
    }

    /**
     * Get finance record ID.
     *
     * @return int
     */
    public function getFinanceRecordId(): int
    {
        return $this->financeRecordId;
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
