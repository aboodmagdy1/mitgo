# Driver Search System - Mobile Integration Guide

## Overview

This document provides comprehensive integration instructions for mobile developers to implement the wave-based driver search system with real-time WebSocket communication.

## Architecture

### Channels

1. **`driver.{driver_id}`** - Driver personal channel for receiving trip requests
2. **`trip.{trip_id}`** - Shared channel for client and assigned driver (all trip events)

### Real-Time Communication

- **Pusher/WebSocket** for trip events and status updates
- **Node.js Socket.IO** for high-frequency location updates

---

## Client App Integration

### 1. Trip Creation

**Endpoint:** `POST /api/v1/client/trips`

**Response:**
```json
{
  "success": true,
  "message": "Trip created successfully",
  "data": {
    "trip": {
      "id": 123,
      "status": 1,
      "pickup_address": "King Fahd Road, Riyadh",
      "dropoff_address": "Al Olaya, Riyadh",
      "estimated_fare": 45.50,
      "estimated_duration": 15,
      "distance": 5.2
    },
    "search_settings": {
      "search_wave_time": 30,
      "search_wave_count": 10,
      "driver_acceptance_time": 60
    }
  }
}
```

### 2. Subscribe to Trip Channel

```javascript
// Using Pusher
const pusher = new Pusher('YOUR_PUSHER_KEY', {
  cluster: 'YOUR_CLUSTER'
});

const tripChannel = pusher.subscribe(`trip.${tripId}`);
```

### 3. Listen for Events

#### Driver Accepted
```javascript
tripChannel.bind('driver_accepted', (data) => {
  console.log('Driver assigned:', data.driver);
  
  // Update UI
  setDriverInfo(data.driver);
  setTripStatus('IN_ROUTE_TO_PICKUP');
  
  // Show driver on map
  showDriverMarker(data.driver.current_lat, data.driver.current_long);
});
```

#### Driver Location Updates
```javascript
tripChannel.bind('driver_location_updated', (data) => {
  // Update driver marker on map
  updateDriverMarker(data.lat, data.long);
  
  // Optionally update ETA
  calculateETA(myLocation, data.lat, data.long);
});
```

#### No Driver Found
```javascript
tripChannel.bind('no_driver_found', (data) => {
  console.log('No drivers available');
  
  // Show error message
  showAlert('No drivers available at this time. Please try again later.');
  setTripStatus('NO_DRIVER_FOUND');
});
```

#### Trip Cancelled
```javascript
tripChannel.bind('trip_cancelled', (data) => {
  console.log('Trip cancelled:', data.cancelled_by);
  
  // Show cancellation message
  if (data.cancellation_fee > 0) {
    showAlert(`Trip cancelled. Fee: ${data.cancellation_fee} SAR`);
  } else {
    showAlert('Trip cancelled successfully');
  }
  
  // Navigate to home screen
  navigateToHome();
});
```

### 4. Manual Retry (Try Again)

After `search_wave_time` seconds, show "Try Again" button:

```javascript
// Start countdown
let countdown = search_wave_time; // e.g., 30 seconds

const timer = setInterval(() => {
  countdown--;
  updateCountdownUI(countdown);
  
  if (countdown <= 0) {
    clearInterval(timer);
    showTryAgainButton();
  }
}, 1000);

// When user clicks "Try Again"
async function searchNextWave() {
  try {
    const response = await fetch(`/api/v1/client/trips/${tripId}/search-next-wave`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      console.log(`Wave ${result.data.wave}: ${result.data.drivers_notified} drivers notified`);
      // Restart countdown
      startCountdown();
    } else {
      showAlert(result.message);
    }
  } catch (error) {
    console.error('Error searching next wave:', error);
  }
}
```

### 5. UI States

**During Search:**
- Show loading animation
- Display pickup and dropoff addresses
- Display estimated fare
- Show "Searching for drivers..." message
- Show countdown timer
- Enable "Try Again" button after countdown

**Driver Found:**
- Show driver info (name, photo, rating)
- Show driver location on map
- Show vehicle info (model, color, plate)
- Track driver in real-time

---

## Driver App Integration

