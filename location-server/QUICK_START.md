# Quick Start - Production Deployment

## 🚀 5-Minute Production Setup

### Step 1: Install PM2

```bash
# Install PM2 globally
npm install -g pm2

# Verify installation
pm2 -v
```

### Step 2: Make Deploy Script Executable

```bash
cd location-server
chmod +x deploy.sh
```

### Step 3: Deploy

```bash
# Run deployment script
./deploy.sh
```

That's it! Your server is now running with PM2.

---

## ✅ Verify It's Working

```bash
# Check PM2 status
pm2 status

# View logs
pm2 logs location-server

# Monitor in real-time
pm2 monit

# Test health endpoint
curl http://localhost:3000/health
```

---

## 🔄 Auto-Start on Server Reboot

```bash
# Generate startup script
pm2 startup

# Copy and run the command it outputs (something like):
# sudo env PATH=$PATH:/usr/bin pm2 startup systemd -u your-user --hp /home/your-user

# Save current process list
pm2 save

# Test by rebooting
sudo reboot

# After reboot, check if server is running
pm2 status
```

---

## 📊 Common Commands

```bash
# View logs
pm2 logs location-server

# Restart server
pm2 restart location-server

# Stop server
pm2 stop location-server

# Delete from PM2
pm2 delete location-server

# Monitor resources
pm2 monit

# Show detailed info
pm2 show location-server

# List all processes
pm2 list

# Reload (zero-downtime)
pm2 reload location-server
```

---

## 🔧 Update & Redeploy

```bash
# Pull latest code
git pull

# Run deploy script
./deploy.sh
```

---

## 🌐 Production URLs

After deployment, your server will be available at:

- **Local:** `http://localhost:3000`
- **With Nginx:** `http://your-domain.com`
- **With SSL:** `https://your-domain.com`

Update your mobile app to use the production URL:

```javascript
// Before (development)
const socket = io('http://localhost:3000');

// After (production)
const socket = io('https://location.saudi-driver.com');
```

---

## 🔒 Security Checklist

- [ ] Change default port if needed
- [ ] Configure firewall (UFW)
- [ ] Setup SSL certificate (Let's Encrypt)
- [ ] Restrict CORS origins in `.env`
- [ ] Use strong Pusher credentials
- [ ] Enable Nginx reverse proxy
- [ ] Setup log rotation
- [ ] Configure rate limiting

---

## 📈 Monitoring

### PM2 Built-in Monitoring

```bash
# Real-time monitoring
pm2 monit

# Web dashboard
pm2 plus  # Optional paid service
```

### Check Logs

```bash
# View all logs
pm2 logs

# View only errors
pm2 logs --err

# View last 100 lines
pm2 logs --lines 100

# Follow logs in real-time
pm2 logs --lines 0
```

---

## 🆘 Troubleshooting

### Server Won't Start

```bash
# Check error logs
pm2 logs location-server --err

# Check if port is in use
sudo lsof -i :3000

# Kill process on port
sudo kill -9 $(sudo lsof -t -i:3000)

# Try starting again
pm2 restart location-server
```

### High Memory Usage

```bash
# Check memory
pm2 show location-server

# Restart to clear memory
pm2 restart location-server
```

### Can't Connect from Mobile

1. Check firewall allows port 3000
2. Check Nginx configuration
3. Verify SSL certificate is valid
4. Check CORS settings in `.env`

---

## 📱 Mobile App Configuration

Update your mobile app Socket.IO connection:

**React Native / Flutter:**
```javascript
import io from 'socket.io-client';

const socket = io('https://location.saudi-driver.com', {
  transports: ['websocket', 'polling'],
  secure: true,
  rejectUnauthorized: true,
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: 5
});

// Send location
socket.emit('driver_location', {
  trip_id: tripId,
  driver_id: driverId,
  lat: latitude,
  long: longitude
});

// Receive confirmation
socket.on('location_received', (data) => {
  console.log('Location sent successfully');
});
```

---

## 🎯 Performance Tips

1. **Use PM2 Cluster Mode** (already configured in ecosystem.config.js)
   - Runs multiple instances
   - Load balances automatically
   - Uses all CPU cores

2. **Enable Compression**
   - Already enabled in server.js
   - Reduces bandwidth usage

3. **Monitor Memory**
   - PM2 auto-restarts if memory exceeds 500MB
   - Adjust in ecosystem.config.js if needed

4. **Use Nginx**
   - Handles SSL/TLS
   - Serves static files
   - Load balancing
   - Caching

---

## 💰 Server Requirements

**Minimum:**
- 1 vCPU
- 1 GB RAM
- 20 GB Storage
- Ubuntu 20.04 or newer

**Recommended:**
- 2 vCPU
- 2 GB RAM
- 40 GB Storage
- Ubuntu 22.04 LTS

**Providers:**
- DigitalOcean: $12/month (recommended)
- AWS Lightsail: $10/month
- Linode: $10/month
- Vultr: $12/month

---

## 📞 Support

For detailed documentation, see:
- [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md) - Complete deployment guide
- [HOW_IT_WORKS.md](./HOW_IT_WORKS.md) - Architecture explanation
- [README.md](./README.md) - Development guide

---

**Your location server is production-ready! 🎉**

Next steps:
1. ✅ Server running with PM2
2. ⬜ Setup Nginx reverse proxy
3. ⬜ Install SSL certificate
4. ⬜ Configure firewall
5. ⬜ Update mobile app URLs
6. ⬜ Test end-to-end

