<?php

namespace App\Http\Controllers\API\V1\Driver;


use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Client\Trips\CancelTripRequest;
use App\Http\Requests\API\V1\Driver\Trips\EndTripRequest;
use App\Http\Requests\API\V1\Driver\Trips\StartTripRequest;
use App\Http\Requests\API\V1\Driver\Trips\UpdateStatusRequest;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use App\Services\TripService;
use App\Http\Resources\API\V1\Driver\MyTripsResource;
use App\Http\Resources\API\V1\Driver\DriverHomeTripResource;
use App\Http\Resources\API\V1\TripResource;
use Illuminate\Http\Request;
use Predis\Command\Argument\Server\To;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TripController extends Controller
{

    public function __construct(
                protected TripService $tripService,
        ){
        }



        // driver home data 
        //  return any active trip request or trip 
        public function home(){
            $user = Auth::user();
            $driverId = $user->driver->id;
            
            // First, check if driver has an active trip (already accepted)
            $activeTrip = $this->tripService->getDriverActiveTrip($driverId);
            
            if ($activeTrip) {
                return $this->successResponse(
                    new DriverHomeTripResource($activeTrip), 
                    __('Active trip retrieved successfully')
                );
            }
            
            // If no active trip, check for pending trip request
            $pendingRequest = $this->tripService->getDriverPendingRequest($driverId);
            
            if ($pendingRequest) {
                return $this->successResponse(
                    new DriverHomeTripResource($pendingRequest), 
                    __('Pending trip request retrieved successfully')
                );
            }
            
            // No active trip or pending request
            return $this->successResponse(
                null, 
                __('You don\'t have any active trips or requests')
            );
        }

    public function index(){
        $user = Auth::user();
        $status_indicator = request()->get('status');
        $trips = $this->tripService->getMyTrips($status_indicator, $user->id);
        if($trips->isEmpty()){
            return $this->successResponse([], __('You don\'t have any trips'));
        }
        
        return $this->successResponse(
            MyTripsResource::collection($trips), 
            __('trips retrieved successfully')
        ); 
    }

    /**
     * Show a single trip with full details including invoice
     */
    public function show(Request $request,$id){
        $user = Auth::user();
        $trip = $this->tripService->findWithRelations($id, ['user.media','paymentMethod','payment']);

        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }

        // Ensure the trip belongs to the authenticated user
        if($trip->driver_id != $user->driver->id){
            return $this->forbidden(__('You are not authorized to view this trip'));
        }
        
        return $this->successResponse(
            new MyTripsResource($trip), 
            __('Trip retrieved successfully')
        );
    }

    public function accept($id){
        $user = Auth::user();
        $trip = $this->tripService->findById($id);
        
        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }
        
        try {
            $acceptedTrip = $this->tripService->acceptTrip($trip, $user->driver->id);
            
            return $this->successResponse(
                new TripResource($acceptedTrip),
                __('Trip accepted successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Reject a trip request
     * Driver explicitly declines the trip, clearing it from their UI
     * Does not affect trip status or other drivers
     */
    public function rejectTrip($id)
    {
        $user = Auth::user();
        $driverId = $user->driver->id;
        
        // Verify driver was actually notified about this trip
        $hasActiveRequest = $this->tripService->driverHasActiveRequest($driverId, $id);
        
        if (!$hasActiveRequest) {
            return $this->notFound(__('No active trip request found'));
        }
        
        try {
            // Call service to handle rejection
            $this->tripService->rejectTrip($id, $driverId);
            
            return $this->successResponse(
                null,
                __('Trip request rejected successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    
    // public function cancelTrip(CancelTripRequest $request , $id){
    //     $user = Auth::user();
    //     $trip = $this->tripService->findWithRelations($id, ['user', 'zone', 'vehicleType']);
    //     if(!$trip){
    //         return $this->notFound(__('Trip not found'));
    //     }
    //     if($trip->driver_id != $user->driver->id){
    //         return $this->forbidden(__('You are not authorized to cancel this trip'));
    //     }
        
    //     try {
    //         $cancelledTrip = $this->tripService->cancelTrip(
    //             $trip, 
    //             $request->validated()['reason_id'],
    //             TripStatus::CANCELLED_BY_DRIVER
    //         );
            
    //         // Inform driver if cancellation fee was charged to rider
    //         $message = $cancelledTrip->cancellation_fee > 0 
    //             ? __('Trip cancelled. Cancellation fee charged to rider: :fee', ['fee' => $cancelledTrip->cancellation_fee])
    //             : __('Trip cancelled successfully');
            
    //         return $this->successResponse(
    //             new TripResource($cancelledTrip),
    //             $message
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    // public function arrived($id){
    //     $user = Auth::user();
    //     $trip = $this->tripService->findById($id);
        
    //     if(!$trip){
    //         return $this->notFound(__('Trip not found'));
    //     }
        
    //     try {
    //         $arrivedTrip = $this->tripService->arrivedAtPickup($trip);
            
    //         return $this->successResponse(
    //             new TripResource($arrivedTrip),
    //             __('Trip arrived at pickup successfully')
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    // public function start(StartTripRequest $request , $id){
    //     $user = Auth::user();
    //     $trip = $this->tripService->findById($id);
        
    //     if(!$trip){
    //         return $this->notFound(__('Trip not found'));
    //     }


    //     if($trip->driver_id != $user->driver->id){
    //         return $this->forbidden(__('You are not authorized to start this trip'));
    //     }

    //     if($trip->status !== TripStatus::PICKUP_ARRIVED){
    //         return $this->badRequest(__('Can only start trip when arrived at pickup location'));
    //     }
        
    //     try {
    //         $startedTrip = $this->tripService->start($trip, $request->validated());
            
    //         return $this->successResponse(
    //             new TripResource($startedTrip),
    //             __('Trip started successfully')
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }   
    // public function end(EndTripRequest $request , $id){
    //     $user = Auth::user();
    //     $trip = $this->tripService->findById($id);
        
    //     if(!$trip){
    //         return $this->notFound(__('Trip not found'));
    //     }
        
    //     if($trip->driver_id != $user->driver->id){
    //         return $this->forbidden(__('You are not authorized to end this trip'));
    //     }
        
    //     if($trip->status !== TripStatus::IN_PROGRESS){
    //         return $this->badRequest(__('Can only end trip when in progress'));
    //     }

    //     try {
    //         $endedTrip = $this->tripService->endTrip($trip, $request->validated());
            
    //         return $this->successResponse(
    //             new TripResource($endedTrip),
    //             __('Trip ended successfully')
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    

    // /**
    //  * Mark rider as no-show (applies cancellation fee if waiting time expired)
    //  * Driver must have arrived at pickup location and waiting time must have passed
    //  * No cancellation reason needed - the reason is implicit (rider no-show)
    //  */
    // public function markRiderNoShow($id){
    //     $user = Auth::user();
    //     $trip = $this->tripService->findWithRelations($id, ['user', 'zone', 'vehicleType']);
        
    //     if(!$trip){
    //         return $this->notFound(__('Trip not found'));
    //     }
        
    //     if($trip->driver_id != $user->driver->id){
    //         return $this->forbidden(__('You are not authorized to modify this trip'));
    //     }
        
    //     // Validate trip status - must be at pickup
    //     if($trip->status !== TripStatus::PICKUP_ARRIVED){
    //         return $this->badRequest(__('Can only mark no-show when arrived at pickup location'));
    //     }
        
    //     try {
    //         // Cancel trip with RIDER_NO_SHOW status (no reason needed - it's implicit)
    //         $cancelledTrip = $this->tripService->cancelTrip(
    //             $trip, 
    //             null, // No reason needed for no-show
    //             TripStatus::RIDER_NO_SHOW
    //         );
            
    //         return $this->successResponse(
    //             new MyTripsResource($cancelledTrip),
    //             __('Rider marked as no-show. Cancellation fee applied: :fee', [
    //                 'fee' => $cancelledTrip->cancellation_fee
    //             ])
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }

    /**
     * Confirm cash payment for a trip
     * Driver confirms they received cash payment from the client
     */
    public function confirmCashPayment(\App\Http\Requests\API\V1\Driver\Trips\ConfirmCashPaymentRequest $request, $id){
        $user = Auth::user();
        $trip = $this->tripService->findWithRelations($id, ['payment', 'driver', 'user']);
        
        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }
        
        if($trip->driver_id != $user->driver->id){
            return $this->forbidden(__('You are not authorized to confirm payment for this trip'));
        }
        
        // Validate payment method is cash
        if($trip->payment_method_id != 1){
            return $this->badRequest(__('Cannot confirm payment for this payment method'));
        }
        
        // Validate trip status
        if($trip->status !== TripStatus::COMPLETED_PENDING_PAYMENT){
            return $this->badRequest(__('Trip must be in pending payment status'));
        }
        
        try {
            $confirmedTrip = $this->tripService->driverConfirmPayment($trip);
            
            return $this->successResponse(
                new TripResource($confirmedTrip),
                __('Payment confirmed successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Update trip status with a unified endpoint
     * Handles: arrived, start, end, no-show actions with conditional validation
     */
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $user = Auth::user();
        $trip = $this->tripService->findById($id);
        
        if (!$trip) {
            return $this->notFound(__('Trip not found'));
        }
        
        // Verify ownership
        if ($trip->driver_id !== $user->driver->id) {
            return $this->forbidden(__('You are not authorized to update this trip'));
        }
        
        $action = (int) $request->input('action');
        $validated = $request->validated();
        
        try {
            $result = match($action) {
                TripStatus::CANCELLED_BY_DRIVER->value => $this->handleCancel($trip),
                TripStatus::PICKUP_ARRIVED->value => $this->handleArrived($trip),
                TripStatus::IN_PROGRESS->value => $this->handleStart($trip, $validated),
                TripStatus::COMPLETED->value, 
                TripStatus::COMPLETED_PENDING_PAYMENT->value => $this->handleEnd($trip, $validated),
                TripStatus::RIDER_NO_SHOW->value => $this->handleNoShow($trip),
                default => throw new \InvalidArgumentException(__('Invalid action'))
            };
            
            $message = $this->getSuccessMessage($action);
            
            return $this->successResponse(
                new TripResource($result),
                $message
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle arrived at pickup action
     */
    private function handleArrived(Trip $trip): Trip
    {
        if ($trip->status !== TripStatus::IN_ROUTE_TO_PICKUP) {
            throw new \Exception(__('Can only mark arrived when in route to pickup'));
        }
        return $this->tripService->arrivedAtPickup($trip);
    }

    private function handleCancel(Trip $trip): Trip
    {
        if ($trip->status !== TripStatus::IN_PROGRESS) {
            throw new \Exception(__('Can only cancel trip when in progress'));
        }
        return $this->tripService->cancelTrip($trip, null, TripStatus::CANCELLED_BY_DRIVER);
    }

    /**
     * Handle start trip action
     */
    private function handleStart(Trip $trip, array $data): Trip
    {
        if ($trip->status !== TripStatus::PICKUP_ARRIVED) {
            throw new \Exception(__('Can only start trip when arrived at pickup'));
        }
        
        // Extract only the fields needed for starting trip
        $startData = [
            'pickup_lat' => $data['pickup_lat'],
            'pickup_long' => $data['pickup_long'],
            'pickup_address' => $data['pickup_address'],
        ];
        
        return $this->tripService->start($trip, $startData);
    }

    /**
     * Handle end trip action
     */
    private function handleEnd(Trip $trip, array $data): Trip
    {
        if ($trip->status !== TripStatus::IN_PROGRESS) {
            throw new \Exception(__('Can only end trip when in progress'));
        }
        
        // Extract only the fields needed for ending trip
        $endData = [
            'dropoff_lat' => $data['dropoff_lat'],
            'dropoff_long' => $data['dropoff_long'],
            'dropoff_address' => $data['dropoff_address'],
        ];
        
        return $this->tripService->endTrip($trip, $endData);
    }

    /**
     * Handle rider no-show action
     */
    private function handleNoShow(Trip $trip): Trip
    {
        if ($trip->status !== TripStatus::PICKUP_ARRIVED) {
            throw new \Exception(__('Can only mark no-show when arrived at pickup'));
        }
        return $this->tripService->cancelTrip($trip, null, TripStatus::RIDER_NO_SHOW);
    }

    /**
     * Get success message based on action
     */
    private function getSuccessMessage(int $action): string
    {
        return match($action) {
            TripStatus::PICKUP_ARRIVED->value => __('Arrived at pickup successfully'),
            TripStatus::IN_PROGRESS->value => __('Trip started successfully'),
            TripStatus::COMPLETED->value,
            TripStatus::COMPLETED_PENDING_PAYMENT->value => __('Trip ended successfully'),
            TripStatus::RIDER_NO_SHOW->value => __('Rider marked as no-show'),
            TripStatus::CANCELLED_BY_DRIVER->value => __('Trip cancelled successfully'),
            default => __('Status updated successfully'),
        };
    }

}
