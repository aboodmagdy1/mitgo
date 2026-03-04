# Client Home API Response Examples

## Endpoint: `GET /api/client/home`

This endpoint returns:
- Saved locations (home, work, favorites)
- Active trip (if any exists)

The active trip response structure changes based on the trip status.

---

## 1. SEARCHING Status - Waiting for Driver

This is returned when the client has created a trip and the system is searching for an available driver.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    // Saved locations for quick trip creation
    "saved_locations": [
      {
        "id": 1,
        "type": "home",           // "home", "work", or "other"
        "name": "Home",
        "address": "King Fahd Road, Riyadh",
        "lat": 24.7136,
        "long": 46.6753
      },
      {
        "id": 2,
        "type": "work",
        "name": "Office",
        "address": "Olaya Street, Riyadh",
        "lat": 24.7231,
        "long": 46.6842
      }
    ],
    
    // Active trip details
    "active_trip": {
      // Unique trip identifier
      "trip_id": 123,
      
      // Current trip status
      "status": {
        "id": 1,              // Status ID (1 = SEARCHING)
        "text": "Searching"
      },
      
      // Pickup location
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Destination location
      "dropoff": {
        "lat": 24.7743,
        "long": 46.7385,
        "address": "King Abdullah Road, Riyadh"
      },
      
      // Estimated trip cost (in SAR)
      "estimated_fare": 50.00,
      
      // Estimated trip duration (in minutes)
      "estimated_duration": 25,
      
      // Estimated distance (in kilometers)
      "estimated_distance": 15.5,
      
      // Selected vehicle type
      "vehicle_type": {
        "id": 2,
        "name": "Economy"
      },
      
      // Selected payment method
      "payment_method": {
        "id": 1,
        "name": "Cash"
      },
      
      // Driver search progress
      "search_progress": {
        "current_wave": 2,              // Current search wave (drivers batch)
        "total_waves": 10,              // Maximum waves before giving up
        "progress_percentage": 20       // 0-100% progress
      }
    }
  }
}
```

**WebSocket Events to Listen:**
- Channel: `trip.{tripId}`
- Event: `.driver_accepted` - A driver has accepted the trip
- Event: `.search_progress` - Real-time search progress updates

**Actions Available:**
- Cancel trip: `POST /api/client/trips/{id}/cancel`
- Request next wave: `POST /api/client/trips/{id}/search-next-wave` (manual retry)

**Note:** The system automatically searches in waves. Each wave notifies 5 nearby drivers. If no driver accepts within 60 seconds, the next wave starts automatically.

---

## 2. IN_ROUTE_TO_PICKUP Status - Driver Accepted & Coming

This is returned when a driver has accepted the trip and is on their way to the pickup location.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    "saved_locations": [...],  // Same as above
    
    "active_trip": {
      // Trip identifier
      "trip_id": 123,
      
      // Current status
      "status": {
        "id": 4,                      // Status ID (4 = IN_ROUTE_TO_PICKUP)
        "text": "In Route To Pickup"
      },
      
      // Pickup location
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Destination
      "dropoff": {
        "lat": 24.7743,
        "long": 46.7385,
        "address": "King Abdullah Road, Riyadh"
      },
      
      // Estimated fare
      "estimated_fare": 50.00,
      
      // Driver information
      "driver": {
        "id": 42,
        "name": "Mohammed Ali",
        "phone": "+966501234567",
        "rating": 4.8,                                      // Average rating (0-5)
        "avatar": "https://example.com/storage/avatars/driver-42.jpg",
        
        // Driver's real-time location (updated every 5-10 seconds)
        "current_lat": 24.7050,
        "current_long": 46.6700,
        
        // Vehicle details
        "vehicle": {
          "model": "Toyota Camry 2023",
          "color": "White",
          "plate_number": "ABC 1234"
        }
      }
    }
  }
}
```

**WebSocket Events to Listen:**
- Channel: `trip.{tripId}`
- Event: `.driver_arrived` - Driver has arrived at pickup location
- Event: `.trip_distance_updated` - Real-time distance/ETA updates (every 10 seconds)

**Actions Available:**
- Cancel trip: `POST /api/client/trips/{id}/cancel` (may incur cancellation fee)
- Call driver: Use `driver.phone` to initiate call

**Note:** 
- Monitor `current_lat` and `current_long` to track driver's real-time location on the map
- Listen to `.trip_distance_updated` event for ETA to pickup location

---

## 3. PICKUP_ARRIVED Status - Driver Waiting at Pickup

