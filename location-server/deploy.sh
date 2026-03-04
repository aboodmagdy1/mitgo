#!/bin/bash

# Location Server Deployment Script
# Usage: ./deploy.sh

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Starting Location Server Deployment...${NC}\n"

# Check if PM2 is installed
if ! command -v pm2 &> /dev/null; then
    echo -e "${RED}❌ PM2 is not installed. Installing...${NC}"
    npm install -g pm2
fi

# Navigate to project directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo -e "${YELLOW}📂 Current directory: $SCRIPT_DIR${NC}"

# Pull latest code (if using git)
if [ -d ".git" ]; then
    echo -e "${YELLOW}📥 Pulling latest code from git...${NC}"
    git pull origin main || git pull origin master
else
    echo -e "${YELLOW}⚠️  Not a git repository, skipping pull${NC}"
fi

# Install/Update dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"
npm install --production

# Create logs directory if it doesn't exist
mkdir -p logs

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    echo -e "${YELLOW}Creating .env from .env.example...${NC}"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${YELLOW}⚠️  Please edit .env file with your credentials${NC}"
        exit 1
    else
        echo -e "${RED}❌ .env.example not found. Please create .env manually${NC}"
        exit 1
    fi
fi

# Test the server configuration
echo -e "${YELLOW}🧪 Testing server configuration...${NC}"
node -c server.js
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Server configuration is valid${NC}"
else
    echo -e "${RED}❌ Server configuration has errors${NC}"
    exit 1
fi

# Check if server is already running
if pm2 list | grep -q "location-server"; then
    echo -e "${YELLOW}🔄 Reloading existing server (zero-downtime)...${NC}"
    pm2 reload ecosystem.config.js --env production
else
    echo -e "${YELLOW}🚀 Starting server for the first time...${NC}"
    pm2 start ecosystem.config.js --env production
fi

# Save PM2 process list
echo -e "${YELLOW}💾 Saving PM2 process list...${NC}"
pm2 save

# Show status
echo -e "\n${GREEN}✅ Deployment complete!${NC}\n"
echo -e "${YELLOW}📊 Server Status:${NC}"
pm2 status

echo -e "\n${YELLOW}📋 Server Info:${NC}"
pm2 info location-server

echo -e "\n${GREEN}🎉 Location Server is now running!${NC}"
echo -e "${YELLOW}View logs: pm2 logs location-server${NC}"
echo -e "${YELLOW}Monitor: pm2 monit${NC}"
echo -e "${YELLOW}Stop: pm2 stop location-server${NC}"
echo -e "${YELLOW}Restart: pm2 restart location-server${NC}"

