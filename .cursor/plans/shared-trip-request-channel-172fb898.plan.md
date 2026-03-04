---
name: Dual-Channel Trip Events System
overview: ""
todos:
  - id: c942d1f5-033b-40ca-8f0f-da5bdbad5534
    content: Update TripRequestSent event to use trip-request.{tripId} channel and include complete trip, rider, and distance data
    status: pending
  - id: 33f83369-9879-4a8f-a5cd-01266d29c850
    content: Update TripRequestExpired event to use trip-request.{tripId} channel and include reason
    status: pending
  - id: c1ee98f4-be5e-4472-bc48-3076d50063ac
    content: Add calculatePickupDistance method to DriverSearchService for per-driver distance/duration
    status: pending
  - id: 856301f1-9eeb-418a-b228-dd9215e2667f
    content: Update notifyDrivers() in DriverSearchService to broadcast once with all driver data
    status: pending
  - id: 0ee86f22-dd6f-41b7-9782-bcb6c6a7d99d
    content: Update acceptTrip() in TripService to fire single event with reason 'accepted'
    status: pending
  - id: c33ba430-edfa-4a2a-8d91-0f9e3a7e5c3f
    content: Update ExpireDriverRequest job to fire single event to shared channel
    status: pending
  - id: 6ae9f60a-a88b-423b-b494-6563d5a4798f
    content: Update cancelTrip() to fire TripRequestExpired with reason 'cancelled'
    status: pending
  - id: 151abd1b-68dd-464b-8a78-9e65bd88f55e
    content: "Test complete flow: send request, accept, verify single broadcast to shared channel"
    status: pending
isProject: false
---

# Dual-Channel Trip Events System

## Phase 1: Create Missing Event Files

### Step 1: Create New Event Classes

Create 4 new event files with dual-channel broadcasting:

**Files to create:**

- `app/Events/TripStarted.php` - broadcasts to both channels when driver starts trip
- `app/Events/TripEnded.php` - broadcasts to both channels when driver ends trip
- `app/Events/TripNoShow.php` - broadcasts to both channels when rider no-show
- `app/Events/TripCompleted.php` - broadcasts to both channels after payment confirmed

**Structure for each event:**

```php
public function broadcastOn(): array
{
    return [
        new Channel("trip.{$this->trip->id}"),        // Rider channel
        new Channel("driver-trip.{$this->trip->id}")  // Driver channel
    ];
}
```

Each event will have:

- `getDriverData()` - returns driver-specific payload
- `getRiderData()` - returns rider-specific payload  
- Context detection to return correct data per channel

---

## Phase 2: Refactor Existing Events to Dual-Channel

### Step 2: Update TripDriverArrived Event

**File:** `app/Events/TripDriverArrived.php`

Add dual-channel broadcasting with:

- Driver data: rider info, pickup/dropoff, earnings
- Rider data: driver info, vehicle, ETA, trip details

### Step 3: Update TripDriverAccepted Event

**File:** `app/Events/TripDriverAccepted.php`

Add dual-channel broadcasting with:

- Driver data: rider info, trip details, earnings
- Rider data: driver info, vehicle, ETA

### Step 4: Update TripCancelled Event

**File:** `app/Events/TripCancelled.php`

Add dual-channel broadcasting with:

- Driver data: cancellation reason, compensation info
- Rider data: cancellation reason, refund info

---

## Phase 3: Update Service Methods

### Step 5: Add Event Broadcasts to Existing Methods

**File:** `app/Services/TripService.php`

Update existing methods to fire new events:

**arrivedAtPickup() method:**

```php
event(new TripDriverArrived($trip));
```

**startTrip() method:**

```php
event(new TripStarted($trip));
```

**endTrip() method:**

```php
event(new TripEnded($trip));
```

**confirmPayment() method:**

```php
event(new TripCompleted($trip));
```

### Step 6: Update cancelTrip to Fire No-Show Event

**File:** `app/Services/TripService.php`

Find the existing `cancelTrip()` method (signature: `public function cancelTrip(Trip $trip, ?int $cancelReasonId, ?TripStatus $cancelStatus = null)`)

Add conditional event firing at the end of the method based on final status:

```php
// After all existing logic and $trip->update()
// Fire appropriate event based on final status
if ($trip->status === TripStatus::RIDER_NO_SHOW) {
    event(new TripNoShow($trip));
} else {
    event(new TripCancelled($trip));
}
```

