# 🥧 Raspberry Pi Signage Player Setup

Complete guide for setting up Raspberry Pi devices as digital signage displays.

## Hardware Requirements

### Recommended Setup
- **Raspberry Pi 4 Model B** (4GB or 8GB RAM)
- **Power Supply**: Official 15W USB-C power supply
- **Storage**: 32GB+ microSD card (Class 10 or better)
- **Display**: Any HDMI monitor or TV
- **Network**: WiFi or Ethernet connection

### Also Works With
- Raspberry Pi 4 (2GB) - For basic signage
- Raspberry Pi 3 Model B+ - Reduced performance
- Raspberry Pi Zero 2 W - Single display only

## Operating System Options

### Option 1: Raspberry Pi OS Lite (Recommended)
Minimal OS with just what's needed for kiosk mode.

### Option 2: Raspberry Pi OS Desktop
Full desktop environment if you need local access.

## Quick Setup

### 1. Install Raspberry Pi OS

```bash
# Flash Raspberry Pi OS Lite to SD card using Raspberry Pi Imager
# Enable SSH and configure WiFi during setup

# First boot - update system
sudo apt update && sudo apt upgrade -y
```

### 2. Install Required Packages

```bash
# Install Chromium browser and X server
sudo apt install -y \
    chromium-browser \
    unclutter \
    xserver-xorg \
    xinit \
    x11-xserver-utils \
    matchbox-window-manager \
    xautomation

# Optional: Install network tools
sudo apt install -y curl wget net-tools
```

### 3. Configure Auto-Login (for Lite version)

```bash
sudo raspi-config
# Navigate to: System Options -> Boot / Auto Login -> Console Autologin
```

### 4. Create Kiosk Startup Script

```bash
# Create the kiosk script
sudo nano /home/pi/start-signage.sh
```

Paste this content:

```bash
#!/bin/bash

# Signage Player Startup Script for Raspberry Pi
# This script launches Chromium in kiosk mode

# Configuration
DEVICE_ID="${DEVICE_ID:-display-$(hostname)-$(date +%s)}"
API_URL="${API_URL:-http://YOUR_SERVER_IP:8095}"
PLAYER_URL="$API_URL/player.html?device_id=$DEVICE_ID"

# Disable screen blanking and power saving
xset s off
xset -dpms
xset s noblank

# Hide mouse cursor
unclutter -idle 0.1 &

# Start window manager
matchbox-window-manager &

# Wait for network
while ! ping -c 1 -W 1 $(echo $API_URL | sed 's|http://||' | cut -d: -f1) &> /dev/null; do
    echo "Waiting for network..."
    sleep 5
done

# Clear browser cache on startup (optional)
rm -rf /home/pi/.config/chromium/Default/Cache/*

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
    "$PLAYER_URL"
```

Make it executable:

```bash
chmod +x /home/pi/start-signage.sh
```

### 5. Create Systemd Service for Auto-Start

```bash
sudo nano /etc/systemd/system/signage-player.service
```

Paste this content:

```ini
[Unit]
Description=Sentra Signage Player
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=pi
Environment=DISPLAY=:0
Environment=XAUTHORITY=/home/pi/.Xauthority
Environment=DEVICE_ID=display-rpi-001
Environment=API_URL=http://YOUR_SERVER_IP:8095
ExecStartPre=/bin/sleep 10
ExecStart=/usr/bin/startx /home/pi/start-signage.sh -- -nocursor
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl enable signage-player.service
sudo systemctl start signage-player.service
```

### 6. Configure Device ID

Set a unique device ID for this display:

```bash
# Edit the service file
sudo nano /etc/systemd/system/signage-player.service

# Change the DEVICE_ID line to something unique:
Environment=DEVICE_ID=display-lobby-main-001

# Update the API_URL to your server:
Environment=API_URL=http://192.168.1.100:8095

# Reload and restart
sudo systemctl daemon-reload
sudo systemctl restart signage-player.service
```

## Advanced Configuration

### WiFi Power Management (Disable)

