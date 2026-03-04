# Trip Payments Integration Guide

This document explains the trip payments system integration and how to use it.

## Overview

The `trip_payments` table stores financial transaction details separately from trip logistics, following the Single Responsibility Principle and best database design practices.

## Database Structure

### Trip Payments Table
- `trip_id` - Foreign key to trips table
- `payment_method_id` - Payment method used
- `commission_rate` - Platform commission percentage at time of transaction
- `commission_amount` - Platform earnings (calculated from fare × commission rate)
- `total_amount` - Gross amount before coupon discount (for accounting/reporting purposes)
- `final_amount` - Final amount charged to customer (after coupon discount) - what customer actually pays
- `driver_earning` - Amount driver receives (fare - commission + additional fees)
- `status` - Payment status (0: Pending, 1: Completed, 2: Failed, 3: Refunded)
- `coupon_discount` - Discount amount applied
- `coupon_id` - Foreign key to coupons table
- `additional_fees` - Extra fees (waiting time, tolls, etc.)

**Note:** 
- `total_amount` = Gross fare before coupon discount (used for accounting, reporting, and driver earnings calculation)
- `final_amount` = Net amount after coupon discount (what customer actually pays)
- For trips without coupons: `total_amount` = `final_amount`
- For cancellations: `total_amount` = `final_amount` = cancellation fee

## Models

### TripPayment Model
Location: `app/Models/TripPayment.php`

**Relationships:**
- `trip()` - BelongsTo Trip
- `paymentMethod()` - BelongsTo PaymentMethod
- `coupon()` - BelongsTo Coupon

**Helper Methods:**
- `isCompleted()` - Check if payment is completed
- `isPending()` - Check if payment is pending
- `isFailed()` - Check if payment failed
- `getBaseFareAttribute()` - Get base fare before fees and discounts

### Trip Model Updates
Added relationship:
```php
public function payment(): HasOne
{
    return $this->hasOne(TripPayment::class);
}
```

## Service Layer

### TripService Methods

#### Complete a Trip
```php
public function completeTrip(Trip $trip, array $data = []): Trip
```

**Usage:**
```php
$tripService = app(TripService::class);

$completedTrip = $tripService->completeTrip($trip, [
    'actual_fare' => 85.50,
    'waiting_fee' => 10.00,
    'additional_fees' => 5.00,
    'actual_duration' => 45, // minutes
]);
```

**What it does:**
1. Calculates total amount including fees and discounts
2. Gets commission rate from settings
3. Calculates platform commission
4. Calculates driver earnings
5. Updates trip status to PAID
6. Creates payment record
7. Uses database transaction for atomicity

#### Cancel a Trip
```php
public function cancelTrip(
    Trip $trip, 
    int $cancelReasonId, 
    ?float $cancellationFee = null,
    ?TripStatus $cancelStatus = null
): Trip
```

**Usage:**
```php
// Cancel by rider
$cancelledTrip = $tripService->cancelTrip(
    $trip, 
    $cancelReasonId, 
    15.00, // cancellation fee
    TripStatus::CANCELLED_BY_RIDER
);

// Cancel by driver
$cancelledTrip = $tripService->cancelTrip(
    $trip, 
    $cancelReasonId, 
    null, // no fee
    TripStatus::CANCELLED_BY_DRIVER
);
```

## API Resources

### MyTripsResource
Location: `app/Http/Resources/API/V1/Client/MyTripsResource.php`

**List View:**
```json
{
    "id": 123,
    "status": {
        "id": 10,
        "name": "Paid"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 20.00
}
```

