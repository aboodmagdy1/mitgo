module.exports = {
  apps: [{
    name: 'location-server',
    script: './server.js',
    
    // Instances
    instances: 1,  // Single instance (Socket.IO requires this unless using Redis adapter)
    exec_mode: 'fork',  // Fork mode for Socket.IO compatibility
    
    // Watch & Restart
    watch: false,  // Don't watch files in production
    ignore_watch: ['node_modules', 'logs', 'public'],
    max_memory_restart: '500M',  // Restart if memory exceeds 500MB
    
    // Environment
    env: {
      NODE_ENV: 'development',
      PORT: 3000
    },
    env_production: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    
    // Logging
    error_file: './logs/error.log',
    out_file: './logs/output.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    
    // Restart Strategy
    autorestart: true,
    max_restarts: 10,
    min_uptime: '10s',
    restart_delay: 4000,
    
    // Advanced
    listen_timeout: 3000,
    kill_timeout: 5000,
    wait_ready: true,
    
    // Source map support
    source_map_support: true,
    
    // Graceful shutdown
    shutdown_with_message: true
  }],

  // Deployment configuration (optional)
  deploy: {
    production: {
      user: 'nodeapp',
      host: 'your-server-ip',
      ref: 'origin/main',
      repo: 'git@github.com:your-repo/saudi-driver.git',
      path: '/home/nodeapp/saudi-driver',
      'post-deploy': 'cd location-server && npm install --production && pm2 reload ecosystem.config.js --env production',
      env: {
        NODE_ENV: 'production'
      }
    }
  }
};

