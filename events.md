# Realtime Events and Flows

This document explains all broadcast events, channels, and the end-to-end flow for each scenario in the driver search lifecycle.

## Channels
- `driver.{driverId}`: Personal channel for a specific driver (initial trip request delivery).
- `trip-request.{tripId}`: Shared channel for all drivers invited to a specific trip (expiration/clear events).
- `trip.{tripId}`: Trip lifecycle channel consumed by the client app (driver accepted, etc.).
- Real-time location events: `driver_location_updated`, `client_location_updated`, `trip_distance_updated` (via Socket.IO + Pusher).

## Events
- `.new_trip_request` (class: `App\Events\TripRequestSent`) → on `driver.{driverId}`
  - Sent when a driver is invited to a trip.
  - Data: trip_id, pickup/dropoff, estimated_fare, estimated_duration, estimated_distance, rider(name, phone, avatar), acceptance_time, expires_at, pickup_arrive_distance, pickup_arrive_duration.

- `.trip_request_expired` (class: `App\Events\TripRequestExpired`) → on `trip-request.{tripId}`
  - Sent when the pending request for a trip should be cleared for all invited drivers.
  - Data: trip_id.

- `.trip_distance_updated` → on `trip.{tripId}`
  - Sent every 10 seconds during active trip (status 4: IN_ROUTE_TO_PICKUP or status 7: IN_PROGRESS).
  - Provides real-time distance and estimated time from driver to pickup/dropoff location.
  - Data: trip_id, distance_km, duration_minutes, distance_text, duration_text, trip_status, destination_type, timestamp.

### Unified Trip Lifecycle Events (same payload on both channels)
All of the following broadcast to BOTH `trip.{tripId}` and `driver-trip.{tripId}` with the SAME payload shape for simplicity.

- `.driver_accepted` (class: `App\Events\TripDriverAccepted`)
- `.driver_arrived` (class: `App\Events\TripDriverArrived`)
- `.trip_started` (class: `App\Events\TripStarted`)
- `.trip_ended` (class: `App\Events\TripEnded`)
- `.trip_completed` (class: `App\Events\TripCompleted`)
- `.trip_cancelled` (class: `App\Events\TripCancelled`)
- `.trip_no_show` (class: `App\Events\TripNoShow`)

Unified payload shape (example):

```json
{
  "trip_id": 123,
  "status": { "id": 7, "text": "In Progress" },
  "arrived_at": "2025-01-01 12:00:00",   // when applicable
  "started_at": "2025-01-01 12:05:00",   // when applicable
  "ended_at": "2025-01-01 12:30:00",     // when applicable
  "driver": {
    "id": 10,
    "name": "Mohammed",
    "phone": "+9665...",
    "current_lat": 24.71,
    "current_long": 46.67,
    "vehicle": { "model": "Camry", "color": "White", "plate_number": "ABC 1234" }
  },
  "rider": { "id": 20, "name": "Ahmed", "phone": "+9665..." },
  "pickup": { "lat": 24.71, "long": 46.67, "address": "Riyadh" },
  "dropoff": { "lat": 24.75, "long": 46.70, "address": "King Fahd Rd" },
  "estimated_duration": 18,               // when applicable
  "estimated_fare": 45.0,                 // when applicable
  "actual_duration": 22,                  // when applicable
  "actual_distance": 12.5,                // when applicable
  "actual_fare": 48.0,                    // when applicable
  "payment": {                             // when applicable
    "method_id": 1,
    "total_amount": 48.0,
    "coupon_discount": 0.0,
    "status": 1
  },
  "cancelled_by": "rider",               // for trip_cancelled
  "cancellation_fee": 15.0                // for trip_cancelled (if any)
}
```

## Job Architecture: Single Per-Trip Expiration

### Problem with Per-Driver Jobs (Old Approach)
Previously, each driver notification spawned a separate `ExpireDriverRequest` job:
- Notify 5 drivers → 5 jobs in queue
- Each job scheduled for `acceptance_time` delay (e.g., 60 seconds)
- If driver accepts at T=10s → trip resolved, but all 5 jobs still wake up at T=60s
- Each job checks Redis, finds nothing, logs, and exits → wasteful duplication
- With multiple waves: even more jobs at staggered times

### Solution: ExpireTripRequest (Single Job Per Trip)
**Architecture:**
- ONE job per trip (not per driver)
- Job is "smart" - reschedules itself if acceptance window extends (multiple waves)
- Job exits immediately if trip resolves early (via `trip:{tripId}:resolved` flag)
- Uses `ShouldBeUnique` to prevent duplicate jobs in queue

