<?php

namespace App\Services;
use App\Http\Resources\API\V1\UserResource;
use App\Http\Resources\API\V1\Driver\DriverProfileResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use App\Services\BaseService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;

class AuthService extends BaseService
{
    use ApiResponseTrait;

    public function __construct(
        private UserService $userService,
        private DriverService $driverService,
    ) {
    }

    public function login(array $data)
    {
        $data['phone'] = '966'.$data['phone'];
       $user = $this->userService->findByPhone($data['phone']);
        if(!$user){
            $user = $this->userService->create($data);
        } else {
            // Handle all type cases
            $isRider = $data['type'] == 0;
            $isDriver = $data['type'] == 1;
            $userIsDriver = $user->hasRole('driver');

            // Step 1: Check account type mismatch (must be checked first)
            // Case 1: Rider trying to login but user is a driver
            if ($isRider && $userIsDriver) {
                return $this->errorResponse(__('This phone number is registered as a driver account. Please use the driver application.'), 400);
            }
            
            // Case 2: Driver trying to login but user is a rider
            if ($isDriver && !$userIsDriver) {
                return $this->errorResponse(__('This phone number is registered as a rider account. Please use the rider application.'), 400);
            }

            // Step 2: Check driver-specific approval status (only for drivers)
            // Case 3: Driver trying to login but driver account is not approved
            if ($isDriver && $userIsDriver && $user->driver && !$user->driver->isApproved()) {
                return $this->errorResponse(__('Your registration request is still under review. Please wait for admin approval.'), 403);
            }
            
            // Step 3: Check account deactivation (applies to both riders and drivers)
            // Case 4: Account is deactivated
            if (!$user->is_active) {
                return $this->errorResponse(__('Your account has been deactivated. Please contact support.'), 403);
            }

            // All checks passed - user can proceed with login
        }

        
        $user->sendActiveCode();
        if(isset($data['fcm_token']) && $data['fcm_token']){
        $user->fcmTokens()->updateOrCreate([
            'user_id' => $user->id,
        ],[
                'token' => $data['fcm_token'],
            ]);
        }

        return $this->ok([],__('please verify your account'));
    }
    public function verifyActiveCode(array $data)
    {
        $data['phone'] = '966' . $data['phone'];
        $user = User::where('phone', $data['phone'])
                   ->where('active_code', $data['code'])
                   ->first();
        
        if (!$user) {
            return $this->errorResponse(__('Invalid verification code'));
        }

        // Clear the active code
        $user->update(['active_code' => null]);
        
        // Determine if this is first login (no name means incomplete profile)
        $isFirstLogin = empty($user->name);
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Load relationships for drivers
      
        
        if ($isFirstLogin) {
            return $this->successResponse([
                'token' => $token,
                'first_login' => true,
                'user' => null
            ], __('Account verified successfully. Please complete your registration.'));
        }
        
        return $this->successResponse([
            'token' => $token,
            'first_login' => false,
            'user' =>  UserResource::make($user),
        ], __('Account verified successfully. Welcome back!'));
    }

    public function resendActiveCode($phone)
    {
        $phone =  '966' . $phone;
        $user = User::where('phone',$phone)->first();
        if(!$user){
            return $this->errorResponse(__('User not found'),404);
        }
        $user->sendActiveCode();
        return $this->successResponse([],__('Active code sent successfully'));
    }
    public function logout(Request $request){
        $user = Auth::user();
        $token = PersonalAccessToken::findToken($request->bearerToken());
        $user->fcmTokens()->where('token',$request->input('fcm_token'))->delete();
        $token->delete();

        return $this->successResponse([],__('Logged out successfully'));
    }
    public function deleteAccount(){
        $user = Auth::user();
        $user->delete();
        return $this->successResponse([],__('Account deleted successfully'));
    }
    public function clientRegister(array $data){
        return $this->userService->createWithBusinessLogic($data);
    }
    public function driverRegister(array $data){
        return $this->driverService->createWithBusinessLogic($data);
    }
   
    public function updateClientProfile(array $data, User $user)
    {
       return $this->userService->updateWithBusinessLogic($user,$data);

    }

    public function updateDriverProfile(array $data, User $user)
    {
        // Handle avatar separately for media upload
        $avatar = null;
        if (array_key_exists('avatar', $data)) {
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }

        // Prepare data for User model update
        $userData = [];
        $userFields = ['name', 'phone', 'city_id'];
        foreach ($userFields as $field) {
            if (array_key_exists($field, $data)) {
                $userData[$field] = $data[$field];
            }
        }

        // Prepare data for Driver model update
        $driverData = [];
        $driverFields = ['absher_phone', 'national_id', 'license_number', 'date_of_birth'];
        foreach ($driverFields as $field) {
            if (array_key_exists($field, $data)) {
                $driverData[$field] = $data[$field];
            }
        }

        // Prepare data for DriverVehicle model update
        $vehicleData = [];
        $vehicleFields = ['seats', 'color', 'plate_number', 'brand_model_id'];
        foreach ($vehicleFields as $field) {
            if (array_key_exists($field, $data)) {
                $vehicleData[$field] = $data[$field];
            }
        }
        
        // Handle car_license_number mapping to license_number in vehicle
        if (array_key_exists('car_license_number', $data)) {
            $vehicleData['license_number'] = $data['car_license_number'];
        }

        try {
            // Update User model
            if (!empty($userData)) {
                $user->update($userData);
            }

            // Handle avatar upload
            if ($avatar) {
                $user->clearMediaCollection('avatar');
                $user->addMedia($avatar)->toMediaCollection('avatar');
            }

            // Update Driver model
            if (!empty($driverData) && $user->driver) {
                $user->driver->update($driverData);
            }

            // Update DriverVehicle model
            if (!empty($vehicleData) && $user->driver && $user->driver->vehicle) {
                $user->driver->vehicle->update($vehicleData);
            }

            // Refresh user data to get updated relationships with all necessary data
            return $this->successResponse(
               DriverProfileResource::make($user)
            ,__('Driver profile updated successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse(__('Failed to update driver profile: ') . $e->getMessage(), 500);
        }
    }
    public function updateDriverStatus(User $user)
    {
        $user->driver->update(['status' => !$user->driver->status]);
        $status = $user->driver->status;
        return $status;
    }
    public function updateLocation(array $data, User $user)
    {
        $data['latest_lat'] = $data['lat'];
        $data['latest_long'] = $data['long'];
        $user->update($data);
        return $this->successResponse([],__('Location updated successfully'));
    }
}
