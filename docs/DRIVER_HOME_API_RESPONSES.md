# Driver Home API Response Examples

## Endpoint: `GET /api/driver/home`

This endpoint returns either:
- An active trip (already accepted by driver)
- A pending trip request (sent to driver but not yet accepted)
- Empty response if no trips/requests

---

## 1. SEARCHING Status - Pending Trip Request

This is returned when a trip request has been sent to the driver but not yet accepted.

```json
{
  "success": true,
  "message": "Pending trip request retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
    "status": {
      "id": 1,              // Status ID (1 = SEARCHING)
      "text": "Searching"   // Human-readable status text
    },
    
    // Pickup location details
    "pickup": {
      "lat": 24.7136,       // Latitude
      "long": 46.6753,      // Longitude
      "address": "King Fahd Road, Riyadh"  // Full address
    },
    
    // Dropoff (destination) location details
    "dropoff": {
      "lat": 24.7743,
      "long": 46.7385,
      "address": "King Abdullah Road, Riyadh"
    },
    
    // Price estimation for this trip
    "estimated_fare": 50.00,        // In SAR
    
    // Time estimation (in minutes)
    "estimated_duration": 25,
    
    // Distance estimation (in kilometers)
    "estimated_distance": 15.5,
    
    // Required vehicle type
    "vehicle_type": {
      "id": 2,
      "name": "Economy"
    },
    
    // Selected payment method
    "payment_method": {
      "id": 1,
      "name": "Cash"       // or "Card", "Wallet", etc.
    },
    
    // Rider (customer) information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"  // Profile picture URL
    },
    
    // Time in seconds driver has to accept this request
    "acceptance_time": 60,
    
    // ISO 8601 timestamp when this request expires
    "expires_at": "2025-12-01T12:01:00+03:00",
    
    // Distance from driver's current location to pickup (in km)
    // Calculated based on driver's current location
    "pickup_arrive_distance": 2.5,
    
    // Estimated time to reach pickup location (in minutes)
    "pickup_arrive_duration": 5
  }
}
```

**Actions Available:**
- Accept trip: `POST /api/driver/trips/{id}/accept`
- Reject trip: `POST /api/driver/trips/{id}/reject`

---

## 2. IN_ROUTE_TO_PICKUP Status - Driver Accepted & Going to Pickup

This is returned when the driver has accepted the trip and is on their way to pickup location.

```json
{
  "success": true,
  "message": "Active trip retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
    "status": {
      "id": 4,                      // Status ID (4 = IN_ROUTE_TO_PICKUP)
      "text": "In Route To Pickup"
    },
    
    // Pickup location details
    "pickup": {
      "lat": 24.7136,
      "long": 46.6753,
      "address": "King Fahd Road, Riyadh"
    },
    
    // Dropoff location details
    "dropoff": {
      "lat": 24.7743,
      "long": 46.7385,
      "address": "King Abdullah Road, Riyadh"
    },
    
    // Estimated fare for this trip
    "estimated_fare": 50.00,
    
    // Rider (customer) information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"
    }
  }
}
```

**Actions Available:**
- Mark as arrived: `POST /api/driver/trips/{id}/status` with `action: 5` (PICKUP_ARRIVED)

**Note:** Mobile app should track driver's real-time location and send it to the server.

---

## 3. PICKUP_ARRIVED Status - Driver Arrived at Pickup Location

This is returned when the driver has reached the pickup location and is waiting for the rider.

```json
{
  "success": true,
  "message": "Active trip retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
    "status": {
      "id": 5,                  // Status ID (5 = PICKUP_ARRIVED)
      "text": "Pickup Arrived"
    },
    
    // Pickup location details
    "pickup": {
      "lat": 24.7136,
      "long": 46.6753,
      "address": "King Fahd Road, Riyadh"
    },
    
    // Dropoff location details
    "dropoff": {
      "lat": 24.7743,
      "long": 46.7385,
      "address": "King Abdullah Road, Riyadh"
    },
    
    // Timestamp when driver arrived at pickup
    // Format: Y-m-d H:i:s
    "arrived_at": "2025-12-01 12:05:30",
    
    // Rider information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"
    }
  }
}
```

**Actions Available:**
- Start trip: `POST /api/driver/trips/{id}/status` with `action: 7` (IN_PROGRESS)
  - Required fields: `pickup_lat`, `pickup_long`, `pickup_address`
- Mark rider no-show: `POST /api/driver/trips/{id}/status` with `action: 10` (RIDER_NO_SHOW)

---

## 4. IN_PROGRESS Status - Trip Started

This is returned when the rider is in the vehicle and the trip is in progress.

```json
{
  "success": true,
  "message": "Active trip retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
    "status": {
      "id": 7,              // Status ID (7 = IN_PROGRESS)
      "text": "In Progress"
    },
    
    // Pickup location details (actual pickup location where rider was picked up)
    "pickup": {
      "lat": 24.7136,
      "long": 46.6753,
      "address": "King Fahd Road, Riyadh"
    },
    
    // Dropoff location details (original destination)
    "dropoff": {
      "lat": 24.7743,
      "long": 46.7385,
      "address": "King Abdullah Road, Riyadh"
    },
    
    // Timestamp when trip started
    "started_at": "2025-12-01 12:10:00",
    
    // Estimated trip duration (in minutes)
    "estimated_duration": 25,
    
    // Estimated fare
    "estimated_fare": 50.00,
    
    // Rider information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"
    }
  }
}
```

**Actions Available:**
- End trip: `POST /api/driver/trips/{id}/status` with `action: 8` or `9` (COMPLETED or COMPLETED_PENDING_PAYMENT)
  - Required fields: `dropoff_lat`, `dropoff_long`, `dropoff_address`

