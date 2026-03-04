<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\TripPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function App\Helpers\get_distance_and_duration;
use function App\Helpers\in_any_zone;
use function App\Helpers\setting;
use App\Services\PricingService;
use App\Services\VehicleTypeService;
use App\Services\CouponService;
use App\Services\DriverSearchService;
use App\Events\TripCreated;
use App\Events\TripDriverAccepted;
use App\Events\TripDriverArrived;
use App\Events\TripStarted;
use App\Events\TripEnded;
use App\Events\TripCompleted;
use App\Events\TripNoShow;
use App\Events\TripRequestExpired;
use App\Events\TripCancelled as TripCancelledEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TripService extends BaseService
{
    protected $trip;
    protected $pricingService;
    protected $vehicleTypeService;
    protected $driverSearchService;
    
    public function __construct(
        Trip $trip, 
        PricingService $pricingService, 
        VehicleTypeService $vehicleTypeService,
        DriverSearchService $driverSearchService
    ) {
        $this->trip = $trip;
        $this->pricingService = $pricingService;
        $this->vehicleTypeService = $vehicleTypeService;
        $this->driverSearchService = $driverSearchService;
        parent::__construct($trip);
    }

    /**
     * Get Trip with relationships
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->trip->with($relations)->get();
    }

    public function getActiveTrip(int $userId)
    {
        // get the active trip for the user
        return $this->trip->where('user_id', $userId)->active()->first();
    }

    /**
     * Find active trip for client with all necessary relationships
     * Returns trip with driver, vehicle, payment method data
     */
    public function findActiveTrip(int $userId): ?Trip
    {
        return $this->trip
            ->where('user_id', $userId)
            ->active()
            ->with([
                'driver.user', 
                'driver.vehicle', 
                'vehicleType', 
                'paymentMethod',
                'payment'
            ])
            ->first();
    }

    /**
     * Get active trip for a driver by driver_id
     * Returns trips that the driver has already accepted
     */
    public function getDriverActiveTrip(int $driverId): ?Trip
    {
        return $this->trip
            ->where('driver_id', $driverId)
            ->active()
            ->with(['user', 'vehicleType', 'paymentMethod'])
            ->first();
    }

    /**
     * Get pending trip request for a driver
     * Returns trip that was sent to driver but not yet accepted
     */
    public function getDriverPendingRequest(int $driverId): ?Trip
    {
        // Check if driver has active request in Redis
        if (!$this->driverSearchService->hasActiveRequest($driverId)) {
            return null;
        }

        // Get the trip ID from Redis
        $tripId = Redis::get("driver:{$driverId}:active_request");
        
        if (!$tripId) {
            return null;
        }

        // Get the trip and verify it's still in SEARCHING status
        $trip = $this->trip
            ->where('id', $tripId)
            ->where('status', TripStatus::SEARCHING)
            ->with(['user', 'vehicleType', 'paymentMethod'])
            ->first();

        return $trip;
    }

    /**
     * Find Trip with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Trip
    {
        return $this->trip->with($relations)->find($id);
    }

    /**
     * Create Trip with business logic
     */
    public function createWithBusinessLogic(array $data): Trip
    {
        // Add your business logic here before creating
        $this->validateBusinessRules($data);
        
        $trip = $this->create($data);
        
        // Add your business logic here after creating
        $this->afterCreate($trip);
        
        return $trip;
    }

    /**
     * Update Trip with business logic
     */
    public function updateWithBusinessLogic(Trip $trip, array $data): bool
    {
        // Add your business logic here before updating
        $this->validateBusinessRules($data, $trip);
        
        $updated = $this->update($trip, $data);
        
        if ($updated) {
            // Add your business logic here after updating
            $this->afterUpdate($trip);
        }
        
        return $updated;
    }

    /**
     * Delete Trip with business logic
     */
    public function deleteWithBusinessLogic(Trip $trip): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($trip);
        
        $deleted = $this->delete($trip);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($trip);
        }
        
        return $deleted;
    }



    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?Trip $trip = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(Trip $trip): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }

    /**
     * After create business logic
     */
    protected function afterCreate(Trip $trip): void
    {
        // Add your post-creation business logic here
        event(new TripCreated($trip));

        // Example: Send notifications, update related records, etc.
    }

    /**
     * After update business logic
     */
    protected function afterUpdate(Trip $trip): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(Trip $trip): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }

    /**
     * Get available car list with estimated costs
     */
    public function getVehicleTypesList(array $data)
    {
       
        if(!isset($data['dropoff_lat']) || !isset($data['dropoff_long'])){
            return $this->pricingService->getVehicleTypesWithoutPricing();
        }
        // Get distance and duration
        $result = get_distance_and_duration(
            [$data['pickup_lat'], $data['pickup_long']], 
            [$data['dropoff_lat'], $data['dropoff_long']]
        );
        
        $distance = $result['distance']; // in km
        $duration = $result['duration']; // in minutes

        // Determine the zone
        $zone = in_any_zone($data['pickup_lat'], $data['pickup_long']);

        // Use request time if provided, otherwise use current time
        $requestDateTime = isset($data['scheduled_at']) 
            ? \Carbon\Carbon::parse($data['scheduled_at'])
            : now();

        
        // Get vehicle types with pricing (including modifiers)
        $vehicles = $this->pricingService->getVehicleTypesWithPricing($zone, $distance, $duration, $requestDateTime);

        return $vehicles;

    }

    /**
     * Calculate trip details (distance, duration, zone, pricing)
     * Reusable method following DRY principle
     */
    public function calculateTripDetails(array $data , $trip = null): array
    {
        // Get distance and duration using helper
        $result = get_distance_and_duration(
            [$data['pickup_lat'], $data['pickup_long']], 
            [$data['dropoff_lat'], $data['dropoff_long']]
        );
        
        $distance = $result['distance']; // in km
        $duration = $result['duration']; // in minutes

        // Determine the zone
        $zone = in_any_zone($data['pickup_lat'], $data['pickup_long']);

        // Determine trip date/time for pricing calculation
        $requestDateTime = $this->getTripDateTime($data);

        // Get vehicle type
        $vehicleType = $this->vehicleTypeService->findById($data['vehicle_type_id']);
        
        if (!$vehicleType) {
            throw new \Exception(__('Vehicle type not found'));
        }

        // Get pricing for the selected vehicle type
        $pricing = $this->pricingService->getPricingForVehicle($vehicleType, $zone);
        
        if (!$pricing) {
            throw new \Exception(__('Pricing not available for this vehicle type'));
        }

        // Calculate base cost
        $baseCost = $pricing->calculateBaseCost($distance, $duration);

        // Get and apply modifiers if zone exists
        $estimatedFare = $baseCost;
        if ($zone) {
            $modifierPercentage = $this->pricingService->getActivePricingModifiers($zone, $requestDateTime);
            if ($modifierPercentage > 0) {
                $estimatedFare = $this->pricingService->applyModifiers($baseCost, $modifierPercentage);
            }
        }
        
        // Apply coupon if provided and calculate discount amount
        $discountAmount = 0;
        if(isset($data['coupon'])){
            $fareBeforeCoupon = $estimatedFare;
            $estimatedFare = $this->pricingService->applyCoupon($estimatedFare, $data['coupon']);
            $discountAmount = $fareBeforeCoupon - $estimatedFare;
        }

        return [
            'distance' => $distance,
            'estimated_duration' => $duration,
            'zone' => $zone,
            'estimated_fare' => round($estimatedFare, 2),
            'discount_amount' => round($discountAmount, 2),
            'request_datetime' => $requestDateTime,
        ];
    }

    public function createTrip(array $data, int $userId): Trip
    {
        // Use database transaction to ensure atomicity
        return DB::transaction(function () use ($data, $userId) {
            // Calculate trip details (distance, fare, zone, etc.)
            $tripDetails = $this->calculateTripDetails($data);

            // Determine trip type
            $isScheduled = isset($data['scheduled_date']) && isset($data['scheduled_time']);
            $tripType = $isScheduled ? \App\Enums\TripType::scheduled : \App\Enums\TripType::immediate;

            // Prepare scheduled datetime if applicable
            $scheduledAt = null;
            if ($isScheduled) {
                $scheduledAt = \Carbon\Carbon::parse($data['scheduled_date'] . ' ' . $data['scheduled_time']);
            }

            // Prepare trip data
            $tripData = [
                'number'=> random_int(100000, 999999),
                'user_id' => $userId,
                'vehicle_type_id' => $data['vehicle_type_id'],
                'payment_method_id' => $data['payment_method_id'],
                'zone_id' => $tripDetails['zone']?->id,
                'coupon_id' => isset($data['coupon']) ? $data['coupon']->id : null,
                'type' => $tripType,
                'status' => TripStatus::SEARCHING,
                'is_scheduled' => $isScheduled,
                'scheduled_date' => $isScheduled ? $data['scheduled_date'] : null,
                'scheduled_time' => $isScheduled ? $data['scheduled_time'] : null,
                'scheduled_at' => $scheduledAt,
                'pickup_lat' => $data['pickup_lat'],
                'pickup_long' => $data['pickup_long'],
                'pickup_address' => $data['pickup_address'] ?? null,
                'dropoff_lat' => $data['dropoff_lat'],
                'dropoff_long' => $data['dropoff_long'],
                'dropoff_address' => $data['dropoff_address'] ?? null,
                'distance' => $tripDetails['distance'],
                'estimated_duration' => $tripDetails['estimated_duration'],
                'estimated_fare' => $tripDetails['estimated_fare'],
            ];

            // Create the trip
            $trip = $this->create($tripData);

            // Record coupon usage if coupon was applied
            if (isset($data['coupon']) && $tripDetails['discount_amount'] > 0) {
                app(CouponService::class)->recordUsage(
                    $data['coupon'],
                    $userId,
                    $trip->id,
                    $tripDetails['discount_amount']
                );
            }

            // Load relationships for response
            $trip->load(['vehicleType', 'paymentMethod', 'zone', 'driver', 'coupon']);

            // Trigger post-creation logic (notifications, etc.)
            $this->afterCreate($trip);

            // Fire event to initiate driver search

            return $trip;
        });
    }

    /**
     * Get trip datetime for pricing calculation
     */
    protected function getTripDateTime(array $data): \Carbon\Carbon
    {
        if (isset($data['scheduled_date']) && isset($data['scheduled_time'])) {
            return \Carbon\Carbon::parse($data['scheduled_date'] . ' ' . $data['scheduled_time']);
        }

        return now();
    }
    /**
     * Get user's trips filtered by status
     * 
     * @param string|null $status_indicator null = all completed/scheduled/cancelled, 'scheduled' = only scheduled
     * @param int $userId
     * @return Collection
     */
    public function getMyTrips($status_indicator, $userId)
    {
        
        $statuses = match($status_indicator) {
            'scheduled', '1' => [TripStatus::SCHEDULED],
            'completed' => [TripStatus::COMPLETED, TripStatus::PAID, TripStatus::COMPLETED_PENDING_PAYMENT],
            'cancelled' => [TripStatus::CANCELLED_BY_DRIVER, TripStatus::CANCELLED_BY_RIDER, TripStatus::CANCELLED_BY_SYSTEM],
            'in_progress' => [TripStatus::SEARCHING, TripStatus::IN_ROUTE_TO_PICKUP, TripStatus::PICKUP_ARRIVED, TripStatus::IN_PROGRESS],
            // Default: all completed, scheduled, cancelled, and in-progress trips
            default => [
                TripStatus::RIDER_NOT_FOUND,
                TripStatus::SEARCHING,
                TripStatus::NO_DRIVER_FOUND,
                TripStatus::RIDER_NO_SHOW,
                TripStatus::SCHEDULED, 
                TripStatus::COMPLETED,
                TripStatus::PAID,
                TripStatus::COMPLETED_PENDING_PAYMENT,
                TripStatus::CANCELLED_BY_DRIVER, 
                TripStatus::CANCELLED_BY_RIDER, 
                TripStatus::CANCELLED_BY_SYSTEM,
                TripStatus::IN_ROUTE_TO_PICKUP,
                TripStatus::PICKUP_ARRIVED,
                TripStatus::IN_PROGRESS
            ],
        };
        $user = Auth::user();

        $column = 'user_id';
        if($user->isDriver()){
            $userId = $user->driver->id;
            $column = 'driver_id';
        }

        return $this->trip->where($column, $userId)
            ->with(['payment', 'vehicleType', 'paymentMethod', 'driver'])
            ->whereIn('status', $statuses)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function getCurrentTrip(int $userId): ?Trip
    {
        return $this->trip->where('user_id', $userId)
            ->whereIn('status', [TripStatus::IN_PROGRESS, TripStatus::SEARCHING , TripStatus::IN_ROUTE_TO_PICKUP, TripStatus::PICKUP_ARRIVED])
            ->first();
    }


    public function endTrip(Trip $trip, array $data = []): Trip
    {
        return DB::transaction(function () use ($trip, $data) {
            // Recalculate actual distance and duration with new dropoff location
            $result = get_distance_and_duration(
                [$trip->pickup_lat, $trip->pickup_long],
                [$data['dropoff_lat'], $data['dropoff_long']]
            );
            
            Log::info('endTrip', ['result' => $result]);
            $actualDistance = $result['distance']; // in km
            $actualDuration = $trip->started_at->diffInMinutes($trip->ended_at);
            Log::info('endTrip', ['actualDistance' => $actualDistance, 'actualDuration' => $actualDuration]);

            // Get pricing for the trip's vehicle type and zone
            $pricing = $this->pricingService->getPricingForVehicle(
                $trip->vehicleType,
                $trip->zone
            );
            Log::info('endTrip', ['pricing' => $pricing]);
            if (!$pricing) {
                throw new \Exception(__('Pricing not available for this trip'));
            }
            
            // Calculate base cost with actual distance and duration
            $actualFare = $pricing->calculateBaseCost($actualDistance, $actualDuration);
            Log::info('endTrip', ['actualFare' => $actualFare]);
            // Apply modifiers if zone exists (use original trip datetime for consistency)
            $tripDateTime = $trip->scheduled_at ?? $trip->created_at;
            if ($trip->zone) {
                $modifierPercentage = $this->pricingService->getActivePricingModifiers(
                    $trip->zone,
                    $tripDateTime
                );
                if ($modifierPercentage > 0) {
                    $actualFare = $this->pricingService->applyModifiers($actualFare, $modifierPercentage);
                }
            }
            Log::info('endTrip', ['actualFare' => $actualFare]);
            
            // Store fare before coupon (this is the base for commission calculation)
            $fareBeforeCoupon = $actualFare;
            
            // Get commission rate from settings (percentage)
            $commissionRate = setting('general', 'commission_rate') ?? 10;
            Log::info('endTrip', ['commissionRate' => $commissionRate]);
            
            // Calculate commission amount BEFORE coupon discount (platform earning)
            $commissionAmount = ($fareBeforeCoupon * $commissionRate) / 100;
            Log::info('endTrip', ['commissionAmount' => $commissionAmount]);
            
            // Calculate driver earning (fare before coupon - commission)
            $driverEarning = $fareBeforeCoupon - $commissionAmount;
            Log::info('endTrip', ['driverEarning' => $driverEarning]);
            
            // Reapply coupon if it was used (DO NOT record new usage)
            $couponDiscount = 0;
            $fareAfterCoupon = $actualFare; // Default to fare before coupon
            if ($trip->coupon_id && $trip->coupon) {
                $fareAfterCoupon = $this->pricingService->applyCoupon($actualFare, $trip->coupon);
                $couponDiscount = $fareBeforeCoupon - $fareAfterCoupon;
            }
            Log::info('endTrip', ['fareAfterCoupon' => $fareAfterCoupon, 'couponDiscount' => $couponDiscount]);
            
            // Total amount is the fare before coupon (for accounting/reporting purposes)
            $totalAmount = $fareBeforeCoupon;
            // Final amount is what the customer actually pays (after coupon discount)
            $finalAmount = $fareAfterCoupon;
            Log::info('endTrip', ['totalAmount' => $totalAmount, 'finalAmount' => $finalAmount]);
            
            // Update trip with actual values and completed status
            $updateData = [
                'dropoff_lat' => $data['dropoff_lat'],
                'dropoff_long' => $data['dropoff_long'],
                'dropoff_address' => $data['dropoff_address'],
                'actual_fare' => $fareAfterCoupon, // Client pays fare after coupon
                'actual_distance' => $actualDistance,
                'actual_duration' => $actualDuration,
                'ended_at' => now(),
            ];

            // Based on payment method we will update the status 
            if($trip->payment_method_id == 2){
                $updateData['status'] = TripStatus::COMPLETED;
                // Withdraw from client wallet (amount after coupon discount)
                $trip->user->forceWithdraw($fareAfterCoupon * 100, [
                    'type' => 'trip_payment',
                    'trip_id' => $trip->number,
                    'description' => __('Payment for trip #:number', ['number' => $trip->number]),
                ]);
                
                // Deposit driver earning to driver wallet
                $trip->driver->user->deposit($driverEarning * 100, [
                    'type' => 'trip_earning',
                    'trip_id' => $trip->number,
                    'description' => __('Trip earning for trip #:number', ['number' => $trip->number]),
                ]);
            }else{
                $updateData['status'] = TripStatus::COMPLETED_PENDING_PAYMENT;
            }

            $trip->update($updateData);
            Log::info('endTrip', ['trip' => $trip]);
            // Create payment record with pending status
            $payment = TripPayment::create([
                'trip_id' => $trip->id,
                'payment_method_id' => $trip->payment_method_id,
                'commission_rate' => $commissionRate,
                'commission_amount' => round($commissionAmount, 2),
                'total_amount' => round($totalAmount, 2), // Gross amount before coupon (for accounting)
                'final_amount' => round($finalAmount, 2), // Final amount customer pays (after coupon)
                'driver_earning' => round($driverEarning, 2),
                'status' => $trip->payment_method_id == 2 ? 1 : 0, // Completed for wallet, pending for others
                'coupon_discount' => round($couponDiscount, 2),
                'coupon_id' => $trip->coupon_id,
            ]);
            Log::info('endTrip', ['payment' => $payment]);
            // Reload relationships
            $trip->load(['payment', 'driver.user', 'driver.vehicle', 'user', 'vehicleType', 'paymentMethod', 'coupon']);
            Log::info('endTrip', ['trip' => $trip]);

            // Broadcast trip ended to both channels
            event(new TripEnded($trip));
            return $trip;
        });
    }

    /**
     * Confirm payment for a trip (used for online payments)
     * Marks payment as completed and deposits driver earning to driver wallet
     */
    public function confirmPayment(Trip $trip, $transactionId = null): Trip
    {
        return DB::transaction(function () use ($trip, $transactionId) {
            // Load payment relationship
            $trip->load(['payment', 'driver.user']);
            
            if (!$trip->payment) {
                throw new \Exception(__('Payment record not found for this trip'));
            }
            
            // Check if payment is already completed
            if ($trip->payment->isCompleted()) {
                throw new \Exception(__('Payment already completed'));
            }
            
            // Update payment status to completed
            $trip->payment->update(['status' => 1]);
            if($transactionId){
                $trip->payment->update(['transaction_id' => $transactionId]);
            }
            // Update trip status to completed
            $trip->update(['status' => TripStatus::COMPLETED]);
            
            // Deposit driver earning to driver wallet
            $driverEarning = $trip->payment->driver_earning;
            $trip->driver->user->deposit($driverEarning * 100, [
                'type' => 'trip_earning',
                'trip_id' => $trip->id,
                'description' => __('Trip earning for trip #:number', ['number' => $trip->number]),
            ]);
            
            // Reload relationships
            $trip->load(['payment', 'driver.user', 'driver.vehicle', 'user', 'vehicleType', 'paymentMethod', 'coupon']);

            // Broadcast trip completed to both channels
            event(new TripCompleted($trip));
            return $trip;
        });
    }

    // Needed for cach payment
    public function driverConfirmPayment(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip) {
            // Load payment relationship
            $trip->load(['payment', 'driver.user']);
            
            if (!$trip->payment) {
                throw new \Exception(__('Payment record not found for this trip'));
            }
            
            // Check if payment is already completed
            if ($trip->payment->isCompleted()) {
                throw new \Exception(__('Payment already completed'));
            }
            
            // Update payment status to completed
            $trip->payment->update(['status' => 1]);
            
            // Update trip status to completed
            $trip->update(['status' => TripStatus::COMPLETED]);
            
            // withdraw commission if > 0 
            $commissionAmount = $trip->payment->commission_amount;
            if ($commissionAmount > 0) {
                $trip->driver->user->forceWithdraw($commissionAmount * 100, [
                    'type' => 'app_commission',
                    'trip_id' => $trip->id,
                ]);
            }

            // Reload relationships for event broadcasting
            $trip->load(['driver.user', 'driver.vehicle', 'user', 'payment', 'coupon']);
            
            // Broadcast trip completed event to both channels
            event(new TripCompleted($trip));
            
            return $trip;
        });
    }


    public function cancelTrip(Trip $trip, ?int $cancelReasonId, ?TripStatus $cancelStatus = null): Trip
    {
        $user = Auth::user();
        $cancellationFee = 0;
        Log::info('cancelTrip', ['trip_id' => $trip->id, 'cancelReasonId' => $cancelReasonId, 'cancelStatus' => $cancelStatus?->value]);
        
        // Check if trip is already cancelled
        if ($trip->isCancelled()) {
            throw new \Symfony\Component\HttpFoundation\Exception\BadRequestException(__('Trip already cancelled'));
        }
       
        if (!$trip->relationLoaded('user')) {
            $trip->load('user');
        }
        
        // Check if cancellation fee should be applied (before transaction)
        $shouldApplyFee = false;
        
        // Case 1: Driver marks rider as no-show
        if ($cancelStatus === TripStatus::RIDER_NO_SHOW) {
            $shouldApplyFee = true;
            Log::info('Case 1: RIDER_NO_SHOW - fee will apply');
        }
        
        // Case 2: Client cancels after driver arrived and free waiting time expired
        Log::info('Checking Case 2 conditions', [
            'cancelStatus' => $cancelStatus?->value,
            'cancelStatus_name' => $cancelStatus?->name,
            'trip_status' => $trip->status->value,
            'trip_status_name' => $trip->status->name,
            'is_CANCELLED_BY_RIDER' => $cancelStatus === TripStatus::CANCELLED_BY_RIDER,
            'is_PICKUP_ARRIVED' => $trip->status === TripStatus::PICKUP_ARRIVED,
        ]);
        
        if ($cancelStatus === TripStatus::CANCELLED_BY_RIDER && 
            $trip->status === TripStatus::PICKUP_ARRIVED) {
            Log::info('Checking if waiting time expired', ['arrived_at' => $trip->arrived_at]);
            if ($trip->hasWaitingTimeExpired()) {
                $shouldApplyFee = true;
                Log::info('Case 2: Client cancel after waiting expired - fee will apply');
            } else {
                Log::info('Case 2: Client cancel before waiting expired - no fee');
            }
        } else {
            Log::info('Case 2 conditions NOT met - skipping');
        }
        
        // Case 3: Driver cancels after arriving and free waiting time expired
        // (Driver cancels but rider still pays the fee since driver waited)
        if ($cancelStatus === TripStatus::CANCELLED_BY_DRIVER && 
            $trip->status === TripStatus::PICKUP_ARRIVED) {
            Log::info('Checking if waiting time expired for driver cancel');
            if ($trip->hasWaitingTimeExpired()) {
                $shouldApplyFee = true;
                Log::info('Case 3: Driver cancel after waiting expired - fee will apply');
            } else {
                Log::info('Case 3: Driver cancel before waiting expired - no fee');
            }
        }
        
        // Calculate fee before transaction
        if ($shouldApplyFee) {
            Log::info('Calculating cancellation fee');
            $cancellationFee = $this->calculateCancellationFee($trip);
            Log::info('Cancellation fee calculated', ['fee' => $cancellationFee]);
        } else {
            Log::info('No fee will be applied');
        }
        
        // Execute trip cancellation in transaction
        $trip = DB::transaction(function () use ($trip, $cancelReasonId, $cancellationFee, $cancelStatus, $shouldApplyFee) {
            Log::info('Inside transaction');
            
            // Create payment record if fee applies (but don't do wallet operation here)
            if ($shouldApplyFee && $cancellationFee > 0) {
                Log::info('Creating cancellation payment', [
                    'cancellation_fee' => $cancellationFee,
                    'user_id' => $trip->user_id,
                ]);
                
                // Create payment record
                $this->createCancellationPayment($trip, $cancellationFee);
            }
            
            // Update trip with cancellation details
            $trip->update([
                'status' => $cancelStatus ?? TripStatus::CANCELLED_BY_RIDER,
                'cancel_reason_id' => $cancelReasonId,
                'cancellation_fee' => $cancellationFee,
            ]); 
            
            Log::info('Trip updated with cancellation details');
            
            // Reload relationships
            $trip->load(['payment', 'cancelReason', 'user']);
            Log::info('End of transaction');
            
            return $trip;
        });
        
        // Deduct from wallet AFTER transaction completes (to avoid deadlock)
        if ($shouldApplyFee && $cancellationFee > 0) {
            Log::info('Withdrawing from wallet (after transaction)', [
                'user_balance_before' => $trip->user->balance / 100,
                'cancellation_fee' => $cancellationFee
            ]);
            
            try {
                $withdrawal = $trip->user->forceWithdraw($cancellationFee * 100, [
                    'type' => 'trip_cancellation_fee',
                    'trip_id' => $trip->id,
                    'description' => __('Cancellation fee for trip #:number', ['number' => $trip->number]),
                ]);
                
                // Refresh user to get updated balance
                $trip->user->refresh();
                
                Log::info('Wallet withdrawn successfully', [
                    'user_balance_sar' => $trip->user->balance / 100,
                    'withdrawal_id' => $withdrawal->id ?? null
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to withdraw from wallet', [
                    'error' => $e->getMessage(),
                    'trip_id' => $trip->id,
                    'user_id' => $trip->user_id
                ]);
                // Note: Trip is already cancelled, we just couldn't deduct the fee
                // You might want to handle this case differently
            }
        }
        
        // If trip was in SEARCHING status, clear search data and notify drivers
        if ($trip->status === TripStatus::CANCELLED_BY_RIDER || 
            $trip->status === TripStatus::CANCELLED_BY_SYSTEM) {
            
            // Get notified drivers
            $notifiedDriverIds = $this->driverSearchService->getNotifiedDrivers($trip->id);
            
            // Clear search data including all driver active requests (trip is cancelled)
            // This will broadcast TripRequestExpired with reason 'cancelled' to shared channel
            $this->driverSearchService->clearTripSearchData($trip->id, true, 'cancelled');
            
            Log::info('Trip cancelled, cleared search data', [
                'trip_id' => $trip->id,
                'notified_drivers' => count($notifiedDriverIds)
            ]);
        }
        
        // Fire appropriate event based on final status
        if ($trip->status === TripStatus::RIDER_NO_SHOW) {
            event(new TripNoShow($trip));
        } else {
            // Broadcast cancellation event
            $cancelledBy = match($cancelStatus) {
                TripStatus::CANCELLED_BY_RIDER => 'rider',
                TripStatus::CANCELLED_BY_DRIVER => 'driver',
                TripStatus::CANCELLED_BY_SYSTEM => 'system',
                default => 'unknown',
            };
            event(new TripCancelledEvent($trip, $cancelledBy));
        }
        
        return $trip;
    }

    /**
     * Calculate cancellation fee from zone pricing
     */
    protected function calculateCancellationFee(Trip $trip): float
    {
        Log::info('calculateCancellationFee called', [
            'trip_id' => $trip->id,
            'zone_id' => $trip->zone_id,
            'vehicle_type_id' => $trip->vehicle_type_id
        ]);
        
        if (!$trip->zone_id || !$trip->vehicle_type_id) {
            Log::warning('Missing zone or vehicle type', [
                'zone_id' => $trip->zone_id,
                'vehicle_type_id' => $trip->vehicle_type_id
            ]);
            return 0;
        }

        $pricing = $this->pricingService->getPricingForVehicle(
            $trip->vehicleType, 
            $trip->zone
        );

        if (!$pricing) {
            Log::warning('No pricing found for trip', [
                'trip_id' => $trip->id,
                'zone_id' => $trip->zone_id,
                'vehicle_type_id' => $trip->vehicle_type_id
            ]);
            return 0;
        }

        $fee = (float) $pricing->cancellation_fee ?? 0;
        Log::info('Cancellation fee from pricing', [
            'pricing_id' => $pricing->id ?? null,
            'cancellation_fee' => $fee,
            'pricing_table' => get_class($pricing)
        ]);
        
        return $fee;
    }

    /**
     * Create payment record for cancellation fee
     * For cancellations: Platform keeps 100% of the fee as commission
     */
    protected function createCancellationPayment(Trip $trip, float $cancellationFee): TripPayment
    {
        Log::info('Creating cancellation payment', [
            'trip_id' => $trip->id,
            'cancellation_fee' => $cancellationFee,
        ]);
        
        $payment = TripPayment::create([
            'trip_id' => $trip->id,
            'payment_method_id' => $trip->payment_method_id,
            'total_amount' => $cancellationFee, // For cancellations, total = final (no coupon)
            'final_amount' => $cancellationFee, // Customer pays the cancellation fee
            'driver_earning' => 0, // Driver doesn't earn from cancellation fees
            'commission_rate' => 100, // Platform takes 100%
            'commission_amount' => $cancellationFee, // Platform keeps full cancellation fee
            'status' => 1, // Completed status
            'coupon_discount' => 0,
            'additional_fees' => 0,
        ]);
        
        Log::info('Cancellation payment created', [
            'payment_id' => $payment->id,
            'total_amount' => $payment->total_amount,
            'commission_amount' => $payment->commission_amount
        ]);
        
        return $payment;
    }

    /**
     * Get search settings for mobile app
     */
    public function getSearchSettings(): array
    {
        return [
            'search_wave_time' => setting('general', 'search_wave_time') ?: 30,
            'search_wave_count' => setting('general', 'search_wave_count') ?: 10,
            'driver_acceptance_time' => setting('general', 'driver_acceptance_time') ?: 60,
        ];
    }

    /**
     * Search for drivers in the next wave (manual retry from client)
     */
    public function searchNextWave(Trip $trip): array
    {
        Log::info('Client requested next wave search', ['trip_id' => $trip->id]);
        
        return $this->driverSearchService->searchNextWave($trip);
    }

    /**
     * Accept trip by driver with race condition protection
     * Uses pessimistic locking to ensure only one driver can accept a trip
     */
    public function acceptTrip(Trip $trip, int $driverId): Trip
    {
        return DB::transaction(function () use ($trip, $driverId) {
            // Lock the trip row to prevent concurrent updates (race condition protection)
            $trip = Trip::where('id', $trip->id)->lockForUpdate()->first();
            
            // Check if trip is still available for acceptance
            if ($trip->status !== TripStatus::SEARCHING) {
                throw new \Exception(__('Trip already accepted by another driver or not available for acceptance'));
            }
            
            // Get all notified drivers before clearing
            $notifiedDriverIds = $this->driverSearchService->getNotifiedDrivers($trip->id);
            
            // Assign driver and update status to in-route-to-pickup
            $trip->update([
                'driver_id' => $driverId,
                'status' => TripStatus::IN_ROUTE_TO_PICKUP,
            ]);
            
            // Reload relationships for response
            $trip->load(['driver.user', 'driver.vehicle', 'user', 'vehicleType', 'paymentMethod']);
            
            // Set resolved flag (signals ExpireTripRequest job to exit immediately)
            Redis::setex("trip:{$trip->id}:resolved", 600, 1);
            
            // Broadcast to trip channel that driver accepted
            event(new TripDriverAccepted($trip));
            
            // Fire ONE TripRequestExpired to shared channel (all notified drivers will receive)
            event(new TripRequestExpired($trip->id));
            
            // NOW clear the accepting driver's Redis active request
            $this->driverSearchService->clearActiveRequest($driverId);
            
            // Clear trip search data (wave, radius, etc)
            $this->driverSearchService->clearTripSearchData($trip->id, false);
            
            Log::info('Driver accepted trip', [
                'trip_id' => $trip->id,
                'driver_id' => $driverId,
                'other_drivers_notified' => count($notifiedDriverIds) - 1
            ]);
            
            return $trip;
        });
    }

    public function arrivedAtPickup(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip) {
            // Lock the trip row to prevent concurrent updates (race condition protection)
            $trip = Trip::where('id', $trip->id)->lockForUpdate()->first();
            
            // Check if trip is still available for acceptance
            if ($trip->status !== TripStatus::IN_ROUTE_TO_PICKUP) {
                throw new \Exception(__('Trip already arrived at pickup'));
            }
            
            // Update status to in-route-to-pickup
            $trip->update([
                'status' => TripStatus::PICKUP_ARRIVED,
                'arrived_at' => now(),
            ]);
            
            // Reload relationships for response
            $trip->load(['driver.user', 'driver.vehicle', 'user', 'vehicleType', 'paymentMethod']);

            // Broadcast arrival to both channels with unified payload
            event(new TripDriverArrived($trip));
            
            return $trip;
        });
    }


    public function start(Trip $trip, array $data): Trip{
        $trip->update([
            'status' => TripStatus::IN_PROGRESS,
            'started_at' => now(),
            'pickup_lat' => $data['pickup_lat'],
            'pickup_long' => $data['pickup_long'],
            'pickup_address' => $data['pickup_address'],
        ]);
        // Reload relationships for event broadcasting
        $trip->load(['driver.user', 'driver.vehicle', 'user']);
        // Broadcast trip start
        event(new TripStarted($trip));
        return $trip;
    }
    public function rateTrip(Trip $trip , $data ): Trip{
        $trip->rate()->create([
            'rating' => $data['rate'],
            'rating_comment_id' => $data['rate_id'],
            'user_id'=>Auth::user()->id,
            'driver_id'=>$trip->driver_id,
        ]);
        return $trip;
    }

    /**
     * Check if driver has active request for this trip
     */
    public function driverHasActiveRequest(int $driverId, int $tripId): bool
    {
        return $this->driverSearchService->hasActiveRequest($driverId, $tripId);
    }

    /**
     * Handle driver rejecting a trip request
     * Clears request only for this driver, does not affect trip or other drivers
     */
    public function rejectTrip(int $tripId, int $driverId): void
    {
        Log::info('Driver rejecting trip request', [
            'trip_id' => $tripId,
            'driver_id' => $driverId
        ]);
        
        // Clear driver's active request (allows them to receive new requests)
        $this->driverSearchService->clearActiveRequest($driverId);
        
        // Fire TripRequestExpired ONLY to this driver's personal channel
        event(new TripRequestExpired($tripId, 'rejected', $driverId));
        
        Log::info('Driver rejection processed', [
            'trip_id' => $tripId,
            'driver_id' => $driverId
        ]);
    }
    
} 