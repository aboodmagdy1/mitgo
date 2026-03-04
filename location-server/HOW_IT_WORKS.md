# How Socket.IO Works with Laravel & Pusher

## Architecture Overview

```
┌─────────────────┐
│  Mobile App     │
│  (Driver)       │
└────────┬────────┘
         │ Socket.IO
         │ (WebSocket)
         ▼
┌─────────────────┐
│  Node.js        │
│  Location       │
│  Server         │
│  (Port 3000)    │
└────────┬────────┘
         │ HTTP API
         │ (Pusher SDK)
         ▼
┌─────────────────┐
│  Pusher Cloud   │
│  (WebSocket     │
│   Service)      │
└────────┬────────┘
         │ WebSocket
         ▼
┌─────────────────┐
│  Laravel App    │
│  + Mobile Apps  │
│  (Subscribers)  │
└─────────────────┘
```

## Why This Architecture?

### ❌ Direct Laravel → Pusher (What We DON'T Do)

```php
// In Laravel Controller
$pusher->trigger('trip.123', 'location_updated', $data);
```

**Problems:**
- Laravel is slow for real-time updates (PHP overhead)
- Can't handle high-frequency location updates (every 1-3 seconds)
- Database queries slow down broadcasts
- PHP-FPM/Apache limits concurrent connections

### ✅ Socket.IO Server → Pusher (What We DO)

```
Driver App → Socket.IO (fast) → Pusher → All Subscribers
```

**Benefits:**
- **Fast:** Node.js handles thousands of connections
- **Efficient:** No database queries, pure streaming
- **Scalable:** WebSocket connections stay open
- **Decoupled:** Location server independent of Laravel

---

## How It Works: Step by Step

### Step 1: Driver Connects

**Mobile App (Driver):**
```javascript
const socket = io('http://your-server.com:3000');

socket.on('connect', () => {
    console.log('Connected to location server');
});
```

**What Happens:**
1. Driver app opens WebSocket connection to Node server
2. Connection stays open (persistent)
3. No HTTP overhead for each update

### Step 2: Driver Sends Location

**Mobile App (Every 2-3 seconds):**
```javascript
socket.emit('driver_location', {
    trip_id: 123,
    driver_id: 456,
    lat: 24.7136,
    long: 46.6753
});
```

**Node Server Receives:**
```javascript
socket.on('driver_location', async (data) => {
    // Validate data
    if (!data.trip_id || !data.lat || !data.long) {
        return socket.emit('error', { message: 'Invalid data' });
    }
    
    // Broadcast to Pusher
    await pusher.trigger(`trip.${data.trip_id}`, 'driver_location_updated', {
        lat: data.lat,
        long: data.long,
        driver_id: data.driver_id,
        timestamp: Date.now()
    });
    
    // Confirm to driver
    socket.emit('location_received', { success: true });
});
```

### Step 3: Pusher Broadcasts

**Pusher Cloud:**
- Receives HTTP request from Node server
- Broadcasts to all subscribers on `trip.123` channel
- Uses WebSocket to push to clients

### Step 4: Riders Receive Update

**Mobile App (Rider):**
```javascript
const pusher = new Pusher('your-key', { cluster: 'eu' });
const channel = pusher.subscribe('trip.123');

channel.bind('driver_location_updated', (data) => {
    // Update map marker
    updateDriverMarker(data.lat, data.long);
});
```

**Laravel App (Admin Dashboard):**
```javascript
// In Blade template
Echo.channel('trip.123')
    .listen('driver_location_updated', (data) => {
        console.log('Driver at:', data.lat, data.long);
    });
```

---

## Socket.IO vs Pusher: When to Use Each

### Socket.IO (Node Server)
**Use for:**
- ✅ High-frequency updates (location every 1-3 seconds)
- ✅ Bidirectional communication (send & receive)
- ✅ Custom validation/transformation
- ✅ Rate limiting
- ✅ Logging location updates

**Example:**
```javascript
// Driver sends location
socket.emit('driver_location', data);

// Server validates and forwards
socket.on('driver_location', (data) => {
    // Validate
    // Transform
    // Forward to Pusher
});
```

