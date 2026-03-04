# Redis Usage in Driver Search System

This document explains how the application uses Redis (via Laravel's `Redis` facade) to power the real‑time, wave‑based driver search flow. All keys are ephemeral and exist only while a trip is searching or pending driver response.

## Why Redis?

- Extremely fast reads/writes (in‑memory)
- Native TTL (time to live) handling for temporary data
- Atomic operations on sets/strings to coordinate concurrency across workers

## Namespacing Convention

Keys are namespaced by entity for clarity and to avoid collisions:

- `trip:{trip_id}:current_wave` → integer: current wave number
- `trip:{trip_id}:current_radius` → float: radius in km used for the last search
- `trip:{trip_id}:notified_drivers` → Set<int>: driver IDs already notified in any wave
- `trip:{trip_id}:search_started` → flag: idempotency guard to prevent double start
- `driver:{driver_id}:active_request` → string: trip ID currently shown to driver (has TTL)

## Redis Operations Used

All examples use Laravel's `Illuminate\Support\Facades\Redis` facade.

### 1) Simple Strings (get/set/setex/del)

```php
use Illuminate\Support\Facades\Redis;

// Set a value without expiry (e.g., counters, flags)
Redis::set("trip:{$tripId}:current_wave", 1);

// Read a value (returns string|null)
$wave = (int) Redis::get("trip:{$tripId}:current_wave");

// Set with TTL (seconds) – used for driver acceptance window
$ttlSeconds = 60;
Redis::setex("driver:{$driverId}:active_request", $ttlSeconds, $tripId);

// Check flag/guard (idempotency)
if (Redis::get("trip:{$tripId}:search_started")) {
    // already started – skip duplicate work
}

// Delete keys when search is finished/cancelled
Redis::del(
    "trip:{$tripId}:current_wave",
    "trip:{$tripId}:current_radius",
    "trip:{$tripId}:notified_drivers",
    "trip:{$tripId}:search_started"
);

// Clear a driver's active request (e.g., after accept/expire)
Redis::del("driver:{$driverId}:active_request");
```

### 2) Sets (sadd/smembers)

We use Redis Sets to track the unique list of drivers that have already been notified for a trip (across all waves). Sets give O(1) membership semantics and deduplicate automatically.

```php
// Add one or more drivers to the notified set
Redis::sadd("trip:{$tripId}:notified_drivers", $driverId1, $driverId2);

// Fetch all notified driver IDs as an array of strings
$notified = Redis::smembers("trip:{$tripId}:notified_drivers");

// Example: exclude already-notified drivers from DB query
$excludedDriverIds = $notified ?: [];
```

### 3) Existence Checks (exists)

Used to quickly determine if a driver is already locked with a visible popup.

```php
if (Redis::exists("driver:{$driverId}:active_request")) {
    // Another request is already on screen – skip this driver
}
```

### 4) Coordinating Idempotency

To prevent the same trip search from starting twice (e.g., duplicate event dispatch or queue retry), we set a guard flag when initiating the search and delete it at cleanup.

```php
$guardKey = "trip:{$tripId}:search_started";
if (Redis::get($guardKey)) {
    // Search already started – return early
    return;
}

// First time – mark as started
Redis::set($guardKey, 1);

// ... run search waves ...

// On completion/cancellation/assignment
Redis::del($guardKey);
```

## Where These Operations Are Used

- `app/Services/DriverSearchService.php`
  - `initiateSearch()`
    - Sets `trip:{trip}:search_started` guard
    - Initializes `trip:{trip}:current_wave`, `current_radius`, clears `notified_drivers`
  - `searchNextWave()`
    - Reads `current_wave`, increments and writes back
    - Reads `current_radius` and may update it
    - Reads `notified_drivers` to exclude previously-notified drivers
    - For each selected driver:
      - Adds to `notified_drivers` via `sadd`
      - Sets `driver:{driver}:active_request` via `setex` with TTL = acceptance time
  - `clearTripSearchData()`
    - Deletes all `trip:*` keys and clears `driver:*:active_request` for notified drivers
  - `hasActiveRequest()/setActiveRequest()/clearActiveRequest()`
    - Encapsulate `exists/setex/del` for `driver:{driver}:active_request`

## Operational Notes

- All `driver:{driver}:active_request` keys have a TTL equal to the acceptance window (e.g., 60s). If a driver ignores the popup, the key auto‑expires and the expiration job will broadcast a `trip_request_expired` event.
- All `trip:*` keys are cleared when the trip is assigned, cancelled, or marked as `NO_DRIVER_FOUND`.
- Keys are intentionally short‑lived and do not store historical data.

## Local Debugging

Use the Redis CLI to inspect keys while testing:

```bash
redis-cli KEYS 'trip:*'
redis-cli GET trip:123:current_wave
redis-cli SMEMBERS trip:123:notified_drivers
redis-cli GET driver:45:active_request
```

On Laravel Tinker:

```php
Redis::keys('trip:*');
Redis::get('trip:123:current_wave');
Redis::smembers('trip:123:notified_drivers');
Redis::get('driver:45:active_request');
```

## Failure & Retry Considerations

- If a queue listener retries `InitiateDriverSearch`, the `trip:{trip}:search_started` guard ensures the search isn’t started twice.
- If a worker dies mid‑wave, already notified drivers are preserved in `trip:{trip}:notified_drivers`; the next wave will correctly exclude them.
- If Pusher broadcast fails transiently, the job log will show the failure while Redis state remains consistent.

## Cleanup Checklist

Always ensure `clearTripSearchData($tripId)` is triggered on:

- Driver acceptance (trip assigned)
- Trip cancellation (by client/system)
- Max waves reached → `NO_DRIVER_FOUND`

This prevents stale keys and allows future trips to reuse the same driver immediately.


