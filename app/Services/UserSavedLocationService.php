<?php

namespace App\Services;

use App\Models\UserSavedLocation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserSavedLocationService extends BaseService
{
    protected $userSavedLocation;

    public function __construct(UserSavedLocation $userSavedLocation)
    {
        $this->userSavedLocation = $userSavedLocation;
        parent::__construct($userSavedLocation);
    }


    public function getUserSavedLocations(int $userId)
    {
        return $this->userSavedLocation->where('user_id', $userId)->get();
    }

    /**
     * Create UserSavedLocation with business logic
     */
    public function createWithBusinessLogic(array $data): UserSavedLocation
    {
        $userSavedLocation = $this->create($data);        
        return $userSavedLocation;
    }

    /**
     * Update UserSavedLocation with business logic
     */
    public function updateWithBusinessLogic(UserSavedLocation $location, array $data): UserSavedLocation
    {
        $location->update($data);
                return $location;
    }

    /**
     * Delete UserSavedLocation with business logic
     */
    public function deleteWithBusinessLogic(UserSavedLocation $userSavedLocation): bool
    {
        
        $deleted = $this->delete($userSavedLocation);
        return $deleted;
    }



   
}