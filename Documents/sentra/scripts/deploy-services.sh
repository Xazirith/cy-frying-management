#!/bin/bash
# Deploy Sentra microservices to production server

set -e

SERVER="Xazirith@sentra-vps"
REMOTE_PATH="/opt/sentra-core"

echo "🚀 Deploying Sentra microservices to production..."

# Push to git
echo "📤 Pushing to git..."
git push origin main

# SSH to server and update
echo "🔄 Updating server..."
ssh $SERVER << 'ENDSSH'
cd /opt/sentra-core

# Pull latest
git pull origin main

# Rebuild and restart all services
docker-compose build
docker-compose up -d

# Show status
echo ""
echo "📊 Service Status:"
docker-compose ps

ENDSSH

echo "✅ Deployment complete!"
