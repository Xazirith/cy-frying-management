# 🥧 Raspberry Pi Quick Setup

## One-Line Install

SSH into your Raspberry Pi and run:

```bash
bash <(curl -sSL https://raw.githubusercontent.com/YOUR_REPO/sentra/main/services/signage/pi-setup/install.sh) display-YOUR-ID http://YOUR-SERVER:8095
```

**Or download and run:**

```bash
wget https://YOUR_SERVER/pi-setup/install.sh
chmod +x install.sh
./install.sh display-lobby-001 http://192.168.1.100:8095
```

## What Gets Installed

- ✅ Chromium browser (kiosk mode)
- ✅ X server and window manager
- ✅ Auto-start service
- ✅ Screen blanking disabled
- ✅ Cursor hidden
- ✅ WiFi power management disabled
- ✅ Optimized settings for 24/7 operation

## After Installation

1. Display will auto-start after reboot
2. Press F11 in player for fullscreen
3. Press Ctrl+S to toggle status bar
4. Service runs automatically on boot

## Troubleshooting

```bash
# View logs
sudo journalctl -u signage-player.service -f

# Restart player
sudo systemctl restart signage-player.service

# Check service status
sudo systemctl status signage-player.service

# Manual test
DISPLAY=:0 chromium-browser --kiosk http://google.com
```

## Management

The display will appear in your signage management interface at:
```
http://YOUR-SERVER:8095/manager.html
```

Look for the device ID you specified during installation.

## Hardware Setup Tips

1. **Power Supply**: Use official Raspberry Pi power supply
2. **SD Card**: Use Class 10 or better, 32GB minimum
3. **Cooling**: Consider heatsink or fan for 24/7 operation
4. **Mounting**: Secure Pi behind display with VESA mount adapter
5. **Cable Management**: Use short, quality HDMI cables

## Production Deployment

For deploying multiple displays:

1. Set up one "master" Pi with everything configured
2. Create an image backup:
   ```bash
   sudo dd if=/dev/sdX of=signage-master.img bs=4M status=progress
   ```
3. Flash image to new SD cards
4. Change device ID on each:
   ```bash
   sudo nano /etc/systemd/system/signage-player.service
   # Edit DEVICE_ID line
   sudo systemctl daemon-reload
   sudo systemctl restart signage-player.service
   ```

## Support

For detailed documentation see: `RASPBERRY_PI_SETUP.md`
