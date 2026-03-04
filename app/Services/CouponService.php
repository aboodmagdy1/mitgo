<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class CouponService extends BaseService
{
    protected $coupon;

    public function __construct(Coupon $coupon)
    {
        $this->coupon = $coupon;
        parent::__construct($coupon);
    }

    /**
     * Get Trip with relationships
     */
    public function findByCode($code): ?Coupon
    {
       $coupon = $this->coupon->where('code', $code);
      
       return $coupon->first();
    }
    /**
     * Validate coupon code and check availability
     * @throws \Exception if coupon is invalid or unavailable
     */
    public function validateCoupon(string $code): Coupon
    {
        // First check if coupon exists
        $coupon = $this->findByCode($code);
        
        if(!$coupon){
            throw new \Exception(__('invalid coupon code'));
        }
        
        // Check if coupon is available (active, not expired, has remaining usage)
        if(!$coupon->isAvailable()){
            throw new \Exception(__('unavailable coupon code'));
        }
        
        // Check if user hasn't exceeded their personal usage limit
        if(!$coupon->availableForUser(Auth::user())){
            throw new \Exception(__('you have reached the maximum usage limit for this coupon'));
        }

        return $coupon;
    }

    /**
     * Record coupon usage in coupon_usages table and increment coupon used_count
     */
    public function recordUsage(Coupon $coupon, int $userId, int $tripId, float $discountAmount): CouponUsage
    {
        // Create coupon usage record
        $couponUsage = CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'trip_id' => $tripId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);

        // Increment coupon's total used_count
        $coupon->increment('used_count');

        return $couponUsage;
    }

    /**
     * Calculate the actual discount amount based on coupon type
     */
    public function calculateDiscountAmount(Coupon $coupon, float $fareBeforeDiscount): float
    {
        if ($coupon->type == Coupon::TYPE_FIXED_AMOUNT) {
            // Fixed amount: discount cannot exceed the fare
            return min($coupon->amount, $fareBeforeDiscount);
        } else {
            // Percentage discount
            $percentageDiscount = ($fareBeforeDiscount * $coupon->amount) / 100;
            
            // Apply max_discount_amount limit if set
            if ($coupon->max_discount_amount) {
                $percentageDiscount = min($percentageDiscount, $coupon->max_discount_amount);
            }
            
            // Discount cannot exceed the fare
            return min($percentageDiscount, $fareBeforeDiscount);
        }
    }

    /**
     * Find Trip with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Coupon
    {
        return $this->coupon->with($relations)->find($id);
    }

    /**
     * Create Trip with business logic
     */
    public function createWithBusinessLogic(array $data): Coupon
    {
        // Add your business logic here before creating
        $this->validateBusinessRules($data);
        
        $coupon = $this->create($data);
        
        // Add your business logic here after creating
        $this->afterCreate($coupon);
        
        return $coupon;
    }

    /**
     * Update Trip with business logic
     */
    public function updateWithBusinessLogic(Coupon $coupon, array $data): bool
    {
        // Add your business logic here before updating
        $this->validateBusinessRules($data, $coupon);
        
        $updated = $this->update($coupon, $data);
        
        if ($updated) {
            // Add your business logic here after updating
                    $this->afterUpdate($coupon);
        }
        
        return $updated;
    }

    /**
     * Delete Trip with business logic
     */
    public function deleteWithBusinessLogic(Coupon $coupon): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($coupon);
        
        $deleted = $this->delete($coupon);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($coupon);
        }
        
        return $deleted;
    }



    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?Coupon $coupon = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(Coupon $coupon): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }

    /**
     * After create business logic
     */
    protected function afterCreate(Coupon $coupon): void
    {
        // Add your post-creation business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After update business logic
     */
    protected function afterUpdate(Coupon $coupon): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(Coupon $coupon): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }



}