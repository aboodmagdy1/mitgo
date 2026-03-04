# Shared Trip Request Channel Implementation

## Overview
Successfully implemented a shared `trip-request.{tripId}` channel for broadcasting trip expiration events to all notified drivers, reducing broadcast overhead from N events to 1 event per trip completion.

## Architecture

### Event Flow

```
1. Trip created → Drivers searched
2. Drivers notified → Each receives TripRequestSent on driver.{driverId} (includes trip_id)
3. Mobile apps subscribe to trip-request.{tripId}
4. Trip resolved (accepted/expired/cancelled) → ONE event to trip-request.{tripId}
5. All subscribed drivers receive expiration and clear UI
```

### Channels Used

| Channel | Purpose | Events |
|---------|---------|--------|
| `driver.{driverId}` | Initial trip request notification | `new_trip_request` (TripRequestSent) |
| `trip-request.{tripId}` | Broadcast expiration/completion | `trip_request_expired` (TripRequestExpired) |
| `trip.{tripId}` | Client updates | `driver_accepted` (TripDriverAccepted) |

## Implementation Details

### 1. TripRequestExpired Event
**File:** `app/Events/TripRequestExpired.php`

**Changes:**
- Changed channel from `driver.{driverId}` to `trip-request.{tripId}`
- Removed `$driverId` property
- Added `$reason` property (optional: expired, accepted, cancelled, no_driver)
- Constructor signature: `__construct(int $tripId, ?string $reason = null)`

**Broadcast Data:**
```json
{
  "trip_id": 123,
  "reason": "accepted" // or "expired", "cancelled", "no_driver"
}
```

### 2. TripRequestSent Event (Enhanced)
**File:** `app/Events/TripRequestSent.php`

**Enhancements:**
- Added `$driverLat` and `$driverLong` properties
- Calculate real-time pickup distance/duration using Google Distance Matrix API
- Added rider avatar to broadcast data

**New Broadcast Data:**
```json
{
  "trip_id": 123,
  "pickup_lat": 24.7136,
  "pickup_long": 46.6753,
  "pickup_address": "King Fahd Road, Riyadh",
  "dropoff_lat": 24.7240,
  "dropoff_long": 46.6850,
  "dropoff_address": "Kingdom Centre, Riyadh",
  "estimated_fare": 25.50,
  "estimated_duration": 15,
  "estimated_distance": 5.2,
  "pickup_arrive_distance": 2.3,  // NEW: Driver's distance to pickup (km)
  "pickup_arrive_duration": 5,     // NEW: Driver's time to pickup (min)
  "vehicle_type": {
    "id": 1,
    "name": "Standard"
  },
  "payment_method": {
    "id": 1,
    "name": "Cash"
  },
  "rider": {
    "name": "Mohammed",
    "phone": "+966501234567",
    "avatar": "https://..."  // NEW: Rider avatar URL
  },
  "acceptance_time": 60,
  "expires_at": "2025-10-14T19:10:00Z"
}
```

### 3. TripService - acceptTrip()
**File:** `app/Services/TripService.php`

**Changes:**
- Fire ONE `TripRequestExpired($tripId, 'accepted')` to shared channel
- Removed foreach loop that fired individual events
- All notified drivers automatically receive expiration via shared channel

### 4. ExpireDriverRequest Job
**File:** `app/Jobs/ExpireDriverRequest.php`

**Changes (Option A - Simpler):**
- Job still runs per driver (checks individual Redis)
- Added Redis flag `trip:{tripId}:expiration_broadcast` to prevent duplicate events
- First job to run broadcasts to shared channel with reason 'expired'
- Subsequent jobs skip broadcasting (already done)
- All jobs still clear their individual driver Redis keys

### 5. DriverSearchService
**File:** `app/Services/DriverSearchService.php`

**Changes:**

**Method: `clearTripSearchData()`**
- Added `$reason` parameter
- When `$clearDriverRequests = true`, fires ONE event to shared channel
- Used for trip cancellation and no driver found scenarios

**Method: `notifyDrivers()`**
- Fetch driver's current location (latest_lat, latest_long)
- Pass driver location to `TripRequestSent` event
- Event calculates real-time pickup distance/duration

**Method: `handleNoDriverFound()`**
- Calls `clearTripSearchData($tripId, true, 'no_driver')`
- Broadcasts expiration with reason 'no_driver'

## Event Reasons

| Reason | When | Broadcast By |
|--------|------|--------------|
| `accepted` | Driver accepts trip | TripService::acceptTrip() |
| `expired` | No response after timeout | ExpireDriverRequest job |
| `cancelled` | Rider/System cancels trip | TripService::cancelTrip() |
| `no_driver` | Max waves reached | DriverSearchService::handleNoDriverFound() |