Method already updates:

- `status` (CANCELLED_BY_RIDER, CANCELLED_BY_DRIVER, or RIDER_NO_SHOW)
- `cancellation_fee` (calculated fee if applicable)
- `cancellation_reason_id` (ID or null)

---

## Phase 4: Add Driver Status Controller

### Step 7: Add Status Update Endpoint

**File:** `app/Http/Controllers/API/V1/Driver/TripController.php`

Add method:

```php
public function updateStatus(Request $request, Trip $trip)
{
    $action = $request->input('action'); // TripStatus enum value
    
    // Verify ownership
    if ($trip->driver_id !== auth()->user()->driver->id) {
        return $this->forbidden();
    }
    
    $result = match($action) {
        TripStatus::DRIVER_ARRIVED->value => $this->tripService->arrivedAtPickup($trip),
        TripStatus::IN_PROGRESS->value => $this->tripService->startTrip($trip),
        TripStatus::COMPLETED->value => $this->tripService->endTrip($trip),
        TripStatus::RIDER_NO_SHOW->value => $this->tripService->cancelTrip($trip, null, TripStatus::RIDER_NO_SHOW),
        default => throw new \InvalidArgumentException('Invalid action')
    };
    
    return $this->successResponse($result);
}
```

**File:** `routes/API/V1/driver.routes.php`

Add route:

```php
Route::post('trips/{trip}/status', [TripController::class, 'updateStatus']);
```

Keep separate:

```php
Route::post('trips/{trip}/confirm-payment', [TripController::class, 'confirmPayment']);
```

---

## Phase 5: Documentation

### Step 8: Update events.md

**File:** `events.md`

Add comprehensive documentation:

**Channel Architecture:**

1. **Driver Channels:**
  - `driver.{driverId}` - Personal driver notifications
  - `driver-trip.{tripId}` - Trip lifecycle (driver perspective)
2. **Rider Channels:**
  - `trip.{tripId}` - Trip lifecycle (rider perspective)
  - `trip-request.{tripId}` - Request expiration (shared)

**Events by Channel:**

**driver.{driverId}:**

- `trip_request_sent` - New trip available

**driver-trip.{tripId}:**

- `driver_accepted` - Trip accepted (driver view)
- `driver_arrived` - Arrived at pickup (driver view)
- `trip_started` - Trip started (driver view)
- `trip_ended` - Trip ended (driver view)
- `trip_completed` - Payment confirmed (driver view)
- `trip_cancelled` - Trip cancelled (driver view)
- `trip_no_show` - Rider no-show (driver view)

**trip.{tripId}:**

- `search_progress` - Search wave progress
- `driver_accepted` - Driver accepted (rider view)
- `driver_arrived` - Driver arrived (rider view)
- `trip_started` - Trip started (rider view)
- `trip_ended` - Trip ended (rider view)
- `trip_completed` - Payment confirmed (rider view)
- `trip_cancelled` - Trip cancelled (rider view)
- `trip_no_show` - Rider no-show (rider view)

**trip-request.{tripId}:**

- `trip_request_expired` - Request expired/cleared

**Mobile Integration Examples:**

```javascript
// Driver app
Echo.channel(`driver-trip.${tripId}`)
  .listen('.trip_cancelled', (data) => {
    // data.status.text = "Trip cancelled by rider"
    // data.compensation = {...}
  });

// Rider app
Echo.channel(`trip.${tripId}`)
  .listen('.trip_cancelled', (data) => {
    // data.status.text = "Trip has been cancelled"  
    // data.refund = {...}
  });
```

---

## Summary

**New Files (4):**

- TripStarted.php
- TripEnded.php
- TripNoShow.php
- TripCompleted.php

**Modified Files (7):**

- TripDriverArrived.php
- TripDriverAccepted.php
- TripCancelled.php
- TripService.php
- Driver/TripController.php
- driver.routes.php
- events.md

**New Routes:**

- POST /api/driver/trips/{id}/status (actions: arrive, start, end, no_show)
- Keep: POST /api/driver/trips/{id}/confirm-payment

**Key Changes:**

- Dual-channel broadcasting for all trip lifecycle events
- Driver sees: rider info, earnings, technical status
- Rider sees: driver info, vehicle, friendly status
- cancelTrip fires TripNoShow event when status is RIDER_NO_SHOW
- Unified status endpoint for driver actions

