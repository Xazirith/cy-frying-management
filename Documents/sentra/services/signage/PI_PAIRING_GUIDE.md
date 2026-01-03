# 🥧 Complete Raspberry Pi Signage Setup Guide

## Overview

This guide covers everything needed to pair Raspberry Pi devices with your Sentra signage system for 24/7 digital signage displays.

## Quick Start (5 Minutes)

### 1. Flash SD Card
- Download [Raspberry Pi Imager](https://www.raspberrypi.com/software/)
- Flash Raspberry Pi OS Lite to SD card
- Enable SSH and configure WiFi in imager settings

### 2. Boot and Connect
```bash
# Find your Pi's IP
nmap -sn 192.168.1.0/24

# SSH in (default password: raspberry)
ssh pi@192.168.1.XXX
```

### 3. Run Installer
```bash
# One-line install
bash <(curl -sSL https://raw.githubusercontent.com/YOUR_REPO/sentra/main/services/signage/pi-setup/install.sh) display-YOUR-ID http://YOUR-SERVER-IP:8095

# Or download and run
wget YOUR-SERVER/pi-setup/install.sh
chmod +x install.sh
./install.sh display-lobby-001 http://192.168.1.100:8095
```

### 4. Reboot
```bash
sudo reboot
```

Display will auto-start in ~30 seconds!

## What You Need

### Hardware (Per Display)
- ✅ Raspberry Pi 4 (4GB recommended)
- ✅ Official power supply (15W USB-C)
- ✅ microSD card (32GB Class 10+)
- ✅ HDMI cable
- ✅ Monitor/TV with HDMI
- ✅ Network connection (WiFi or Ethernet)

**Total cost: ~$95 per display** (see [HARDWARE_GUIDE.md](HARDWARE_GUIDE.md))

### Software (Included)
- ✅ Raspberry Pi OS Lite
- ✅ Chromium browser (kiosk mode)
- ✅ Auto-start configuration
- ✅ All optimizations

## Setup Methods

### Method 1: Automated Installer (Recommended)
Use the install script for automatic setup:
```bash
./install.sh <device-id> <api-url>
```

**What it does:**
- Installs all required packages
- Creates startup script
- Configures systemd service
- Applies optimizations
- Enables auto-start

**Time: 5-10 minutes**

### Method 2: Manual Setup
Follow [RASPBERRY_PI_SETUP.md](RASPBERRY_PI_SETUP.md) for step-by-step manual installation.

**Time: 20-30 minutes**

### Method 3: Pre-Built Image
- Set up one "master" Pi
- Create SD card image
- Clone to multiple devices
- Change device ID on each

**Time: 5 minutes per additional Pi**

## Features

### Display Player
- ✅ **Full-screen kiosk mode** - No browser UI
- ✅ **Auto-start on boot** - Runs automatically
- ✅ **Auto-reconnect** - Handles network issues
- ✅ **No screen blanking** - Display stays on 24/7
- ✅ **Hidden cursor** - Professional appearance
- ✅ **Hardware accelerated** - Smooth playback

### Content Support
- 🖼️ Gallery images (from Sentra gallery)
- 🎬 Video files
- 🌐 Web pages
- 📝 Text announcements
- 🌤️ Weather widgets
- 🕐 Clock displays
- 🔧 Custom modules

### Management
- 📊 Real-time status monitoring
- 📡 Heartbeat every 30 seconds
- 📈 Analytics tracking
- ⏰ Scheduled playlists
- 🔄 Remote content updates
- 🔧 Service logs

## Configuration

### Set Device ID
```bash
sudo nano /etc/systemd/system/signage-player.service

# Change this line:
Environment=DEVICE_ID=display-your-unique-id

sudo systemctl daemon-reload
sudo systemctl restart signage-player.service
```

### Set API URL
```bash
sudo nano /etc/systemd/system/signage-player.service

# Change this line:
Environment=API_URL=http://your-server-ip:8095

sudo systemctl daemon-reload
sudo systemctl restart signage-player.service
```

### Screen Rotation (Portrait Mode)
```bash
sudo nano /boot/config.txt

# Add one of these:
display_rotate=0    # Normal (landscape)
display_rotate=1    # 90 degrees
display_rotate=2    # 180 degrees
display_rotate=3    # 270 degrees (portrait)

sudo reboot
```

### Custom Resolution
```bash
sudo nano /boot/config.txt

# Add these:
hdmi_group=2
hdmi_mode=82    # 1920x1080 @ 60Hz

# Other common modes:
# 4:  720p 60Hz
# 16: 1024x768 60Hz
# 82: 1920x1080 60Hz
# 85: 1280x720 60Hz

sudo reboot
```

## Maintenance

### View Logs
```bash
# Service logs
sudo journalctl -u signage-player.service -f

# Last 50 lines
sudo journalctl -u signage-player.service -n 50
```

### Restart Player
```bash
sudo systemctl restart signage-player.service
```

### Check Status
```bash
sudo systemctl status signage-player.service
```

### Run System Test
```bash
./test.sh
```

### Update Content
Content updates automatically every 5 minutes. Force update:
```bash
# Restart player to fetch new playlist
sudo systemctl restart signage-player.service
```

## Troubleshooting

### Display Not Starting
```bash
# Check service
sudo systemctl status signage-player.service

# View logs
sudo journalctl -u signage-player.service -n 50

# Test manually
DISPLAY=:0 chromium-browser --kiosk http://google.com
```

### Black Screen
```bash
# Check HDMI
/opt/vc/bin/tvservice -s

# Force HDMI output
sudo nano /boot/config.txt
# Add: hdmi_force_hotplug=1
sudo reboot
```

### Network Issues
```bash
# Test connection
ping -c 4 8.8.8.8
ping -c 4 YOUR-SERVER-IP

# Check WiFi
iwconfig wlan0

# Disable WiFi power save
sudo nano /etc/rc.local
# Add before 'exit 0':
/sbin/iwconfig wlan0 power off
```

### Performance Issues
```bash
# Check temperature
vcgencmd measure_temp

# If over 70°C, add cooling or reduce load

# Check memory
free -h

# Clear browser cache
rm -rf ~/.config/chromium/Default/Cache/*
sudo systemctl restart signage-player.service
```

## Multiple Displays

### Naming Convention
Use a consistent naming scheme:
```
display-<location>-<number>

Examples:
- display-lobby-001
- display-conf-a-001
- display-break-room-001
- display-floor2-north-001
```

### Tracking Spreadsheet
| Device ID | Location | IP Address | MAC Address | Playlist | Status |
|-----------|----------|------------|-------------|----------|--------|
| display-lobby-001 | Main Lobby | 192.168.1.100 | b8:27:eb:xx | Main | Online |
| display-conf-a-001 | Conf Room A | 192.168.1.101 | b8:27:eb:xx | Meeting | Online |

### Bulk Deployment
1. Set up master Pi with all configuration
2. Create image: `sudo dd if=/dev/sdX of=master.img bs=4M`
3. Flash to all SD cards
4. Boot each and change device ID
5. Register in management interface

## Optimization Tips

### For 24/7 Operation
- ✅ Use official power supply
- ✅ Ensure adequate cooling
- ✅ Enable watchdog timer
- ✅ Schedule nightly reboot (3 AM)
- ✅ Use quality SD card (Endurance series)
- ✅ Monitor temperature regularly

### For Video Content
- ✅ Use Pi 4 with 4GB+ RAM
- ✅ Enable hardware acceleration
- ✅ Use 1080p max resolution
- ✅ Keep videos under 2 minutes
- ✅ Use H.264 codec

### For Low Power
- ✅ Disable Bluetooth
- ✅ Disable unused services
- ✅ Lower screen brightness
- ✅ Use static images instead of video
- ✅ Reduce GPU memory allocation

## Security

### Basic Security
```bash
# Change default password
passwd

# Update system
sudo apt update && sudo apt upgrade -y

# Enable firewall
sudo apt install ufw
sudo ufw allow ssh
sudo ufw enable
```

### Production Security
```bash
# Disable password auth (use SSH keys)
sudo nano /etc/ssh/sshd_config
# Set: PasswordAuthentication no

# Auto security updates
sudo apt install unattended-upgrades
sudo dpkg-reconfigure unattended-upgrades

# Disable root login
sudo passwd -l root
```

## Cost Analysis

### Per Display Cost
| Item | Cost |
|------|------|
| Raspberry Pi 4 4GB | $55 |
| Power Supply | $8 |
| microSD 32GB | $10 |
| Case | $15 |
| HDMI Cable | $7 |
| **Hardware Total** | **$95** |
| 24" Monitor | $90 |
| **Complete Setup** | **$185** |

### 5-Year TCO (Total Cost of Ownership)
| Item | Cost |
|------|------|
| Initial hardware | $185 |
| Electricity (30W @ $0.12/kWh) | $155 |
| SD card replacement (year 3) | $10 |
| **Total 5-Year Cost** | **$350** |

**vs. Commercial Signage: $1,000-2,500**
**Savings: 65-85%**

## Remote Management

### SSH Access
```bash
# From your computer
ssh pi@display-lobby-001.local

# Or by IP
ssh pi@192.168.1.100
```

### VNC Access (Optional)
```bash
# Enable VNC on Pi
sudo raspi-config
# Interface Options -> VNC -> Enable

# Connect from computer using RealVNC Viewer
# Address: 192.168.1.100:5900
```

### Web Management
Access from anywhere:
```
http://YOUR-SERVER:8095/manager.html?tenant_id=default
```

## Support & Resources

### Documentation
- **Complete Setup**: [RASPBERRY_PI_SETUP.md](RASPBERRY_PI_SETUP.md)
- **Hardware Guide**: [HARDWARE_GUIDE.md](HARDWARE_GUIDE.md)
- **Signage System**: [../README.md](../README.md)
- **Main Docs**: [../../SIGNAGE_SYSTEM.md](../../SIGNAGE_SYSTEM.md)

### Scripts
- **Installer**: `pi-setup/install.sh`
- **Tester**: `pi-setup/test.sh`
- **Quick Start**: `quickstart.sh`
- **Example Setup**: `example_setup.py`

### Community
- Raspberry Pi Forums
- Sentra GitHub Issues
- Discord/Slack channels

## FAQ

**Q: Can I use Raspberry Pi 3?**
A: Yes, but performance is reduced. Pi 4 recommended for video and complex content.

**Q: How many displays can one server handle?**
A: 50+ displays per server easily. Limited by network/bandwidth.

**Q: Can I use 4K displays?**
A: Yes, Pi 4 supports 4K @ 30Hz. Use Pi 4 8GB for best 4K performance.

**Q: What happens if network goes down?**
A: Player continues showing current playlist. Reconnects automatically when network returns.

**Q: How do I update content?**
A: Update playlists in management interface. Displays refresh every 5 minutes automatically.

**Q: Can I use touchscreens?**
A: Yes, touchscreens work. Future version will support interactive content.

**Q: How reliable is this for 24/7?**
A: Very reliable with proper cooling and quality SD card. Many deployments run for months without restart.

**Q: Can I power off displays at night?**
A: Yes, use a timer on the monitor power, or schedule via playlist scheduling.

---

**Ready to deploy? Start with the automated installer!**

```bash
bash <(curl -sSL YOUR-SERVER/pi-setup/install.sh) display-001 http://YOUR-SERVER:8095
```
