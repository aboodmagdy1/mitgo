<?php

namespace App\Services;

use App\Http\Resources\API\V1\Client\ClientHomeTripResource;
use App\Http\Resources\API\V1\Client\SavedLocationResource;
use Illuminate\Support\Facades\Auth;

class HomeService extends BaseService
{

    public function __construct(
        protected TripService $tripService,
        protected UserSavedLocationService $userSavedLocationService,
    ) {
    }

    /**
     * Get home data for client
     * Returns saved locations and active trip (if any)
     */
    public function getHomeData(){
        $user = Auth::user();
        
        // Get user's saved locations
        $savedLocations = $this->userSavedLocationService->getUserSavedLocations($user->id);
        
        // Get active trip with necessary relationships
        $activeTrip = $this->tripService->findActiveTrip($user->id);

        return [
            'saved_locations' => SavedLocationResource::collection($savedLocations),
            'active_trip' => $activeTrip ? ClientHomeTripResource::make($activeTrip) : null,
        ];
    }
}