This is returned when the driver has reached the pickup location and is waiting for the client.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    "saved_locations": [...],
    
    "active_trip": {
      // Trip identifier
      "trip_id": 123,
      
      // Current status
      "status": {
        "id": 5,                  // Status ID (5 = PICKUP_ARRIVED)
        "text": "Pickup Arrived"
      },
      
      // Pickup location
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Destination
      "dropoff": {
        "lat": 24.7743,
        "long": 46.7385,
        "address": "King Abdullah Road, Riyadh"
      },
      
      // When driver arrived at pickup
      // Format: Y-m-d H:i:s
      "arrived_at": "2025-12-01 12:05:30",
      
      // Driver details
      "driver": {
        "id": 42,
        "name": "Mohammed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "avatar": "https://example.com/storage/avatars/driver-42.jpg",
        "current_lat": 24.7136,         // Should be near pickup location
        "current_long": 46.6753,
        "vehicle": {
          "model": "Toyota Camry 2023",
          "color": "White",
          "plate_number": "ABC 1234"
        }
      }
    }
  }
}
```

**WebSocket Events to Listen:**
- Channel: `trip.{tripId}`
- Event: `.trip_started` - Trip has started (client is in vehicle)
- Event: `.trip_cancelled` - Driver marked client as no-show

**Actions Available:**
- Cancel trip: `POST /api/client/trips/{id}/cancel` (cancellation fee will apply)
- Call driver: Use `driver.phone` if you can't find them

**Important:** 
- After a certain waiting time (e.g., 5 minutes), the driver can mark you as "no-show" which will cancel the trip and charge a cancellation fee
- Make sure to meet the driver at the agreed pickup location

---

## 4. IN_PROGRESS Status - Trip Started

This is returned when the client is in the vehicle and the trip is in progress.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    "saved_locations": [...],
    
    "active_trip": {
      // Trip identifier
      "trip_id": 123,
      
      // Current status
      "status": {
        "id": 7,              // Status ID (7 = IN_PROGRESS)
        "text": "In Progress"
      },
      
      // Pickup location (actual location where you were picked up)
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Destination (original)
      "dropoff": {
        "lat": 24.7743,
        "long": 46.7385,
        "address": "King Abdullah Road, Riyadh"
      },
      
      // When trip started
      "started_at": "2025-12-01 12:10:00",
      
      // Estimated trip duration (in minutes)
      "estimated_duration": 25,
      
      // Estimated fare
      "estimated_fare": 50.00,
      
      // Driver details
      "driver": {
        "id": 42,
        "name": "Mohammed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "avatar": "https://example.com/storage/avatars/driver-42.jpg",
        "current_lat": 24.7200,         // Real-time location during trip
        "current_long": 46.6800,
        "vehicle": {
          "model": "Toyota Camry 2023",
          "color": "White",
          "plate_number": "ABC 1234"
        }
      }
    }
  }
}
```

**WebSocket Events to Listen:**
- Channel: `trip.{tripId}`
- Event: `.trip_ended` - Trip has ended (reached destination)
- Event: `.trip_distance_updated` - Real-time distance/ETA to destination (every 10 seconds)

**Actions Available:**
- View real-time progress on map
- Call driver if needed

**Note:** 
- You cannot cancel the trip once it's in progress
- The final fare may differ from estimated_fare based on actual distance/duration
- Track driver's location in real-time using `current_lat` and `current_long`

---

## 5. COMPLETED_PENDING_PAYMENT Status - Trip Ended, Processing Payment

