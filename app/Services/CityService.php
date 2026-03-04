<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CityService extends BaseService
{
    protected $city;

    public function __construct(City $city)
    {
        $this->city = $city;
        parent::__construct($city);
    }

    /**
     * Get City with relationships
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->city->with($relations)->get();
    }

    /**
     * Find City with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?City
    {
        return $this->city->with($relations)->find($id);
    }

    /**
     * Create City with business logic
     */
    public function createWithBusinessLogic(array $data): City
    {
        // Add your business logic here before creating
        $this->validateBusinessRules($data);
        
        $city = $this->create($data);
        
        // Add your business logic here after creating
        $this->afterCreate($city);
        
        return $city;
    }

    /**
     * Update City with business logic
     */
    public function updateWithBusinessLogic(City $city, array $data): bool
    {
        // Add your business logic here before updating
        $this->validateBusinessRules($data, $city);
        
        $updated = $this->update($city, $data);
        
        if ($updated) {
            // Add your business logic here after updating
            $this->afterUpdate($city);
        }
        
        return $updated;
    }

    /**
     * Delete City with business logic
     */
    public function deleteWithBusinessLogic(City $city): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($city);
        
        $deleted = $this->delete($city);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($city);
        }
        
        return $deleted;
    }

    /**
     * Get users for City
     */
    public function getUsers(City $city): Collection
    {
        return $city->users;
    }

    /**
     * Add users to City
     */
    public function addUser(City $city, array $data): Model
    {
        return $city->users()->create($data);
    }

    /**
     * Get projects for City
     */
    public function getProjects(City $city): Collection
    {
        return $city->projects;
    }

    /**
     * Add projects to City
     */
    public function addProject(City $city, array $data): Model
    {
        return $city->projects()->create($data);
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?City $city = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(City $city): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }

    /**
     * After create business logic
     */
    protected function afterCreate(City $city): void
    {
        // Add your post-creation business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After update business logic
     */
    protected function afterUpdate(City $city): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(City $city): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }
}