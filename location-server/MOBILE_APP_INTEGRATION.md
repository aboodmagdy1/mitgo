# Mobile App Integration Guide - Distance & Time Tracking

## Overview

The location server now calculates real-time distance and estimated time between the driver and pickup/dropoff locations using Google Maps Distance Matrix API.

---

## What Changed

### New Feature: Real-time Distance & ETA

The mobile app can now receive:
- **Distance to pickup** (when trip status = 4: IN_ROUTE_TO_PICKUP)
- **Distance to dropoff** (when trip status = 7: IN_PROGRESS)
- **Estimated time** to reach destination
- Updates every 10 seconds automatically

---

## Mobile App Changes Required

### 1. Update Location Payload (Driver App)

**BEFORE:**
```javascript
socket.emit('driver_location', {
    trip_id: 123,
    driver_id: 456,
    lat: 24.7136,
    long: 46.6753
});
```

**AFTER:**
```javascript
socket.emit('driver_location', {
    trip_id: currentTrip.id,
    driver_id: driver.id,
    lat: driver.latitude,
    long: driver.longitude,
    
    // NEW: Add these fields
    trip_status: currentTrip.status,        // 4 or 7
    pickup_lat: currentTrip.pickup_latitude,
    pickup_long: currentTrip.pickup_longitude,
    dropoff_lat: currentTrip.dropoff_latitude,
    dropoff_long: currentTrip.dropoff_longitude
});
```

### 2. Listen for Distance Updates (Both Apps)

**Add this listener:**
```javascript
// Subscribe to trip channel
const channel = pusher.subscribe(`trip.${tripId}`);

// NEW: Listen for distance/time updates
channel.bind('trip_distance_updated', (data) => {
    console.log('Distance:', data.distance_km, 'km');
    console.log('Time:', data.duration_minutes, 'min');
    console.log('Destination:', data.destination_type); // 'pickup' or 'dropoff'
    
    // Update UI
    if (data.destination_type === 'pickup') {
        setETA(`Driver arriving in ${data.duration_text}`);
        setDistance(data.distance_text);
    } else {
        setETA(`Arriving at destination in ${data.duration_text}`);
        setDistance(data.distance_text);
    }
});
```

---

## Event Payload Details

### trip_distance_updated Event

```json
{
  "trip_id": 123,
  "distance_km": 2.5,              // Numeric value in kilometers
  "duration_minutes": 8.3,         // Numeric value in minutes
  "distance_text": "2.5 km",       // Human-readable from Google Maps
  "duration_text": "8 mins",       // Human-readable from Google Maps
  "trip_status": 4,                // 4 = pickup, 7 = dropoff
  "destination_type": "pickup",    // "pickup" or "dropoff"
  "timestamp": 1729350123456
}
```

**Field descriptions:**
- `distance_km`: Distance in kilometers (numeric) - use for calculations
- `duration_minutes`: Time in minutes (numeric) - use for calculations
- `distance_text`: Formatted text from Google Maps (e.g., "2.5 km")
- `duration_text`: Formatted text from Google Maps (e.g., "8 mins")
- `trip_status`: Current trip status (4 or 7)
- `destination_type`: Whether calculating to "pickup" or "dropoff"
- `timestamp`: Unix timestamp when calculated

---

## Trip Status Values

```
4 = IN_ROUTE_TO_PICKUP  → Distance to pickup location calculated
7 = IN_PROGRESS         → Distance to dropoff location calculated
```

**Only these two statuses trigger distance calculations.**

---

## When Updates Are Sent

- **Frequency:** Every 10 seconds (throttled to reduce API costs)
- **Condition:** Only when driver sends location WITH `trip_status` = 4 or 7
- **Requirements:**
  - Valid `trip_status` (4 or 7)
  - Valid pickup/dropoff coordinates
  - Driver location is valid

---

## Example Implementation

### React Native / Expo

```javascript
import { io } from 'socket.io-client';
import Pusher from 'pusher-js/react-native';

// 1. Connect to location server (Driver App)
const socket = io('https://location.saudi-driver.com');

// 2. Send location updates
const sendLocation = () => {
  if (currentTrip && driverLocation) {
    socket.emit('driver_location', {
      trip_id: currentTrip.id,
      driver_id: driver.id,
      lat: driverLocation.latitude,
      long: driverLocation.longitude,
      trip_status: currentTrip.status,
      pickup_lat: currentTrip.pickup_latitude,
      pickup_long: currentTrip.pickup_longitude,
      dropoff_lat: currentTrip.dropoff_latitude,
      dropoff_long: currentTrip.dropoff_longitude
    });
  }
};

// Send location every 3 seconds
useInterval(sendLocation, 3000);

// 3. Listen for distance updates (Both Apps)
const pusher = new Pusher(PUSHER_KEY, { cluster: 'eu' });
const channel = pusher.subscribe(`trip.${tripId}`);

channel.bind('trip_distance_updated', (data) => {
  setDistance(data.distance_text);
  setDuration(data.duration_text);
  
  // Show notification when close
  if (data.distance_km < 0.5 && data.destination_type === 'pickup') {
    showNotification('Driver is nearby!');
  }
});
```

### Flutter

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

// 1. Connect to location server (Driver App)
IO.Socket socket = IO.io('https://location.saudi-driver.com', <String, dynamic>{
  'transports': ['websocket'],
  'autoConnect': true,
});