This is returned when the trip has ended and the system is processing the payment.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    "saved_locations": [...],
    
    "active_trip": {
      // Trip identifier
      "trip_id": 123,
      
      // Current status
      "status": {
        "id": 9,                              // Status ID (9 = COMPLETED_PENDING_PAYMENT)
        "text": "Completed Pending Payment"
      },
      
      // Pickup location (actual)
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Dropoff location (actual - may differ from original if changed during trip)
      "dropoff": {
        "lat": 24.7750,
        "long": 46.7390,
        "address": "King Abdullah Financial District, Riyadh"
      },
      
      // When trip ended (dropoff time)
      "ended_at": "2025-12-01 12:35:45",
      
      // When trip started
      "started_at": "2025-12-01 12:10:00",
      
      // Actual trip duration (in minutes)
      "actual_duration": 26,
      
      // Actual distance traveled (in kilometers)
      "actual_distance": 16.2,
      
      // Final fare amount (calculated based on actual distance/duration)
      "actual_fare": 52.50,
      
      // Driver details
      "driver": {
        "id": 42,
        "name": "Mohammed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "avatar": "https://example.com/storage/avatars/driver-42.jpg",
        "current_lat": 24.7750,
        "current_long": 46.7390,
        "vehicle": {
          "model": "Toyota Camry 2023",
          "color": "White",
          "plate_number": "ABC 1234"
        }
      },
      
      // Payment information
      "payment": {
        "method_id": 1,           // 1 = Cash, 2 = Card, etc.
        "total_amount": 52.50,    // Final amount
        "status": 0               // 0 = Pending, 1 = Completed
      }
    }
  }
}
```

**WebSocket Events to Listen:**
- Channel: `trip.{tripId}`
- Event: `.trip_completed` - Payment completed successfully

**Actions Available:**
- Rate trip: `POST /api/client/trips/{id}/rate` (after payment completion)

**Note:** 
- For **Cash** payments: Wait for driver to confirm receiving payment
- For **Card/Wallet** payments: Processing automatically, usually takes 1-5 seconds
- You'll be notified via WebSocket when payment is complete

---

## 6. COMPLETED Status - Trip Fully Completed

This is returned when the trip is completed and payment is confirmed. The trip is now ready for rating.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    "saved_locations": [...],
    
    "active_trip": {
      // Trip identifier
      "trip_id": 123,
      
      // Current status
      "status": {
        "id": 8,          // Status ID (8 = COMPLETED)
        "text": "Completed"
      },
      
      // Pickup location
      "pickup": {
        "lat": 24.7136,
        "long": 46.6753,
        "address": "King Fahd Road, Riyadh"
      },
      
      // Dropoff location
      "dropoff": {
        "lat": 24.7750,
        "long": 46.7390,
        "address": "King Abdullah Financial District, Riyadh"
      },
      
      // When payment was completed
      "completed_at": "2025-12-01 12:36:00",
      
      // When trip ended
      "ended_at": "2025-12-01 12:35:45",
      
      // When trip started
      "started_at": "2025-12-01 12:10:00",
      
      // Actual trip duration (in minutes)
      "actual_duration": 26,
      
      // Actual distance (in kilometers)
      "actual_distance": 16.2,
      
      // Final fare paid
      "actual_fare": 52.50,
      
      // Driver details
      "driver": {
        "id": 42,
        "name": "Mohammed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "avatar": "https://example.com/storage/avatars/driver-42.jpg",
        "current_lat": 24.7750,
        "current_long": 46.7390,
        "vehicle": {
          "model": "Toyota Camry 2023",
          "color": "White",
          "plate_number": "ABC 1234"
        }
      },
      
      // Payment details
      "payment": {
        "method_id": 1,               // Payment method used
        "total_amount": 52.50,        // Total amount paid
        "coupon_discount": 5.00,      // Discount if coupon was applied
        "status": 1                   // 1 = Completed
      }
    }
  }
}
```

**Actions Available:**
- Rate driver: `POST /api/client/trips/{id}/rate`
  - Required: `rating` (1-5), optional: `comment`
- View trip details: `GET /api/client/trips/{id}`

**Note:** 
- After rating, this trip will no longer appear in the home endpoint
- You can view trip history in the trips list endpoint
- Receipt/invoice is available in the trip details

---

## 7. No Active Trip

This is returned when the client has no active trips. They are free to create a new trip.

```json
{
  "success": true,
  "message": "Home data retrieved successfully",
  "data": {
    // Saved locations for quick access
    "saved_locations": [
      {
        "id": 1,
        "type": "home",
        "name": "Home",
        "address": "King Fahd Road, Riyadh",
        "lat": 24.7136,
        "long": 46.6753
      },
      {
        "id": 2,
        "type": "work",
        "name": "Office",
        "address": "Olaya Street, Riyadh",
        "lat": 24.7231,
        "long": 46.6842
      }
    ],
    
    // No active trip
    "active_trip": null
  }
}
```

**Actions Available:**
- Create new trip: `POST /api/client/trips`
- Add/manage saved locations: `POST /api/client/saved-locations`

---

## Important Notes for Mobile Developer

### 1. **Polling Strategy**
```javascript
// Pseudocode example
if (user.isLoggedIn && !hasActiveTrip) {
  // Poll every 5 seconds when no active trip
  pollInterval = 5000;
} else if (hasActiveTrip && trip.status === SEARCHING) {
  // Poll more frequently during driver search
  pollInterval = 2000;
} else if (hasActiveTrip && trip.status === IN_PROGRESS) {
  // Poll less frequently during trip (rely on WebSocket)
  pollInterval = 10000;
} else {
  // Default polling
  pollInterval = 5000;
}
```

