<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class FinanceRecordAssignedEvent
{
    use SerializesModels;

    protected int $financeRecordId;
    protected int $assignToUserId;
    protected ?int $assignedByUserId;
    protected ?int $previousAssignToUserId;
    protected ?string $comment;

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

    public function getFinanceRecordId(): int
    {
        return $this->financeRecordId;
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
