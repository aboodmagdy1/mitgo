# WebSocket Events Reference

Quick reference for all trip lifecycle WebSocket events and their payloads.

---

## Channel Architecture

### Rider App
- **`trip.{tripId}`** - Subscribe to trip lifecycle events (rider perspective)

### Driver App
- **`driver.{driverId}`** - Subscribe to personal notifications
- **`driver-trip.{tripId}`** - Subscribe to trip lifecycle events (driver perspective)

### Shared
- **`trip-request.{tripId}`** - Trip request expiration (all notified drivers)

---

## Events Overview

| Event Name | Rider Channel | Driver Channel | Triggered When |
|------------|---------------|----------------|----------------|
| `search_progress` | ✅ `trip.{tripId}` | ❌ | Search wave updates |
| `trip_request_sent` | ❌ | ✅ `driver.{driverId}` | New trip request sent to driver |
| `driver_accepted` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Driver accepts trip |
| `driver_arrived` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Driver arrives at pickup |
| `trip_started` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Trip starts |
| `trip_ended` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Trip ends |
| `trip_completed` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Payment confirmed |
| `trip_cancelled` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Trip cancelled |
| `trip_no_show` | ✅ `trip.{tripId}` | ✅ `driver-trip.{tripId}` | Rider no-show |
| `trip_request_expired` | ❌ | ✅ `trip-request.{tripId}` | Request expired/accepted |

---

## Event Details

### 1. search_progress

**Channel:** `trip.{tripId}` (Rider only)

**Payload:**
```json
{
  "trip_id": 123,
  "current_wave": 2,
  "total_waves": 10,
  "radius": 5.5,
  "drivers_notified": 8,
  "status": "searching"
}
```

---

### 2. trip_request_sent

**Channel:** `driver.{driverId}` (Driver only)

**Payload:**
```json
{
  "trip_id": 123,
  "rider": {
    "id": 45,
    "name": "Mohammed Hassan",
    "phone": "+966509876543",
    "rating": 4.5
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "dropoff": {
    "lat": 24.7243,
    "long": 46.6394,
    "address": "Olaya Street, Riyadh"
  },
  "distance": {
    "to_pickup": 2.3,
    "trip_distance": 5.2,
    "duration_to_pickup": 8
  },
  "estimated_earning": 18.50,
  "estimated_fare": 25.50,
  "expires_at": "2025-11-19T10:32:00Z",
  "acceptance_timeout": 60
}
```

---

### 3. driver_accepted

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 4,
    "text": "In Route to Pickup"
  },
  "driver": {
    "id": 15,
    "name": "Ahmed Ali",
    "phone": "+966501234567",
    "rating": 4.8,
    "current_lat": 24.7100,
    "current_long": 46.6700,
    "vehicle": {
      "model": "Toyota Camry 2023",
      "color": "White",
      "plate_number": "ABC 1234"
    }
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "dropoff": {
    "lat": 24.7243,
    "long": 46.6394,
    "address": "Olaya Street, Riyadh"
  },
  "estimated_fare": 25.50,
  "estimated_duration": 15
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 4,
    "text": "In Route to Pickup"
  },
  "rider": {
    "id": 45,
    "name": "Mohammed Hassan",
    "phone": "+966509876543",
    "rating": 4.5
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "dropoff": {
    "lat": 24.7243,
    "long": 46.6394,
    "address": "Olaya Street, Riyadh"
  },
  "distance": 5.2,
  "estimated_duration": 15,
  "earnings": {
    "base_fare": 25.50,
    "estimated_earning": 21.67,
    "commission_rate": 15
  }
}
```

---

### 4. driver_arrived

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 5,
    "text": "Pickup Arrived"
  },
  "arrived_at": "2025-11-19T10:33:00Z",
  "driver": {
    "id": 15,
    "name": "Ahmed Ali",
    "phone": "+966501234567",
    "current_lat": 24.7136,
    "current_long": 46.6753,
    "vehicle": {
      "model": "Toyota Camry 2023",
      "color": "White",
      "plate_number": "ABC 1234"
    }
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  }
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 5,
    "text": "Pickup Arrived"
  },
  "arrived_at": "2025-11-19T10:33:00Z",
  "rider": {
    "id": 45,
    "name": "Mohammed Hassan",
    "phone": "+966509876543"
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "waiting_time": {
    "free_minutes": 5,
    "started_at": "2025-11-19T10:33:00Z"
  }
}
```

