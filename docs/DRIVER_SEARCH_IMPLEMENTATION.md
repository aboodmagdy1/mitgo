# Driver Search System - Implementation Summary

## Overview

The wave-based driver search system has been successfully implemented with real-time WebSocket communication, proximity-based driver selection, and Redis-powered request tracking.

## What Was Implemented

### 1. Core Services

#### DriverSearchService (`app/Services/DriverSearchService.php`)
- **Proximity-based search** using Haversine formula
- **Wave management** with Redis tracking
- **Driver filtering**: online, approved, matching vehicle type, no active trips
- **Active request tracking** to ensure drivers see only one popup at a time
- **Radius expansion**: Starts at 2km, expands by 1km per wave
- **Batch notifications**: 5 drivers per wave

#### Enhanced TripService (`app/Services/TripService.php`)
- `getSearchSettings()`: Returns search configuration for mobile
- `searchNextWave()`: Handles manual retry from client
- Updated `createTrip()`: Fires TripCreated event
- Updated `acceptTrip()`: Broadcasts events and clears Redis data
- Updated `cancelTrip()`: Clears search data and notifies drivers

### 2. Events & Broadcasting

#### Events Created
- `TripCreated` - Triggers initial driver search
- `TripRequestSent` - Broadcasts to individual driver channels
- `TripRequestExpired` - Notifies driver when request times out
- `TripDriverAccepted` - Broadcasts to trip channel when driver accepts
- `TripAlreadyAssigned` - Notifies losing drivers
- `TripNoDriverFound` - Notifies client when max waves reached
- `TripCancelled` - Broadcasts cancellation to all parties

#### Listener
- `InitiateDriverSearch` - Listens to TripCreated event

#### Job
- `ExpireDriverRequest` - Delayed job to expire driver requests

### 3. Controllers & Routes

#### Client Routes (`routes/API/V1/client.routes.php`)
- `POST /api/v1/client/trips` - Create trip (returns search_settings)
- `POST /api/v1/client/trips/{id}/search-next-wave` - Manual retry

#### Driver Routes
- `POST /api/v1/driver/trips/{id}/accept` - Accept trip (already existed, enhanced)

### 4. Broadcast Channels (`routes/channels.php`)
- `driver.{driver_id}` - Driver personal channel for trip requests
- `trip.{trip_id}` - Shared channel for client + assigned driver

### 5. Node.js Location Server

Created standalone Node.js server for high-frequency location updates:
- **Path**: `location-server/`
- **Dependencies**: Socket.IO, Pusher, Express
- **Purpose**: Handle driver location updates without overloading Laravel
- **Events**: `driver_location`, `client_location`

### 6. Settings

Updated `GeneralSettings`:
- `driver_acceptance_time` - Already existed in settings (60 seconds default)
- `search_wave_time` - Already existed (30 seconds default)
- `search_wave_count` - Already existed (10 waves default)

### 7. Redis Data Structure

Temporary tracking (auto-expires):
```
trip:{trip_id}:current_wave → integer
trip:{trip_id}:current_radius → float (km)
trip:{trip_id}:notified_drivers → Set of driver IDs
driver:{driver_id}:active_request → trip_id (TTL)
```

## Search Algorithm Flow

### Wave 1 (Initial Search)
1. Client creates trip → Status: `SEARCHING`
2. Backend fires `TripCreated` event
3. `InitiateDriverSearch` listener executes
4. Find drivers within 2km radius
5. Filter: online, approved, correct vehicle type, no active trip, no active popup
6. Take first 5 drivers
7. For each driver:
   - Set active request in Redis (TTL: 60s)
   - Broadcast `new_trip_request` to `driver.{driver_id}`
   - Schedule `ExpireDriverRequest` job
8. Client waits 30 seconds

### Wave 2+ (Manual Retry)
1. Client clicks "Try Again"
2. Calls `/search-next-wave`
3. Get remaining drivers from same radius
4. If < 5 drivers in radius, expand by 1km
5. Notify next 5 drivers
6. Repeat until wave_count reached

### Driver Acceptance
1. Driver clicks "Accept"
2. Backend locks trip record (race condition protection)
3. Assign driver to trip
4. Clear Redis search data
5. Broadcast `driver_accepted` to `trip.{trip_id}` (client sees)
6. Broadcast `trip_already_assigned` to other drivers
7. Driver subscribes to `trip.{trip_id}`

### Max Waves Reached
1. After 10 waves (or configured count)
2. Update trip status to `NO_DRIVER_FOUND`
3. Broadcast `no_driver_found` to trip channel
4. Clear Redis data

## Configuration

### Required Environment Variables

```env
# Pusher (Already configured in Laravel)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Redis (For tracking)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue (For delayed jobs)
QUEUE_CONNECTION=database  # or redis
```

### Node.js Location Server Setup

```bash
cd location-server
npm install
cp .env.example .env  # Configure Pusher credentials
npm start  # or npm run dev for development
```

## Mobile Developer Integration

Comprehensive guide: `docs/DRIVER_SEARCH_MOBILE_INTEGRATION.md`

### Quick Summary

**Client App:**
1. Create trip → Receive `search_settings`
2. Subscribe to `trip.{trip_id}`
3. Show loading + countdown
4. Manual "Try Again" after 30s
5. Listen for `driver_accepted`, `no_driver_found`

