#!/bin/bash
#
# Raspberry Pi Signage Player - Test Script
# Run this on your Pi to verify everything is working
#

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔═══════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Raspberry Pi Signage Player - System Test      ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════╝${NC}"
echo ""

PASS=0
FAIL=0
WARN=0

# Test function
test_check() {
    local name="$1"
    local command="$2"
    
    printf "%-45s" "$name"
    
    if eval "$command" &>/dev/null; then
        echo -e "[${GREEN}✓${NC}]"
        ((PASS++))
        return 0
    else
        echo -e "[${RED}✗${NC}]"
        ((FAIL++))
        return 1
    fi
}

test_warn() {
    local name="$1"
    local command="$2"
    
    printf "%-45s" "$name"
    
    if eval "$command" &>/dev/null; then
        echo -e "[${GREEN}✓${NC}]"
        ((PASS++))
        return 0
    else
        echo -e "[${YELLOW}⚠${NC}]"
        ((WARN++))
        return 1
    fi
}

echo -e "${BLUE}🔍 Hardware Checks${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if Raspberry Pi
if grep -q "Raspberry Pi" /proc/cpuinfo 2>/dev/null; then
    MODEL=$(cat /proc/device-tree/model 2>/dev/null)
    echo -e "Device Model:                                 ${GREEN}$MODEL${NC}"
    ((PASS++))
else
    echo -e "Device Model:                                 ${YELLOW}Not a Raspberry Pi${NC}"
    ((WARN++))
fi

# CPU temp
if [ -f /sys/class/thermal/thermal_zone0/temp ]; then
    TEMP=$(($(cat /sys/class/thermal/thermal_zone0/temp)/1000))
    if [ $TEMP -lt 70 ]; then
        echo -e "CPU Temperature:                              ${GREEN}${TEMP}°C${NC}"
        ((PASS++))
    elif [ $TEMP -lt 80 ]; then
        echo -e "CPU Temperature:                              ${YELLOW}${TEMP}°C (Warm)${NC}"
        ((WARN++))
    else
        echo -e "CPU Temperature:                              ${RED}${TEMP}°C (Hot!)${NC}"
        ((FAIL++))
    fi
fi

# Memory
TOTAL_MEM=$(free -m | awk '/^Mem:/{print $2}')
FREE_MEM=$(free -m | awk '/^Mem:/{print $7}')
echo -e "Memory:                                       ${GREEN}${FREE_MEM}MB free / ${TOTAL_MEM}MB total${NC}"

echo ""
echo -e "${BLUE}📦 Software Checks${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_check "Chromium browser installed" "which chromium-browser"
test_check "X server installed" "which X"
test_check "Unclutter installed" "which unclutter"
test_check "Matchbox window manager" "which matchbox-window-manager"

echo ""
echo -e "${BLUE}⚙️  Service Checks${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_check "Signage service exists" "systemctl cat signage-player.service"
test_check "Signage service enabled" "systemctl is-enabled signage-player.service"
test_warn "Signage service running" "systemctl is-active signage-player.service"

# Check startup script
if [ -f /home/pi/start-signage.sh ]; then
    if [ -x /home/pi/start-signage.sh ]; then
        echo -e "Startup script:                               ${GREEN}Found & executable${NC}"
        ((PASS++))
    else
        echo -e "Startup script:                               ${YELLOW}Not executable${NC}"
        ((WARN++))
    fi
else
    echo -e "Startup script:                               ${RED}Missing${NC}"
    ((FAIL++))
fi

echo ""
echo -e "${BLUE}🌐 Network Checks${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Internet connectivity
test_check "Internet connection" "ping -c 1 -W 2 8.8.8.8"

# Get IP address
IP_ADDR=$(hostname -I | awk '{print $1}')
if [ -n "$IP_ADDR" ]; then
    echo -e "IP Address:                                   ${GREEN}$IP_ADDR${NC}"
    ((PASS++))
else
    echo -e "IP Address:                                   ${RED}Not found${NC}"
    ((FAIL++))
fi

# Check API server
if systemctl cat signage-player.service &>/dev/null; then
    API_URL=$(systemctl show -p Environment signage-player.service | grep -oP 'API_URL=\K[^ ]+')
    if [ -n "$API_URL" ]; then
        echo -e "API URL:                                      ${BLUE}$API_URL${NC}"
        
        # Test API connection
        API_HOST=$(echo $API_URL | sed 's|http://||' | cut -d: -f1)
        if ping -c 1 -W 2 $API_HOST &>/dev/null; then
            echo -e "API server reachable:                         ${GREEN}Yes${NC}"
            ((PASS++))
        else
            echo -e "API server reachable:                         ${RED}No${NC}"
            ((FAIL++))
        fi
    fi