---

### 5. trip_started

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 7,
    "text": "In Progress"
  },
  "started_at": "2025-11-19T10:35:00Z",
  "driver": {
    "id": 15,
    "name": "Ahmed Ali",
    "phone": "+966501234567",
    "current_lat": 24.7136,
    "current_long": 46.6753,
    "vehicle": {
      "model": "Toyota Camry 2023",
      "color": "White",
      "plate_number": "ABC 1234"
    }
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "dropoff": {
    "lat": 24.7243,
    "long": 46.6394,
    "address": "Olaya Street, Riyadh"
  },
  "estimated_duration": 15,
  "estimated_fare": 25.50
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 7,
    "text": "In Progress"
  },
  "started_at": "2025-11-19T10:35:00Z",
  "rider": {
    "id": 45,
    "name": "Mohammed Hassan",
    "phone": "+966509876543"
  },
  "pickup": {
    "lat": 24.7136,
    "long": 46.6753,
    "address": "King Fahd Road, Riyadh"
  },
  "dropoff": {
    "lat": 24.7243,
    "long": 46.6394,
    "address": "Olaya Street, Riyadh"
  },
  "distance": 5.2,
  "estimated_duration": 15,
  "estimated_earning": 21.67
}
```

---

### 6. trip_ended

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 8,
    "text": "Completed Pending Payment"
  },
  "ended_at": "2025-11-19T10:53:00Z",
  "actual_distance": 5.8,
  "actual_duration": 18,
  "actual_fare": 22.95,
  "payment": {
    "payment_method_id": 2,
    "total_amount": 28.75,
    "coupon_discount": 5.80,
    "final_amount": 22.95
  },
  "driver": {
    "id": 15,
    "name": "Ahmed Ali",
    "rating": 4.8
  }
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 8,
    "text": "Completed Pending Payment"
  },
  "ended_at": "2025-11-19T10:53:00Z",
  "actual_distance": 5.8,
  "actual_duration": 18,
  "earnings": {
    "total_fare": 28.75,
    "commission_rate": 15,
    "commission_amount": 4.31,
    "driver_earning": 24.44
  },
  "rider": {
    "id": 45,
    "name": "Mohammed Hassan"
  }
}
```

---

### 7. trip_completed

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 16,
    "text": "Completed"
  },
  "payment": {
    "status": 1,
    "total_amount": 28.75,
    "coupon_discount": 5.80,
    "final_amount": 22.95
  },
  "driver": {
    "id": 15,
    "name": "Ahmed Ali",
    "rating": 4.8
  },
  "can_rate": true
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 16,
    "text": "Completed"
  },
  "payment": {
    "status": 1,
    "driver_earning": 24.44,
    "commission_amount": 4.31
  },
  "wallet": {
    "new_balance": 350.75
  }
}
```

---

### 8. trip_cancelled

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 12,
    "text": "Cancelled by Rider"
  },
  "cancelled_by": "rider",
  "cancellation_reason": "Changed my mind",
  "cancellation_fee": 5.00,
  "refund": {
    "eligible": false,
    "amount": 0
  }
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 12,
    "text": "Cancelled by Rider"
  },
  "cancelled_by": "rider",
  "cancellation_reason": "Changed my mind",
  "compensation": {
    "eligible": true,
    "amount": 5.00
  }
}
```

---

### 9. trip_no_show

**Channels:** `trip.{tripId}` + `driver-trip.{tripId}`