### 1. Subscribe to Driver Channel (When Online)

```javascript
const driverChannel = pusher.subscribe(`driver.${driverId}`);
```

### 2. Listen for New Trip Requests

#### New Trip Request
```javascript
driverChannel.bind('new_trip_request', (data) => {
  console.log('New trip request received:', data);
  
  // IMPORTANT: Only show ONE popup at a time
  if (hasActivePopup) {
    console.log('Already showing a trip request, ignoring this one');
    return;
  }
  
  // Show popup with countdown
  showTripRequestPopup({
    tripId: data.trip_id,
    pickupAddress: data.pickup_address,
    dropoffAddress: data.dropoff_address,
    estimatedFare: data.estimated_fare,
    distance: data.distance,
    riderName: data.rider.name,
    riderRating: data.rider.rating,
    acceptanceTime: data.acceptance_time // e.g., 60 seconds
  });
  
  // Start countdown timer
  startAcceptanceCountdown(data.acceptance_time, data.trip_id);
});
```

#### Trip Request Expired
```javascript
driverChannel.bind('trip_request_expired', (data) => {
  console.log('Trip request expired:', data.trip_id);
  
  // Auto-hide popup
  if (currentPopupTripId === data.trip_id) {
    hidePopup();
  }
});
```

#### Trip Already Assigned
```javascript
driverChannel.bind('trip_already_assigned', (data) => {
  console.log('Trip already taken by another driver');
  
  // Hide popup and show message
  hidePopup();
  showToast('Another driver accepted this trip');
});
```

### 3. Accept Trip Request

```javascript
async function acceptTripRequest(tripId) {
  try {
    const response = await fetch(`/api/v1/driver/trips/${tripId}/accept`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Hide popup
      hidePopup();
      
      // Subscribe to trip channel
      const tripChannel = pusher.subscribe(`trip.${tripId}`);
      
      // Navigate to trip screen
      navigateToActiveTrip(result.data.trip);
      
      // Start sending location updates
      startLocationBroadcast(tripId);
    } else {
      // Trip already taken
      showAlert(result.message);
      hidePopup();
    }
  } catch (error) {
    console.error('Error accepting trip:', error);
  }
}
```

### 4. Countdown Timer Implementation

```javascript
function startAcceptanceCountdown(seconds, tripId) {
  let remaining = seconds;
  
  const timer = setInterval(() => {
    remaining--;
    updatePopupCountdown(remaining);
    
    if (remaining <= 0) {
      clearInterval(timer);
      // Mobile auto-hides popup
      // Backend will send trip_request_expired event
      hidePopup();
    }
  }, 1000);
  
  // Store timer reference to clear if driver accepts
  activeTimers[tripId] = timer;
}
```

### 5. Location Broadcasting (Socket.IO)

**Connect to Node.js Server:**
```javascript
import io from 'socket.io-client';

const locationSocket = io('http://your-server:3000');

locationSocket.on('connect', () => {
  console.log('Connected to location server');
});

locationSocket.on('error', (error) => {
  console.error('Location server error:', error);
});
```

**Send Location Updates (Every 5 seconds during active trip):**
```javascript
function startLocationBroadcast(tripId) {
  const interval = setInterval(async () => {
    // Get current location from GPS
    const position = await getCurrentPosition();
    
    // Send to Node.js server
    locationSocket.emit('driver_location', {
      trip_id: tripId,
      driver_id: driverId,
      lat: position.coords.latitude,
      long: position.coords.longitude
    });
  }, 5000); // Every 5 seconds
  
  // Store interval to clear when trip ends
  activeLocationBroadcast = interval;
}

function stopLocationBroadcast() {
  if (activeLocationBroadcast) {
    clearInterval(activeLocationBroadcast);
    activeLocationBroadcast = null;
  }
}

// Listen for confirmation
locationSocket.on('location_received', (data) => {
  console.log('Location update confirmed:', data.trip_id);
});
```

### 6. Listen to Trip Channel Events

After accepting a trip, subscribe to trip channel:

