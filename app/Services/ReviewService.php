<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewService extends BaseService
{
    protected $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
        parent::__construct($review);
    }

    /**
     * Get reviews with relationships
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->review->with($relations)->get();
    }

    /**
     * Find review with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Review
    {
        return $this->review->with($relations)->find($id);
    }

    /**
     * Get reviews for a specific provider
     */
    public function getProviderReviews(int $providerId, array $relations = []): Collection
    {
        return $this->review->with($relations)
            ->where('provider_id', $providerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

   

    /**
     * Check if user has already reviewed a provider
     */
    public function userHasReviewedProvider(int $userId, int $providerId): bool
    {
        return $this->review->where('user_id', $userId)
            ->where('provider_id', $providerId)
            ->exists();
    }

    /**
     * Create review with business logic
     */
    public function createWithBusinessLogic(array $data): Review
    {
        // Add your business logic here before creating
        $this->validateBusinessRules($data);
        
        // Check if user has already reviewed this provider
        if ($this->userHasReviewedProvider($data['user_id'], $data['provider_id'])) {
            throw new \Exception('You have already reviewed this provider');
        }

        // Validate provider exists
        $provider = Provider::find($data['provider_id']);
        if (!$provider) {
            throw new \Exception('Provider not found');
        }

        $review = $this->create($data);
        
        // Add your business logic here after creating
        $this->afterCreate($review);
        
        return $review;
    }

    /**
     * Update review with business logic
     */
    public function updateWithBusinessLogic(Review $review, array $data): bool
    {
        // Add your business logic here before updating
        $this->validateBusinessRules($data, $review);
        
        $updated = $this->update($review, $data);
        
        if ($updated) {
            // Add your business logic here after updating
            $this->afterUpdate($review);
        }
        
        return $updated;
    }

    /**
     * Delete review with business logic
     */
    public function deleteWithBusinessLogic(Review $review): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($review);
        
        $deleted = $this->delete($review);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($review);
        }
        
        return $deleted;
    }

    /**
     * Get provider statistics
     */
    public function getProviderStats(int $providerId): array
    {
        $threeMonthsAgo = now()->subMonths(3);
        
        $stats = DB::table('reviews')
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            ')
            ->where('provider_id', $providerId)
            ->where('created_at', '>=', $threeMonthsAgo)
            ->first();

        return [
            'total_reviews' => (int) $stats->total_reviews,
            'average_rating' => round((float) $stats->average_rating, 2),
            'rating_distribution' => [
                'five_star' => (int) $stats->five_star,
                'four_star' => (int) $stats->four_star,
                'three_star' => (int) $stats->three_star,
                'two_star' => (int) $stats->two_star,
                'one_star' => (int) $stats->one_star,
            ]
        ];
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?Review $review = null): void
    {
        // Rating must be between 1 and 5
        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            throw new \Exception('Rating must be between 1 and 5');
        }

        // Comment length validation
        if (isset($data['comment']) && strlen($data['comment']) > 1000) {
            throw new \Exception('Comment must not exceed 1000 characters');
        }
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(Review $review): void
    {
        // Add your deletion validation logic here
        // For example, check if user can delete this review
    }

    /**
     * After create hook
     */
    protected function afterCreate(Review $review): void
    {
        // Clear provider rating cache
        Review::clearProviderRatingCache($review->provider_id);
    }

    /**
     * After update hook
     */
    protected function afterUpdate(Review $review): void
    {
        // Clear provider rating cache
        Review::clearProviderRatingCache($review->provider_id);
    }

    /**
     * After delete hook
     */
    protected function afterDelete(Review $review): void
    {
        // Clear provider rating cache
        Review::clearProviderRatingCache($review->provider_id);
    }
}
