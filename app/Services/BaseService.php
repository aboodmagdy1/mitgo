<?php

namespace App\Services;

use App\Jobs\SendAdminNotfication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated records
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Find a record by ID
     */
    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findByIdOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete a record
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Get records with conditions
     */
    public function getWhere(array $conditions): Collection
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->get();
    }

    /**
     * Count records
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function sendAdminNotification(string $title, string $body, array $actions = [], string $type = 'database'): void
    {
        SendAdminNotfication::dispatch($title, $body, $actions, $type);
    }
}   