# Trip Status Handling Guide

This document explains how different trip statuses are handled in the API responses, particularly in `MyTripsResource`.

## Trip Status Matrix

| Status | Driver Shown | Invoice Shown | Cost Source | Description |
|--------|-------------|---------------|-------------|-------------|
| **COMPLETED** (16) | ✅ Yes | ✅ Yes | `payment.total_amount` → `actual_fare` → `estimated_fare` | Trip completed successfully |
| **PAID** (10) | ✅ Yes | ✅ Yes | `payment.total_amount` → `actual_fare` → `estimated_fare` | Trip completed and payment confirmed |
| **COMPLETED_PENDING_PAYMENT** (8) | ✅ Yes | ✅ Yes | `payment.total_amount` → `actual_fare` → `estimated_fare` | Trip done, waiting payment |
| **SCHEDULED** (15) | ⚠️ If assigned | ❌ No | `estimated_fare` | Future scheduled trip |
| **IN_PROGRESS** (7) | ✅ Yes | ❌ No | `estimated_fare` | Trip currently happening |
| **IN_ROUTE_TO_PICKUP** (4) | ✅ Yes | ❌ No | `estimated_fare` | Driver heading to pickup |
| **PICKUP_ARRIVED** (5) | ✅ Yes | ❌ No | `estimated_fare` | Driver arrived at pickup |
| **CANCELLED_BY_RIDER** (12) | ⚠️ If was assigned | ⚠️ Only if fee > 0 | `payment.total_amount` → `cancellation_fee` or `0` | Rider cancelled |
| **CANCELLED_BY_DRIVER** (11) | ⚠️ If was assigned | ⚠️ Only if fee > 0 | `payment.total_amount` → `cancellation_fee` or `0` | Driver cancelled |
| **CANCELLED_BY_SYSTEM** (13) | ⚠️ If was assigned | ⚠️ Only if fee > 0 | `payment.total_amount` → `cancellation_fee` or `0` | System cancelled |
| **SEARCHING** (1) | ❌ No | ❌ No | `estimated_fare` | Looking for driver |
| **NO_DRIVER_FOUND** (3) | ❌ No | ❌ No | `estimated_fare` | No driver available |
| **PAYMENT_FAILED** (9) | ✅ Yes | ❌ No | `estimated_fare` | Payment processing failed |
| **TRIP_EXPIRED** (14) | ❌ No | ❌ No | `0` | Trip expired |
| **RIDER_NO_SHOW** (2) | ⚠️ If was assigned | ❌ No | `cancellation_fee` or `0` | Rider didn't show up |
| **RIDER_NOT_FOUND** (6) | ⚠️ If was assigned | ❌ No | `cancellation_fee` or `0` | Rider not found at pickup |

## API Response Examples

### 1. Completed Trip (List View)
```json
GET /api/v1/client/trips

{
    "id": 123,
    "status": {
        "id": 16,
        "name": "Completed"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 20.00
}
```

### 2. Completed Trip (Detail View)
```json
GET /api/v1/client/trips/123

{
    "id": 123,
    "status": {
        "id": 16,
        "name": "Completed"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 20.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 1,
        "name": "فيزا",
        "type": "card"
    },
    "driver": {
        "id": 456,
        "name": "Ahmed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "vehicle": {
            "model": "Toyota Camry 2022",
            "color": "White",
            "plate_number": "ABC 1234"
        }
    },
    "invoice": {
        "vehicle_type": "سيارة صغيرة",
        "payment_method": "فيزا",
        "base_cost": 18.00,
        "additional_fees": 2.00,
        "coupon_discount": 0.00,
        "total": 20.00,
        "distance": 12.5,
        "duration": 25,
        "payment_status": "Completed"
    }
}
```

### 3. Scheduled Trip (Driver Not Assigned)
```json
GET /api/v1/client/trips/124

{
    "id": 124,
    "status": {
        "id": 15,
        "name": "Scheduled"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 25.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 2,
        "name": "محفظة",
        "type": "wallet"
    },
    "driver": null,
    "invoice": null
}
```

### 4. Scheduled Trip (Driver Assigned)
```json
GET /api/v1/client/trips/125

{
    "id": 125,
    "status": {
        "id": 15,
        "name": "Scheduled"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 25.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 1,
        "name": "فيزا",
        "type": "card"
    },
    "driver": {
        "id": 789,
        "name": "Mohammed Hassan",
        "phone": "+966507654321",
        "rating": 4.9
    },
    "invoice": null
}
```

### 5. Cancelled Trip (No Fee)
```json
GET /api/v1/client/trips/126

{
    "id": 126,
    "status": {
        "id": 12,
        "name": "Cancelled by Rider"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 0.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 1,
        "name": "فيزا",
        "type": "card"
    },
    "driver": null,
    "invoice": null
}
```