#### Rider Payload (`trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 2,
    "text": "Rider No Show"
  },
  "cancellation_fee": 10.00,
  "payment": {
    "payment_method_id": 2,
    "amount_charged": 10.00
  }
}
```

#### Driver Payload (`driver-trip.{tripId}`)
```json
{
  "trip_id": 123,
  "status": {
    "id": 2,
    "text": "Rider No Show"
  },
  "compensation": {
    "amount": 10.00,
    "reason": "Rider did not show up"
  }
}
```

---

### 10. trip_request_expired

**Channel:** `trip-request.{tripId}` (Shared - all notified drivers)

**Payload:**
```json
{
  "trip_id": 123,
  "reason": "accepted",
  "message": "Trip was accepted by another driver"
}
```

**Possible Reasons:**
- `"accepted"` - Another driver accepted
- `"cancelled"` - Rider cancelled
- `"expired"` - Request timed out
- `"rejected"` - You rejected the request

---

## Flutter Implementation Example

```dart
import 'package:laravel_echo/laravel_echo.dart';

// Initialize Echo
Echo echo = Echo({
  'broadcaster': 'pusher',
  'client': pusherClient,
  'auth': {
    'headers': {
      'Authorization': 'Bearer $token'
    }
  }
});

// Rider: Subscribe to trip events
echo.channel('trip.$tripId')
  .listen('.driver_accepted', (e) {
    print('Driver accepted: ${e['driver']['name']}');
  })
  .listen('.driver_arrived', (e) {
    print('Driver arrived at: ${e['arrived_at']}');
  })
  .listen('.trip_started', (e) {
    print('Trip started');
  })
  .listen('.trip_ended', (e) {
    print('Trip ended. Fare: ${e['actual_fare']}');
  })
  .listen('.trip_completed', (e) {
    print('Trip completed. Please rate driver.');
  })
  .listen('.trip_cancelled', (e) {
    print('Trip cancelled by: ${e['cancelled_by']}');
  });

// Driver: Subscribe to personal notifications
echo.channel('driver.$driverId')
  .listen('.trip_request_sent', (e) {
    print('New trip request: ${e['trip_id']}');
    // Show notification
  });

// Driver: Subscribe to trip-specific events
echo.channel('driver-trip.$tripId')
  .listen('.driver_arrived', (e) {
    print('You arrived. Waiting time: ${e['waiting_time']['free_minutes']} min');
  })
  .listen('.trip_started', (e) {
    print('Trip started. Earning: ${e['estimated_earning']}');
  })
  .listen('.trip_ended', (e) {
    print('Trip ended. You earned: ${e['earnings']['driver_earning']}');
  });

// Driver: Subscribe to shared request expiration
echo.channel('trip-request.$tripId')
  .listen('.trip_request_expired', (e) {
    print('Request expired: ${e['reason']}');
    // Hide trip request notification
  });
```

---

## Status Codes Reference

| ID | Status | Description |
|----|--------|-------------|
| 1 | SEARCHING | Looking for drivers |
| 2 | RIDER_NO_SHOW | Rider didn't show |
| 3 | NO_DRIVER_FOUND | No drivers available |
| 4 | IN_ROUTE_TO_PICKUP | Driver heading to pickup |
| 5 | PICKUP_ARRIVED | Driver at pickup location |
| 6 | RIDER_NOT_FOUND | Rider not found |
| 7 | IN_PROGRESS | Trip active |
| 8 | COMPLETED_PENDING_PAYMENT | Awaiting payment |
| 9 | PAYMENT_FAILED | Payment failed |
| 10 | PAID | Payment completed |
| 11 | CANCELLED_BY_DRIVER | Driver cancelled |
| 12 | CANCELLED_BY_RIDER | Rider cancelled |
| 13 | CANCELLED_BY_SYSTEM | System cancelled |
| 14 | TRIP_EXPIRED | Scheduled trip expired |
| 15 | SCHEDULED | Future trip |
| 16 | COMPLETED | Trip completed |

---

## Important Notes

1. **Always check status.id** for programmatic logic, not status.text (text is localized)
2. **Timestamps** are in ISO 8601 UTC format
3. **Prices** are in Saudi Riyal (SAR) with 2 decimal places
4. **Coordinates** have 8 decimal precision
5. **Null safety**: Check for null fields before accessing nested properties
6. **Channel lifecycle**: Subscribe when trip is created, unsubscribe when completed/cancelled
7. **Reconnection**: Handle WebSocket disconnects gracefully with automatic reconnection

---

**WebSocket URL:** `wss://ws.example.com`  
**Auth Endpoint:** `/broadcasting/auth`  
**Protocol:** Pusher (via Laravel Echo)