// 2. Send location updates
Timer.periodic(Duration(seconds: 3), (timer) {
  if (currentTrip != null && driverLocation != null) {
    socket.emit('driver_location', {
      'trip_id': currentTrip.id,
      'driver_id': driver.id,
      'lat': driverLocation.latitude,
      'long': driverLocation.longitude,
      'trip_status': currentTrip.status,
      'pickup_lat': currentTrip.pickupLatitude,
      'pickup_long': currentTrip.pickupLongitude,
      'dropoff_lat': currentTrip.dropoffLatitude,
      'dropoff_long': currentTrip.dropoffLongitude,
    });
  }
});

// 3. Listen for distance updates (Both Apps)
PusherChannelsFlutter pusher = PusherChannelsFlutter.getInstance();
await pusher.init(
  apiKey: PUSHER_KEY,
  cluster: 'eu',
);

Channel channel = await pusher.subscribe(channelName: 'trip.$tripId');

await channel.bind(
  eventName: 'trip_distance_updated',
  onEvent: (PusherEvent event) {
    final data = jsonDecode(event.data);
    setState(() {
      distance = data['distance_text'];
      duration = data['duration_text'];
    });
  },
);
```

---

## UI/UX Recommendations

### Rider App

**Show:**
- "Driver is 2.5 km away"
- "Arriving in 8 mins"
- Progress indicator based on distance
- Notification when driver is < 500m away

**Example:**
```
┌────────────────────────────┐
│  Driver Approaching        │
│  🚗 2.5 km away            │
│  ⏱️  Arriving in 8 mins    │
│  [████████░░] 80%          │
└────────────────────────────┘
```

### Driver App

**Show:**
- "1.2 km to pickup location"
- "5 mins remaining"
- Turn-by-turn directions
- Tap to open in Maps app

**Example:**
```
┌────────────────────────────┐
│  To Pickup Location        │
│  📍 1.2 km remaining       │
│  ⏱️  5 mins                 │
│  [Open in Maps]            │
└────────────────────────────┘
```

---

## Error Handling

### Missing Distance Updates

If the app stops receiving distance updates:

```javascript
let lastDistanceUpdate = Date.now();

channel.bind('trip_distance_updated', (data) => {
  lastDistanceUpdate = Date.now();
  // Update UI
});

// Check if updates stopped
setInterval(() => {
  const timeSinceUpdate = Date.now() - lastDistanceUpdate;
  
  if (timeSinceUpdate > 30000) { // 30 seconds
    // Fallback: Calculate straight-line distance
    const straightLine = calculateHaversineDistance(
      driverLat, driverLong,
      destinationLat, destinationLong
    );
    setDistance(`~${straightLine.toFixed(1)} km`);
    setDuration('Calculating...');
  }
}, 5000);
```

### Connection Issues

```javascript
socket.on('connect_error', (error) => {
  console.error('Location server connection error:', error);
  showToast('Unable to connect to location server');
  
  // Retry connection
  setTimeout(() => socket.connect(), 5000);
});

socket.on('disconnect', () => {
  console.log('Disconnected from location server');
  // Continue using last known data
});
```

---

## Testing

### Test with Test UI

1. Open: http://localhost:3000 (development) or https://location.saudi-driver.com (production)
2. Enter your Pusher credentials
3. Select trip status (4 or 7)
4. Enter pickup/dropoff coordinates
5. Start simulation
6. Watch distance/time updates in real-time

### Test with Mobile App

1. **Driver app:** Send location with trip_status = 4
2. **Rider app:** Subscribe to `trip.{tripId}` channel
3. **Expected:** Both apps receive distance updates every 10 seconds
4. **Verify:** Distance decreases as driver moves closer

---

## Backward Compatibility

✅ **Fully backward compatible!**

- Old apps without `trip_status`: Location tracking works normally
- New apps with `trip_status`: Get distance/time updates
- No breaking changes to existing API

---

## Performance & Costs

### API Usage

- **Frequency:** 1 calculation per 10 seconds (6 per minute)
- **Cost:** ~$0.18 per hour per active trip
- **Free tier:** 40,000 requests/month (~6,600 trip-hours)

### Network Usage

- **Location updates:** ~500 bytes per update (every 3 seconds)
- **Distance updates:** ~300 bytes per update (every 10 seconds)
- **Total:** ~200 KB per hour per trip (very light)

---

## Questions & Support

**Need help?**
- Check test UI at http://localhost:3000
- View server logs for debugging
- Contact backend team with trip_id for investigation

**Common issues:**
1. No distance updates → Check trip_status is 4 or 7
2. Wrong destination → Verify pickup/dropoff coordinates
3. Slow updates → Check Google Maps API quota

---

## Checklist for Mobile Team

- [ ] Update location emission to include trip_status
- [ ] Add pickup/dropoff coordinates to location payload
- [ ] Add listener for `trip_distance_updated` event
- [ ] Update UI to show distance/time
- [ ] Handle missing updates gracefully
- [ ] Test with both status 4 and status 7
- [ ] Test error handling (disconnection, etc.)
- [ ] Update app to use production location server URL

---

**Ready to integrate!** The location server is live and waiting for mobile app updates. 🚀

