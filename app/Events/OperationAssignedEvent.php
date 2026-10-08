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

    protected int $operationId;
    protected int $assignToUserId;
    protected ?int $assignedByUserId;
    protected ?int $previousAssignToUserId;
    protected ?string $comment;

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

    public function getOperationId(): int
    {
        return $this->operationId;
    }

    public function getAssignToUserId(): int
    {
        return $this->assignToUserId;
    }

    public function getAssignedByUserId(): ?int
    {
        return $this->assignedByUserId;
    }

    public function getPreviousAssignToUserId(): ?int
    {
        return $this->previousAssignToUserId;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
