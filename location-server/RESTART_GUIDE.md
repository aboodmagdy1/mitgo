# PM2 Restart Guide

## Quick Restart Commands

### Restart the location server
```bash

```pm2 restart location-server

### Alternative: Restart by ID
```bash
pm2 list  # Find the ID
pm2 restart 0  # Replace 0 with your app ID
```

### Restart all PM2 processes
```bash
pm2 restart all
```

## Full Reload Commands (Zero Downtime)

### Graceful reload (recommended for production)
```bash
pm2 reload location-server
```

## View Logs After Restart

### Check if server started correctly
```bash
pm2 logs location-server --lines 50
```

### Monitor in real-time
```bash
pm2 monit
```

## Check Status

### View process status
```bash
pm2 status
```

### View detailed info
```bash
pm2 show location-server
```

## Common Scenarios

### After code changes (like you just did)
```bash
cd /path/to/saudi-driver/location-server
pm2 restart location-server
pm2 logs location-server
```

### If you see errors
```bash
pm2 stop location-server
pm2 delete location-server
cd /path/to/saudi-driver/location-server
pm2 start server.js --name location-server
```

### Save PM2 configuration after changes
```bash
pm2 save
```

## Verification Checklist

After restarting, verify:

1. **Check PM2 status**
   ```bash
   pm2 status
   ```
   Should show: `online` status

2. **Check logs**
   ```bash
   pm2 logs location-server --lines 20
   ```
   Should show: "🔒 HTTP server running behind reverse proxy (production)"

3. **Test health endpoint**
   ```bash
   curl http://localhost:3000/health
   ```
   Should return: `{"status":"ok","message":"Location server is running"}`

4. **Test Socket.IO**
   ```bash
   curl http://localhost:3000/socket.io/
   ```
   Should return Socket.IO response (not an error)

## Troubleshooting

### Server won't start
```bash
pm2 logs location-server --err
```

### Port already in use
```bash
pm2 delete location-server
pm2 start server.js --name location-server
```

### Need to change port
Edit `.env` file:
```bash
nano /path/to/saudi-driver/location-server/.env
# Change LOCATION_SERVER_PORT=3000 to desired port
pm2 restart location-server
```

