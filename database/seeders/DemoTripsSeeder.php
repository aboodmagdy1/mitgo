<?php

namespace Database\Seeders;

use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\PaymentMethod;
use App\Models\Trip;
use App\Models\TripPayment;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DemoTripsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have the necessary related records
        $user = User::query()->first();
        $driver = Driver::query()->with('user')->first();
        $vehicleType = VehicleType::query()->first();
        $zone = Zone::query()->first();
        $walletMethod = PaymentMethod::query()->where('id', 2)->first() ?? PaymentMethod::query()->first();

        if (! $user || ! $driver || ! $vehicleType || ! $zone || ! $walletMethod) {
            $this->command?->warn('DemoTripsSeeder skipped: missing user/driver/vehicleType/zone/paymentMethod.');
            return;
        }

        // Clean existing demo data (optional but keeps things tidy on repeated runs)
        TripPayment::query()->delete();
        Trip::query()->delete();

        $now = Carbon::now();

        // Distribution of statuses
        $statusBuckets = [
            TripStatus::COMPLETED->value => 80,
            TripStatus::COMPLETED_PENDING_PAYMENT->value => 40,
            TripStatus::CANCELLED_BY_RIDER->value => 20,
            TripStatus::CANCELLED_BY_DRIVER->value => 20,
            TripStatus::PAYMENT_FAILED->value => 10,
            TripStatus::SCHEDULED->value => 10,
            TripStatus::IN_PROGRESS->value => 20,
        ];

        $statuses = [];
        foreach ($statusBuckets as $statusValue => $count) {
            $statuses = array_merge($statuses, array_fill(0, $count, $statusValue));
        }

        $statuses = Arr::shuffle($statuses);

        foreach (array_slice($statuses, 0, 200) as $index => $statusValue) {
            $startedAt = $now->copy()->subDays(rand(0, 30))->subMinutes(rand(0, 120));
            $endedAt = (clone $startedAt)->addMinutes(rand(10, 45));

            $baseFare = rand(2000, 12000) / 100; // 20 – 120
            $waitingFee = rand(0, 500) / 100;    // 0 – 5
            $cancellationFee = in_array($statusValue, [
                TripStatus::CANCELLED_BY_DRIVER->value,
                TripStatus::CANCELLED_BY_RIDER->value,
            ], true) ? rand(500, 3000) / 100 : 0;

            $distance = rand(3, 25); // km

            $trip = Trip::query()->create([
                'number' => random_int(100000, 999999),
                'user_id' => $user->id,
                'driver_id' => $driver->id,
                'vehicle_type_id' => $vehicleType->id,
                'payment_method_id' => $walletMethod->id,
                'zone_id' => $zone->id,
                'status' => $statusValue,
                'pickup_lat' => 24.7136,
                'pickup_long' => 46.6753,
                'pickup_address' => 'Demo pickup address',
                'dropoff_lat' => 24.7743,
                'dropoff_long' => 46.7386,
                'dropoff_address' => 'Demo dropoff address',
                'distance' => $distance,
                'estimated_duration' => rand(10, 45),
                'actual_duration' => rand(10, 45),
                'cancellation_fee' => $cancellationFee,
                'waiting_fee' => $waitingFee,
                'estimated_fare' => $baseFare,
                'actual_fare' => $baseFare + $waitingFee - $cancellationFee,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);

            // Create payment records for trips that should appear in financial reports
            if (in_array($statusValue, [
                TripStatus::COMPLETED->value,
                TripStatus::COMPLETED_PENDING_PAYMENT->value,
                TripStatus::PAID->value,
                TripStatus::CANCELLED_BY_DRIVER->value,
                TripStatus::CANCELLED_BY_RIDER->value,
            ], true)) {
                $commissionRate = 10;
                $fareBeforeCoupon = max($baseFare + $waitingFee, 0);
                $commissionAmount = round(($fareBeforeCoupon * $commissionRate) / 100, 2);
                $driverEarning = round($fareBeforeCoupon - $commissionAmount, 2);

                $finalAmount = $fareBeforeCoupon;
                if ($cancellationFee > 0) {
                    $fareBeforeCoupon = $cancellationFee;
                    $commissionRate = 100;
                    $commissionAmount = $fareBeforeCoupon;
                    $driverEarning = 0;
                    $finalAmount = $cancellationFee;
                }

                TripPayment::query()->create([
                    'trip_id' => $trip->id,
                    'payment_method_id' => $walletMethod->id,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'total_amount' => $fareBeforeCoupon,
                    'final_amount' => $finalAmount,
                    'driver_earning' => $driverEarning,
                    'status' => $statusValue === TripStatus::COMPLETED_PENDING_PAYMENT->value ? 0 : 1,
                    'coupon_discount' => 0,
                    'additional_fees' => $waitingFee,
                ]);
            }
        }
    }
}

