# Location Server - Test UI

## Quick Start

### 1. Install Dependencies
```bash
cd location-server
npm install
```

### 2. Configure Environment
Create `.env` file:
```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=eu
LOCATION_SERVER_PORT=3000
```

### 3. Start Server
```bash
node server.js
```

### 4. Open Test UI
Open your browser and navigate to:
```
http://localhost:3000
```

## Using the Test UI

### Initial Setup

1. **Enter Pusher Credentials**
   - Copy `PUSHER_APP_KEY` from your `.env` file
   - Copy `PUSHER_APP_CLUSTER` from your `.env` file
   - Click "Connect to Pusher"
   - Wait for status to show "✓ Subscribed"

2. **Configure Trip**
   - Enter Trip ID (default: 123)
   - Enter Driver ID (default: 456)

### Test Scenarios

#### Scenario 1: Single Location Update
1. Click "📍 Send Current Location"
2. Check event log for confirmation
3. Verify in Pusher Debug Console

#### Scenario 2: Simulated Driver Movement
1. Click "▶️ Start Simulation"
2. Watch driver marker move on map
3. See real-time events in log panel
4. Click "⏸️ Stop Simulation" to stop

#### Scenario 3: Manual Position Update
1. Click anywhere on the map
2. Driver marker jumps to clicked location
3. Click "📍 Send Current Location" to broadcast

#### Scenario 4: Dual Browser Test (Driver + Rider)
1. Open two browser tabs
2. **Tab 1 (Driver):** Start simulation
3. **Tab 2 (Rider):** Watch location updates
4. Both tabs show synchronized movement

### Features

**Map Controls:**
- 🖱️ Click to set driver position
- 🔍 Zoom in/out with mouse wheel
- 📍 Driver marker shows current position
- 📈 Blue line shows movement history

**Simulation Controls:**
- Speed: Slow (3s), Normal (1s), Fast (0.5s)
- Randomized movement pattern
- Up to 100 location history points

**Statistics Panel:**
- **Sent:** Number of locations sent
- **Received:** Number of locations received via Pusher
- **Latitude/Longitude:** Current coordinates
- **Latency:** Time between send and receive

**Event Log:**
- Color-coded events (info, success, error, warning)
- Timestamps for each event
- Auto-scroll to latest
- Clear button to reset

## Testing with Pusher Debug Console

1. Go to https://dashboard.pusher.com
2. Select your app
3. Click "Debug Console"
4. Subscribe to `trip.123` (or your trip ID)
5. Run simulation and watch events appear

## Expected Event Flow

```
Browser (test.html)
    ↓ Socket.IO emit('driver_location')
Node Server (server.js)
    ↓ pusher.trigger('trip.123', 'driver_location_updated')
Pusher Cloud
    ↓ WebSocket broadcast
Browser (Pusher client)
    ↓ Event received
Map Updated + Log Entry
```

## Troubleshooting

**"Socket.IO: ✗ Disconnected"**
- Make sure server is running (`node server.js`)
- Check port 3000 is not in use

**"Pusher: ⏳ Connecting..."**
- Verify Pusher credentials are correct
- Check cluster matches your Pusher app (eu, us2, etc.)

**No events received**
- Ensure both Socket.IO and Pusher are connected
- Check browser console for errors
- Verify trip ID matches in both sender and receiver

**Map not loading**
- Check internet connection (Leaflet loads from CDN)
- Disable ad blockers if needed

## API Reference

### Socket.IO Events

**Emit (Client → Server):**
```javascript
socket.emit('driver_location', {
    trip_id: 123,
    driver_id: 456,
    lat: 24.7136,
    long: 46.6753
});
```

**Receive (Server → Client):**
```javascript
socket.on('location_received', (data) => {
    // { success: true, trip_id: 123 }
});
```

### Pusher Events

**Channel:** `trip.{trip_id}`

**Event:** `driver_location_updated`

**Payload:**
```json
{
  "lat": 24.7136,
  "long": 46.6753,
  "driver_id": 456,
  "timestamp": 1729350123456
}
```

## Development

### File Structure
```
location-server/
├── server.js           # Main server file
├── public/
│   └── test.html      # Test UI
├── .env               # Environment variables
├── package.json       # Dependencies
└── README.md          # This file
```

### Dependencies
- `express` - Web server
- `socket.io` - WebSocket server
- `pusher` - Pusher HTTP API client
- `cors` - CORS support
- `dotenv` - Environment variables

### Adding Features

To add custom markers or features:
1. Edit `public/test.html`
2. Add Leaflet.js code in `<script>` section
3. Refresh browser (no server restart needed)

## Production Deployment

**Security Notes:**
- Don't expose Pusher credentials in frontend for production
- Use authentication for Socket.IO connections
- Implement rate limiting
- Add input validation

**Recommended Setup:**
- Use environment-specific credentials
- Deploy behind reverse proxy (nginx)
- Enable HTTPS/WSS
- Monitor connection counts

## Support

For issues or questions:
1. Check Pusher Debug Console for events
2. Check browser console for errors
3. Check server logs for Socket.IO events
4. Verify credentials in `.env` file