**Flow:**
1. When notifying drivers (any wave):
   - Set `trip:{tripId}:expires_at` = timestamp of window close
   - Dispatch `ExpireTripRequest` (uniqueId prevents duplicates)
   
2. When job runs:
   - Check if `trip:{tripId}:resolved` exists → exit immediately
   - Check if `now() < expires_at` → reschedule via `release(remainingSeconds)`
   - If `now() >= expires_at` and trip still SEARCHING → broadcast expiration

3. When trip resolves (accept/cancel/no-driver):
   - Set `trip:{tripId}:resolved = 1` (TTL 10 min)
   - Broadcast `.trip_request_expired` immediately
   - Queued job will exit when it wakes

**Benefits:**
- 1 job instead of N jobs (5 drivers = 80% reduction)
- Handles multiple waves elegantly (job reschedules itself)
- Early resolution = instant no-op (resolved flag)
- Cleaner logs, predictable behavior

### Redis Keys for Job Coordination
- `trip:{tripId}:expires_at` → Unix timestamp when acceptance window closes (updated with each wave)
- `trip:{tripId}:resolved` → Flag (value=1, TTL=600s) set when trip accepted/cancelled/no-driver (signals job to exit)
- `driver:{driverId}:active_request` → Active trip ID for each driver (TTL=acceptance_time)

## Primary Components and Responsibilities

- `DriverSearchService::notifyDrivers()`
  - Adds drivers to `trip:{tripId}:notified_drivers`
  - Sets `driver:{driverId}:active_request` with TTL = acceptance_time
  - Sets/updates `trip:{tripId}:expires_at` = now + acceptance_time
  - Dispatches `ExpireTripRequest` (job uniqueness prevents duplicates)
  - Broadcasts `.new_trip_request` to each `driver.{driverId}` with enriched trip payload

- `TripService::acceptTrip()`
  - Sets trip status to IN_ROUTE_TO_PICKUP
  - Sets `trip:{tripId}:resolved = 1` (signals job to exit)
  - Broadcasts `.driver_accepted` on `trip.{tripId}` (for client)
  - Broadcasts one `.trip_request_expired` on `trip-request.{tripId}` (tells all drivers to clear UI)
  - Clears accepting driver's `driver:{driverId}:active_request`
  - Clears trip search metadata (waves, radius, notified set)

- `ExpireTripRequest` job (one per trip)
  - Checks if `trip:{tripId}:resolved` exists → exits immediately (trip already handled)
  - Gets `trip:{tripId}:expires_at` timestamp
  - If `now() < expires_at` → calls `release(remainingSeconds)` to reschedule (window extended by new wave)
  - If `now() >= expires_at` and trip still SEARCHING:
    - Broadcasts `.trip_request_expired` on `trip-request.{tripId}`
    - Clears all driver active_request keys
    - Logs completion

- `DriverSearchService::clearTripSearchData($tripId, true|false, ?reason)`
  - When true (on cancel/no-driver):
    - Sets `trip:{tripId}:resolved = 1` (signals job to exit)
    - Clears all drivers' active_request keys
    - Broadcasts a single `.trip_request_expired` on `trip-request.{tripId}`
  - Always clears trip metadata keys

## Scenarios

### 1) Driver Accepts (Single Wave)
Timeline:
1. T=0s: Wave 1 - notify 3 drivers
   - Set `trip:123:expires_at = T+60s`
   - Dispatch `ExpireTripRequest` (delay=60s)
   - Each driver receives `.new_trip_request` on `driver.{driverId}`
2. T=0s: Drivers subscribe to `trip-request.123`
3. T=10s: Driver 2 accepts → `TripService::acceptTrip()`:
   - Set `trip:123:resolved = 1`
   - Broadcast `.driver_accepted` on `trip.123`
   - Broadcast `.trip_request_expired` on `trip-request.123`
   - All 3 drivers clear UI immediately
4. T=60s: `ExpireTripRequest` job wakes up:
   - Checks `trip:123:resolved` → exists
   - Exits immediately (no broadcast, no work)

Result: All invited drivers clear request UI immediately via shared channel. Job exits cleanly.

### 2) Multiple Waves, Then Timeout
Timeline:
1. T=0s: Wave 1 - notify 3 drivers
   - Set `trip:123:expires_at = T+60s`
   - Dispatch `ExpireTripRequest` (delay=60s)
2. T=30s: Wave 2 - notify 2 more drivers
   - Update `trip:123:expires_at = T+90s` (extended!)
   - Dispatch `ExpireTripRequest` → ignored (job already queued, ShouldBeUnique)
