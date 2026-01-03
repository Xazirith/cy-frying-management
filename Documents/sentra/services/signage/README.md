# Sentra Signage System

Multi-tenant digital signage system with gallery integration.

## Features

- **Multi-Tenant Support**: Complete isolation between tenants
- **Display Management**: Register and manage multiple displays
- **Playlist System**: Create and schedule content playlists
- **Content Types**:
  - Gallery images (from gallery module)
  - Videos
  - Web pages/URLs
  - Text announcements
  - Weather widgets
  - Clock widgets
  - Custom modules

- **Scheduling**: Time-based and day-of-week playlist scheduling
- **Analytics**: Track display status and content playback
- **Real-time Updates**: Auto-refresh playlists and heartbeat monitoring

## Quick Start

### 1. Start the Signage Service

```bash
docker-compose up -d sentra-signage
```

### 2. Register a Display

```bash
curl -X POST http://localhost:8088/api/tenants/default/signage/displays \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Lobby Display",
    "device_id": "display-lobby-001",
    "location": "Main Lobby",
    "orientation": "landscape",
    "resolution": "1920x1080"
  }'
```

### 3. Create a Playlist

```bash
curl -X POST http://localhost:8088/api/tenants/default/signage/playlists \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Playlist",
    "description": "Main lobby content",
    "is_default": true
  }'
```

### 4. Add Content to Playlist

```bash
# Add gallery images
curl -X POST http://localhost:8088/api/tenants/default/signage/playlists/1/items \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "gallery",
    "content_data": {"tag": "featured", "limit": 10},
    "duration": 15,
    "position": 0
  }'

# Add text announcement
curl -X POST http://localhost:8088/api/tenants/default/signage/playlists/1/items \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "text",
    "content_data": {
      "content": "<h1>Welcome!</h1><p>Visit our website</p>",
      "style": {"background": "#3498db"}
    },
    "duration": 10,
    "position": 1
  }'

# Add clock widget
curl -X POST http://localhost:8088/api/tenants/default/signage/playlists/1/items \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "clock",
    "content_data": {
      "format": "24h",
      "timezone": "UTC",
      "style": "digital"
    },
    "duration": 20,
    "position": 2
  }'
```

### 5. Open Display Player

Open in browser (fullscreen recommended):
```
http://localhost:8095/player.html?device_id=display-lobby-001
```

Or use the management interface:
```
http://localhost:8095/manager.html?tenant_id=default
```

## Display Player

The player runs in any modern web browser and supports:

- **Auto-rotation**: Content rotates based on duration
- **Transitions**: Smooth fade between items
- **Fullscreen**: Press F11 for fullscreen mode
- **Status Bar**: Press Ctrl+S to toggle status bar
- **Auto-reconnect**: Automatically reconnects on network issues
- **Offline Cache**: Stores config locally

### Player URL Parameters