### Pusher (Broadcasting)
**Use for:**
- ✅ Broadcasting to many clients
- ✅ Laravel integration (Laravel Echo)
- ✅ Managed infrastructure
- ✅ Presence channels (who's online)
- ✅ Client events

**Example:**
```php
// Laravel broadcasts trip events
broadcast(new TripStarted($trip));

// Mobile apps receive via Pusher
Echo.channel('trip.123')
    .listen('TripStarted', (data) => {
        // Handle event
    });
```

---

## Integration with Laravel

### Option 1: Laravel Broadcasts Trip Events (Current)

**Laravel:**
```php
// app/Events/TripStarted.php
class TripStarted implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel("trip.{$this->trip->id}"),
            new Channel("driver-trip.{$this->trip->id}")
        ];
    }
}

// When trip starts
event(new TripStarted($trip));
```

**Mobile App:**
```javascript
// Subscribe to trip events
pusher.subscribe('trip.123')
    .bind('trip_started', (data) => {
        // Trip started!
    });
```

### Option 2: Location Server Notifies Laravel (Optional)

**Node Server:**
```javascript
socket.on('driver_location', async (data) => {
    // Broadcast to Pusher
    await pusher.trigger(...);
    
    // Optionally notify Laravel every N seconds
    if (shouldNotifyLaravel(data)) {
        await axios.post('https://saudi-driver.test/api/location-update', data);
    }
});
```

**Laravel API:**
```php
// routes/api.php
Route::post('/location-update', function(Request $request) {
    // Store location in database for history
    DriverLocation::create([
        'driver_id' => $request->driver_id,
        'trip_id' => $request->trip_id,
        'lat' => $request->lat,
        'long' => $request->long,
    ]);
});
```

---

## Pusher Debug Console Explained

### What You See:

```
EVENT: driver_location_updated
CHANNEL: trip.123
DATA: {
  "lat": 24.7136,
  "long": 46.6753,
  "driver_id": 456,
  "timestamp": 1729350123456
}
```

### What It Means:

1. **EVENT:** The name of the event being broadcast
2. **CHANNEL:** Which channel received it (`trip.123`)
3. **DATA:** The payload sent to all subscribers

### How to Use It:

1. **Subscribe to a channel:**
   - Type: `trip.123` in "Subscribe to channel"
   - Click Subscribe

2. **Send location from test UI:**
   - Click "Start Simulation"
   - Watch events appear in real-time

3. **Verify data:**
   - Check lat/long values
   - Verify timestamp is recent
   - Confirm driver_id matches

---

## Common Issues & Solutions

### Issue 1: "Socket.IO not connected"

**Cause:** Node server not running or wrong URL

**Solution:**
```bash
# Check if server is running
curl http://localhost:3000/health

# Should return: {"status":"ok","message":"Location server is running"}

# If not, start server
cd location-server
node server.js
```

### Issue 2: Map Not Loading

**Cause:** Leaflet.js CDN blocked or slow internet

**Solution:**
- Check browser console for errors
- Verify internet connection
- Try refreshing page
- Check if CDN is accessible: https://unpkg.com/leaflet@1.9.4/dist/leaflet.css

### Issue 3: Pusher Not Receiving Events

**Cause:** Wrong credentials or channel name

**Solution:**
```javascript
// Check credentials match .env
PUSHER_APP_KEY=your_key_here
PUSHER_APP_CLUSTER=eu

// Check channel name matches
// Node server: trip.${tripId}
// Subscriber: trip.123
```

### Issue 4: High Latency

**Cause:** Too many hops or slow network

**Check:**
- Pusher cluster location (use closest: eu, us2, ap1)
- Network speed
- Server location

**Solution:**
- Use CDN/edge locations
- Reduce update frequency
- Compress data

---

## Performance Optimization

### 1. Reduce Update Frequency

```javascript
// Instead of every 1 second
setInterval(() => sendLocation(), 1000);

// Use 3-5 seconds for production
setInterval(() => sendLocation(), 3000);
```

### 2. Send Only When Moving

```javascript
let lastLat = null;
let lastLong = null;

function sendLocationIfMoved(lat, long) {
    const distance = calculateDistance(lastLat, lastLong, lat, long);
    
    // Only send if moved > 10 meters
    if (distance > 0.01) {
        sendLocation(lat, long);
        lastLat = lat;
        lastLong = long;
    }
}
```

### 3. Batch Updates

```javascript
// Instead of sending each location
// Batch multiple points and send every 5 seconds
let locationBuffer = [];

setInterval(() => {
    if (locationBuffer.length > 0) {
        socket.emit('driver_locations_batch', locationBuffer);
        locationBuffer = [];
    }
}, 5000);
```

---

## Production Deployment

### 1. Use Environment Variables

```env
# .env
NODE_ENV=production
LOCATION_SERVER_PORT=3000
PUSHER_APP_ID=xxx
PUSHER_APP_KEY=xxx
PUSHER_APP_SECRET=xxx
PUSHER_APP_CLUSTER=eu
ALLOWED_ORIGINS=https://saudi-driver.test,https://app.saudi-driver.com
```

### 2. Add Authentication

```javascript
// Require auth token
socket.on('driver_location', async (data, callback) => {
    // Verify JWT token
    const token = socket.handshake.auth.token;
    const driver = await verifyToken(token);
    
    if (!driver) {
        return callback({ error: 'Unauthorized' });
    }
    
    // Process location
});
```

### 3. Rate Limiting

```javascript
const rateLimit = new Map();

socket.on('driver_location', (data) => {
    const key = `driver:${data.driver_id}`;
    const now = Date.now();
    const lastUpdate = rateLimit.get(key) || 0;
    
    // Max 1 update per second
    if (now - lastUpdate < 1000) {
        return socket.emit('error', { message: 'Rate limit exceeded' });
    }
    
    rateLimit.set(key, now);
    // Process location
});
```

### 4. Use PM2 for Process Management

```bash
# Install PM2
npm install -g pm2

# Start server
pm2 start server.js --name location-server

# Auto-restart on crash
pm2 startup
pm2 save

# Monitor
pm2 monit
```

---

## Testing Checklist

- [ ] Node server starts without errors
- [ ] Health endpoint returns 200: `curl http://localhost:3000/health`
- [ ] Test UI loads at `http://localhost:3000`
- [ ] Socket.IO shows "✓ Connected"
- [ ] Pusher shows "✓ Subscribed"
- [ ] Map displays correctly
- [ ] Sending location shows in event log
- [ ] Pusher Debug Console shows events
- [ ] Latency is < 500ms
- [ ] Multiple tabs can connect simultaneously

---

## Summary

**Socket.IO Server:**
- Receives high-frequency location updates from drivers
- Validates and transforms data
- Forwards to Pusher for broadcasting

**Pusher:**
- Broadcasts location to all subscribers (riders, admin)
- Handles WebSocket connections at scale
- Integrates with Laravel Echo

**Laravel:**
- Broadcasts trip lifecycle events (started, ended, etc.)
- Stores historical location data (optional)
- Manages trip state and business logic

**Mobile Apps:**
- Drivers send location via Socket.IO
- Riders receive location via Pusher
- Both receive trip events via Pusher

This architecture separates concerns and optimizes for real-time performance! 🚀