Prevent WiFi from sleeping:

```bash
sudo nano /etc/rc.local

# Add before 'exit 0':
/sbin/iwconfig wlan0 power off
```

### Watchdog Timer

Auto-reboot if system freezes:

```bash
# Enable watchdog
sudo modprobe bcm2835_wdt
echo "bcm2835_wdt" | sudo tee -a /etc/modules

# Install watchdog package
sudo apt install -y watchdog

# Configure
sudo nano /etc/watchdog.conf
# Uncomment: watchdog-device = /dev/watchdog
# Uncomment: max-load-1 = 24

sudo systemctl enable watchdog
sudo systemctl start watchdog
```

### Auto-Reboot Nightly

Reboot every night at 3 AM to clear memory:

```bash
sudo crontab -e

# Add this line:
0 3 * * * /sbin/shutdown -r now
```

### Screen Rotation

For portrait displays:

```bash
sudo nano /boot/config.txt

# Add one of these:
display_rotate=0    # Normal
display_rotate=1    # 90 degrees
display_rotate=2    # 180 degrees
display_rotate=3    # 270 degrees

# Reboot
sudo reboot
```

### Custom Resolution

```bash
sudo nano /boot/config.txt

# Add these lines:
hdmi_group=2
hdmi_mode=82    # 1920x1080 60Hz

# Common modes:
# 4:  720p 60Hz
# 16: 1024x768 60Hz
# 82: 1920x1080 60Hz
# 85: 1280x720 60Hz
```

## Performance Optimization

### Reduce GPU Memory (for headless kiosk)

```bash
sudo nano /boot/config.txt

# Set GPU memory to minimum for browser:
gpu_mem=128
```

### Disable Unused Services

```bash
# Disable Bluetooth (if not needed)
sudo systemctl disable bluetooth
sudo systemctl disable hciuart

# Disable unused services
sudo systemctl disable triggerhappy
sudo systemctl disable avahi-daemon
```

### Enable Hardware Acceleration

```bash
# Edit Chromium flags
nano /home/pi/start-signage.sh

# Add these flags to chromium-browser:
--enable-features=VaapiVideoDecoder \
--use-gl=egl \
--enable-hardware-overlays
```

## Monitoring & Management

### View Player Logs

```bash
# Service logs
sudo journalctl -u signage-player.service -f

# System logs
tail -f /var/log/syslog
```

### Restart Player

```bash
sudo systemctl restart signage-player.service
```

### Remote Access

```bash
# SSH into the Pi
ssh pi@<pi-ip-address>

# View current status
sudo systemctl status signage-player.service

# Kill and restart browser
pkill chromium && sudo systemctl restart signage-player.service
```

### VNC Access (Optional)

```bash
# Enable VNC
sudo raspi-config
# Interface Options -> VNC -> Enable

# Install RealVNC Viewer on your computer
# Connect to: <pi-ip-address>:5900
```

## Network Configuration

### Static IP Address

```bash
sudo nano /etc/dhcpcd.conf

# Add at the end:
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1 8.8.8.8

# Or for WiFi:
interface wlan0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1 8.8.8.8
```

### Hostname

```bash
# Set unique hostname
sudo raspi-config
# System Options -> Hostname -> display-lobby-001

# Or manually:
sudo hostnamectl set-hostname display-lobby-001
```

## Troubleshooting

### Display Not Starting

```bash
# Check service status
sudo systemctl status signage-player.service

# Check X server
ps aux | grep X

# Test manually
DISPLAY=:0 chromium-browser --kiosk http://google.com
```

### Black Screen

```bash
# Check HDMI connection
/opt/vc/bin/tvservice -s

# Force HDMI
sudo nano /boot/config.txt
# Add: hdmi_force_hotplug=1

sudo reboot
```

### Browser Crashes

```bash
# Clear browser cache
rm -rf /home/pi/.config/chromium/

# Increase swap
sudo dphys-swapfile swapoff
sudo nano /etc/dphys-swapfile
# Set: CONF_SWAPSIZE=1024
sudo dphys-swapfile setup
sudo dphys-swapfile swapon
```