**Note:** Mobile app should continuously track and send driver's location during the trip.

---

## 5. COMPLETED_PENDING_PAYMENT Status - Trip Ended, Waiting for Payment Confirmation

This is returned when the trip has ended but payment (especially cash) needs to be confirmed by the driver.

```json
{
  "success": true,
  "message": "Active trip retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
    "status": {
      "id": 9,                              // Status ID (9 = COMPLETED_PENDING_PAYMENT)
      "text": "Completed Pending Payment"
    },
    
    // Pickup location (where rider was actually picked up)
    "pickup": {
      "lat": 24.7136,
      "long": 46.6753,
      "address": "King Fahd Road, Riyadh"
    },
    
    // Dropoff location (where rider was actually dropped off)
    "dropoff": {
      "lat": 24.7750,       // May differ from original if destination changed
      "long": 46.7390,
      "address": "King Abdullah Financial District, Riyadh"
    },
    
    // Timestamp when trip ended
    "ended_at": "2025-12-01 12:35:45",
    
    // Timestamp when trip started
    "started_at": "2025-12-01 12:10:00",
    
    // Actual trip duration (in minutes)
    // Calculated from started_at to ended_at
    "actual_duration": 26,
    
    // Actual distance traveled (in kilometers)
    // Calculated from GPS tracking
    "actual_distance": 16.2,
    
    // Actual fare calculated based on actual distance and duration
    "actual_fare": 52.50,
    
    // Rider information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"
    },
    
    // Payment information
    "payment": {
      "method_id": 1,           // 1 = Cash, 2 = Card, etc.
      "total_amount": 52.50,    // Final amount to be paid
      "status": 0               // 0 = Pending, 1 = Completed
    }
  }
}
```

**Actions Available:**
- Confirm cash payment: `POST /api/driver/trips/{id}/confirm-cash-payment`
  - Only for cash payments (method_id = 1)

---

## 6. COMPLETED Status - Trip Fully Completed

This is returned when the trip is fully completed and payment is confirmed.

```json
{
  "success": true,
  "message": "Active trip retrieved successfully",
  "data": {
    // Unique trip identifier
    "trip_id": 123,
    
    // Current trip status
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
    
    // Timestamp when trip was marked as completed
    "completed_at": "2025-12-01 12:36:00",
    
    // Timestamp when trip ended (dropoff)
    "ended_at": "2025-12-01 12:35:45",
    
    // Timestamp when trip started
    "started_at": "2025-12-01 12:10:00",
    
    // Actual trip duration (in minutes)
    "actual_duration": 26,
    
    // Actual distance traveled (in kilometers)
    "actual_distance": 16.2,
    
    // Final fare amount
    "actual_fare": 52.50,
    
    // Rider information
    "rider": {
      "id": 45,
      "name": "Ahmed Al-Saud",
      "phone": "+966501234567",
      "avatar": "https://example.com/storage/avatars/user-45.jpg"
    },
    
    // Payment details
    "payment": {
      "method_id": 1,               // Payment method ID
      "total_amount": 52.50,        // Total amount paid
      "coupon_discount": 5.00,      // Discount amount if coupon was used
      "status": 1                   // 1 = Completed
    }
  }
}
```

**Actions Available:**
- View detailed invoice: `GET /api/driver/trips/{id}`

---

## 7. No Active Trips or Requests

This is returned when the driver has no active trips or pending requests.

```json
{
  "success": true,
  "message": "You don't have any active trips or requests",
  "data": []
}
```

**Note:** Driver is free to receive new trip requests.

---

## Important Notes for Mobile Developer

### 1. **Polling Strategy**
- Poll this endpoint every 3-5 seconds when driver is online and available
- Stop polling when driver goes offline or has an active trip
- Use WebSocket events for real-time updates (more efficient)

### 2. **WebSocket Events**
Listen to these events for real-time updates:
- `driver.{driverId}` channel:
  - `.new_trip_request` - New trip request received
  - `.trip_request_expired` - Trip request expired (rejected by driver)
  
- `trip-request.{tripId}` channel:
  - `.trip_request_expired` - Trip was accepted by another driver or cancelled
  
- `trip.{tripId}` channel:
  - `.driver_accepted` - Driver accepted trip
  - `.driver_arrived` - Driver arrived at pickup
  - `.trip_started` - Trip started
  - `.trip_ended` - Trip ended
  - `.trip_completed` - Trip completed

### 3. **Location Tracking**
- Send driver's location every 5-10 seconds during active trips
- Use GPS with high accuracy for distance calculation
- Battery optimization: reduce frequency when driver is idle

### 4. **Error Handling**
Always check the `success` field:
```json
{
  "success": false,
  "message": "Error message here",
  "data": null
}
```

### 5. **Status Flow**
```
SEARCHING (1) 
  ↓ [Driver accepts]
IN_ROUTE_TO_PICKUP (4)
  ↓ [Driver arrives]
PICKUP_ARRIVED (5)
  ↓ [Trip starts]
IN_PROGRESS (7)
  ↓ [Trip ends]
COMPLETED_PENDING_PAYMENT (9) or COMPLETED (8)
  ↓ [Payment confirmed]
COMPLETED (8)
```

### 6. **Testing**
Use the endpoint with different scenarios:
- Driver with no trips
- Driver with pending request
- Driver with active trip at each status

### 7. **Null Safety**
Some fields may be `null`:
- `pickup_arrive_distance` and `pickup_arrive_duration` (if driver location unavailable)
- `rider.avatar` (if rider has no profile picture)
- `payment` object (if payment not processed yet)
- `arrived_at`, `started_at`, `ended_at`, `completed_at` (depending on status)

Always check for null values before using them in your UI.

