#!/bin/bash

# Sentra Signage System - Quick Start Script

set -e

echo "🖥️  Sentra Signage System - Quick Start"
echo "========================================"

TENANT_ID="${1:-default}"
API_URL="${2:-http://localhost:8088}"

echo ""
echo "📋 Configuration:"
echo "   Tenant ID: $TENANT_ID"
echo "   API URL: $API_URL"
echo ""

# Check if tenants service is running
echo "🔍 Checking services..."
if ! curl -s "$API_URL/health" > /dev/null 2>&1; then
    echo "❌ Tenants service not reachable at $API_URL"
    echo "   Please start the tenants service first."
    exit 1
fi
echo "✓ Tenants service is running"

# Create a default playlist
echo ""
echo "📝 Creating default playlist..."
PLAYLIST_RESPONSE=$(curl -s -X POST "$API_URL/api/tenants/$TENANT_ID/signage?_resource=playlists" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Playlist",
    "description": "Default signage playlist",
    "is_default": true
  }')

PLAYLIST_ID=$(echo "$PLAYLIST_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)

if [ -z "$PLAYLIST_ID" ]; then
    echo "❌ Failed to create playlist"
    echo "$PLAYLIST_RESPONSE"
    exit 1
fi

echo "✓ Created playlist with ID: $PLAYLIST_ID"

# Add some sample content to playlist
echo ""
echo "🎨 Adding sample content to playlist..."

# Add gallery images
curl -s -X POST "$API_URL/api/tenants/$TENANT_ID/signage?_resource=playlists&_sub_resource=items" \
  -H "Content-Type: application/json" \
  -d "{
    \"playlist_id\": $PLAYLIST_ID,
    \"content_type\": \"gallery\",
    \"content_data\": {\"tag\": \"featured\", \"limit\": 10},
    \"duration\": 15,
    \"position\": 0
  }" > /dev/null

echo "✓ Added gallery slideshow"

# Add clock widget
curl -s -X POST "$API_URL/api/tenants/$TENANT_ID/signage?_resource=playlists&_sub_resource=items" \
  -H "Content-Type: application/json" \
  -d "{
    \"playlist_id\": $PLAYLIST_ID,
    \"content_type\": \"clock\",
    \"content_data\": {
      \"format\": \"24h\",
      \"timezone\": \"UTC\",
      \"style\": \"digital\"
    },
    \"duration\": 20,
    \"position\": 1
  }" > /dev/null

echo "✓ Added clock widget"

# Add text announcement
curl -s -X POST "$API_URL/api/tenants/$TENANT_ID/signage?_resource=playlists&_sub_resource=items" \
  -H "Content-Type: application/json" \
  -d "{
    \"playlist_id\": $PLAYLIST_ID,
    \"content_type\": \"text\",
    \"content_data\": {
      \"content\": \"<h1>Welcome!</h1><p>Digital Signage System</p>\",
      \"style\": {\"background\": \"#3498db\"}
    },
    \"duration\": 10,
    \"position\": 2
  }" > /dev/null

echo "✓ Added text announcement"

# Register a sample display
echo ""
echo "🖥️  Registering sample display..."
DEVICE_ID="display-demo-$(date +%s)"

DISPLAY_RESPONSE=$(curl -s -X POST "$API_URL/api/tenants/$TENANT_ID/signage?_resource=displays" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Demo Display\",
    \"device_id\": \"$DEVICE_ID\",
    \"location\": \"Demo Location\",
    \"orientation\": \"landscape\",
    \"resolution\": \"1920x1080\",
    \"playlist_id\": $PLAYLIST_ID
  }")

DISPLAY_ID=$(echo "$DISPLAY_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)

if [ -z "$DISPLAY_ID" ]; then
    echo "❌ Failed to create display"
    echo "$DISPLAY_RESPONSE"
    exit 1
fi

echo "✓ Created display with ID: $DISPLAY_ID"
echo "  Device ID: $DEVICE_ID"

# Print summary
echo ""
echo "✅ Setup Complete!"
echo "=================="
echo ""
echo "📊 Summary:"
echo "   Playlist ID: $PLAYLIST_ID"
echo "   Display ID: $DISPLAY_ID"
echo "   Device ID: $DEVICE_ID"
echo ""
echo "🎮 Next Steps:"
echo ""
echo "1. Open Display Player:"
echo "   http://localhost:8095/player.html?device_id=$DEVICE_ID"
echo ""
echo "2. Open Management Interface:"
echo "   http://localhost:8095/manager.html?tenant_id=$TENANT_ID"
echo ""
echo "3. View display config:"
echo "   curl \"$API_URL/api/tenants/$TENANT_ID/signage?_resource=displays\""
echo ""
echo "4. View playlist:"
echo "   curl \"$API_URL/api/tenants/$TENANT_ID/signage?_resource=playlists\""
echo ""
echo "Press F11 in the player for fullscreen mode"
echo "Press Ctrl+S in the player to toggle status bar"
echo ""