3. T=60s: `ExpireTripRequest` job runs (first time):
   - `now=60, expires_at=90` → still in window
   - Calls `release(30)` → reschedule for 30 more seconds
4. T=90s: `ExpireTripRequest` job runs (second time):
   - `now=90, expires_at=90` → window closed
   - Trip still SEARCHING
   - Broadcasts `.trip_request_expired` on `trip-request.123`
   - Clears all 5 driver active_request keys
   - All 5 drivers clear UI

Result: Job intelligently reschedules itself, broadcasts once when final window closes.

### 3) Multiple Waves, Accept During Wave 3
Timeline:
1. T=0s: Wave 1 - 3 drivers, `expires_at=T+60s`
2. T=30s: Wave 2 - 2 drivers, `expires_at=T+90s`
3. T=50s: Wave 3 - 2 drivers, `expires_at=T+110s`
4. T=55s: Driver from wave 1 accepts:
   - Set `trip:123:resolved = 1`
   - Broadcast `.trip_request_expired` on `trip-request.123`
   - All 7 drivers clear UI immediately
5. T=60s: `ExpireTripRequest` job wakes up:
   - Checks `trip:123:resolved` → exists
   - Exits immediately (would have rescheduled to T+110, but exits instead)

Result: Early resolution, job exits cleanly without rescheduling.

### 4) Trip Cancelled by Rider/System While Searching
Timeline:
1. `TripService::cancelTrip()` → `clearTripSearchData($tripId, true)`
   - Sets `trip:123:resolved = 1`
   - Clears all drivers' active_request keys
   - Broadcasts `.trip_request_expired` on `trip-request.123`
   - Clears trip metadata keys
2. Later: `ExpireTripRequest` job wakes up → sees `resolved` flag → exits

Result: All invited drivers clear request UI immediately. Job exits cleanly.

### 5) No Driver Found (after max waves)
Timeline:
1. `DriverSearchService::handleNoDriverFound()`
   - Updates trip to NO_DRIVER_FOUND
   - Broadcasts client-side event (TripNoDriverFound)
   - Calls `clearTripSearchData($tripId, true)`:
     - Sets `trip:123:resolved = 1`
     - Broadcasts `.trip_request_expired` on `trip-request.123`
2. Later: `ExpireTripRequest` job wakes up → sees `resolved` flag → exits

Result: All invited drivers clear request UI. Job exits cleanly.

## Operational Notes
- **Job Efficiency:** The `ExpireTripRequest` job may wake up after trip resolution (accept/cancel). This is expected - the job checks the `resolved` flag and exits immediately with minimal overhead. This is far more efficient than trying to "cancel" queued jobs.
- **Wave Handling:** When multiple search waves occur, the job automatically reschedules itself by checking `expires_at` and calling `release()` with the remaining time. Only one job exists per trip thanks to `ShouldBeUnique`.
- **Broadcast Guarantee:** `.trip_request_expired` is always broadcast exactly once per trip - either by immediate resolution (accept/cancel/no-driver) or by the job when the window closes naturally.
- **Redis Cleanup:** Driver `active_request` keys are cleared either by the job (on timeout) or by resolution handlers (on accept/cancel). Trip metadata keys are always cleared when resolution occurs.

## Mobile Integration Guidance
1. Listen on `driver.{driverId}` for `.new_trip_request` and immediately subscribe to `trip-request.{tripId}`
2. On `.trip_request_expired` from the shared `trip-request.{tripId}` channel, set the active trip request to null
3. On `.trip_request_expired` from the personal `driver.{driverId}` channel (rejection), set active trip request to null
4. User can tap "Accept" → calls `/accept` or "Reject" → calls `/reject`
5. Optionally listen on `trip.{tripId}` for `.driver_accepted` to update client-side trip state

## Trip Lifecycle Events

### Complete Trip Flow with Events

**1. Trip Creation & Driver Search**
- Event: `.new_trip_request` → `driver.{driverId}` (for each notified driver)
- Event: `.trip_request_expired` → `trip-request.{tripId}` (when accepted/cancelled/expired)

**2. Driver Accepts**
- API: `POST /api/driver/trips/{id}/accept`
- Service: `TripService::acceptTrip()`
- Event: `.driver_accepted` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `IN_ROUTE_TO_PICKUP`

**2b. Driver Rejects (Alternative to Accept)**
- API: `POST /api/driver/trips/{id}/reject`
- Service: `TripService::rejectTrip()`
- Event: `.trip_request_expired` → `driver.{driverId}` (personal channel only)
- Trip remains in `SEARCHING` status
- Other notified drivers are NOT affected
- Rejecting driver can immediately receive new trip requests