### 6. Cancelled Trip (With Cancellation Fee)
```json
GET /api/v1/client/trips/127

{
    "id": 127,
    "status": {
        "id": 12,
        "name": "Cancelled by Rider"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 15.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 1,
        "name": "فيزا",
        "type": "card"
    },
    "driver": {
        "id": 456,
        "name": "Ahmed Ali",
        "phone": "+966501234567",
        "rating": 4.8
    },
    "invoice": {
        "vehicle_type": "سيارة صغيرة",
        "payment_method": "فيزا",
        "base_cost": 0.00,
        "additional_fees": 15.00,
        "coupon_discount": 0.00,
        "total": 15.00,
        "distance": 0,
        "duration": 0,
        "payment_status": "Completed"
    }
}
```

### 7. In Progress Trip
```json
GET /api/v1/client/trips/128

{
    "id": 128,
    "status": {
        "id": 7,
        "name": "In Progress"
    },
    "dropoff_address": "King Fahd Road, Riyadh",
    "cost": 25.00,
    "pickup_address": "Al Olaya Street, Riyadh",
    "payment_method": {
        "id": 1,
        "name": "فيزا",
        "type": "card"
    },
    "driver": {
        "id": 456,
        "name": "Ahmed Ali",
        "phone": "+966501234567",
        "rating": 4.8,
        "vehicle": {
            "model": "Toyota Camry 2022",
            "color": "White",
            "plate_number": "ABC 1234"
        }
    },
    "invoice": null
}
```

## Filtering Trips

You can filter trips by status using the `status` query parameter:

### Get All Trips (Default)
```
GET /api/v1/client/trips
```
Returns: SCHEDULED, COMPLETED, PAID, COMPLETED_PENDING_PAYMENT, and all CANCELLED trips

### Get Only Scheduled Trips
```
GET /api/v1/client/trips?status=scheduled
GET /api/v1/client/trips?status=1
```
Returns: Only SCHEDULED trips

### Get Only Completed Trips
```
GET /api/v1/client/trips?status=completed
```
Returns: COMPLETED, PAID, COMPLETED_PENDING_PAYMENT trips

### Get Only Cancelled Trips
```
GET /api/v1/client/trips?status=cancelled
```
Returns: CANCELLED_BY_RIDER, CANCELLED_BY_DRIVER, CANCELLED_BY_SYSTEM trips

## Cost Calculation Logic

### Completed Trips
```
Priority order:
1. payment.total_amount (if payment exists)
2. actual_fare (if calculated)
3. estimated_fare (fallback)
```

### Cancelled Trips
```
If cancellation_fee > 0:
  - Priority: payment.total_amount → cancellation_fee
Else:
  - Cost: 0.00
```

### Scheduled/In-Progress Trips
```
Cost: estimated_fare (calculated at trip creation)
```

## Invoice Display Rules

**Invoice is shown ONLY when:**
1. Trip status is COMPLETED, PAID, or COMPLETED_PENDING_PAYMENT
2. OR trip is CANCELLED with cancellation_fee > 0

**Invoice includes:**
- Vehicle type name
- Payment method name
- Base cost (fare before fees)
- Additional fees (waiting time, tolls, etc.)
- Coupon discount (if applied)
- Total amount
- Distance and duration
- Payment status

## Driver Display Rules

**Driver is shown when:**
1. ✅ **Always shown for:**
   - COMPLETED, PAID, COMPLETED_PENDING_PAYMENT
   - IN_PROGRESS, IN_ROUTE_TO_PICKUP, PICKUP_ARRIVED

2. ⚠️ **Conditionally shown (if assigned):**
   - SCHEDULED (only if driver already assigned)
   - All CANCELLED statuses (shows who was driving, if any)
   - RIDER_NO_SHOW, RIDER_NOT_FOUND

3. ❌ **Never shown for:**
   - SEARCHING (no driver yet)
   - NO_DRIVER_FOUND (none found)
   - TRIP_EXPIRED (no driver found in time)

## Implementation Details

### Resource Methods

#### `getCostByStatus()`
Uses PHP 8.1 match expression to determine cost based on status:
- Handles all completion statuses
- Handles cancellations with/without fees
- Falls back to estimated_fare for active trips

#### `getDriverByStatus()`
Returns driver resource or null based on status:
- Always returns driver for completed trips
- Conditionally returns for scheduled/cancelled
- Uses `TripDriverResource` for transformation

#### `getInvoiceByStatus()`
Returns invoice resource or null:
- Only for completed trips or cancellations with fees
- Uses `TripInvoiceResource` for transformation
- Handles payment data gracefully

## Testing Recommendations

Test the following scenarios:
1. ✅ Completed trip with payment
2. ✅ Completed trip without payment record (edge case)
3. ✅ Scheduled trip without driver
4. ✅ Scheduled trip with assigned driver
5. ✅ Cancelled trip without fee
6. ✅ Cancelled trip with fee
7. ✅ In-progress trip
8. ✅ Trip searching for driver
9. ✅ List view vs detail view responses
10. ✅ Filtering by status parameter