- `device_id`: Display device ID (required)
- `api_url`: API endpoint (default: http://localhost:8095)
- `tenant_id`: Tenant ID (default: default)
- `show_status`: Show status bar (default: true)

## Content Types

### Gallery Content

Pull from gallery module:

```json
{
  "content_type": "gallery",
  "content_id": "123",  // Specific gallery item
  "duration": 15
}
```

Or by tag:

```json
{
  "content_type": "gallery",
  "content_data": {
    "tag": "featured",
    "limit": 10
  },
  "duration": 15
}
```

### Video Content

```json
{
  "content_type": "video",
  "content_data": {
    "url": "https://example.com/video.mp4"
  },
  "duration": 30
}
```

### URL/Web Page

```json
{
  "content_type": "url",
  "content_data": {
    "url": "https://example.com",
    "refresh_interval": 60
  },
  "duration": 20
}
```

### Text Announcement

```json
{
  "content_type": "text",
  "content_data": {
    "content": "<h1>Title</h1><p>Message</p>",
    "style": {"background": "#3498db", "color": "#fff"},
    "animation": "fade"
  },
  "duration": 10
}
```

### Weather Widget

```json
{
  "content_type": "weather",
  "content_data": {
    "location": "New York",
    "units": "metric",
    "style": "modern"
  },
  "duration": 20
}
```

### Clock Widget

```json
{
  "content_type": "clock",
  "content_data": {
    "format": "24h",
    "timezone": "America/New_York",
    "style": "digital"
  },
  "duration": 15
}
```

## Scheduling

Schedule different playlists for different times:

```bash
curl -X POST http://localhost:8088/api/tenants/default/signage/schedules \
  -H "Content-Type: application/json" \
  -d '{
    "display_id": 1,
    "playlist_id": 2,
    "start_time": "09:00",
    "end_time": "17:00",
    "days_of_week": "1,2,3,4,5",
    "priority": 10
  }'
```

Days: 1=Monday, 2=Tuesday, ..., 7=Sunday

## API Endpoints

### Display Endpoints (No Auth)

- `GET /display/config?device_id={id}` - Get display config
- `GET /display/playlist?device_id={id}` - Get current playlist
- `POST /display/heartbeat` - Send heartbeat
- `POST /display/analytics` - Log analytics

### Management Endpoints (via Tenants Service)

**Displays:**
- `GET /api/tenants/{tenant_id}/signage/displays`
- `POST /api/tenants/{tenant_id}/signage/displays`
- `GET /api/tenants/{tenant_id}/signage/displays/{id}`
- `PUT /api/tenants/{tenant_id}/signage/displays/{id}`
- `DELETE /api/tenants/{tenant_id}/signage/displays/{id}`

**Playlists:**
- `GET /api/tenants/{tenant_id}/signage/playlists`
- `POST /api/tenants/{tenant_id}/signage/playlists`
- `GET /api/tenants/{tenant_id}/signage/playlists/{id}`
- `PUT /api/tenants/{tenant_id}/signage/playlists/{id}`
- `DELETE /api/tenants/{tenant_id}/signage/playlists/{id}`

**Playlist Items:**
- `GET /api/tenants/{tenant_id}/signage/playlists/{id}/items`
- `POST /api/tenants/{tenant_id}/signage/playlists/{id}/items`
- `PUT /api/tenants/{tenant_id}/signage/playlists/{id}/items/{item_id}`
- `DELETE /api/tenants/{tenant_id}/signage/playlists/{id}/items/{item_id}`

**Schedules:**
- `GET /api/tenants/{tenant_id}/signage/schedules`
- `POST /api/tenants/{tenant_id}/signage/schedules`
- `PUT /api/tenants/{tenant_id}/signage/schedules/{id}`
- `DELETE /api/tenants/{tenant_id}/signage/schedules/{id}`

**Analytics:**
- `GET /api/tenants/{tenant_id}/signage/analytics`

## Architecture

- **Signage Module** (`tenants/modules/signage.py`): Database and business logic
- **Signage Service** (`services/signage/signage_service.py`): Display endpoints
- **Tenants Service**: Management endpoints
- **Player** (`player.html`): Browser-based display client
- **Manager** (`manager.html`): Web-based admin interface

## Local Module Extension

Create custom content modules in `services/signage/modules/`:

```python
# modules/custom_widget.py
def render(config):
    """Return HTML for custom widget"""
    return f"<div>{config['message']}</div>"
```

Register in playlist:
```json
{
  "content_type": "custom",
  "content_data": {
    "module": "custom_widget",
    "message": "Hello World"
  },
  "duration": 10
}
```

## Multi-Tenant Usage

Each tenant has complete isolation:

```bash
# Tenant A
curl http://localhost:8088/api/tenants/tenant-a/signage/displays

# Tenant B  
curl http://localhost:8088/api/tenants/tenant-b/signage/displays
```

Players access via device_id which is tenant-scoped.

## Hardware Recommendations

- **Display**: Any screen with HDMI/DisplayPort
- **Player Device**: 
  - Raspberry Pi 4 (4GB+)
  - Intel NUC
  - Any PC with modern browser
- **Browser**: Chrome/Chromium (recommended), Firefox
- **Network**: Stable connection for content sync

## Troubleshooting

**Display shows "Device not found":**
- Verify device_id is registered
- Check network connectivity

**Content not updating:**
- Check heartbeat in logs
- Verify playlist has items
- Check schedule configuration

**Images not loading:**
- Verify gallery service is running
- Check image paths in gallery items
- Check CORS settings

## Development

Run locally:
```bash
cd services/signage
python3 signage_service.py
```

Access player:
```bash
python3 -m http.server 8000
# Open http://localhost:8000/player.html
```