**3. Driver Arrives at Pickup**
- API: `POST /api/driver/trips/{id}/arrived` OR `POST /api/driver/trips/{id}/status` (action=5)
- Service: `TripService::arrivedAtPickup()`
- Event: `.driver_arrived` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `PICKUP_ARRIVED`

**4. Trip Started**
- API: `POST /api/driver/trips/{id}/start`
- Service: `TripService::start()`
- Event: `.trip_started` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `IN_PROGRESS`

**5. Trip Ended**
- API: `POST /api/driver/trips/{id}/end`
- Service: `TripService::endTrip()`
- Event: `.trip_ended` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `COMPLETED` (wallet) or `COMPLETED_PENDING_PAYMENT` (cash)

**6. Payment Confirmed (Cash Only)**
- API: `POST /api/driver/trips/{id}/confirm-cash-payment`
- Service: `TripService::driverConfirmPayment()`
- Event: `.trip_completed` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `COMPLETED`

**Alternative Flows:**

**Cancellation (Driver or Rider)**
- API: `POST /api/driver/trips/{id}/cancel` OR `POST /api/client/trips/{id}/cancel`
- Service: `TripService::cancelTrip()`
- Event: `.trip_cancelled` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `CANCELLED_BY_DRIVER` or `CANCELLED_BY_RIDER`

**Rider No-Show**
- API: `POST /api/driver/trips/{id}/no-show` OR `POST /api/driver/trips/{id}/status` (action=2)
- Service: `TripService::cancelTrip(trip, null, RIDER_NO_SHOW)`
- Event: `.trip_no_show` → `trip.{tripId}` & `driver-trip.{tripId}`
- Status: `RIDER_NO_SHOW`

### Unified Driver Status Endpoint

**Single endpoint for all driver actions:**
```
POST /api/driver/trips/{id}/status
Content-Type: application/json
```

**Request body varies by action:**

1. **Arrive at pickup** (action=5):
```json
{ "action": 5 }
```

2. **Start trip** (action=7):
```json
{
  "action": 7,
  "pickup_lat": 24.7136,
  "pickup_long": 46.6753,
  "pickup_address": "King Fahd Road, Riyadh"
}
```

3. **End trip** (action=16 or 8):
```json
{
  "action": 16,
  "dropoff_lat": 24.7500,
  "dropoff_long": 46.7000,
  "dropoff_address": "Olaya Street, Riyadh"
}
```

4. **Rider no-show** (action=2):
```json
{ "action": 2 }
```

**Separate endpoints still available:**
- `POST /api/driver/trips/{id}/cancel` - Cancel trip (needs `reason_id`)
- `POST /api/driver/trips/{id}/confirm-cash-payment` - Confirm cash payment

**Legacy endpoints** (backward compatible, use unified endpoint instead):
- `/arrived`, `/start`, `/end`, `/no-show`

---

## Real-Time Location & Distance Tracking

### Overview

The system provides real-time location tracking and distance/time calculations using a Node.js Socket.IO server integrated with Google Maps Distance Matrix API.

### Architecture

```
Mobile App (Driver) → Socket.IO Server → Google Maps API → Pusher → Mobile Apps (Driver + Rider)
```

### Events

#### 1. Driver Location Update

**Sent by:** Driver mobile app (via Socket.IO)
**Event:** `driver_location`
**Channel:** Socket.IO connection to location server

**Payload:**
```json
{
  "trip_id": 123,
  "driver_id": 456,
  "lat": 24.7136,
  "long": 46.6753,
  "trip_status": 4,
  "pickup_lat": 24.7200,
  "pickup_long": 46.6800,
  "dropoff_lat": 24.7500,
  "dropoff_long": 46.7000
}
```

**When to send:**
- Every 1-3 seconds while driver is on an active trip
- Include `trip_status` for distance calculations:
  - `4` = IN_ROUTE_TO_PICKUP (calculate distance to pickup)
  - `7` = IN_PROGRESS (calculate distance to dropoff)

#### 2. Driver Location Updated (Broadcast)

**Broadcast by:** Location server (via Pusher)
**Event:** `driver_location_updated`
**Channel:** `trip.{tripId}`

**Payload:**
```json
{
  "lat": 24.7136,
  "long": 46.6753,
  "driver_id": 456,
  "timestamp": 1729350123456
}
```

**Who receives:**
- Rider mobile app (to show driver approaching on map)
- Driver mobile app (for tracking history)
- Admin dashboard (for monitoring)

#### 3. Trip Distance Updated (Broadcast)

**Broadcast by:** Location server (via Pusher)
**Event:** `trip_distance_updated`
**Channel:** `trip.{tripId}`