```javascript
const tripChannel = pusher.subscribe(`trip.${tripId}`);

// Listen for cancellations
tripChannel.bind('trip_cancelled', (data) => {
  console.log('Trip cancelled by:', data.cancelled_by);
  
  // Stop location broadcast
  stopLocationBroadcast();
  
  // Show message
  showAlert('Trip was cancelled');
  
  // Navigate to home
  navigateToHome();
});
```

---

## Important Implementation Notes

### For Client App

1. **Always subscribe to trip channel immediately after trip creation**
2. **Implement countdown timer for "Try Again" button**
3. **Handle all trip status changes via WebSocket events**
4. **Display loading state during search**
5. **Maximum waves**: After `search_wave_count` attempts, show "No drivers available"

### For Driver App

1. **Only show ONE trip request popup at a time**
   - If already showing a popup, ignore new requests
   - Driver must accept/reject/ignore current request first

2. **Auto-hide popup after `driver_acceptance_time` seconds**
   - Don't wait for backend event
   - Mobile handles countdown and hiding

3. **Subscribe to driver channel when going online**
   - Unsubscribe when going offline

4. **Location updates only during active trips**
   - Start after accepting trip
   - Stop when trip ends

5. **Handle race conditions gracefully**
   - If another driver accepts first, show friendly message
   - Hide popup immediately

---

## Testing Checklist

### Client App
- [ ] Trip creation returns search settings
- [ ] Subscribe to trip channel successfully
- [ ] Countdown timer works correctly
- [ ] "Try Again" button triggers next wave
- [ ] Driver acceptance updates UI
- [ ] Driver location updates on map
- [ ] No driver found shows error
- [ ] Cancellation works properly

### Driver App
- [ ] Subscribe to driver channel when online
- [ ] New trip popup appears with countdown
- [ ] Only one popup shows at a time
- [ ] Popup auto-hides after timeout
- [ ] Accept trip works correctly
- [ ] Subscribe to trip channel after accepting
- [ ] Location broadcasting starts
- [ ] Race condition handled (already accepted)
- [ ] Location updates send successfully

---

## API Endpoints Reference

### Client Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/client/trips` | Create new trip |
| POST | `/api/v1/client/trips/{id}/search-next-wave` | Trigger next wave search |
| POST | `/api/v1/client/trips/{id}/cancel` | Cancel trip |

### Driver Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/driver/trips/{id}/accept` | Accept trip request |
| POST | `/api/v1/driver/trips/{id}/arrived` | Mark arrived at pickup |
| POST | `/api/v1/driver/trips/{id}/start` | Start trip |
| POST | `/api/v1/driver/trips/{id}/end` | End trip |

---

## WebSocket Events Summary

### Driver Channel (`driver.{driver_id}`)
- `new_trip_request` - New trip available
- `trip_request_expired` - Request timeout
- `trip_already_assigned` - Lost race condition

### Trip Channel (`trip.{trip_id}`)
- `driver_accepted` - Driver assigned
- `driver_location_updated` - Real-time location
- `driver_arrived` - Driver at pickup
- `trip_started` - Trip in progress
- `trip_completed` - Trip finished
- `trip_cancelled` - Trip cancelled
- `no_driver_found` - No drivers available

---

## Troubleshooting

### Client App

**Issue**: Not receiving driver location updates
- **Solution**: Ensure subscribed to trip channel
- **Solution**: Check Pusher connection status
- **Solution**: Verify trip has assigned driver

**Issue**: "Try Again" not working
- **Solution**: Check trip status is still SEARCHING
- **Solution**: Verify authentication token is valid

### Driver App

**Issue**: Not receiving trip requests
- **Solution**: Ensure subscribed to driver channel
- **Solution**: Check driver is online and approved
- **Solution**: Verify no active trip in progress
- **Solution**: Check no popup already showing

**Issue**: Location not broadcasting
- **Solution**: Verify Socket.IO connection
- **Solution**: Check GPS permissions enabled
- **Solution**: Ensure trip is active

**Issue**: Accept trip fails
- **Solution**: Another driver likely accepted first
- **Solution**: Show user-friendly message
- **Solution**: Wait for next request

---

## Support

For questions or issues, contact the backend development team.