### 2. **WebSocket Events (Recommended)**
More efficient than polling. Subscribe to these channels:

```javascript
// Subscribe to trip channel when trip is created
const tripChannel = pusher.subscribe(`trip.${tripId}`);

// Listen to events
tripChannel.bind('driver_accepted', handleDriverAccepted);
tripChannel.bind('driver_arrived', handleDriverArrived);
tripChannel.bind('trip_started', handleTripStarted);
tripChannel.bind('trip_ended', handleTripEnded);
tripChannel.bind('trip_completed', handleTripCompleted);
tripChannel.bind('trip_distance_updated', updateDriverLocation);
tripChannel.bind('search_progress', updateSearchProgress);
```

### 3. **Real-Time Location Tracking**
- Display driver's location on map using `driver.current_lat` and `driver.current_long`
- Update every 5-10 seconds (from polling or WebSocket)
- Draw route from driver to pickup (when IN_ROUTE_TO_PICKUP)
- Draw route from current location to dropoff (when IN_PROGRESS)

### 4. **Status Flow**
```
User creates trip
    ↓
SEARCHING (1)
    ↓ [Driver accepts]
IN_ROUTE_TO_PICKUP (4)
    ↓ [Driver arrives]
PICKUP_ARRIVED (5)
    ↓ [Trip starts]
IN_PROGRESS (7)
    ↓ [Trip ends]
COMPLETED_PENDING_PAYMENT (9)
    ↓ [Payment processed]
COMPLETED (8)
    ↓ [User rates]
Trip archived
```

### 5. **Saved Locations**
- Always returned in home endpoint
- Used for quick trip creation (tap on saved location to set as pickup/dropoff)
- Types: `home`, `work`, `other`

### 6. **Error Handling**
```json
{
  "success": false,
  "message": "You already have an active trip",
  "data": null
}
```

Common errors:
- "You already have an active trip" - User tried to create trip while one is active
- "No drivers available in your area" - Search exhausted all waves
- "Trip not found" - Invalid trip ID

### 7. **Payment Methods**
- **Cash (id: 1)**: Driver confirms payment manually
- **Card (id: 2)**: Automatic processing
- **Wallet (id: 3)**: Automatic processing

### 8. **Cancellation Fees**
Cancellation may incur fees based on trip status:
- SEARCHING: No fee
- IN_ROUTE_TO_PICKUP: Fee may apply after certain time
- PICKUP_ARRIVED: Fee will apply
- IN_PROGRESS: Cannot cancel

### 9. **Search Progress**
During SEARCHING status, show progress to user:
```javascript
const progress = searchProgress.progress_percentage;
const message = `Searching for drivers... Wave ${searchProgress.current_wave} of ${searchProgress.total_waves}`;
```

### 10. **Null Safety**
Fields that may be `null`:
- `active_trip` - When no active trip
- `driver` - When status is SEARCHING
- `driver.avatar` - If driver has no photo
- `payment` - If payment not yet processed
- `payment.coupon_discount` - If no coupon used
- `arrived_at`, `started_at`, `ended_at`, `completed_at` - Depending on status

### 11. **Testing Scenarios**
Test with different states:
1. No active trip
2. Trip in SEARCHING (with different wave numbers)
3. Driver accepted (show driver location)
4. Driver arrived (show arrived time)
5. Trip in progress (track route)
6. Trip completed pending payment
7. Trip fully completed (show rating UI)

### 12. **UI Recommendations**

**SEARCHING Status:**
- Show animated searching indicator
- Display search progress bar
- Show "Cancel" button (no fee)
- Option to "Request More Drivers" (manual next wave)

**IN_ROUTE_TO_PICKUP:**
- Show driver's photo, name, rating
- Display vehicle details
- Show "Call Driver" button
- Real-time map with driver location
- ETA to pickup location

**PICKUP_ARRIVED:**
- Highlight "Driver is waiting" message
- Show how long driver has been waiting
- Prominent "Call Driver" button

**IN_PROGRESS:**
- Show route on map
- Display ETA to destination
- Show estimated vs actual fare comparison
- Real-time driver location

**COMPLETED_PENDING_PAYMENT:**
- Show trip summary
- Display final fare breakdown
- If cash: "Driver is waiting for payment"

**COMPLETED:**
- Show "Rate Your Trip" UI
- Display trip summary and receipt
- Option to view detailed invoice

