# Production Deployment Guide - Location Server

## 🚀 Complete Production Setup

This guide covers deploying your Node.js location server to production with auto-restart, monitoring, and security.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [PM2 Process Manager](#pm2-process-manager)
4. [Nginx Reverse Proxy](#nginx-reverse-proxy)
5. [SSL/HTTPS Setup](#sslhttps-setup)
6. [Security Hardening](#security-hardening)
7. [Monitoring & Logging](#monitoring--logging)
8. [Deployment Automation](#deployment-automation)

---

## Prerequisites

### Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Node.js (v20 LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node -v  # Should show v20.x.x
npm -v   # Should show 10.x.x

# Install build tools
sudo apt install -y build-essential
```

---

## Server Setup

### 1. Create Deployment User

```bash
# Create dedicated user for the app
sudo adduser --disabled-password --gecos "" nodeapp

# Add to sudo group (optional, for maintenance)
sudo usermod -aG sudo nodeapp

# Switch to new user
sudo su - nodeapp
```

### 2. Upload Your Code

**Option A: Git Clone (Recommended)**
```bash
# Install git
sudo apt install -y git

# Clone repository
cd /home/nodeapp
git clone https://github.com/your-repo/saudi-driver.git
cd saudi-driver/location-server

# Install dependencies
npm install --production
```

**Option B: SCP/SFTP Upload**
```bash
# From your local machine
scp -r location-server/ nodeapp@your-server:/home/nodeapp/

# On server
cd /home/nodeapp/location-server
npm install --production
```

### 3. Configure Environment

```bash
# Create production .env file
nano .env
```

**Production .env:**
```env
NODE_ENV=production
LOCATION_SERVER_PORT=3000
PUSHER_APP_ID=your_production_app_id
PUSHER_APP_KEY=your_production_key
PUSHER_APP_SECRET=your_production_secret
PUSHER_APP_CLUSTER=eu

# Security
ALLOWED_ORIGINS=https://saudi-driver.test,https://app.saudi-driver.com

# Optional: Database for logging
DB_HOST=localhost
DB_USER=nodeapp
DB_PASSWORD=secure_password
DB_NAME=location_logs
```

### 4. Test Server

```bash
# Test run
node server.js

# Should see:
# Location server listening on port 3000
# Pusher configured for cluster: eu

# Test health endpoint (in another terminal)
curl http://localhost:3000/health
```

---

## PM2 Process Manager

PM2 keeps your server running 24/7, auto-restarts on crashes, and manages logs.

### 1. Install PM2

```bash
# Install PM2 globally
sudo npm install -g pm2

# Verify installation
pm2 -v
```

### 2. Create PM2 Ecosystem File

```bash
# In location-server directory
nano ecosystem.config.js
```

**ecosystem.config.js:**
```javascript
module.exports = {
  apps: [{
    name: 'location-server',
    script: './server.js',
    instances: 1,  // Single instance (Socket.IO requires this or Redis adapter)
    exec_mode: 'fork',  // Fork mode for Socket.IO compatibility
    watch: false,  // Don't watch in production
    max_memory_restart: '500M',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: './logs/error.log',
    out_file: './logs/output.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    max_restarts: 10,
    min_uptime: '10s',
    restart_delay: 4000
  }]
};
```

> **⚠️ Important: Socket.IO and Cluster Mode**
> 
> Socket.IO does **not work** with PM2 cluster mode (`instances: 2+`) without additional configuration. Running multiple instances causes WebSocket connections to fail because connections get distributed across instances.
>
> **Solutions:**
> - **Option 1 (Recommended):** Run in fork mode with 1 instance (shown above)
> - **Option 2 (Advanced):** Use Redis adapter for multi-instance setup (see bottom of this file)

### 3. Start with PM2

```bash
# Create logs directory
mkdir -p logs

# Start application
pm2 start ecosystem.config.js

# Check status
pm2 status

# View logs
pm2 logs location-server

# Monitor in real-time
pm2 monit
```

### 4. Setup Auto-Start on Boot

```bash
# Generate startup script
pm2 startup

# This will output a command like:
# sudo env PATH=$PATH:/usr/bin pm2 startup systemd -u nodeapp --hp /home/nodeapp
# Copy and run that command

# Save current PM2 process list
pm2 save

# Test reboot
sudo reboot

# After reboot, check if server is running
pm2 status
```

### 5. PM2 Commands Cheat Sheet

```bash
# Start
pm2 start ecosystem.config.js

# Stop
pm2 stop location-server

# Restart
pm2 restart location-server

# Reload (zero-downtime)
pm2 reload location-server

# Delete from PM2
pm2 delete location-server

# View logs
pm2 logs location-server --lines 100

# Monitor
pm2 monit

# Show process info
pm2 show location-server

# List all processes
pm2 list
```

---

## Nginx Reverse Proxy

Use Nginx to:
- Handle SSL/HTTPS
- Serve static files
- Load balance
- Add security headers

### 1. Install Nginx

```bash
sudo apt install -y nginx

# Start and enable
sudo systemctl start nginx
sudo systemctl enable nginx

# Check status
sudo systemctl status nginx
```

### 2. Configure Nginx

```bash
# Create config file
sudo nano /etc/nginx/sites-available/location-server
```

**Basic Configuration:**
```nginx
upstream location_server {
    # Single instance (unless using Redis adapter for clustering)
    server 127.0.0.1:3000;
    keepalive 64;
}

server {
    listen 80;
    server_name location.saudi-driver.com;

    # Redirect to HTTPS (will setup later)
    # return 301 https://$server_name$request_uri;

    # For now, proxy to Node.js
    location / {
        proxy_pass http://location_server;
        proxy_http_version 1.1;
        
        # WebSocket support
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        
        # Headers
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Health check endpoint
    location /health {
        proxy_pass http://location_server/health;
        access_log off;
    }

    # Static files (test UI)
    location /public {
        alias /home/nodeapp/saudi-driver/location-server/public;
        expires 1d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3. Enable Configuration

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/location-server /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 4. Configure Firewall

```bash
# Allow Nginx
sudo ufw allow 'Nginx Full'

# Allow SSH (important!)
sudo ufw allow ssh

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## SSL/HTTPS Setup

Use Let's Encrypt for free SSL certificates.

### 1. Install Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d location.saudi-driver.com

# Follow prompts:
# - Enter email
# - Agree to terms
# - Choose: Redirect HTTP to HTTPS (option 2)
```

### 2. Certbot Auto-Updates Nginx Config

After running certbot, your Nginx config will look like:

```nginx
server {
    listen 80;
    server_name location.saudi-driver.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name location.saudi-driver.com;

    ssl_certificate /etc/letsencrypt/live/location.saudi-driver.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/location.saudi-driver.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # ... rest of config
}
```

### 3. Auto-Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically sets up cron job for renewal
# Check cron
sudo systemctl status certbot.timer
```

### 4. Update Mobile App URLs

**Before:**
```javascript
const socket = io('http://localhost:3000');
```

**After:**
```javascript
const socket = io('https://location.saudi-driver.com', {
    secure: true,
    rejectUnauthorized: true
});
```

---

## Security Hardening

### 1. Update server.js for Production

```javascript
// Add to server.js
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// Security headers
app.use(helmet());

// Rate limiting
const limiter = rateLimit({
    windowMs: 1 * 60 * 1000, // 1 minute
    max: 100, // 100 requests per minute
    message: 'Too many requests from this IP'
});
app.use('/api/', limiter);

// CORS - restrict origins
const allowedOrigins = process.env.ALLOWED_ORIGINS?.split(',') || [];
app.use(cors({
    origin: function(origin, callback) {
        if (!origin || allowedOrigins.includes(origin)) {
            callback(null, true);
        } else {
            callback(new Error('Not allowed by CORS'));
        }
    },
    credentials: true
}));

// Socket.IO authentication
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    if (!token) {
        return next(new Error('Authentication required'));
    }
    // Verify token here
    next();
});
```

### 2. Install Security Packages

```bash
npm install helmet express-rate-limit
```

### 3. Environment Security

```bash
# Secure .env file
chmod 600 .env
chown nodeapp:nodeapp .env

# Never commit .env to git
echo ".env" >> .gitignore
```

### 4. Disable Root Login

```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Change:
PermitRootLogin no
PasswordAuthentication no  # Use SSH keys only

# Restart SSH
sudo systemctl restart sshd
```

---

## Monitoring & Logging

### 1. PM2 Monitoring

```bash
# Real-time monitoring
pm2 monit

# Web dashboard (optional)
pm2 install pm2-server-monit
```

### 2. Log Rotation

```bash
# Install logrotate config
sudo nano /etc/logrotate.d/location-server
```

**logrotate config:**
```
/home/nodeapp/saudi-driver/location-server/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 nodeapp nodeapp
    sharedscripts
    postrotate
        pm2 reloadLogs
    endscript
}
```

### 3. Setup Alerts

**Install PM2 Plus (Optional - Paid):**
```bash
# Link to PM2 Plus for advanced monitoring
pm2 link <secret_key> <public_key>
```

**Or use simple email alerts:**
```bash
# Install pm2-slack or pm2-discord
pm2 install pm2-slack

# Configure
pm2 set pm2-slack:slack_url https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

### 4. Health Check Monitoring

**Create monitoring script:**
```bash
nano /home/nodeapp/health-check.sh
```

```bash
#!/bin/bash
HEALTH_URL="https://location.saudi-driver.com/health"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)

if [ $RESPONSE != "200" ]; then
    echo "Health check failed! Status: $RESPONSE"
    # Send alert (email, Slack, etc.)
    pm2 restart location-server
fi
```

```bash
# Make executable
chmod +x /home/nodeapp/health-check.sh

# Add to crontab (check every 5 minutes)
crontab -e

# Add line:
*/5 * * * * /home/nodeapp/health-check.sh
```

---

## Deployment Automation

### 1. Create Deployment Script

```bash
nano /home/nodeapp/deploy.sh
```

```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Navigate to project
cd /home/nodeapp/saudi-driver/location-server

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
npm install --production

# Run tests (if you have any)
# npm test

# Reload PM2 (zero-downtime)
echo "🔄 Reloading application..."
pm2 reload ecosystem.config.js

# Check status
pm2 status

echo "✅ Deployment complete!"
echo "📊 Server status:"
pm2 info location-server
```

```bash
# Make executable
chmod +x /home/nodeapp/deploy.sh

# Run deployment
./deploy.sh
```

### 2. GitHub Actions (Optional)

**.github/workflows/deploy.yml:**
```yaml
name: Deploy Location Server

on:
  push:
    branches: [ main ]
    paths:
      - 'location-server/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: nodeapp
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /home/nodeapp/saudi-driver
            ./deploy.sh
```

---

## Quick Start Checklist

- [ ] Server has Node.js v20 installed
- [ ] Code uploaded to `/home/nodeapp/saudi-driver/location-server`
- [ ] `.env` file configured with production credentials
- [ ] Dependencies installed: `npm install --production`
- [ ] PM2 installed globally: `npm install -g pm2`
- [ ] Server started: `pm2 start ecosystem.config.js`
- [ ] PM2 startup configured: `pm2 startup` + `pm2 save`
- [ ] Nginx installed and configured
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] Firewall configured (UFW)
- [ ] Health check working: `curl https://location.saudi-driver.com/health`
- [ ] Logs rotating properly
- [ ] Monitoring setup (PM2 monit)

---

## Maintenance Commands

```bash
# View logs
pm2 logs location-server --lines 100

# Restart server
pm2 restart location-server

# Update code and deploy
cd /home/nodeapp/saudi-driver
git pull
cd location-server
npm install --production
pm2 reload location-server

# Check server health
curl https://location.saudi-driver.com/health

# Monitor resources
pm2 monit

# View Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# Restart Nginx
sudo systemctl restart nginx

# Check disk space
df -h

# Check memory
free -h

# Check PM2 processes
pm2 list
```

---

## Troubleshooting

### Server Won't Start

```bash
# Check PM2 logs
pm2 logs location-server --err

# Check if port is in use
sudo netstat -tulpn | grep 3000

# Kill process on port
sudo kill -9 $(sudo lsof -t -i:3000)

# Restart
pm2 restart location-server
```

### High Memory Usage

```bash
# Check memory
pm2 show location-server

# Restart if needed
pm2 restart location-server

# Adjust max memory in ecosystem.config.js
max_memory_restart: '500M'
```

### SSL Certificate Issues

```bash
# Renew certificate
sudo certbot renew

# Test renewal
sudo certbot renew --dry-run

# Check certificate expiry
sudo certbot certificates
```

---

## Performance Optimization

### 1. Enable Compression

```javascript
// In server.js
const compression = require('compression');
app.use(compression());
```

### 2. Use Redis for Session Storage

```bash
# Install Redis
sudo apt install redis-server

# In server.js
const redis = require('redis');
const client = redis.createClient();
```

### 3. Monitor Performance

```bash
# Install PM2 metrics
pm2 install pm2-metrics

# View metrics
pm2 metrics
```

---

## Backup Strategy

```bash
# Create backup script
nano /home/nodeapp/backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/home/nodeapp/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup code
tar -czf $BACKUP_DIR/location-server_$DATE.tar.gz \
    /home/nodeapp/saudi-driver/location-server \
    --exclude=node_modules \
    --exclude=logs

# Keep only last 7 days
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/location-server_$DATE.tar.gz"
```

```bash
# Schedule daily backup
crontab -e

# Add line (runs at 2 AM daily):
0 2 * * * /home/nodeapp/backup.sh
```

---

## Cost Estimation

**Minimum Server Requirements:**
- CPU: 1 vCPU
- RAM: 1 GB
- Storage: 20 GB
- Bandwidth: 1 TB/month

**Recommended Providers:**
- DigitalOcean: $6/month (Basic Droplet)
- AWS Lightsail: $5/month
- Linode: $5/month
- Vultr: $6/month

**Additional Costs:**
- Domain: ~$12/year
- SSL: Free (Let's Encrypt)
- Monitoring: Free (PM2) or $10/month (PM2 Plus)

---

## Advanced: Socket.IO Cluster Mode with Redis

If you need to run multiple instances for high traffic, you must use the Redis adapter to sync Socket.IO connections across instances.

### 1. Install Redis

```bash
# Install Redis
sudo apt install -y redis-server

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis

# Verify
redis-cli ping  # Should return: PONG
```

### 2. Install Socket.IO Redis Adapter

```bash
cd /home/nodeapp/saudi-driver/location-server
npm install @socket.io/redis-adapter redis
```

### 3. Update server.js

```javascript
// Add at the top with other requires
const { createAdapter } = require("@socket.io/redis-adapter");
const { createClient } = require("redis");

// After creating io instance
const pubClient = createClient({ host: 'localhost', port: 6379 });
const subClient = pubClient.duplicate();

Promise.all([pubClient.connect(), subClient.connect()]).then(() => {
    io.adapter(createAdapter(pubClient, subClient));
    console.log('Redis adapter connected');
});

// ... rest of your code
```

### 4. Update ecosystem.config.js for Cluster Mode

```javascript
module.exports = {
  apps: [{
    name: 'location-server',
    script: './server.js',
    instances: 2,  // Now you can use multiple instances
    exec_mode: 'cluster',
    // ... rest of config
  }]
};
```

### 5. Restart with PM2

```bash
pm2 restart location-server
pm2 logs location-server  # Should see "Redis adapter connected"
```

**Performance Note:** A single instance can handle thousands of concurrent connections. Only use cluster mode if you're expecting very high traffic (10,000+ concurrent users).

---

## Support & Resources

- PM2 Documentation: https://pm2.keymetrics.io/docs/
- Nginx Documentation: https://nginx.org/en/docs/
- Let's Encrypt: https://letsencrypt.org/
- Node.js Best Practices: https://github.com/goldbergyoni/nodebestpractices

---

**Your location server is now production-ready! 🎉**

