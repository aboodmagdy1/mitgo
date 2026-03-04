<?php

namespace App\Http\Controllers\API\V1\Client;


use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Client\Trips\CancelTripRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\API\V1\Client\Trips\VehicleTypesListRequest;
use App\Http\Requests\API\V1\Client\Trips\CreateTripRequest;
use App\Http\Requests\API\V1\Client\Trips\TripRateRequest;
use App\Http\Resources\API\V1\TripResource;
use App\Services\TripService;
use App\Services\UserService;
use App\Services\CouponService;
use App\Services\DriverSearchService;
use App\Http\Resources\API\V1\Client\MyTripsResource;
use App\Models\Trip;
use App\Jobs\ProcessDriverSearch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class TripController extends Controller
{

    public function __construct(
                protected TripService $tripService,
                protected CouponService $couponService,
                protected DriverSearchService $driverSearchService
        ){
        }


    /**
     * Get available vehicle types with estimated pricing
     */
    public function vehicleTypesList(VehicleTypesListRequest $request){
        $data = $request->validated();
        $types = $this->tripService->getVehicleTypesList($data);

        return $this->successResponse($types, __('vehicle types retrieved successfully'));
    }

    /**
     * Create a new trip
     * Recalculates pricing server-side to ensure security
     */
    public function createTrip(CreateTripRequest $request)
    {
        try {
            $data = $request->validated();
            
            $user = Auth::user();

            // check coupon code is provided
            if(isset($data['coupon'])){
                $coupon = $this->couponService->validateCoupon($data['coupon']);
                $data['coupon'] = $coupon;
            }
            if($user->hasActiveTrip()){
                return $this->badRequest(__('You already have an active trip'));
            }

            
            $trip = $this->tripService->createTrip($data, $user->id);

            return $this->created(null, __('Trip created successfully'));
        } catch (\Exception $e) {
            return $this->badRequest($e->getMessage());
        }
    }
    public function cancelTrip(CancelTripRequest $request , $id){
        $user = Auth::user();
        // Load necessary relationships including user, zone, and vehicleType for fee calculation
        $trip = $this->tripService->findWithRelations($id, ['user', 'zone', 'vehicleType']);
        
        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }
        if($trip->user_id !== $user->id){
            return $this->forbidden(__('You are not authorized to cancel this trip'));
        }
        
        
        try {
            Log::info('Client cancelling trip', ['trip_id' => $id, 'status' => $trip->status->value]);
            
            $cancelledTrip = $this->tripService->cancelTrip(
                $trip, 
                $request->validated()['reason_id'],
                TripStatus::CANCELLED_BY_RIDER
            );
            
            // Determine message based on whether fee was applied
            $message = $cancelledTrip->cancellation_fee > 0 
                ? __('Trip cancelled. Cancellation fee applied: :fee', ['fee' => $cancelledTrip->cancellation_fee])
                : __('Trip cancelled successfully');
            
            Log::info('Trip cancelled successfully', ['trip_id' => $id, 'fee' => $cancelledTrip->cancellation_fee]);
            
            return $this->successResponse(
                new MyTripsResource($cancelledTrip),
                $message
            );
        }catch(\Symfony\Component\HttpFoundation\Exception\BadRequestException $e){
            return $this->badRequest($e->getMessage());
        }
        catch (\Exception $e) {
            Log::error('Error cancelling trip', ['trip_id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse($e->getMessage());
        }
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
        $trip = $this->tripService->findWithRelations($id, ['driver.user.media','payment.paymentMethod','vehicleType','driver.vehicle']);

        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }

        // Ensure the trip belongs to the authenticated user
        if($trip->user_id !== $user->id){
            return $this->forbidden(__('You are not authorized to view this trip'));
        }
        
        return $this->successResponse(
            new MyTripsResource($trip), 
            __('Trip retrieved successfully')
        );
    }

    /**
     * Confirm online payment for a trip
     * Client confirms successful payment through online payment gateway
     */
    public function confirmOnlinePayment(\App\Http\Requests\API\V1\Client\Trips\ConfirmOnlinePaymentRequest $request, $id){
        $user = Auth::user();
        $trip = $this->tripService->findWithRelations($id, ['payment', 'driver', 'user']);
        
        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }
        
        if($trip->user_id !== $user->id){
            return $this->forbidden(__('You are not authorized to confirm payment for this trip'));
        }
        
        // Validate payment method is online payment (>= 3)
        if($trip->payment_method_id < 3){
            return $this->badRequest(__('Cannot confirm payment for this payment method'));
        }
        
        // Validate trip status
        if($trip->status !== TripStatus::COMPLETED_PENDING_PAYMENT){
            return $this->badRequest(__('Trip must be in pending payment status'));
        }
        
        try {
            $confirmedTrip = $this->tripService->confirmPayment($trip, $request->validated()['transaction_id']);
            
            return $this->successResponse(
                new TripResource($confirmedTrip),
                __('Payment confirmed successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function rateTrip(TripRateRequest $request, $id){
        $user = Auth::user();
        
        $trip = $this->tripService->findWithRelations($id, ['driver', 'user']);
        if(!$trip){
            return $this->notFound(__('Trip not found'));
        }
        if($trip->user_id !== $user->id){
            return $this->forbidden(__('You are not authorized to rate this trip'));
        }
        if($trip->status !== TripStatus::COMPLETED){
            return $this->badRequest(__('Trip must be completed to rate'));
        }
        
        if($trip->rate){
            return $this->badRequest(__('You have already rated this trip'));
        }
        $data = $request->validated();
        $rating = $this->tripService->rateTrip($trip, $data);
        return $this->successResponse([], __('Trip rated successfully'));

    }

    /**
     * Search for drivers in the next wave (manual retry from client)
     */
    public function searchNextWave($id)
    {
        $user = Auth::user();
        $trip = Trip::find($id);

        if (!$trip) {
            return $this->notFound(__('Trip not found'));
        }

        if ($trip->user_id !== $user->id) {
            return $this->forbidden(__('You are not authorized to perform this action'));
        }

        if ($trip->status !== TripStatus::SEARCHING) {
            return $this->badRequest(__('Trip is not in searching status'));
        }

        try {
            $result = $this->tripService->searchNextWave($trip);

            if ($result['success']) {
                return $this->successResponse($result, __('Searching for drivers...'));
            } else {
                return $this->badRequest($result['message'], $result);
            }
        } catch (\Exception $e) {
            Log::error('Error searching next wave', [
                'trip_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Restart automatic driver search (after no driver found)
     */
    public function restartSearch($id)
    {
        $user = Auth::user();
        $trip = Trip::find($id);

        if (!$trip) {
            return $this->notFound(__('Trip not found'));
        }

        if ($trip->user_id !== $user->id) {
            return $this->forbidden(__('You are not authorized to perform this action'));
        }

        // Only allow restart if trip is in NO_DRIVER_FOUND status
        if ($trip->status !== TripStatus::NO_DRIVER_FOUND) {
            return $this->badRequest(__('Trip is not available for search restart'));
        }

        try {
            // Reset trip status to SEARCHING
            $trip->update(['status' => TripStatus::SEARCHING]);

            // Clear old search data (but don't broadcast since we're restarting)
            $this->driverSearchService->clearTripSearchData($trip->id, false);

            // Restart automatic search from wave 1
            ProcessDriverSearch::dispatch($trip->id, 1);

            Log::info('Driver search restarted', [
                'trip_id' => $trip->id,
                'user_id' => $user->id
            ]);

            return $this->successResponse(new MyTripsResource($trip), __('Search restarted. Looking for drivers...'));
        } catch (\Exception $e) {
            Log::error('Error restarting search', [
                'trip_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

}