**Detail View (includes invoice):**
```json
{
    "id": 123,
    "status": {
        "id": 10,
        "name": "Paid"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 20.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method_id": 1,
    "driver": {
        "id": 456,
        "name": "Ahmed Ali",
        "phone": "+966501234567",
        "rating": 4.8
    },
    "invoice": {
        "vehicle_type": "سيارة صغيرة",
        "payment_method": "فيزا",
        "base_cost": 18.00,
        "additional_fees": 2.00,
        "coupon_discount": 0.00,
        "total": 20.00,
        "distance": 12.5,
        "duration": 25,
        "payment_status": "Completed"
    }
}
```

### TripInvoiceResource
Location: `app/Http/Resources/API/V1/Client/TripInvoiceResource.php`

Handles invoice data transformation, calculating:
- Base fare
- Additional fees
- Coupon discounts
- Final total
- Commission amounts (if needed)
- Driver earnings (if needed)

## API Endpoints

### Get Trip List
```
GET /api/v1/client/trips
Authorization: Bearer {token}
```

Returns all trips for authenticated user with basic info.

### Get Trip Details
```
GET /api/v1/client/trips/{id}
Authorization: Bearer {token}
```

Returns full trip details including invoice data.

## Example Usage Flow

### 1. Create Trip
```php
// Trip is created in SEARCHING or SCHEDULED status
$trip = $tripService->createTrip($data, $userId);
// No payment record yet - trip not completed
```

### 2. Driver Accepts & Completes Trip
```php
// When driver completes trip
$completedTrip = $tripService->completeTrip($trip, [
    'actual_fare' => 85.50,
    'waiting_fee' => 10.00,
    'actual_duration' => 45,
]);

// Now trip has payment record with all financial details
```

### 3. View Invoice
```php
// User requests trip details
$trip = Trip::with(['payment', 'vehicleType', 'paymentMethod', 'driver'])
    ->find($tripId);
    
return new MyTripsResource($trip);
// Invoice is included in response
```

## Commission Rate Configuration

The commission rate is stored in settings and can be configured via admin panel:

**Setting:** `GeneralSettings::commission_rate`
**Location:** Settings > General Settings
**Type:** Integer (percentage)
**Default:** 10%

## Payment Status Codes

- `0` - Pending
- `1` - Completed
- `2` - Failed
- `3` - Refunded

## Trip Status Codes (TripStatus Enum)

- `1` - SEARCHING
- `4` - IN_ROUTE_TO_PICKUP
- `5` - PICKUP_ARRIVED
- `7` - IN_PROGRESS
- `8` - COMPLETED_PENDING_PAYMENT
- `9` - PAYMENT_FAILED
- `10` - PAID (Completed & Paid)
- `11` - CANCELLED_BY_DRIVER
- `12` - CANCELLED_BY_RIDER
- `13` - CANCELLED_BY_SYSTEM
- `15` - SCHEDULED

## Benefits of Separate Payments Table

1. **Financial Tracking** - Separate audit trail for money transactions
2. **Commission Management** - Captures commission rate at time of transaction (rates may change)
3. **Complex Pricing** - Handles base fare, fees, discounts separately
4. **Payment States** - Track payment processing status independently
5. **Driver Payouts** - Clear separation of driver earnings vs platform commission
6. **Refunds** - Easy to handle refund workflows
7. **Data Normalization** - Trip logistics separate from financial transactions
8. **Reporting** - Simplified financial reporting and analytics

## Migration Notes

If you have existing trips with payments stored directly in trips table:
1. Create migration to transfer data to trip_payments table
2. Calculate commission amounts based on current settings
3. Set payment status based on trip status
4. Verify data integrity
5. Update application code to use new structure

## Testing

When testing payment integration:
1. Test trip completion with various fee combinations
2. Test cancellations with and without fees
3. Test coupon discount calculations
4. Verify commission calculations
5. Check payment status transitions
6. Test eager loading to avoid N+1 queries

## Future Enhancements

Possible future improvements:
- Payment gateway integration
- Refund processing
- Split payments (e.g., partial wallet + card)
- Payment retry logic for failed transactions
- Payment history/audit log
- Driver payout scheduling
- Tax calculations

