<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Review\StoreReviewRequest;
use App\Http\Requests\API\V1\Review\UpdateReviewRequest;
use App\Http\Resources\API\V1\ReviewResource;
use App\Models\Review;
use App\Models\Provider;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Get all reviews for a specific provider
     */
    public function getProviderReviews($id)
    {
        try {
            // Check if provider exists
            $provider = Provider::find($id);
            if(!$provider){
                return $this->errorResponse(__('Provider not found'), 404);
            }
            
            
            $reviews = $this->reviewService->getProviderReviews($provider->id, ['user']);
            
            return $this->successResponse(
                [
                    'provider' =>[
                        'name' => $provider->store_name,
                        'avatar' => $provider->getFirstMediaUrl('logo'),
                        'rating' => $provider->getAverageRating(),
                    ],
                    'reviews' => ReviewResource::collection($reviews),
                ],
                __('Provider reviews retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return  $this->errorResponse(__('Failed to retrieve provider reviews'), 500);
        }
    }



    /**
     * Store a new review
     */
    public function store(StoreReviewRequest $request, int $providerId)
    {
        try {
            // Check if provider exists
            $provider = Provider::find($providerId);
            if(!$provider){
                return $this->errorResponse(__('Provider not found'), 404);
            }
            
            // Check if user has already reviewed this provider
            if ($this->reviewService->userHasReviewedProvider(Auth::id(), $provider->id)) {
                return $this->errorResponse(__('You have already reviewed this provider'), 409);
            }
            
            $data = $request->validated();
            $data['user_id'] = Auth::id();
            $data['provider_id'] = $provider->id;
            
            $review = $this->reviewService->createWithBusinessLogic($data);
            
            return $this->successResponse(
                [],
                __('Review created successfully')
            );
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return $this->errorResponse(__('Failed to create review'), 500);
        }
    }

}