## Mobile App Integration

### Subscribe to Channels
```javascript
// 1. Listen on personal channel for new requests
Echo.channel(`driver.${driverId}`)
  .listen('.new_trip_request', (data) => {
    // Show trip request dialog
    setActiveTripRequest(data);
    
    // 2. Subscribe to shared expiration channel
    Echo.channel(`trip-request.${data.trip_id}`)
      .listen('.trip_request_expired', (event) => {
        // Clear UI regardless of reason
        setActiveTripRequest(null);
        
        // Optional: Show message based on reason
        if (event.reason === 'accepted') {
          // Another driver accepted
        } else if (event.reason === 'expired') {
          // Time ran out
        } else if (event.reason === 'cancelled') {
          // Trip cancelled
        }
      });
  });
```

### Unsubscribe When Done
```javascript
// After accepting or request expires
Echo.leave(`trip-request.${tripId}`);
```

## Benefits

### Performance
- **Before:** N broadcasts per trip (one per driver)
- **After:** 1 broadcast per trip (shared channel)
- **Example:** 10 drivers = 90% reduction in broadcasts

### Scalability
- Handles 100+ drivers per trip efficiently
- Single Redis operation instead of N operations
- Reduced network traffic

### Data Richness
- Real-time distance/duration from driver to pickup
- Rider avatar for better UX
- Estimated fare, duration, distance already calculated

### Simplicity
- One event type for all expiration scenarios
- Mobile app uses single handler
- Clean, maintainable code

## Redis Keys Used

| Key | Purpose | TTL |
|-----|---------|-----|
| `driver:{driverId}:active_request` | Driver's active trip request | acceptance_time + 10s |
| `trip:{tripId}:notified_drivers` | Set of notified driver IDs | Until trip cleared |
| `trip:{tripId}:current_wave` | Current search wave number | Until trip cleared |
| `trip:{tripId}:current_radius` | Current search radius (km) | Until trip cleared |
| `trip:{tripId}:search_started` | Idempotency flag | Until trip cleared |
| `trip:{tripId}:expiration_broadcast` | Prevent duplicate broadcasts | 300s (5 min) |

## Testing Scenarios

### Scenario 1: Driver Accepts Trip
```
1. Driver 1, 2, 3 receive trip requests on personal channels
2. All subscribe to trip-request.{tripId}
3. Driver 2 accepts after 10 seconds
4. ONE event broadcast to trip-request.{tripId} with reason 'accepted'
5. All 3 drivers receive expiration and clear UI
6. Driver 2 gets API response with trip details
```

### Scenario 2: Request Times Out
```
1. Driver 1, 2 receive trip requests
2. Both subscribe to trip-request.{tripId}
3. No driver responds
4. After 60 seconds, first job runs
5. Job broadcasts to trip-request.{tripId} with reason 'expired'
6. Both drivers receive expiration
7. Second job runs, sees broadcast flag, skips event
```

### Scenario 3: Client Cancels Trip
```
1. Multiple drivers receive requests
2. Client cancels before anyone accepts
3. clearTripSearchData() broadcasts to shared channel with reason 'cancelled'
4. All drivers receive expiration
5. All Redis keys cleared
```

### Scenario 4: No Driver Found
```
1. Max waves reached (e.g., 10 waves)
2. handleNoDriverFound() broadcasts with reason 'no_driver'
3. All notified drivers receive expiration
4. Trip status updated to NO_DRIVER_FOUND
```

## Files Modified

1. `app/Events/TripRequestExpired.php` - Changed to shared channel
2. `app/Events/TripRequestSent.php` - Enhanced with distance/duration calculation
3. `app/Services/TripService.php` - Fire single event on accept/cancel
4. `app/Services/DriverSearchService.php` - Fire single event, fetch driver location
5. `app/Jobs/ExpireDriverRequest.php` - Prevent duplicate broadcasts

## Rollback Plan

If issues arise:
1. Revert `TripRequestExpired.php` to use `driver.{driverId}` channel
2. Revert `TripService::acceptTrip()` to loop through drivers
3. Revert job to fire per-driver events
4. Mobile app continues to work (just receives on personal channel)

## Future Enhancements

1. **Option B for Job:** Create single `ExpireTripRequest` job per trip (more optimal)
2. **Private Channel:** Use `private-trip-request.{tripId}` with authorization
3. **Batch Distance Calculation:** Call Google API once for all drivers
4. **WebSocket Optimization:** Use presence channels to track active subscriptions

