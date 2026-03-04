<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Requests\API\V1\Auth\ActiveCodeRequest;
use App\Http\Requests\API\V1\Auth\ChangeLanguageRequest;
use App\Http\Requests\API\V1\Auth\LoginRequest;
use App\Http\Requests\API\V1\Client\RegisterRequest;
use App\Http\Requests\API\V1\Client\UpdateClientProfileRequest;
use App\Http\Requests\API\V1\Driver\RegisterReqest as DriverRegisterRequest;
use App\Http\Requests\API\V1\Driver\UpdateProfileRequest;
use App\Http\Requests\API\V1\Auth\UpdateLocationRequest;

    use App\Http\Resources\API\V1\Client\ProfileResource;
use App\Models\User;
use App\Services\AuthService;
use App\Http\Resources\API\V1\Driver\DriverProfileResource;
use App\Http\Resources\API\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseAuthCrontroller
{
    public function __construct(
        protected AuthService $authService,
    ) {
    }
    
    public function login(LoginRequest $request){
        return $this->authService->login($request->validated());
    }
    
    public function verifyActiveCode(ActiveCodeRequest $request){
        return $this->authService->verifyActiveCode($request->validated());
    }
    public function resendActiveCode(Request $request){
        return $this->authService->resendActiveCode($request->input('phone'));
    }
    public function clientRegister(RegisterRequest $request){
        $user = $this->authService->clientRegister($request->validated());
        return $this->successResponse([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => UserResource::make($user),
        ],__('Client profile completed successfully'));
    }
    public function driverRegister(DriverRegisterRequest $request){
        try{
            $user = $this->authService->driverRegister($request->validated());
            return $this->successResponse([],__('Your driver registration request will be reviewed and you will be notified in the shortest possible time'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(),500);
        }
    }
    public function updateClientProfile(UpdateClientProfileRequest $request){
        $user = Auth::user();
        try {
            $this->authService->updateClientProfile($request->validated(),$user);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(),500);
        }
        return $this->successResponse(ProfileResource::make($user),__('Client profile updated successfully'));
    }
    public function getClientProfile(){
        $user = Auth::user();
        return $this->successResponse(ProfileResource::make($user),__('Client profile fetched successfully'));
    }
    public function updateDriverProfile(UpdateProfileRequest $request){
        $user = Auth::user();
        return $this->authService->updateDriverProfile($request->validated(),$user);
    }

    public function getDriverProfile(){
        $user = Auth::user();
        return $this->successResponse(DriverProfileResource::make($user),__('Driver profile fetched successfully'));
    }

    public function changeLanguage(ChangeLanguageRequest $request){
        $user = Auth::user();
        $user->update(['lang' => $request->validated('lang')]);
        return $this->successResponse(['lang' => $user->lang], __('Language changed successfully'));
    }

    public function deleteAccount(){
        $user = Auth::user();
        $user->delete();
        return $this->successResponse([],__('Account deleted successfully'));
    }

    public function updateLocation(UpdateLocationRequest $request){
        $user = Auth::user();
        $this->authService->updateLocation($request->validated(),$user);
        return $this->successResponse([],__('Location updated successfully'));
    }  
    public function toggleDriverStatus(Request $request){
        $user = Auth::user();
        $status = $this->authService->updateDriverStatus($user);
        return $this->successResponse([
            'status' => $user->driver->status,
        ],__('Driver status updated successfully'));
    }
}
