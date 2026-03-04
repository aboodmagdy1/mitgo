<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Enums\Users\ProfileStatus;
use App\Enums\Users\UserTypeEnum;
use App\Http\Resources\API\V1\Driver\EarningTripResource;
use App\Http\Resources\API\V1\UserResource;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\Trip;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Laravel\Sanctum\PersonalAccessToken;

class DriverService extends BaseService
{
    protected $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
        parent::__construct($driver);
    }

   


  
    public function create(array $data): User
    {
        $avatar = null;
        if(array_key_exists('avatar', $data)){
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }
        $data['is_active'] = false;
        $exist = User::where('phone', $data['phone'])->first();
        //update if exist
        if($exist){
            $exist->update($data);
            // Handle avatar for existing user
            if($avatar){
                $exist->clearMediaCollection('avatar');
                $exist->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }
            return $exist;
        }
        $user = User::create($data);
        if($avatar){
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return $user;
    }

    /**
     * Create Driver with business logic
     */
    public function createWithBusinessLogic(array $data): User
    {
        // Driver registration should only update the authenticated user's profile
        $user = AuthFacade::user();
        if (!$user) {
            throw new \Exception(__('User must be authenticated to register as driver'));
        }

        // Check if user already has a driver profile
        if ($user->driver) {
            throw new \Exception(__('User already has registered as driver'));
        }

        // Update user profile data
        $this->updateWithBusinessLogic($user, $data);

        // Create driver profile
        $data['user_id'] = $user->id;
        $data['status'] = 0;
        $data['is_approved'] = false;
        $driver = Driver::create($data);

        // Assign driver role
        if (!$user->hasRole('driver')) {
            $user->assignRole('driver');
        }

        // Create driver vehicle data 
        $vdata = [
            'driver_id' => $driver->id,
            'seats' => (int)$data['seats'],
            'color' => $data['color'],
            'license_number' => $data['car_license_number'],
            'plate_number' => $data['plate_number'],
            'vehicle_brand_model_id' => $data['brand_model_id'],
        ];
        $this->driverVehicleCreate($vdata);

        // Refresh user to load the driver relationship
        $user->refresh();
        
        $this->afterCreate($user, $data);
        
        return $user;
    }


    public function driverVehicleCreate(array $data){
        $vehicle = DriverVehicle::create($data);
        return $vehicle;
    }

    /**
     * Update User with business logic
     */
    public function updateWithBusinessLogic(User $user, array $data): bool
    {
        $avatar = null;
        if(array_key_exists('avatar', $data)){
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }
        $updated = $this->update($user, $data);
        if($avatar){
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        

        
        return $updated;
    }

    /**
     * Delete User with business logic
     */
    public function deleteWithBusinessLogic(User $user): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($user);
        
        $deleted = $this->delete($user);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($user);
        }
        
        return $deleted;
    }



    /**
     * Create user profile based on user type
     */


    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?User $user = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(User $user): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }


    /**
     * After create business logic
     */
    protected function afterCreate(User $user, array $data): void
    {
        $this->sendAdminNotification(__('New driver request'), __('A new driver request has been received'), 
        [Action::make('view')
            ->url(route('filament.admin.resources.drivers.view', $user->driver->id))
            ->label(__('View'))
        ],'database');

       
    }



    /**
     * After update business logic
     */
    protected function afterUpdate(User $user): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(User $user): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }

    public function earningStatistics($dateFilter = null){
        $driver = AuthFacade::user()->driver;
        
        // Initialize trip query for completed trips
        $tripQuery = Trip::with('payment')
            ->where('driver_id', $driver->id)
            ->where('status', TripStatus::COMPLETED);
        
        // Apply date filter based on parameter
        // 0 = today, 1 = this week, 2 = this month
        if ($dateFilter !== null) {
            switch ((int)$dateFilter) {
                case 0: // Today
                    $tripQuery->whereDate('ended_at', today());
                    break;
                    
                case 1: // This week
                    $tripQuery->whereBetween('ended_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                    
                case 2: // This month
                    $tripQuery->whereMonth('ended_at', now()->month)
                             ->whereYear('ended_at', now()->year);
                    break;
            }
        }
        
        // For totals: compute over all filtered completed trips
        $tripsForTotals = (clone $tripQuery)->get();
        $totalTrips = $tripsForTotals->count();
        $totalEarnings = $tripsForTotals->sum('payment.driver_earning');

        // For listing: latest trips limited
        $latestTrips = (clone $tripQuery)
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'total_trips' => $totalTrips,
            'total_earnings' => number_format($totalEarnings, 2),
            'trips' => EarningTripResource::collection($latestTrips)
        ];
    }
    

}   