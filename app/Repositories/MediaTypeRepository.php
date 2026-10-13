<?php

/**
 * MediaType Repository
 * -----------------------------------------
 * Implements the MediaType repository interface, providing data access layer for media types.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Repositories;

use App\Contracts\Repositories\MediaTypeRepositoryInterface;
use App\Models\MediaType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MediaTypeRepository implements MediaTypeRepositoryInterface
{
    /**
     * @var MediaType
     */
    protected MediaType $model;

    /**
     * MediaTypeRepository constructor.
     *
     * @param MediaType $model
     */
    public function __construct(MediaType $model)
    {
        $this->model = $model;
    }

    /**
     * Get all media types with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllMediaTypes(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a media type by ID.
     *
     * @param int $id
     * @return MediaType
     */
    public function getMediaTypeById(int $id): MediaType
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new media type.
     *
     * @param array $data
     * @return MediaType
     */
    public function createMediaType(array $data): MediaType
    {
        return $this->model->create($data);
    }

    /**
     * Delete a media type by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteMediaType(int $id): bool
    {
        return $this->getMediaTypeById($id)->delete();
    }

    /**
     * Update a media type by ID.
     *
     * @param int $id
     * @param array $data
     * @return MediaType
     */
    public function updateMediaType(int $id, array $data): MediaType
    {
        $mediaType = $this->getMediaTypeById($id);
        $mediaType->update($data);
        return $mediaType;
    }

    /**
     * List all media types (id, name) ordered by name.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listMediaTypes()
    {
        return $this->model
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();
    }
}