**Payload:**
```json
{
  "trip_id": 123,
  "distance_km": 2.5,
  "duration_minutes": 8.3,
  "distance_text": "2.5 km",
  "duration_text": "8 mins",
  "trip_status": 4,
  "destination_type": "pickup",
  "timestamp": 1729350123456
}
```

**Fields:**
- `distance_km`: Distance in kilometers (numeric)
- `duration_minutes`: Estimated time in minutes (numeric)
- `distance_text`: Human-readable distance from Google Maps
- `duration_text`: Human-readable duration from Google Maps
- `trip_status`: Current trip status (4 or 7)
- `destination_type`: Either "pickup" or "dropoff"

**When fired:**
- Every 10 seconds (throttled) during status 4 or 7
- Only when driver sends location with valid trip_status
- Requires pickup/dropoff coordinates in location payload

**Who receives:**
- Rider mobile app (to show ETA)
- Driver mobile app (to show remaining distance)

#### 4. Client Location Update (Optional)

**Sent by:** Rider mobile app (via Socket.IO)
**Event:** `client_location`
**Channel:** Socket.IO connection to location server

**Payload:**
```json
{
  "trip_id": 123,
  "user_id": 789,
  "lat": 24.7200,
  "long": 46.6800
}
```

**Use case:** Rider shares their location during trip (optional feature)

### Mobile App Integration

#### Driver App - Sending Location

```javascript
// Connect to location server
const socket = io('https://location.saudi-driver.com');

// Send location updates every 3 seconds
setInterval(() => {
  if (currentTrip && driver.latitude) {
    socket.emit('driver_location', {
      trip_id: currentTrip.id,
      driver_id: driver.id,
      lat: driver.latitude,
      long: driver.longitude,
      trip_status: currentTrip.status, // 4 or 7
      pickup_lat: currentTrip.pickup_latitude,
      pickup_long: currentTrip.pickup_longitude,
      dropoff_lat: currentTrip.dropoff_latitude,
      dropoff_long: currentTrip.dropoff_longitude
    });
  }
}, 3000);
```

#### Driver/Rider App - Receiving Updates

```javascript
// Subscribe to trip channel
const pusher = new Pusher(PUSHER_KEY, { cluster: 'eu' });
const channel = pusher.subscribe(`trip.${tripId}`);

// Listen for driver location updates
channel.bind('driver_location_updated', (data) => {
  // Update driver marker on map
  updateDriverMarker(data.lat, data.long);
});

// Listen for distance/time updates
channel.bind('trip_distance_updated', (data) => {
  // Update UI with ETA
  if (data.destination_type === 'pickup') {
    setETA(`Driver arriving in ${data.duration_text}`);
    setDistance(data.distance_text);
  } else {
    setETA(`Arriving at destination in ${data.duration_text}`);
    setDistance(`${data.distance_km.toFixed(1)} km remaining`);
  }
});
```

### API Costs & Throttling

**Google Maps Distance Matrix API:**
- Cost: $5 per 1,000 requests
- Free tier: $200/month credit (~40,000 requests)

**Throttling Strategy:**
- Distance calculations: Every 10 seconds (not every location update)
- Reduces API calls by 90%
- Typical cost per trip: ~$0.04 per trip per day

**Example calculation:**
- Without throttling: 1 update/sec = 3,600 API calls/hour
- With throttling: 1 update/10sec = 360 API calls/hour
- For 1-hour trip: 360 requests × $0.005 = $1.80
- With 10-sec throttling: $0.18 per hour

### Location Server Setup

**Environment Variables (.env):**
```env
NODE_ENV=production
LOCATION_SERVER_PORT=3000
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=eu
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
```

**Server URL:**
- Development: `http://localhost:3000`
- Production: `https://location.saudi-driver.com`

### Testing

**Test UI:** `http://localhost:3000` or `https://location.saudi-driver.com`

The test UI provides:
- Interactive map with driver/rider markers
- Trip status selector (4 = pickup, 7 = dropoff)
- Pickup/dropoff location inputs
- Real-time distance/time display
- Event log for debugging
- Simulation controls

### Error Handling

**Location server gracefully handles:**
1. Missing Google Maps API key (logs error, continues without distance)
2. Invalid coordinates (validates before API call)
3. Google Maps API errors (logs error, doesn't crash)
4. Network timeouts (retries once, then skips)
5. Missing trip status (broadcasts location only, no distance)

**Mobile app should:**
1. Retry Socket.IO connection if disconnected
2. Handle missing distance updates gracefully
3. Show cached ETA if updates stop
4. Fall back to straight-line distance if API unavailable