**Driver App:**
1. Subscribe to `driver.{driver_id}` when online
2. Show popup on `new_trip_request`
3. Countdown timer (60s)
4. Auto-hide on timeout
5. Accept → Subscribe to `trip.{trip_id}`
6. Send location to Node.js Socket.IO

## Testing

### Manual Testing Scenarios

1. **Happy Path**: Create trip → Driver accepts immediately
2. **Wave Progression**: 5 drivers ignore → Next 5 get request
3. **Race Condition**: Multiple drivers accept → First wins
4. **No Drivers**: No drivers in area → NO_DRIVER_FOUND
5. **Cancellation**: Client cancels during search → Drivers notified
6. **Timeout**: Driver ignores request → Popup expires

### Testing Tools

**Test Driver Search:**
```bash
php artisan tinker

// Create a test trip
$trip = \App\Models\Trip::find(1);
$searchService = app(\App\Services\DriverSearchService::class);
$searchService->initiateSearch($trip);

// Check Redis data
Redis::get('trip:1:current_wave');
Redis::smembers('trip:1:notified_drivers');
```

**Test WebSocket Events:**
- Use Pusher Debug Console
- Monitor events in real-time
- Verify channel subscriptions

**Test Location Server:**
```bash
curl http://localhost:3000/health
```

## Performance Considerations

### Scaling

1. **Redis**: All search tracking is in Redis (fast, temporary)
2. **Database**: Only trip creation and acceptance hit DB
3. **Broadcasting**: Pusher handles WebSocket scaling
4. **Location Updates**: Offloaded to Node.js

### Optimizations

- Haversine distance calculation in SQL (single query)
- Redis SET operations for driver tracking
- Pessimistic locking for race conditions
- Delayed jobs for expirations (don't poll)

## Monitoring

### Key Metrics to Track

1. **Search Success Rate**: % of trips that find drivers
2. **Average Waves**: How many waves before acceptance
3. **Driver Response Time**: Time to accept/reject
4. **Location Update Frequency**: Node.js throughput

### Logging

All operations are logged:
- Search initiated
- Drivers notified
- Acceptance/rejection
- Errors and exceptions

**View Logs:**
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Common Issues

**Issue: No drivers receiving requests**
- Check drivers are online: `Driver::where('status', 1)->count()`
- Verify drivers have correct vehicle type
- Check drivers don't have active trips
- Verify Pusher credentials

**Issue: Redis keys not expiring**
- Check Redis connection
- Verify TTL is set correctly
- Run: `redis-cli KEYS trip:*`

**Issue: Race condition on acceptance**
- Verify database transactions are working
- Check `lockForUpdate()` is in place
- Test with concurrent requests

**Issue: Location updates not working**
- Check Node.js server is running
- Verify Pusher credentials in Node.js
- Test Socket.IO connection
- Check mobile app GPS permissions

## Deployment Checklist

### Backend (Laravel)

- [ ] Configure Redis connection
- [ ] Set up queue worker: `php artisan queue:work`
- [ ] Configure Pusher credentials
- [ ] Update settings in admin panel
- [ ] Test event broadcasting
- [ ] Monitor logs

### Location Server (Node.js)

- [ ] Deploy Node.js server
- [ ] Configure environment variables
- [ ] Use PM2 for process management
- [ ] Set up reverse proxy (Nginx)
- [ ] Monitor with PM2 or similar
- [ ] Configure auto-restart on failure

### Mobile Apps

- [ ] Update Pusher SDK
- [ ] Implement channel subscriptions
- [ ] Add Socket.IO for location
- [ ] Test all events
- [ ] Handle edge cases
- [ ] Add error handling

## Future Enhancements

Potential improvements:
1. **AI-based driver selection** (rating, acceptance rate)
2. **Dynamic radius** based on time of day
3. **Driver pre-notification** (nearby trips)
4. **Analytics dashboard** for search metrics
5. **A/B testing** for wave timings

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Check Redis: `redis-cli MONITOR`
- Check Queue: `php artisan queue:failed`
- Check Pusher Debug Console

## Files Modified/Created

### New Files
- `app/Services/DriverSearchService.php`
- `app/Events/TripCreated.php`
- `app/Events/TripRequestSent.php`
- `app/Events/TripRequestExpired.php`
- `app/Events/TripDriverAccepted.php`
- `app/Events/TripAlreadyAssigned.php`
- `app/Events/TripNoDriverFound.php`
- `app/Events/TripCancelled.php`
- `app/Listeners/InitiateDriverSearch.php`
- `app/Jobs/ExpireDriverRequest.php`
- `location-server/` (entire directory)
- `docs/DRIVER_SEARCH_MOBILE_INTEGRATION.md`
- `docs/DRIVER_SEARCH_IMPLEMENTATION.md`

### Modified Files
- `app/Services/TripService.php`
- `app/Http/Controllers/API/V1/Client/TripController.php`
- `app/Providers/AppServiceProvider.php`
- `routes/API/V1/client.routes.php`
- `routes/channels.php`

## Conclusion

The driver search system is fully implemented and ready for integration with mobile applications. The system uses industry best practices with Redis for speed, WebSocket for real-time updates, and a separate Node.js server for location tracking.

All code is production-ready with proper error handling, logging, and race condition protection.

