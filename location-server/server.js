require('dotenv').config();
const express = require('express');
const http = require('http');
const https = require('https');
const fs = require('fs');
const socketIo = require('socket.io');
const Pusher = require('pusher');
const cors = require('cors');
const fetch = require('node-fetch');

const app = express();

// Create server - Use HTTP behind reverse proxy (recommended for production)
// The reverse proxy (Nginx/Apache) will handle HTTPS/SSL
let server;
const isProduction = process.env.NODE_ENV === 'production';
const useHttps = process.env.USE_HTTPS === 'true'; // Only enable if running standalone without reverse proxy

if (useHttps) {
    // HTTPS server (only if running standalone without reverse proxy)
    console.log('⚠️  Running in standalone HTTPS mode');
    const httpsOptions = {
        key: fs.readFileSync(process.env.SSL_KEY_PATH),
        cert: fs.readFileSync(process.env.SSL_CERT_PATH),
        // Optional: Add CA certificate if using a certificate chain
        ...(process.env.SSL_CA_PATH && { 
            ca: fs.readFileSync(process.env.SSL_CA_PATH) 
        })
    };
    server = https.createServer(httpsOptions, app);
    console.log('🔒 HTTPS server enabled');
} else {
    // HTTP server (recommended for production with reverse proxy)
    server = http.createServer(app);
    if (isProduction) {
        console.log('🔒 HTTP server running behind reverse proxy (production)');
    } else {
        console.log('⚠️  HTTP server enabled (development)');
    }
}

// Enable CORS
app.use(cors());

// Serve static files (test UI)
app.use(express.static('public'));

// Serve test page at root
app.get('/', (req, res) => {
    console.log('Root route accessed');
    console.log('Serving file from:', __dirname + '/public/test.html');
    res.sendFile(__dirname + '/public/test.html');
});

// Socket.IO setup
const io = socketIo(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// Pusher setup
const pusher = new Pusher({
    appId: process.env.PUSHER_APP_ID,
    key: process.env.PUSHER_APP_KEY,
    secret: process.env.PUSHER_APP_SECRET,
    cluster: process.env.PUSHER_APP_CLUSTER,
    useTLS: true
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'ok', message: 'Location server is running' });
});

// Throttle map to prevent excessive API calls
const lastCalculation = new Map();
const CALCULATION_INTERVAL = 10000; // 10 seconds

// Calculate distance and time using Google Maps API
async function calculateDistanceAndTime(origin, destination) {
    const apiKey = process.env.GOOGLE_MAPS_API_KEY;
    
    if (!apiKey) {
        throw new Error('Google Maps API key not configured');
    }
    
    const url = `https://maps.googleapis.com/maps/api/distancematrix/json?` +
        `origins=${origin.lat},${origin.long}` +
        `&destinations=${destination.lat},${destination.long}` +
        `&key=${apiKey}`;
    
    const response = await fetch(url);
    
    if (!response.ok) {
        throw new Error(`Google Maps API request failed: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.status !== 'OK') {
        throw new Error(`Google Maps API error: ${data.status}`);
    }
    
    const element = data.rows[0]?.elements[0];
    
    if (!element || element.status !== 'OK') {
        throw new Error(`No route found: ${element?.status || 'Unknown error'}`);
    }
    
    return {
        distance_km: element.distance.value / 1000,
        duration_minutes: element.duration.value / 60,
        distance_text: element.distance.text,
        duration_text: element.duration.text
    };
}

// Check if we should calculate distance (throttling)
function shouldCalculateDistance(tripId) {
    const last = lastCalculation.get(tripId);
    if (!last) return true;
    return (Date.now() - last) >= CALCULATION_INTERVAL;
}

// Socket.IO connection handling
io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);

    // Driver sends location updates
    socket.on('driver_location', async (data) => {
        const { trip_id, lat, long  } = data;

        if (!trip_id || !lat || !long) {
            socket.emit('error', { message: 'Missing required fields: trip_id, lat, long' });
            return;
        }

        console.log(`Driver location update - Trip: ${trip_id}, Driver: Lat: ${lat}, Long: ${long}, Status: ${trip_status || 'N/A'}`);

        try {
            // // Broadcast driver location (always)
            // await pusher.trigger(`trip.${trip_id}`, 'driver_location_updated', {
            //     lat: parseFloat(lat),
            //     long: parseFloat(long),
            //     timestamp: Date.now()
            // });

           


        } catch (error) {
            console.error('Error broadcasting location:', error);
            socket.emit('error', { 
                message: 'Failed to broadcast location',
                error: error.message 
            });
        }
    });

    // Client location updates (optional - if client shares their location during trip)
    socket.on('client_location', async (data) => {
        const { trip_id, lat, long } = data;

        if (!trip_id || !lat || !long) {
            socket.emit('error', { message: 'Missing required fields: trip_id, lat, long' });
            return;
        }

        console.log(`Client location update - Trip: ${trip_id}, Lat: ${lat}, Long: ${long}`);

        try {
            // Broadcast to Pusher on trip channel
            // await pusher.trigger(`trip.${trip_id}`, 'client_location_updated', {
            //     lat: parseFloat(lat),
            //     long: parseFloat(long),
            //     timestamp: Date.now()
            // });

        } catch (error) {
            console.error('Error broadcasting client location:', error);
            socket.emit('error', { 
                message: 'Failed to broadcast location',
                error: error.message 
            });
        }
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

const PORT = process.env.LOCATION_SERVER_PORT || 3000;

server.listen(PORT, () => {
    const protocol = useHttps ? 'https' : 'http';
    console.log(`\n🚀 Location server running`);
    console.log(`   Protocol: ${protocol.toUpperCase()}`);
    console.log(`   Port: ${PORT}`);
    console.log(`   URL: ${protocol}://localhost:${PORT}`);
    console.log(`   Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`   Pusher Cluster: ${process.env.PUSHER_APP_CLUSTER}\n`);
});

