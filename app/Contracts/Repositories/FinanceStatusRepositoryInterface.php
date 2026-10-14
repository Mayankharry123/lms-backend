<?php

/**
 * FinanceStatus Repository Interface
 * -----------------------------------------
 * Defines the contract for FinanceStatus repository operations, including CRUD and pagination methods.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Contracts\Repositories;

use App\Models\FinanceStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FinanceStatusRepositoryInterface
{
    /**
     * Get all finance statuses with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator<FinanceStatus>
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a finance status by ID
     */
    public function find(int $id): ?FinanceStatus;

    /**
     * Find a finance status by UUID
     */
    public function findByUuid(string $uuid): ?FinanceStatus;

    /**
     * Find a finance status by name
     */
    public function findByName(string $name): ?FinanceStatus;

    /**
     * Find a finance status by slug
     */
    public function findBySlug(string $slug): ?FinanceStatus;

    /**
     * Find the first active finance status
     */
    public function findFirstActive(): ?FinanceStatus;

    /**
     * Create a new finance status
     */
    public function create(array $data): FinanceStatus;

    /**
     * Update a finance status by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Soft delete a finance status by ID
     */
    public function delete(int $id): bool;

    /**
     * Search finance statuses by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;
}