fi

echo ""
echo -e "${BLUE}🖥️  Display Checks${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check HDMI status
if command -v tvservice &>/dev/null; then
    HDMI_STATUS=$(tvservice -s 2>/dev/null)
    if echo "$HDMI_STATUS" | grep -q "0x120002"; then
        echo -e "HDMI Display:                                 ${GREEN}Connected${NC}"
        ((PASS++))
    else
        echo -e "HDMI Display:                                 ${YELLOW}$HDMI_STATUS${NC}"
        ((WARN++))
    fi
fi

# Get display info
if [ -n "$DISPLAY" ] && xdpyinfo &>/dev/null; then
    RESOLUTION=$(xdpyinfo | grep dimensions | awk '{print $2}')
    echo -e "Display Resolution:                           ${GREEN}$RESOLUTION${NC}"
    ((PASS++))
else
    echo -e "Display Resolution:                           ${YELLOW}Not available (X not running)${NC}"
    ((WARN++))
fi

echo ""
echo -e "${BLUE}🔧 Configuration${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Get device ID
if systemctl cat signage-player.service &>/dev/null; then
    DEVICE_ID=$(systemctl show -p Environment signage-player.service | grep -oP 'DEVICE_ID=\K[^ ]+')
    if [ -n "$DEVICE_ID" ]; then
        echo -e "Device ID:                                    ${BLUE}$DEVICE_ID${NC}"
    fi
fi

# Check GPU memory
GPU_MEM=$(vcgencmd get_mem gpu 2>/dev/null | cut -d= -f2)
if [ -n "$GPU_MEM" ]; then
    echo -e "GPU Memory:                                   ${GREEN}$GPU_MEM${NC}"
fi

# Check WiFi power management
if iwconfig wlan0 2>/dev/null | grep -q "Power Management:off"; then
    echo -e "WiFi Power Management:                        ${GREEN}Disabled (Good)${NC}"
    ((PASS++))
else
    echo -e "WiFi Power Management:                        ${YELLOW}Enabled (May cause issues)${NC}"
    ((WARN++))
fi

echo ""
echo -e "${BLUE}📊 System Resources${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Disk space
DISK_USED=$(df -h / | awk 'NR==2 {print $5}')
DISK_AVAIL=$(df -h / | awk 'NR==2 {print $4}')
echo -e "Disk Usage:                                   ${GREEN}${DISK_USED} used, ${DISK_AVAIL} available${NC}"

# CPU load
LOAD=$(uptime | grep -oP 'load average: \K.*')
echo -e "System Load:                                  ${GREEN}$LOAD${NC}"

# Uptime
UPTIME=$(uptime -p)
echo -e "Uptime:                                       ${GREEN}$UPTIME${NC}"

# Check for updates
echo ""
echo -e "${BLUE}🔄 System Updates${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if apt-get update -qq 2>/dev/null; then
    UPDATES=$(apt list --upgradable 2>/dev/null | grep -c upgradable)
    if [ "$UPDATES" -gt 0 ]; then
        echo -e "Available updates:                            ${YELLOW}$UPDATES packages${NC}"
        ((WARN++))
    else
        echo -e "Available updates:                            ${GREEN}System up to date${NC}"
        ((PASS++))
    fi
fi

# Summary
echo ""
echo -e "${BLUE}╔═══════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                  Test Summary                     ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${GREEN}Passed:${NC}  $PASS"
echo -e "  ${YELLOW}Warnings:${NC} $WARN"
echo -e "  ${RED}Failed:${NC}  $FAIL"
echo ""

if [ $FAIL -eq 0 ] && [ $WARN -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! System is ready.${NC}"
    exit 0
elif [ $FAIL -eq 0 ]; then
    echo -e "${YELLOW}⚠ System functional with warnings.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some checks failed. Please review.${NC}"
    echo ""
    echo "Common fixes:"
    echo "  - Install missing packages: cd /home/xazirith/Documents/sentra/services/signage/pi-setup && ./install.sh"
    echo "  - Check service logs: sudo journalctl -u signage-player.service -n 50"
    echo "  - Restart service: sudo systemctl restart signage-player.service"
    exit 1
fi
