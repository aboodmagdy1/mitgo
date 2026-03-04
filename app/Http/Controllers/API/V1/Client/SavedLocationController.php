<?php

namespace App\Http\Controllers\API\V1\Client;


use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Client\StoreSavedLocationRequest;
use App\Http\Requests\API\V1\Client\UpdateSavedLocationRequest;
use App\Http\Resources\API\V1\Client\SavedLocationResource;
use App\Models\UserSavedLocation;
use App\Services\UserSavedLocationService;
use Illuminate\Support\Facades\Auth;

class SavedLocationController extends Controller
{

    public function __construct(
        protected UserSavedLocationService $userSavedLocationService
    ) {
    }

                
    public function savedLocations(){
        $user = Auth::user();
        $savedLocations = $this->userSavedLocationService->getUserSavedLocations($user->id);
        return $this->collectionResponse(SavedLocationResource::collection($savedLocations), __('Saved locations retrieved successfully'));
    }
    public function store(StoreSavedLocationRequest $request){
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $savedLocation = $this->userSavedLocationService->createWithBusinessLogic($data);
        return $this->successResponse([], __('Saved location created successfully'));
    }
    public function update(UpdateSavedLocationRequest $request,UserSavedLocation $savedLocation){
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $savedLocation = $this->userSavedLocationService->updateWithBusinessLogic($savedLocation, $data);
        return $this->successResponse([], __('Saved location updated successfully'));
    }
    public function destroy(UserSavedLocation $savedLocation){
        $user = Auth::user();
        $savedLocation = $this->userSavedLocationService->deleteWithBusinessLogic($savedLocation);
        return $this->successResponse([], __('Saved location deleted successfully'));
    }
    
}
