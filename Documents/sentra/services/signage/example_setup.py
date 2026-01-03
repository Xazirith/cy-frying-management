#!/usr/bin/env python3
"""
Example: Create a complete signage setup programmatically
"""

import requests
import json

# Configuration
API_URL = "http://localhost:8088"
TENANT_ID = "default"

def create_signage_setup():
    """Create a complete signage system setup"""
    
    print("🖥️  Creating Signage Setup for tenant:", TENANT_ID)
    print("=" * 60)
    
    # 1. Create playlists
    print("\n📝 Creating playlists...")
    
    playlists = []
    
    # Main lobby playlist
    resp = requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists",
        json={
            "name": "Lobby Display",
            "description": "Main lobby rotating content",
            "is_default": True
        }
    )
    lobby_playlist = resp.json()["playlist"]
    playlists.append(lobby_playlist)
    print(f"✓ Created: {lobby_playlist['name']} (ID: {lobby_playlist['id']})")
    
    # Morning playlist
    resp = requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists",
        json={
            "name": "Morning Schedule",
            "description": "Morning announcements and news"
        }
    )
    morning_playlist = resp.json()["playlist"]
    playlists.append(morning_playlist)
    print(f"✓ Created: {morning_playlist['name']} (ID: {morning_playlist['id']})")
    
    # 2. Add content to lobby playlist
    print(f"\n🎨 Adding content to '{lobby_playlist['name']}'...")
    
    # Gallery slideshow
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists&_sub_resource=items",
        json={
            "playlist_id": lobby_playlist['id'],
            "content_type": "gallery",
            "content_data": {
                "tag": "featured",
                "limit": 15
            },
            "duration": 15,
            "position": 0,
            "transition": "fade"
        }
    )
    print("✓ Added: Featured gallery slideshow (15 images, 15s each)")
    
    # Clock
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists&_sub_resource=items",
        json={
            "playlist_id": lobby_playlist['id'],
            "content_type": "clock",
            "content_data": {
                "format": "24h",
                "timezone": "America/New_York",
                "style": "digital"
            },
            "duration": 20,
            "position": 1
        }
    )
    print("✓ Added: Clock widget (20s)")
    
    # Welcome message
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists&_sub_resource=items",
        json={
            "playlist_id": lobby_playlist['id'],
            "content_type": "text",
            "content_data": {
                "content": """
                    <h1>Welcome to Our Organization</h1>
                    <p>Thank you for visiting us today!</p>
                """,
                "style": {
                    "background": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
                    "color": "#ffffff"
                }
            },
            "duration": 10,
            "position": 2
        }
    )
    print("✓ Added: Welcome message (10s)")
    
    # Weather widget
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists&_sub_resource=items",
        json={
            "playlist_id": lobby_playlist['id'],
            "content_type": "weather",
            "content_data": {
                "location": "New York",
                "units": "metric",
                "style": "modern"
            },
            "duration": 15,
            "position": 3
        }
    )
    print("✓ Added: Weather widget (15s)")
    
    # 3. Add content to morning playlist
    print(f"\n🌅 Adding content to '{morning_playlist['name']}'...")
    
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists&_sub_resource=items",
        json={
            "playlist_id": morning_playlist['id'],
            "content_type": "text",
            "content_data": {
                "content": "<h1>Good Morning!</h1><p>Today's Schedule</p>",
                "style": {"background": "#f39c12"}
            },
            "duration": 10,
            "position": 0
        }
    )
    print("✓ Added: Morning greeting")
    
    # 4. Create displays
    print("\n🖥️  Creating displays...")
    
    displays = []
    
    # Main lobby display
    resp = requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=displays",
        json={
            "name": "Main Lobby Display",
            "device_id": "display-lobby-main-001",
            "location": "Main Entrance Lobby",
            "orientation": "landscape",
            "resolution": "1920x1080",
            "playlist_id": lobby_playlist['id']
        }
    )
    lobby_display = resp.json()["display"]
    displays.append(lobby_display)
    print(f"✓ Created: {lobby_display['name']}")
    print(f"  Device ID: {lobby_display['device_id']}")
    
    # Conference room display
    resp = requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=displays",
        json={
            "name": "Conference Room A",
            "device_id": "display-conf-a-001",
            "location": "Conference Room A",
            "orientation": "landscape",
            "resolution": "1920x1080",
            "playlist_id": lobby_playlist['id']
        }
    )
    conf_display = resp.json()["display"]
    displays.append(conf_display)
    print(f"✓ Created: {conf_display['name']}")
    print(f"  Device ID: {conf_display['device_id']}")
    
    # 5. Create schedules
    print("\n📅 Creating schedules...")
    
    # Morning schedule for lobby display
    requests.post(
        f"{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=schedules",
        json={
            "display_id": lobby_display['id'],
            "playlist_id": morning_playlist['id'],
            "start_time": "07:00",
            "end_time": "12:00",
            "days_of_week": "1,2,3,4,5",  # Monday-Friday
            "priority": 10
        }
    )
    print(f"✓ Created: Morning schedule for {lobby_display['name']}")
    print("  Time: 07:00-12:00 weekdays")
    
    # Print summary
    print("\n" + "=" * 60)
    print("✅ Setup Complete!")
    print("=" * 60)
    
    print(f"\n📊 Summary:")
    print(f"  Playlists: {len(playlists)}")
    print(f"  Displays: {len(displays)}")
    print(f"  Schedules: 1")
    
    print(f"\n🎮 Display Player URLs:")
    for display in displays:
        print(f"\n  {display['name']}:")
        print(f"  http://localhost:8095/player.html?device_id={display['device_id']}")
    
    print(f"\n🔧 Management Interface:")
    print(f"  http://localhost:8095/manager.html?tenant_id={TENANT_ID}")
    
    print(f"\n📡 API Examples:")
    print(f"\n  Get displays:")
    print(f"  curl '{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=displays'")
    
    print(f"\n  Get playlists:")
    print(f"  curl '{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=playlists'")
    
    print(f"\n  Get analytics:")
    print(f"  curl '{API_URL}/api/tenants/{TENANT_ID}/signage?_resource=analytics'")
    
    return {
        "playlists": playlists,
        "displays": displays
    }


if __name__ == "__main__":
    try:
        result = create_signage_setup()
        print("\n✨ All done!")
    except requests.exceptions.ConnectionError:
        print("\n❌ Error: Could not connect to API at", API_URL)
        print("Please ensure the tenants service is running.")
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
