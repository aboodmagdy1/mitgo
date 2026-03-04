<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TripStatus as EnumsTripStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\API\V1\SettingResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\API\V1\RatingCommentResource;
use App\Http\Resources\API\V1\CancelTripReasonResource;
use App\Http\Resources\API\V1\VehicleBrandResource;
use App\Http\Resources\API\V1\VehicleBrandModelResource;
use App\Models\RatingComment;
use App\Models\TripStatus;
use App\Models\Day;
use App\Models\Onboarding;
use App\Models\Faq;
use App\Services\CityService;
use App\Models\PaymentMethod;
use App\Models\CancelTripReason;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use App\Enums\CancelTripReasonType;
use App\Http\Resources\API\V1\PaymentMethodsResource;
use function App\Helpers\setting;
use function App\Helpers\settings;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class GeneralController extends Controller
{
    public function __construct (
    private CityService $cityService,
     ){}


   
    public function cities()
    {
        try {
            $cities = $this->cityService->getWithRelations();
            // add city with  0 id and name الكل , all 
            return $this->collectionResponse(Cityresource::collection($cities), __('Cities retrieved successfully'));
        }
        catch (\Exception $e) {
            return $this->handleException($e, __('Failed to retrieve cities'));
        }
    }
    public function settings()
    {
        try {
            $emergency_phone = setting('general','emergency_phone');
            return $this->successResponse([
                'emergency_phone' => $emergency_phone,
            ], __('Settings retrieved successfully'));
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return $this->errorResponse( __('Failed to retrieve settings'),500);
        }
    }
    // Content Routes 
    public function terms()
    {
        $terms = setting('content','privacy_'.app()->getLocale());
        return $this->successResponse($terms, __('Terms retrieved successfully'));
    }
    public function aboutUs()
    {
        $aboutUs = setting('content','about_us_'.app()->getLocale());
        return $this->successResponse($aboutUs, __('About Us retrieved successfully'));
    }
    public function faqs()
    {
        $faqs = Faq::all();
        return $this->successResponse(FaqResource::collection($faqs), __('FAQs retrieved successfully'));
    }

    public function paymentMethods()
    {
        $paymentMethods = PaymentMethod::all();
        return $this->successResponse(PaymentMethodsResource::collection($paymentMethods), __('Payment Methods retrieved successfully'));
    }
    public function tripStatuses()
    {
        $tripStatuses = EnumsTripStatus::getValues();
        return $this->successResponse($tripStatuses, __('Trip Statuses retrieved successfully'));
    }
    public function rateComments()
    {
        $rateComments = RatingComment::active()->get();
        return $this->successResponse(RatingCommentResource::collection($rateComments), __('Rate Comments retrieved successfully'));
    }

    public function cancelReasons()
    {
        try {
            $query = CancelTripReason::query();
            
            // Filter by type if provided
            if (request()->has('type') && request('type') !== null) {
                $type = (int) request('type');
                $query->where('type', $type);
            }
            
            
            $reasons = $query->active()->get();
            
            return $this->successResponse(
                CancelTripReasonResource::collection($reasons), 
                __('Cancel reasons retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->errorResponse(__('Failed to retrieve cancel reasons'), 500);
        }
    }
    
    public function vehicleBrands()
    {
        try {
            $brands = VehicleBrand::where('active', true)->get();

            return $this->successResponse(
                VehicleBrandResource::collection($brands),
                __('Vehicle brands retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->errorResponse(__('Failed to retrieve vehicle brands'), 500);
        }
    }

    public function vehicleBrandModels(int $vehicle_brand)
    {
        try {
            $models = VehicleBrandModel::where('vehicle_brand_id', $vehicle_brand)
                ->where('active', true)
                ->get();

            return $this->successResponse(
                VehicleBrandModelResource::collection($models),
                __('Vehicle brand models retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->errorResponse(__('Failed to retrieve vehicle brand models'), 500);
        }
    }
}       