### Network Issues

```bash
# Check connection
ping -c 4 google.com
ping -c 4 YOUR_SERVER_IP

# Check WiFi
iwconfig wlan0

# Restart network
sudo systemctl restart networking
```

## Multiple Displays Setup

For multiple Pi devices, create a spreadsheet:

| Location | Hostname | Device ID | IP Address | MAC Address |
|----------|----------|-----------|------------|-------------|
| Lobby | display-lobby-001 | display-lobby-001 | 192.168.1.100 | b8:27:eb:xx:xx:xx |
| Conf A | display-conf-a-001 | display-conf-a-001 | 192.168.1.101 | b8:27:eb:xx:xx:xx |
| Break Room | display-break-001 | display-break-001 | 192.168.1.102 | b8:27:eb:xx:xx:xx |

## Bulk Deployment Script

For setting up multiple devices:

```bash
#!/bin/bash
# bulk-setup.sh - Run on each Pi

DEVICE_ID=$1
API_URL=$2

if [ -z "$DEVICE_ID" ]; then
    echo "Usage: ./bulk-setup.sh <device-id> <api-url>"
    exit 1
fi

# Update system
sudo apt update && sudo apt upgrade -y

# Install packages
sudo apt install -y chromium-browser unclutter xserver-xorg xinit x11-xserver-utils matchbox-window-manager

# Copy startup script
sudo cp start-signage.sh /home/pi/
sudo chmod +x /home/pi/start-signage.sh

# Create service with device ID
sudo tee /etc/systemd/system/signage-player.service > /dev/null <<EOF
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

[Install]
WantedBy=multi-user.target
EOF

# Enable service
sudo systemctl daemon-reload
sudo systemctl enable signage-player.service

echo "✅ Setup complete for $DEVICE_ID"
echo "Reboot to start: sudo reboot"
```

## Security Recommendations

1. **Change Default Password**
   ```bash
   passwd
   ```

2. **Enable Firewall**
   ```bash
   sudo apt install ufw
   sudo ufw allow ssh
   sudo ufw enable
   ```

3. **Disable SSH Password Auth** (use keys)
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PasswordAuthentication no
   sudo systemctl restart ssh
   ```

4. **Auto Security Updates**
   ```bash
   sudo apt install unattended-upgrades
   sudo dpkg-reconfigure --priority=low unattended-upgrades
   ```

## Cost Breakdown (Per Display)

- Raspberry Pi 4 (4GB): $55
- Power Supply: $8
- microSD Card (32GB): $10
- Case: $10
- HDMI Cable: $5
- **Total: ~$88 per display**

Plus the display/TV itself.

## Backup & Recovery

### Create Image Backup

```bash
# On your computer (not the Pi):
# Insert SD card and find device
lsblk

# Create backup
sudo dd if=/dev/sdX of=signage-pi-backup.img bs=4M status=progress

# Compress
gzip signage-pi-backup.img
```

### Restore from Backup

```bash
# Flash the backup
sudo dd if=signage-pi-backup.img.gz | gunzip | sudo dd of=/dev/sdX bs=4M status=progress
```

## Production Checklist

- [ ] OS updated and configured
- [ ] Unique device ID set
- [ ] API URL configured correctly
- [ ] Auto-start service enabled
- [ ] Screen rotation set (if needed)
- [ ] Static IP configured
- [ ] Hostname set
- [ ] SSH password changed
- [ ] Watchdog enabled
- [ ] Nightly reboot scheduled
- [ ] Display tested for 24+ hours
- [ ] Remote access verified
- [ ] Physical mounting secured
- [ ] Power cable secured
- [ ] Backup image created

## Support

For issues specific to this setup, check:
- Service logs: `sudo journalctl -u signage-player.service -f`
- System logs: `tail -f /var/log/syslog`
- Browser console: Access via VNC

---

**Happy Signage! 🥧📺**
