<?php

/**
 * MediaType Service
 * -----------------------------------------
 * Handles business logic for media type operations, including validation and data transformation.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Services;

use App\Contracts\Repositories\MediaTypeRepositoryInterface;
use App\Models\MediaType;
use Illuminate\Support\Str;

class MediaTypeService
{
    /**
     * @var MediaTypeRepositoryInterface
     */
    protected MediaTypeRepositoryInterface $repository;

    /**
     * MediaTypeService constructor.
     *
     * @param MediaTypeRepositoryInterface $repository
     */
    public function __construct(MediaTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all media types with pagination.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15)
    {
        return $this->repository->getAllMediaTypes($perPage);
    }

    /**
     * Find a media type by ID.
     *
     * @param int $id
     * @return MediaType
     */
    public function findById(int $id): MediaType
    {
        return $this->repository->getMediaTypeById($id);
    }

    /**
     * Create a new media type.
     *
     * @param array $data
     * @return MediaType
     */
    public function create(array $data): MediaType
    {
        $data['slug'] = Str::slug($data['name']);
        return $this->repository->createMediaType($data);
    }

    /**
     * Delete a media type by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->deleteMediaType($id);
    }

    /**
     * Update a media type by ID.
     *
     * @param int $id
     * @param array $data
     * @return MediaType
     */
    public function update(int $id, array $data): MediaType
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->repository->updateMediaType($id, $data);
    }

    /**
     * List all media types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function list()
    {
        return $this->repository->listMediaTypes();
    }
}
