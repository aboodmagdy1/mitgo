<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\CashbackCampaign;
use App\Models\CashbackUsage;
use App\Models\Trip;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;

class CashbackService
{
    public function getActiveCampaignForTrip(Trip $trip): ?CashbackCampaign
    {
        return CashbackCampaign::availableForNow()->first();
    }

    public function isEligible(Trip $trip, CashbackCampaign $campaign): bool
    {
        if ($trip->status !== TripStatus::COMPLETED) {
            return false;
        }

        if (!$campaign->is_active || !$campaign->isWithinDateWindow()) {
            return false;
        }

        if ($campaign->max_trips_global !== null && $campaign->used_trips_global >= $campaign->max_trips_global) {
            return false;
        }

        if (!$campaign->can_stack_with_coupon && $trip->coupon_id) {
            return false;
        }

        if (!$trip->user) {
            return false;
        }

        return $campaign->isAvailableForUser($trip->user);
    }

    public function calculateAmount(CashbackCampaign $campaign, float $fareAfterDiscount): float
    {
        if ($fareAfterDiscount <= 0) {
            return 0.0;
        }

        if ($campaign->type === CashbackCampaign::TYPE_FIXED_AMOUNT) {
            return (float) min($campaign->amount, $fareAfterDiscount);
        }

        $amount = ($fareAfterDiscount * (float) $campaign->amount) / 100;

        if ($campaign->max_cashback_amount !== null) {
            $amount = min($amount, (float) $campaign->max_cashback_amount);
        }

        return (float) min($amount, $fareAfterDiscount);
    }

    public function awardForTrip(Trip $trip, float $fareAfterDiscount): ?CashbackUsage
    {
        if ($fareAfterDiscount <= 0) {
            return null;
        }

        if ($trip->cashbackUsage) {
            return $trip->cashbackUsage;
        }

        $campaign = $this->getActiveCampaignForTrip($trip);

        if (!$campaign) {
            return null;
        }

        if (!$this->isEligible($trip, $campaign)) {
            return null;
        }

        $amount = $this->calculateAmount($campaign, $fareAfterDiscount);

        if ($amount <= 0) {
            return null;
        }

        /** @var User $user */
        $user = $trip->user;

        $transaction = $user->deposit((int) round($amount * 100), [
            'type' => 'cashback',
            'trip_id' => $trip->id,
            'cashback_campaign_id' => $campaign->id,
            'description' => __('Cashback for trip #:number', ['number' => $trip->number]),
        ]);

        if ($campaign->max_trips_global !== null) {
            $campaign->increment('used_trips_global');
        }

        return CashbackUsage::create([
            'cashback_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'cashback_amount' => $amount,
            'wallet_transaction_id' => $transaction instanceof Transaction ? $transaction->id : null,
            'awarded_at' => now(),
        ]);
    }

    public function previewForTrip(Trip $trip, float $fareAfterDiscount): float
    {
        if ($fareAfterDiscount <= 0) {
            return 0.0;
        }

        $campaign = $this->getActiveCampaignForTrip($trip);

        if (!$campaign || !$trip->user) {
            return 0.0;
        }

        if (!$campaign->isAvailableForUser($trip->user)) {
            return 0.0;
        }

        if (!$campaign->can_stack_with_coupon && $trip->coupon_id) {
            return 0.0;
        }

        return $this->calculateAmount($campaign, $fareAfterDiscount);
    }
}

