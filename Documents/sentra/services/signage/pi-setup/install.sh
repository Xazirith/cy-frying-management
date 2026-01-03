#!/bin/bash
#
# Raspberry Pi Signage Player - Automated Installation
# Run this script on your Raspberry Pi to set up the signage player
#
# Usage:
#   curl -sSL https://your-server/pi-setup/install.sh | bash -s -- DEVICE_ID API_URL
#   OR
#   ./install.sh display-lobby-001 http://192.168.1.100:8095
#

set -e

DEVICE_ID="${1}"
API_URL="${2}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}╔═══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Sentra Signage Player - Raspberry Pi Setup     ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running on Raspberry Pi
if ! grep -q "Raspberry Pi" /proc/cpuinfo 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Warning: This doesn't appear to be a Raspberry Pi${NC}"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Get device ID and API URL if not provided
if [ -z "$DEVICE_ID" ]; then
    DEFAULT_DEVICE_ID="display-$(hostname)-$(date +%s)"
    read -p "Enter Device ID [$DEFAULT_DEVICE_ID]: " DEVICE_ID
    DEVICE_ID="${DEVICE_ID:-$DEFAULT_DEVICE_ID}"
fi

if [ -z "$API_URL" ]; then
    read -p "Enter API URL (e.g., http://192.168.1.100:8095): " API_URL
fi

if [ -z "$API_URL" ]; then
    echo -e "${RED}❌ API URL is required${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}📋 Configuration:${NC}"
echo "   Device ID: $DEVICE_ID"
echo "   API URL: $API_URL"
echo ""

read -p "Is this correct? (Y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    echo "Cancelled."
    exit 1
fi

echo ""
echo -e "${GREEN}🔧 Installing required packages...${NC}"

# Update package list
sudo apt-get update -qq

# Install required packages
sudo apt-get install -y -qq \
    chromium-browser \
    unclutter \
    xserver-xorg \
    xinit \
    x11-xserver-utils \
    matchbox-window-manager \
    xautomation \
    curl \
    wget

echo -e "${GREEN}✓ Packages installed${NC}"

echo ""
echo -e "${GREEN}📝 Creating startup script...${NC}"

# Create startup script
cat > /tmp/start-signage.sh << 'EOF'
#!/bin/bash

# Signage Player Startup Script
DEVICE_ID="${DEVICE_ID:-display-unknown}"
API_URL="${API_URL:-http://localhost:8095}"
PLAYER_URL="$API_URL/player.html?device_id=$DEVICE_ID"

# Disable screen blanking
xset s off
xset -dpms
xset s noblank

# Hide cursor
unclutter -idle 0.1 &

# Start window manager
matchbox-window-manager &

# Wait for network
echo "Waiting for network connection..."
while ! ping -c 1 -W 1 $(echo $API_URL | sed 's|http://||' | cut -d: -f1) &> /dev/null; do
    sleep 5
done
echo "Network connected!"

# Clear cache on startup
rm -rf ~/.config/chromium/Default/Cache/* 2>/dev/null || true

# Launch Chromium in kiosk mode
chromium-browser \
    --kiosk \
    --noerrdialogs \
    --disable-infobars \
    --no-first-run \
    --disable-session-crashed-bubble \
    --disable-translate \
    --disable-features=TranslateUI \
    --disable-component-update \
    --check-for-update-interval=31536000 \
    --autoplay-policy=no-user-gesture-required \
    --start-fullscreen \
    --disable-background-timer-throttling \
    --disable-backgrounding-occluded-windows \
    --disable-renderer-backgrounding \
    "$PLAYER_URL"
EOF

sudo mv /tmp/start-signage.sh /home/pi/start-signage.sh
sudo chmod +x /home/pi/start-signage.sh
sudo chown pi:pi /home/pi/start-signage.sh

echo -e "${GREEN}✓ Startup script created${NC}"

echo ""
echo -e "${GREEN}⚙️  Creating systemd service...${NC}"

# Create systemd service
sudo tee /etc/systemd/system/signage-player.service > /dev/null << EOF
[Unit]
Description=Sentra Signage Player
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=pi
Environment=DISPLAY=:0
Environment=XAUTHORITY=/home/pi/.Xauthority
Environment=DEVICE_ID=$DEVICE_ID
Environment=API_URL=$API_URL
ExecStartPre=/bin/sleep 10
ExecStart=/usr/bin/startx /home/pi/start-signage.sh -- -nocursor
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

echo -e "${GREEN}✓ Service created${NC}"

echo ""
echo -e "${GREEN}🚀 Enabling auto-start...${NC}"

# Reload systemd
sudo systemctl daemon-reload

# Enable service
sudo systemctl enable signage-player.service

echo -e "${GREEN}✓ Auto-start enabled${NC}"

echo ""
echo -e "${GREEN}🔧 Applying optimizations...${NC}"

# Disable WiFi power management
if ! grep -q "iwconfig wlan0 power off" /etc/rc.local 2>/dev/null; then
    sudo sed -i 's/^exit 0/\/sbin\/iwconfig wlan0 power off 2>\/dev\/null || true\nexit 0/' /etc/rc.local
    echo -e "${GREEN}✓ WiFi power management disabled${NC}"
fi

# Set GPU memory
if ! grep -q "gpu_mem=128" /boot/config.txt 2>/dev/null; then
    echo "gpu_mem=128" | sudo tee -a /boot/config.txt > /dev/null
    echo -e "${GREEN}✓ GPU memory optimized${NC}"
fi

# Add nightly reboot (optional)
read -p "Enable nightly reboot at 3 AM? (Y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    (sudo crontab -l 2>/dev/null; echo "0 3 * * * /sbin/shutdown -r now") | sudo crontab -
    echo -e "${GREEN}✓ Nightly reboot scheduled${NC}"
fi

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              Installation Complete!               ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}📊 Summary:${NC}"
echo "   Device ID: $DEVICE_ID"
echo "   API URL: $API_URL"
echo "   Service: signage-player.service"
echo ""
echo -e "${YELLOW}⚡ Next Steps:${NC}"
echo ""
echo "1. Register this display in the management interface:"
echo "   Device ID: $DEVICE_ID"
echo ""
echo "2. Reboot to start the player:"
echo "   ${GREEN}sudo reboot${NC}"
echo ""
echo "3. After reboot, the display will auto-start in ~30 seconds"
echo ""
echo -e "${YELLOW}📝 Useful Commands:${NC}"
echo "   View logs:      sudo journalctl -u signage-player.service -f"
echo "   Restart player: sudo systemctl restart signage-player.service"
echo "   Stop player:    sudo systemctl stop signage-player.service"
echo "   Check status:   sudo systemctl status signage-player.service"
echo ""

read -p "Reboot now? (Y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    echo ""
    echo -e "${GREEN}🔄 Rebooting in 5 seconds...${NC}"
    sleep 5
    sudo reboot
else
    echo ""
    echo -e "${YELLOW}Remember to reboot when ready: sudo reboot${NC}"
fi
