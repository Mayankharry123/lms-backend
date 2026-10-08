<?php
/**
 * FinanceStatusChangedEvent
 *
 * @package App\Events
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class FinanceStatusChangedEvent
{
    use SerializesModels;

    protected int $financeRecordId;
    protected ?int $previousStatusId;
    protected ?string $previousStatusName;
    protected int $newStatusId;
    protected ?string $newStatusName;
    protected ?int $updatedByUserId;
    protected ?string $updatedByUserName;
    protected ?string $comment;
    protected $timestamp;

    public function __construct(
        int $financeRecordId,
        ?int $previousStatusId,
        ?string $previousStatusName,
        int $newStatusId,
        ?string $newStatusName,
        ?int $updatedByUserId = null,
        ?string $updatedByUserName = null,
        ?string $comment = null,
        $timestamp = null
    ) {
        $this->financeRecordId = $financeRecordId;
        $this->previousStatusId = $previousStatusId;
        $this->previousStatusName = $previousStatusName;
        $this->newStatusId = $newStatusId;
        $this->newStatusName = $newStatusName;
        $this->updatedByUserId = $updatedByUserId;
        $this->updatedByUserName = $updatedByUserName;
        $this->comment = $comment;
        $this->timestamp = $timestamp ?? now();
    }

    public function getFinanceRecordId(): int
    {
        return $this->financeRecordId;
    }

    public function getPreviousStatusId(): ?int
    {
        return $this->previousStatusId;
    }

    public function getPreviousStatusName(): ?string
    {
        return $this->previousStatusName;
    }

    public function getNewStatusId(): int
    {
        return $this->newStatusId;
    }

    public function getNewStatusName(): ?string
    {
        return $this->newStatusName;
    }

    public function getUpdatedByUserId(): ?int
    {
        return $this->updatedByUserId;
    }

    public function getUpdatedByUserName(): ?string
    {
        return $this->updatedByUserName;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
